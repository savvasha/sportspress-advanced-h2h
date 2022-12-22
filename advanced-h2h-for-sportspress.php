<?php
/**
 * Plugin Name: Advanced H2H for SportsPress
 * Description: Give your league managers the option to use more advanced head to head criteria for tiebreaks.
 * Version: 1.0.0
 * Author: Savvas
 * Author URI: https://profiles.wordpress.org/savvasha/
 * Requires at least: 5.3
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl.html
 *
 * @package advanced-h2h-for-sportspress
 * @category Core
 * @author savvasha
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants.
if ( ! defined( 'SAH2H_PLUGIN_BASE' ) ) {
	define( 'SAH2H_PLUGIN_BASE', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'SAH2H_PLUGIN_DIR' ) ) {
	define( 'SAH2H_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SAH2H_PLUGIN_URL' ) ) {
	define( 'SAH2H_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Include required files.
if ( 'h2h' === get_option( 'sportspress_table_tiebreaker', 'none' ) ) {
	// load needed class functions.
	include SAH2H_PLUGIN_DIR . 'includes/class-sah2h-league-table.php';
	// Override SportsPress templates.
	add_filter( 'sportspress_locate_template', 'sah2h_shortcode_override', 10, 3 );
}

/**
 * Shortcode override
 *
 * @param mixed $template The template path plus the name.
 * @param mixed $template_name The template name.
 * @param mixed $template_path The template path.
 * @return string
 */
function sah2h_shortcode_override( $template = null, $template_name = null, $template_path = null ) {

	if ( 'league-table.php' === $template_name ) {
		$template_path = SAH2H_PLUGIN_DIR . 'templates/';
		$template      = $template_path . $template_name;
	}

	return $template;
}

// Filters.
add_filter( 'sportspress_table_options', 'sah2h_add_settings' );

// Actions.
add_action( 'add_meta_boxes', 'sah2h_add_meta_boxes', 30 );
add_action( 'sportspress_process_sp_column_meta', 'sah2h_save', 15, 2 );
add_action( 'admin_enqueue_scripts', 'sah2h_admin_enqueue_assets', -99 );

/**
 * Add settings.
 *
 * @param array $settings The SportsPress settings array.
 * @return array
 */
function sah2h_add_settings( $settings ) {
	foreach ( $settings as $key => $setting ) {
		if ( 'h2h' === get_option( 'sportspress_table_tiebreaker', 'none' ) ) {
			if ( 'sportspress_table_tiebreaker' === $setting['id'] ) {
				$setting['desc']  = '<div id="h2h-criteria">';
				$setting['desc'] .= '<strong>Head to Head Criteria:</strong><br/>';
				$args             = array(
					'post_type'      => 'sp_column',
					'numberposts'    => -1,
					'posts_per_page' => -1,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
				);
				$stats            = get_posts( $args );
				$h2h_criteria     = array();
				$i                = 1;
				foreach ( $stats as $stat ) {
					$h2h_priority = get_post_meta( $stat->ID, 'h2h_priority', true );
					$h2h_order    = get_post_meta( $stat->ID, 'h2h_order', true );
					$h2h_only     = get_post_meta( $stat->ID, 'h2h_only', true );
					if ( $h2h_priority > 0 ) {
						$h2h_criteria[ $h2h_priority ]            = $stat;
						$h2h_criteria[ $h2h_priority ]->h2h_order = sah2h_get_post_order( $stat->ID );
						if ( 1 === intval( $h2h_only ) ) {
							$h2h_criteria[ $h2h_priority ]->h2h_only = esc_html__( 'Head to Head ', 'advanced-h2h-for-sportspress' );
						}
					}
				}
				ksort( $h2h_criteria );
				foreach ( $h2h_criteria as $h2h_criterion ) {
					$criterion_name   = ( '' !== $h2h_criterion->post_excerpt ) ? $h2h_criterion->post_excerpt : $h2h_criterion->post_title;
					$setting['desc'] .= '(' . $h2h_criterion->h2h_order . ') ' . $h2h_criterion->h2h_only . $criterion_name . '<br/>';
					$i++;
				}
				$setting['desc'] .= '</div>';
			}
		}
		$newsettings[ $key ] = $setting;
	}

	return $newsettings;
}

