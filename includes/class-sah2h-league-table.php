<?php
/**
 * SAH2H League Table Class
 *
 * An Advanced Head to Head League Table Class based on SportsPress League Table Class.
 *
 * @class       SAH2H_League_Table
 * @version     2.0.0
 * @package     SAH2H/Classes
 * @category    Class
 * @author      Savvas
 */

// Exit if SportsPress is not installed and activated.
if ( ! class_exists( 'SP_League_Table' ) ) {
	exit;
}

/**
 * SAH2H League Table Class
 *
 * @class SAH2H_League_Table
 */
class SAH2H_League_Table extends SP_League_Table {

	/** @var array The sort h2h priorities array. */
	public $h2h_priorities;

	/** @var array Temporary save the full table stats for future h2h use.   */
	public $temp_merged = array();

	/** @var string The advanced sorting criteria.   */
	public $h2h_criteria;

	/**
	 * Returns formatted data
	 *
	 * @access public
	 * @param bool $admin
	 * @return array
	 */
	public function data( $admin = false, $team_ids = null ) {
		$league_ids         = sp_get_the_term_ids( $this->ID, 'sp_league' );
		$season_ids         = sp_get_the_term_ids( $this->ID, 'sp_season' );
		$table_stats        = (array) get_post_meta( $this->ID, 'sp_teams', true );
		$usecolumns         = get_post_meta( $this->ID, 'sp_columns', true );
		$adjustments        = get_post_meta( $this->ID, 'sp_adjustments', true );
		$select             = get_post_meta( $this->ID, 'sp_select', true );
		$this->orderby      = get_post_meta( $this->ID, 'sp_orderby', true );
		$this->orderbyorder = get_post_meta( $this->ID, 'sp_order', true );
		$link_events        = get_option( 'sportspress_link_events', 'yes' ) === 'yes' ? true : false;
		$form_limit         = (int) get_option( 'sportspress_form_limit', 5 );

		$this->date = $this->__get( 'date' );

		if ( ! $this->date ) {
			$this->date = 0;
		}

		// Apply defaults.
		if ( empty( $this->orderby ) ) {
			$this->orderby = 'default';
		}
		if ( empty( $this->orderbyorder ) ) {
			$this->orderbyorder = 'ASC';
		}
		if ( empty( $select ) ) {
			$select = 'auto';
		}

		if ( 'range' === $this->date ) {

			$this->relative = get_post_meta( $this->ID, 'sp_date_relative', true );

			if ( $this->relative ) {

				$this->past = get_post_meta( $this->ID, 'sp_date_past', true );

			} else {

				$this->from = get_post_meta( $this->ID, 'sp_date_from', true );
				$this->to   = get_post_meta( $this->ID, 'sp_date_to', true );

			}
		}

		// Get labels from result variables.
		$result_labels = (array) sp_get_var_labels( 'sp_result' );

		// Get labels from outcome variables.
		$outcome_labels = (array) sp_get_var_labels( 'sp_outcome' );

		// Get post type.
		$post_type = sp_get_post_mode_type( $this->ID );

		// Determine if main loop.
		if ( $team_ids ) {

			$is_main_loop = false;

		} else {

			// Get teams automatically if set to auto.
			if ( 'auto' === $select ) {
				$team_ids = array();

				$args = array(
					'post_type'      => $post_type,
					'numberposts'    => -1,
					'posts_per_page' => -1,
					'order'          => 'ASC',
					'tax_query'      => array(
						'relation' => 'AND',
					),
					'fields'         => 'ids',
				);

				if ( $league_ids ) :
					$args['tax_query'][] = array(
						'taxonomy' => 'sp_league',
						'field'    => 'term_id',
						'terms'    => $league_ids,
					);
				endif;

				if ( $season_ids ) :
					$args['tax_query'][] = array(
						'taxonomy' => 'sp_season',
						'field'    => 'term_id',
						'terms'    => $season_ids,
					);
				endif;

				$team_ids = get_posts( $args );
			} else {
				$team_ids = (array) get_post_meta( $this->ID, 'sp_team', false );
			}

			$is_main_loop = true;
		}

		// Get all leagues populated with stats where available.
		$tempdata = sp_array_combine( $team_ids, $table_stats );

		// Create entry for each team in totals.
		$totals       = array();
		$placeholders = array();

		// Initialize incremental counter.
		$this->pos     = 0;
		$this->counter = 0;

		// Initialize team compare.
		$this->compare = null;

		// Initialize streaks counter.
		$streaks = array();

		// Initialize form counter.
		$forms = array();

		// Initialize last counters.
		$last5s  = array();
		$last10s = array();

		// Initialize record counters.
		$homerecords = array();
		$awayrecords = array();

		foreach ( $team_ids as $team_id ) :
			if ( ! $team_id ) {
				continue;
			}

			// Initialize team streaks counter.
			$streaks[ $team_id ] = array(
				'name'  => '',
				'count' => 0,
				'fire'  => 1,
			);

			// Initialize team form counter.
			$forms[ $team_id ] = array();

			// Initialize team last counters.
			$last5s[ $team_id ]  = array();
			$last10s[ $team_id ] = array();

			// Initialize team record counters.
			$homerecords[ $team_id ] = array();
			$awayrecords[ $team_id ] = array();

			// Add outcome types to team last and record counters.
			foreach ( $outcome_labels as $key => $value ) :
				$last5s[ $team_id ][ $key ]      = 0;
				$last10s[ $team_id ][ $key ]     = 0;
				$homerecords[ $team_id ][ $key ] = 0;
				$awayrecords[ $team_id ][ $key ] = 0;
			endforeach;

			// Initialize team totals.
			$totals[ $team_id ] = array(
				'eventsplayed'       => 0,
				'eventsplayed_home'  => 0,
				'eventsplayed_away'  => 0,
				'eventsplayed_venue' => 0,
				'eventminutes'       => 0,
				'eventminutes_home'  => 0,
				'eventminutes_away'  => 0,
				'eventminutes_venue' => 0,
				'streak'             => 0,
				'streak_home'        => 0,
				'streak_away'        => 0,
				'streak_venue'       => 0,
			);

			foreach ( $result_labels as $key => $value ) :
				$totals[ $team_id ][ $key . 'for' ]           = 0;
				$totals[ $team_id ][ $key . 'for_home' ]      = 0;
				$totals[ $team_id ][ $key . 'for_away' ]      = 0;
				$totals[ $team_id ][ $key . 'for_venue' ]     = 0;
				$totals[ $team_id ][ $key . 'against' ]       = 0;
				$totals[ $team_id ][ $key . 'against_home' ]  = 0;
				$totals[ $team_id ][ $key . 'against_away' ]  = 0;
				$totals[ $team_id ][ $key . 'against_venue' ] = 0;
			endforeach;

			foreach ( $outcome_labels as $key => $value ) :
				$totals[ $team_id ][ $key ]            = 0;
				$totals[ $team_id ][ $key . '_home' ]  = 0;
				$totals[ $team_id ][ $key . '_away' ]  = 0;
				$totals[ $team_id ][ $key . '_venue' ] = 0;
			endforeach;

			// Get static stats.
			$static = get_post_meta( $team_id, 'sp_columns', true );

			if ( 'yes' == get_option( 'sportspress_team_column_editing', 'no' ) && $league_ids && $season_ids ) :
				// Add static stats to placeholders.
				foreach ( $league_ids as $league_id ) :
					foreach ( $season_ids as $season_id ) :
						$placeholders[ $team_id ] = (array) sp_array_value( sp_array_value( $static, $league_id, array() ), $season_id, array() );
					endforeach;
				endforeach;
			endif;

		endforeach;

		// Get which event status to include.
		$event_status = get_post_meta( $this->ID, 'sp_event_status', true );

		if ( empty( $event_status ) ) {
			$event_status = array( 'publish', 'future' );
		}

		if ( isset( $this->show_published_events ) ) { // If an attribute was pass through shortcode.
			if ( '1' == $this->show_published_events ) {
				$event_status[] = 'publish';
			} else {
				if ( ( $status_key = array_search( 'publish', $event_status ) ) !== false ) {
					unset( $event_status[ $status_key ] );
				}
			}
		}

		if ( isset( $this->show_future_events ) ) { // If an attribute was pass through shortcode.
			if ( $this->show_future_events == '1' ) {
				$event_status[] = 'future';
			} else {
				if ( ( $status_key = array_search( 'future', $event_status ) ) !== false ) {
					unset( $event_status[ $status_key ] );
				}
			}
		}

		// Make sure to have unique values in the array.
		$event_status = array_unique( $event_status );

		$args = array(
			'post_type'      => 'sp_event',
			'post_status'    => $event_status,
			'numberposts'    => -1,
			'posts_per_page' => -1,
			'orderby'        => 'post_date',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'     => 'sp_format',
					'value'   => apply_filters( 'sportspress_competitive_event_formats', array( 'league' ) ),
					'compare' => 'IN',
				),
			),
			'tax_query'      => array(
				'relation' => 'AND',
			),
		);

		if ( $league_ids ) :
			$args['tax_query'][] = array(
				'taxonomy' => 'sp_league',
				'field'    => 'term_id',
				'terms'    => $league_ids,
			);
		endif;

		if ( $season_ids ) :
			$args['tax_query'][] = array(
				'taxonomy' => 'sp_season',
				'field'    => 'term_id',
				'terms'    => $season_ids,
			);
		endif;

		if ( $this->date !== 0 ) :
			if ( $this->date == 'w' ) :
				$args['year'] = date_i18n( 'Y' );
				$args['w']    = date_i18n( 'W' );
			elseif ( $this->date == 'day' ) :
				$args['year']     = date_i18n( 'Y' );
				$args['day']      = date_i18n( 'j' );
				$args['monthnum'] = date_i18n( 'n' );
			elseif ( $this->date == 'range' ) :
				if ( $this->relative ) :
					add_filter( 'posts_where', array( $this, 'relative' ) );
				else :
					add_filter( 'posts_where', array( $this, 'range' ) );
				endif;
			endif;
		endif;

		$args = apply_filters( 'sportspress_table_data_event_args', $args );

		if ( ! $is_main_loop ) :
			if ( sizeof( $team_ids ) ) :
				$args['meta_query'][] = array(
					'key'     => 'sp_team',
					'value'   => $team_ids,
					'compare' => 'IN',
				);
			endif;
		endif;

		$events = get_posts( $args );

		// Remove range filters.
		remove_filter( 'posts_where', array( $this, 'range' ) );
		remove_filter( 'posts_where', array( $this, 'relative' ) );

		$e = 0;

		// Event loop.
		foreach ( $events as $event ) :

			$teams = (array) get_post_meta( $event->ID, 'sp_team', false );
			$teams = array_filter( $teams );
			if ( ! $is_main_loop && sizeof( array_diff( $teams, $team_ids ) ) ) {
				continue;
			}

			$results = (array) get_post_meta( $event->ID, 'sp_results', true );
			$minutes = get_post_meta( $event->ID, 'sp_minutes', true );
			if ( $minutes === '' ) {
				$minutes = get_option( 'sportspress_event_minutes', 90 );
			}

			$i = 0;

			foreach ( $results as $team_id => $team_result ) :

				if ( ! in_array( $team_id, $teams ) ) {
					continue;
				}

				if ( ! in_array( $team_id, $team_ids ) ) {
					$i++;
					continue;
				}

				if ( $team_result ) :
					foreach ( $team_result as $key => $value ) :

						if ( $key == 'outcome' ) :

							if ( ! is_array( $value ) ) :
								$value = array( $value );
							endif;

							foreach ( $value as $outcome ) :

								// Increment events played and outcome count.
								if ( array_key_exists( $team_id, $totals ) && is_array( $totals[ $team_id ] ) && array_key_exists( $outcome, $totals[ $team_id ] ) ) :
									$totals[ $team_id ]['eventsplayed'] ++;
									$totals[ $team_id ]['eventminutes'] += $minutes;
									$totals[ $team_id ][ $outcome ] ++;

									// Add to home or away stats.
									if ( 0 === $i ) :
										$totals[ $team_id ]['eventsplayed_home'] ++;
										$totals[ $team_id ]['eventminutes_home'] += $minutes;
										$totals[ $team_id ][ $outcome . '_home' ] ++;
									else :
										$totals[ $team_id ]['eventsplayed_away'] ++;
										$totals[ $team_id ]['eventminutes_away'] += $minutes;
										$totals[ $team_id ][ $outcome . '_away' ] ++;
									endif;

									// Add to venue stats.
									if ( sp_is_home_venue( $team_id, $event->ID ) ) :
										$totals[ $team_id ]['eventsplayed_venue'] ++;
										$totals[ $team_id ]['eventminutes_venue'] += $minutes;
										$totals[ $team_id ][ $outcome . '_venue' ] ++;
									endif;
								endif;

								if ( $outcome && $outcome != '-1' ) :

									// Add to streak counter.
									if ( $streaks[ $team_id ]['fire'] && ( $streaks[ $team_id ]['name'] == '' || $streaks[ $team_id ]['name'] == $outcome ) ) :
										$streaks[ $team_id ]['name'] = $outcome;
										$streaks[ $team_id ]['count'] ++;
									else :
										$streaks[ $team_id ]['fire'] = 0;
									endif;

									// Add to form counter.
									$forms[ $team_id ][] = array(
										'id'      => $event->ID,
										'outcome' => $outcome,
									);

									// Add to last 5 counter if sum is less than 5.
									if ( array_key_exists( $team_id, $last5s ) && array_key_exists( $outcome, $last5s[ $team_id ] ) && array_sum( $last5s[ $team_id ] ) < 5 ) :
										$last5s[ $team_id ][ $outcome ] ++;
									endif;

									// Add to last 10 counter if sum is less than 10.
									if ( array_key_exists( $team_id, $last10s ) && array_key_exists( $outcome, $last10s[ $team_id ] ) && array_sum( $last10s[ $team_id ] ) < 10 ) :
										$last10s[ $team_id ][ $outcome ] ++;
									endif;

									// Add to home or away record.
									if ( 0 === $i ) {
										if ( array_key_exists( $team_id, $homerecords ) && array_key_exists( $outcome, $homerecords[ $team_id ] ) ) {
											$homerecords[ $team_id ][ $outcome ] ++;
										}
									} else {
										if ( array_key_exists( $team_id, $awayrecords ) && array_key_exists( $outcome, $awayrecords[ $team_id ] ) ) {
											$awayrecords[ $team_id ][ $outcome ] ++;
										}
									}

								endif;

							endforeach;

										else :
											if ( array_key_exists( $team_id, $totals ) && is_array( $totals[ $team_id ] ) && array_key_exists( $key . 'for', $totals[ $team_id ] ) ) :

												// Get numeric value.
												$value = floatval( $value );

												$totals[ $team_id ][ $key . 'for' ]             += $value;
												$totals[ $team_id ][ $key . 'for' . ( $e + 1 ) ] = $value;

												// Add to home or away stats.
												if ( 0 === $i ) :
													$totals[ $team_id ][ $key . 'for_home' ] += $value;
												else :
													$totals[ $team_id ][ $key . 'for_away' ] += $value;
												endif;

												// Add to venue stats.
												if ( sp_is_home_venue( $team_id, $event->ID ) ) :
													$totals[ $team_id ][ $key . 'for_venue' ] += $value;
												endif;

												foreach ( $results as $other_team_id => $other_result ) :
													if ( $other_team_id != $team_id && array_key_exists( $key . 'against', $totals[ $team_id ] ) ) :

														// Get numeric value of other team's result.
														$value = floatval( sp_array_value( $other_result, $key, 0 ) );

														$totals[ $team_id ][ $key . 'against' ]             += $value;
														$totals[ $team_id ][ $key . 'against' . ( $e + 1 ) ] = $value;

														// Add to home or away stats.
														if ( 0 === $i ) :
															$totals[ $team_id ][ $key . 'against_home' ] += $value;
														else :
															$totals[ $team_id ][ $key . 'against_away' ] += $value;
														endif;

														// Add to venue stats.
														if ( sp_is_home_venue( $team_id, $event->ID ) ) :
															$totals[ $team_id ][ $key . 'against_venue' ] += $value;
														endif;
													endif;
												endforeach;
											endif;
										endif;

				endforeach;
