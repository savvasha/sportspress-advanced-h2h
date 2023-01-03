<?php
/**
 * SAH2H Tiebreak Criteria Class
 *
 * An Advanced Head to Head League Table Tiebreak Criteria Class.
 *
 * @class       SAH2H_Tiebreak_Criteria
 * @version     2.0.0
 * @package     SAH2H/Classes
 * @category    Class
 * @author      Savvas
 */

/**
 * SAH2H Tiebreak Criteria Class
 *
 * @class SAH2H_Tiebreak_Criteria
 */
class SAH2H_Tiebreak_Criteria {

	/**
	 * Output the Regular Order metabox
	 */
	public static function regular_order_output( $post ) {
		// Add a nonce field for security.
		wp_nonce_field( 'sah2h_save_custom_meta', 'sah2h_regular_order_nonce' );

		$sah2h_column_order = get_post_meta( $post->ID, 'sah2h_column_order', true );

		$args    = array(
			'numberposts' => -1,
			'post_type'   => 'sp_column',
			'post_status' => 'publish',
		);
		$columns = get_posts( $args );

		if ( is_array( $sah2h_column_order ) ) {
			$sah2h_column_order         = array_column( $sah2h_column_order, null, 'column' );
			$sorting_sah2h_column_order = array_column( $sah2h_column_order, 'column' );
			usort(
				$columns,
				function ( $a, $b ) use ( $sorting_sah2h_column_order ) {
					$pos_a = array_search( $a->post_name, $sorting_sah2h_column_order );
					$pos_b = array_search( $b->post_name, $sorting_sah2h_column_order );

					return $pos_a - $pos_b;
				}
			);
		}
		?>
		<div class="sah2h-sortable-list-container">
			<p class="description"><?php esc_html_e( 'Drag each item into the order you prefer.', 'sportspress' ); ?></p>
			<ul class="sah2h-layout sah2h-sortable-list sah2h-connected-list ui-sortable">
			<?php
			$i = 1;
			foreach ( $columns as $column ) {
				$h2h_order      = null;
				$h2h_only       = null;
				$h2h_visibility = null;
				$disabled       = '';

				if ( isset( $sah2h_column_order[ $column->post_name ]['order'] ) ) {
					$h2h_order = $sah2h_column_order[ $column->post_name ]['order'];
				}
				if ( isset( $sah2h_column_order[ $column->post_name ]['h2h_visibility'] ) ) {
					$h2h_visibility = $sah2h_column_order[ $column->post_name ]['h2h_visibility'];
				}
				if ( ! $h2h_visibility ) {
					$disabled = 'disabled';
				}
				?>
				<li>
					<div class="sah2h-item-bar sah2h-layout-item-bar">
						<div class="sah2h-item-handle sah2h-layout-item-handle ui-sortable-handle">
							<span class="sah2h-item-title item-title"><?php echo esc_html( $column->post_title ); ?></span>
							<input type="hidden" name="sah2h_column_order[<?php echo esc_attr( $i ); ?>][column]" value="<?php echo esc_html( $column->post_name ); ?>">
						</div>
						
						<select class="h2h_order" name="sah2h_column_order[<?php echo esc_attr( $i ); ?>][order]" <?php echo esc_attr( $disabled ); ?>>
							<?php
							$options = array(
								'DESC' => esc_attr__( 'Descending', 'sportspress' ),
								'ASC'  => esc_attr__( 'Ascending', 'sportspress' ),
							);
							foreach ( $options as $key => $value ) :
								printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( true, $key === $h2h_order, false ), esc_html( $value ) );
							endforeach;
							?>
						</select>
						<input type="hidden" name="sportspress_<?php echo esc_attr( $column->post_name ); ?>_visibility[]" value="0">
						<input class="sah2h-toggle-switch" type="checkbox" name="sah2h_column_order[<?php echo esc_attr( $i ); ?>][h2h_visibility]" id="sah2h_column_show_<?php echo esc_attr( $column->post_name ); ?>" value="1" <?php checked( $h2h_visibility, 1 ); ?> />
						<label for="sah2h_column_show_<?php echo esc_attr( $column->post_name ); ?>"></label>
					</div>
				</li>
				<?php
				$i++;
			}
			?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Output the Tiebreak Order metabox
	 */
	public static function tiebreak_order_output( $post ) {
		// Add a nonce field for security.
		wp_nonce_field( 'sah2h_save_custom_meta', 'sah2h_tiebreak_order_nonce' );

		$sah2h_tiebreak_order = get_post_meta( $post->ID, 'sah2h_tiebreak_order', true );

		$args    = array(
			'numberposts' => -1,
			'post_type'   => 'sp_column',
			'post_status' => 'publish',
		);
		$columns = get_posts( $args );

		if ( is_array( $sah2h_tiebreak_order ) ) {
			$sah2h_tiebreak_order       = array_column( $sah2h_tiebreak_order, null, 'column' );
			$sorting_sah2h_column_order = array_column( $sah2h_tiebreak_order, 'column' );
			usort(
				$columns,
				function ( $a, $b ) use ( $sorting_sah2h_column_order ) {
					$pos_a = array_search( $a->post_name, $sorting_sah2h_column_order );
					$pos_b = array_search( $b->post_name, $sorting_sah2h_column_order );

					return $pos_a - $pos_b;
				}
			);
		}
		?>
		<div class="sah2h-sortable-list-container">
			<p class="description"><?php esc_html_e( 'Drag each item into the order you prefer.', 'sportspress' ); ?></p>
			<ul class="sah2h-layout sah2h-sortable-list sah2h-connected-list ui-sortable">
			<?php
			$i = 1;
			foreach ( $columns as $column ) {
				$h2h_order      = null;
				$h2h_only       = null;
				$h2h_visibility = null;
				$disabled       = '';

				if ( isset( $sah2h_tiebreak_order[ $column->post_name ]['order'] ) ) {
					$h2h_order = $sah2h_tiebreak_order[ $column->post_name ]['order'];
				}
				if ( isset( $sah2h_tiebreak_order[ $column->post_name ]['h2h_only'] ) ) {
					$h2h_only = $sah2h_tiebreak_order[ $column->post_name ]['h2h_only'];
				}
				if ( isset( $sah2h_tiebreak_order[ $column->post_name ]['h2h_visibility'] ) ) {
					$h2h_visibility = $sah2h_tiebreak_order[ $column->post_name ]['h2h_visibility'];
				}
				if ( ! $h2h_visibility ) {
					$disabled = 'disabled';
				}
				?>
				<li>
					<div class="sah2h-item-bar sah2h-layout-item-bar">
						<div class="sah2h-item-handle sah2h-layout-item-handle ui-sortable-handle">
							<span class="sah2h-item-title item-title"><?php echo esc_html( $column->post_title ); ?></span>
							<input type="hidden" name="sah2h_tiebreak_order[<?php echo esc_attr( $i ); ?>][column]" value="<?php echo esc_html( $column->post_name ); ?>">
						</div>
						
						<select class="h2h_order" name="sah2h_tiebreak_order[<?php echo esc_attr( $i ); ?>][order]" <?php echo esc_attr( $disabled ); ?>>
							<?php
							$options = array(
								'DESC' => esc_attr__( 'Descending', 'sportspress' ),
								'ASC'  => esc_attr__( 'Ascending', 'sportspress' ),
							);
							foreach ( $options as $key => $value ) :
								printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( true, $key === $h2h_order, false ), esc_html( $value ) );
							endforeach;
							?>
						</select>
						<input class="sah2h-h2honly-checkbox" type="checkbox" name="sah2h_tiebreak_order[<?php echo esc_attr( $i ); ?>][h2h_only]" id="sah2h_h2honly_<?php echo esc_attr( $column->post_name ); ?>" value="1" <?php checked( $h2h_only, 1 ); ?> <?php echo esc_attr( $disabled ); ?>/>
						<label for="sah2h_h2honly_<?php echo esc_attr( $column->post_name ); ?>">H2H Only</label>
						<input type="hidden" name="sportspress_<?php echo esc_attr( $column->post_name ); ?>_visibility[]" value="0">
						<input class="sah2h-toggle-switch" type="checkbox" name="sah2h_tiebreak_order[<?php echo esc_attr( $i ); ?>][h2h_visibility]" id="sah2h_tiebreak_show_<?php echo esc_attr( $column->post_name ); ?>" value="1" <?php checked( $h2h_visibility, 1 ); ?> />
						<label for="sah2h_tiebreak_show_<?php echo esc_attr( $column->post_name ); ?>"></label>
					</div>
				</li>
				<?php
				$i++;
			}
			?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Output the Tiebreak Order metabox
	 */
	public static function sorting_summary_output( $post ) {
		$sah2h_column_order   = get_post_meta( $post->ID, 'sah2h_column_order', true );
		$sah2h_tiebreak_order = get_post_meta( $post->ID, 'sah2h_tiebreak_order', true );
		$sorting_summary      = array();
		foreach ( (array) $sah2h_column_order as $column ) {
			if ( isset( $column['order'] ) ) {
				$sorting_summary[] = $column;
			}
		}

		foreach ( (array) $sah2h_tiebreak_order as $tiebreak ) {
			if ( isset( $tiebreak['order'] ) ) {
				$sorting_summary[] = $tiebreak;
			}
		}
		?>
		<div class="sah2h-list-container">
			<ol>
		<?php
		foreach ( $sorting_summary as $sorting_criterion ) {
			$column         = get_page_by_path( $sorting_criterion['column'], OBJECT, 'sp_column' );
			$criterion_name = ( '' !== $column->post_excerpt ) ? $column->post_excerpt : $column->post_title;
			if ( isset( $sorting_criterion['h2h_only'] ) ) {
				$criterion_name = esc_html__( 'H2H ', 'advanced-h2h-for-sportspress' ) . $criterion_name;
			}
			if ( 'DESC' == $sorting_criterion['order'] ) {
				$criterion_name .= ' &darr;';
			}
			if ( 'ASC' == $sorting_criterion['order'] ) {
				$criterion_name .= ' &uarr;';
			}
			echo '<li>' . esc_html( $criterion_name ) . '</li>';
		}
		?>
			</ol>
		</div>
		<?php
	}
}