/**
 * Replace order with arrow
 *
 * @param integer $post_id The post id.
 * @return string
 */
function sah2h_get_post_order( $post_id ) {
	$priority = get_post_meta( $post_id, 'h2h_priority', true );
	if ( $priority ) :
		return $priority . ' ' . str_replace(
			array( 'DESC', 'ASC' ),
			array( '&darr;', '&uarr;' ),
			get_post_meta( $post_id, 'h2h_order', true )
		);
	else :
		return '&mdash;';
	endif;
}

/**
 * Add meta boxes.
 */
function sah2h_add_meta_boxes() {
	add_meta_box( 'h2h_h2hdiv', __( 'Advanced Head to Head', 'advanced-h2h-for-sportspress' ), 'sah2h_meta_box', 'sp_column', 'side', 'low' );
}

/**
 * Output the meta box.
 *
 * @param object $post The post object.
 */
function sah2h_meta_box( $post ) {
	$h2h_priority = get_post_meta( $post->ID, 'h2h_priority', true );
	$h2h_order    = get_post_meta( $post->ID, 'h2h_order', true );
	$h2h_only     = get_post_meta( $post->ID, 'h2h_only', true );
	wp_nonce_field( 'h2h_meta_box', 'h2h_meta_box_nonce' );
	?>
	<p><strong><?php esc_html_e( 'H2H Sort Order', 'advanced-h2h-for-sportspress' ); ?></strong></p>
	<p class="h2h-order-selector">
		<select name="h2h_priority">
			<?php
			$options = array( '0' => esc_attr__( 'Disable', 'sportspress' ) );
			$count   = wp_count_posts( 'sp_column' );
			for ( $i = 1; $i <= $count->publish; $i++ ) :
				$options[ $i ] = $i;
			endfor;
			foreach ( $options as $key => $value ) :
				printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( true, intval( $key ) === intval( $h2h_priority ), false ), esc_html( $value ) );
			endforeach;
			?>
		</select>
		<select name="h2h_order">
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
		<br/>
		<label>
			<input type="checkbox" name="h2h_only" value="1" <?php checked( $h2h_only, 1 ); ?> />
			<?php esc_attr_e( 'H2H Only', 'advanced-h2h-for-sportspress' ); ?>
		</label>
	</p>
	<?php
}

/**
 * Save H2H Priorities and Order rules.
 *
 * @param integer $post_id The post id.
 * @param object  $post The post object.
 */
function sah2h_save( $post_id, $post ) {
	// Check if SportsPress is installed and activated ( function_exists('sp_array_value') ) and also check that the genuine nonce was used
	if ( function_exists('sp_array_value') && isset( $_POST['h2h_meta_box_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['h2h_meta_box_nonce'] ) ), 'h2h_meta_box' ) ) {
		update_post_meta( $post_id, 'h2h_priority', sp_array_value( $_POST, 'h2h_priority', '' ) );
		update_post_meta( $post_id, 'h2h_order', sp_array_value( $_POST, 'h2h_order', '' ) );
		update_post_meta( $post_id, 'h2h_only', sp_array_value( $_POST, 'h2h_only', null, 'key' ) );
	}
}

/**
 * Enqueue needed scripts to the admin site.
 */
function sah2h_admin_enqueue_assets() {
	$current_screen = get_current_screen();
	if ( $current_screen && 'sp_column' === $current_screen->id ) {
		wp_enqueue_script( 'sah2h-admin', plugin_dir_url( __FILE__ ) . 'assets/js/sah2h-admin.js', array(), '1.0.0', true );
	}
}