endif;

				$i++;

			endforeach;

			$e++;

		endforeach;

		// Get outcomes.
		$outcomes = array();

		$args  = array(
			'post_type'      => 'sp_outcome',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);
		$posts = get_posts( $args );

		if ( $posts ) :
			foreach ( $posts as $post ) :
				// Get ID.
				$id = $post->ID;

				// Get title.
				$title = $post->post_title;

				// Get abbreviation.
				$abbreviation = get_post_meta( $id, 'sp_abbreviation', true );
				if ( ! $abbreviation ) :
					$abbreviation = substr( $title, 0, 1 );
				endif;

				// Get color.
				$color = get_post_meta( $id, 'sp_color', true );
				if ( '' === $color ) {
					$color = '#888888';
				}

				$outcomes[ $post->post_name ] = array(
					'id'           => $id,
					'title'        => $title,
					'abbreviation' => $abbreviation,
					'color'        => $color,
				);
			endforeach;
		endif;

		foreach ( $streaks as $team_id => $streak ) :
			// Compile streaks counter and add to totals.
			if ( $streak['name'] ) :
				$outcome = sp_array_value( $outcomes, $streak['name'], false );
				if ( $outcome ) :
					$color                        = $outcome['color'];
					$totals[ $team_id ]['streak'] = '<span style="color:' . $color . '">' . $outcome['abbreviation'] . $streak['count'] . '</span>';
				else :
					$totals[ $team_id ]['streak'] = null;
				endif;
			else :
				$totals[ $team_id ]['streak'] = null;
			endif;
		endforeach;

		foreach ( $forms as $team_id => $form ) :
			// Apply form limit.
			if ( $form_limit && count( $form ) > $form_limit ) :
				$form = array_slice( $form, 0, $form_limit );
			endif;

			// Initialize team form array.
			$team_form = array();

			// Reverse form array to display in chronological order.
			$form = array_reverse( $form );

			// Loop through event form.
			foreach ( $form as $form_event ) :
				if ( $form_event['id'] ) :
					$outcome = sp_array_value( $outcomes, $form_event['outcome'], false );
					if ( $outcome ) :
						$abbreviation = $outcome['abbreviation'];
						$color        = $outcome['color'];
						if ( $link_events ) :
							$abbreviation = '<a class="sp-form-event-link" href="' . get_post_permalink( $form_event['id'], false, true ) . '" style="background-color:' . $color . '">' . $abbreviation . '</a>';
						else :
							$abbreviation = '<span class="sp-form-event-link" style="background-color:' . $color . '">' . $abbreviation . '</span>';
						endif;

						// Add to team form.
						$team_form[] = $abbreviation;
					endif;
				endif;
			endforeach;

			// Append to totals.
			if ( count( $team_form ) ) :
				$totals[ $team_id ]['form'] = '<div class="sp-form-events">' . implode( ' ', $team_form ) . '</div>';
			else :
				$totals[ $team_id ]['form'] = null;
			endif;
		endforeach;

		foreach ( $last5s as $team_id => $last5 ) :
			// Add last 5 to totals.
			$totals[ $team_id ]['last5'] = $last5;
		endforeach;

		foreach ( $last10s as $team_id => $last10 ) :
			// Add last 10 to totals.
			$totals[ $team_id ]['last10'] = $last10;
		endforeach;

		foreach ( $homerecords as $team_id => $homerecord ) :
			// Add home record to totals.
			$totals[ $team_id ]['homerecord'] = $homerecord;
		endforeach;

		foreach ( $awayrecords as $team_id => $awayrecord ) :
			// Add away record to totals.
			$totals[ $team_id ]['awayrecord'] = $awayrecord;
		endforeach;

		$args  = array(
			'post_type'      => 'sp_column',
			'numberposts'    => -1,
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		);
		$stats = get_posts( $args );

		$columns              = array();
		$sah2h_criterion      = get_page_by_path( $this->h2h_criteria, OBJECT, 'sah2h_criteria' );
		$this->priorities     = array();
		$temp_priorites       = get_post_meta( $sah2h_criterion->ID, 'sah2h_column_order', true );
		$this->h2h_priorities = array();
		$temp_h2h_priorites   = get_post_meta( $sah2h_criterion->ID, 'sah2h_tiebreak_order', true );

		foreach ( $temp_priorites as $temp_priority ) {
			if ( isset( $temp_priority['order'] ) ) {
				$this->priorities[] = $temp_priority;
			}
		}

		foreach ( $temp_h2h_priorites as $temp_h2h_priority ) {
			if ( isset( $temp_h2h_priority['order'] ) ) {
				$this->h2h_priorities[] = $temp_h2h_priority;
			}
		}

		foreach ( $stats as $stat ) :

			// Get post meta.
			$meta = get_post_meta( $stat->ID );

			// Add equation to object.
			$stat->equation  = sp_array_value( sp_array_value( $meta, 'sp_equation', array() ), 0, null );
			$stat->precision = sp_array_value( sp_array_value( $meta, 'sp_precision', array() ), 0, 0 );

			// Add column name to columns.
			$columns[ $stat->post_name ] = $stat->post_title;

		endforeach;

		// Initialize games back column variable.
		$gb_column = null;

		// Fill in empty placeholder values for each team.
		foreach ( $team_ids as $team_id ) :
			if ( ! $team_id ) {
				continue;
			}

			$placeholders[ $team_id ] = array();

			foreach ( $stats as $stat ) :
				if ( sp_array_value( sp_array_value( $placeholders, $team_id, array() ), $stat->post_name, '' ) == '' ) :

					if ( $stat->equation == null ) :
						$placeholder = sp_array_value( sp_array_value( $adjustments, $team_id, array() ), $stat->post_name, null );
						if ( $placeholder == null ) :
							$placeholder = '-';
						endif;
					else :
						// Solve.
						$placeholder = sp_solve( $stat->equation, sp_array_value( $totals, $team_id, array() ), $stat->precision, 0, $team_id );

						if ( '$gamesback' == $stat->equation ) {
							$gb_column = $stat->post_name;
						}

						if ( ! in_array( $stat->equation, apply_filters( 'sportspress_equation_presets', array( '$gamesback', '$streak', '$form', '$last5', '$last10', '$homerecord', '$awayrecord' ) ) ) ) :
							// Adjustments.
							$adjustment = sp_array_value( $adjustments, $team_id, array() );

							if ( 0 != $adjustment ) :
								$value        = floatval( sp_array_value( $adjustment, $stat->post_name, 0 ) );
								$placeholder += $value;
								$placeholder  = number_format( $placeholder, $stat->precision, '.', '' );
							endif;
						endif;
					endif;

					$placeholders[ $team_id ][ $stat->post_name ] = $placeholder;
				endif;
			endforeach;
		endforeach;

		// Find win and loss variables for games back.
		$w = $l = null;
		if ( $gb_column ) {
			$args     = array(
				'post_type'      => 'sp_outcome',
				'numberposts'    => 1,
				'posts_per_page' => 1,
				'meta_query'     => array(
					array(
						'key'   => 'sp_condition',
						'value' => '>',
					),
				),
			);
			$outcomes = get_posts( $args );

			if ( $outcomes ) {
				$outcome = reset( $outcomes );
				if ( is_array( $stats ) ) {
					foreach ( $stats as $stat ) {
						if ( '$' . $outcome->post_name == $stat->equation ) {
							$w = $stat->post_name;
						}
					}
				}
			}

			// Calculate games back.
			$args     = array(
				'post_type'      => 'sp_outcome',
				'numberposts'    => 1,
				'posts_per_page' => 1,
				'meta_query'     => array(
					array(
						'key'   => 'sp_condition',
						'value' => '<',
					),
				),
			);
			$outcomes = get_posts( $args );

			if ( $outcomes ) {
				$outcome = reset( $outcomes );
				if ( is_array( $stats ) ) {
					foreach ( $stats as $stat ) {
						if ( '$' . $outcome->post_name == $stat->equation ) {
							$l = $stat->post_name;
						}
					}
				}
			}
		}

		// Merge the data and placeholders arrays.
		$merged = array();

		foreach ( $placeholders as $team_id => $team_data ) :

			// Add team name to row.
			$merged[ $team_id ] = array();

			$team_data['name'] = sp_team_short_name( $team_id );

			foreach ( $team_data as $key => $value ) :

				// Use static data if key exists and value is not empty, else use placeholder.
				if ( array_key_exists( $team_id, $tempdata ) && array_key_exists( $key, $tempdata[ $team_id ] ) && $tempdata[ $team_id ][ $key ] != '' ) :
					$value = $tempdata[ $team_id ][ $key ];
				endif;

				$merged[ $team_id ][ $key ] = $value;

			endforeach;

		endforeach;

		uasort( $merged, array( $this, 'sort' ) );

		if ( $is_main_loop ) {
			// Temporary save the full table stat data.
			$this->temp_merged = $merged;
		}

		if ( ! $is_main_loop ) {
			// Check the h2h priorities if are h2h_only.
			foreach ( $this->h2h_priorities as $temp_priority ) {
				if ( isset( $temp_priority['h2h_only'] ) && '' == $temp_priority['h2h_only'] ) {
					// If not replace the current h2h stat data with the full (temporary) stat data.
					foreach ( $team_ids as $temp_team_id ) {
						$merged[ $temp_team_id ][ $temp_priority['column'] ] = $this->temp_merged[ $temp_team_id ][ $temp_priority['column'] ];
					}
				}
			}
			uasort( $merged, array( $this, 'h2h_sort' ) );
		}

		// Calculate position of teams for ties.
		foreach ( $merged as $team_id => $team_columns ) {
			$merged[ $team_id ]['pos'] = $this->calculate_pos( $team_columns, $team_id );
		}

		// Head to head table sorting.
		if ( $is_main_loop ) {
			$order = array();

			foreach ( $this->tiebreakers as $pos => $teams ) {
				if ( count( $teams ) === 1 ) {
					$order[] = reset( $teams );
				} else {
					$standings = $this->data( false, $teams );
					$teams     = array_keys( $standings );
					foreach ( $teams as $team ) {
						$order[] = $team;
					}
				}
			}

			$head_to_head = array();
			foreach ( $order as $team ) {
				$head_to_head[ $team ] = sp_array_value( $merged, $team, array() );
			}
			$merged = $head_to_head;

			// Recalculate position of teams after head to head.
			$this->pos     = 0;
			$this->counter = 0;
			foreach ( $merged as $team_id => $team_columns ) {
				$merged[ $team_id ]['pos'] = $this->calculate_pos( $team_columns, $team_id, false );
			}
		}

		// Rearrange the table if Default ordering is not selected.
		if ( 'default' !== $this->orderby ) {
			uasort( $merged, array( $this, 'simple_order' ) );
			// Recalculate position of teams.
			$this->pos     = 0;
			$this->counter = 0;
			foreach ( $merged as $team_id => $team_columns ) {
				$merged[ $team_id ]['pos'] = $this->calculate_pos( $team_columns, $team_id, false );
			}
		}

		// Rearrange data array to reflect values.
		$data = array();
		foreach ( $merged as $key => $value ) :
			$data[ $key ] = $tempdata[ $key ];
		endforeach;

		if ( ! $is_main_loop ) :
			return $merged;
		elseif ( $admin ) :
			$this->add_gb( $placeholders, $w, $l, $gb_column );
			return array( $columns, $usecolumns, $data, $placeholders, $merged );
		else :
			$this->add_gb( $merged, $w, $l, $gb_column );
			if ( ! is_array( $usecolumns ) ) {
				$usecolumns = array();
			}
			$labels    = array_merge(
				array(
					'pos'  => esc_attr__( 'Pos', 'sportspress' ),
					'name' => esc_attr__(
						'Team',
						'sportspress'
					),
				),
				$columns
			);
			$merged[0] = $labels;
			return $merged;
		endif;
	}

	/**
	 * Sort the table by h2h priorities.
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	public function h2h_sort( $a, $b ) {

		// Loop through priorities.
		foreach ( $this->h2h_priorities as $priority ) :

			// Proceed if columns are not equal.
			if ( sp_array_value( $a, $priority['column'], 0 ) !== sp_array_value( $b, $priority['column'], 0 ) ) :

				// Compare column values.
				$output = (float) sp_array_value( $a, $priority['column'], 0 ) - (float) sp_array_value( $b, $priority['column'], 0 );

				// Flip value if descending order.
				if ( 'DESC' === $priority['order'] ) {
					$output = 0 - $output;
				}

				return ( $output > 0 ? 1 : -1 );

			endif;

		endforeach;

		// Default sort by alphabetical.
		return strcmp( sp_array_value( $a, 'name', '' ), sp_array_value( $b, 'name', '' ) );
	}

}
