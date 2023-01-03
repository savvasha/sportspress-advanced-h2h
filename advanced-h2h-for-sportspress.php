<?php
/**
 * Plugin Name: Advanced H2H for SportsPress
 * Description: Give your league managers the option to use more advanced head to head criteria for tiebreaks.
 * Version: 2.0.0
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

// Override SportsPress templates.
add_filter( 'sportspress_locate_template', 'sah2h_shortcode_override', 10, 3 );
// Load needed class functions for the Tiebreak Criteria.
require SAH2H_PLUGIN_DIR . 'includes/class-sah2h-tiebreak-criteria.php';
// Load needed class functions for the enhanced League Table.
require SAH2H_PLUGIN_DIR . 'includes/class-sah2h-league-table.php';

// Filters.
add_action( 'init', 'sah2h_register_post_type' );

// Actions.
add_action( 'add_meta_boxes', 'sah2h_add_meta_boxes', 30 );
add_action( 'sportspress_process_sp_table_meta', 'sah2h_table_meta_box_save', 15, 2 );
add_action( 'admin_enqueue_scripts', 'sah2h_admin_enqueue_assets', -99 );
add_action( 'add_meta_boxes_sah2h_criteria', 'sah2h_adding_custom_meta_boxes' );
add_action( 'save_post_sah2h_criteria', 'sah2h_criteria_save_meta' );

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

/**
 * Add meta boxes.
 */
function sah2h_add_meta_boxes() {
	add_meta_box( 'sah2h-sorting-criteria-table-meta-box', __( 'Advanced Sorting Criteria', 'advanced-h2h-for-sportspress' ), 'sah2h_table_meta_box', 'sp_table', 'side', 'low' );
}

/**
 * Output the table meta box.
 *
 * @param object $post The post object.
 */
function sah2h_table_meta_box( $post ) {
	$sah2h_criteria = get_post_meta( $post->ID, 'sah2h_criteria', true );
	wp_nonce_field( 'h2h_table_meta_box', 'h2h_table_meta_box_nonce' );
	?>
	<p><strong><?php esc_attr_e( 'Sorting Criteria', 'advanced-h2h-for-sportspress' ); ?></strong></p>
			<p>
			<?php
			$args = array(
				'prepend_options' => array(
					'default' => esc_attr__( 'Default SportsPress Criteria', 'advanced-h2h-for-sportspress' ),
				),
				'post_type'       => array( 'sah2h_criteria' ),
				'name'            => 'sah2h_criteria',
				'selected'        => $sah2h_criteria,
				'values'          => 'slug',
			);
			sp_dropdown_pages( $args );
			?>
			</p>
	<?php
}

/**
 * Save Advanced Sorting Criteria for League Table.
 *
 * @param integer $post_id The post id.
 * @param object  $post The post object.
 */
function sah2h_table_meta_box_save( $post_id, $post ) {
	// Check if SportsPress is installed and activated ( function_exists('sp_array_value') ) and also check that the genuine nonce was used
	if ( function_exists( 'sp_array_value' ) && isset( $_POST['h2h_table_meta_box_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['h2h_table_meta_box_nonce'] ) ), 'h2h_table_meta_box' ) ) {
		update_post_meta( $post_id, 'sah2h_criteria', sp_array_value( $_POST, 'sah2h_criteria', '' ) );
	}
}

/**
 * Enqueue needed scripts to the admin site.
 */
function sah2h_admin_enqueue_assets() {
	$current_screen = get_current_screen();
	if ( $current_screen && in_array( $current_screen->id, array( 'sp_column', 'sah2h_criteria' ) ) ) {
		wp_enqueue_script( 'sah2h-admin', plugin_dir_url( __FILE__ ) . 'assets/js/sah2h-admin.js', array(), '1.5.0', true );
		wp_enqueue_style( 'sah2h-admin', plugin_dir_url( __FILE__ ) . 'assets/css/sah2h-admin.css', array(), '1.5.0', 'all' );
	}
}

/**
 * Register calendars post type
 */
function sah2h_register_post_type() {
	register_post_type(
		'sah2h_criteria',
		array(
			'labels'              => array(
				'name'               => esc_attr__( 'Sorting Criteria', 'advanced-h2h-for-sportspress' ),
				'singular_name'      => esc_attr__( 'Sorting Criterion', 'advanced-h2h-for-sportspress' ),
				'add_new_item'       => esc_attr__( 'Add New Criterion', 'advanced-h2h-for-sportspress' ),
				'edit_item'          => esc_attr__( 'Edit Criterion', 'advanced-h2h-for-sportspress' ),
				'new_item'           => esc_attr__( 'New', 'advanced-h2h-for-sportspress' ),
				'view_item'          => esc_attr__( 'View Criterion', 'advanced-h2h-for-sportspress' ),
				'search_items'       => esc_attr__( 'Search', 'advanced-h2h-for-sportspress' ),
				'not_found'          => esc_attr__( 'No results found.', 'advanced-h2h-for-sportspress' ),
				'not_found_in_trash' => esc_attr__( 'No results found.', 'advanced-h2h-for-sportspress' ),
			),
			'public'              => true,
			'show_ui'             => true,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'hierarchical'        => false,
			'rewrite'             => array(
				'slug'       => 'sah2h_criteria',
				'with_front' => true,
			),
			'menu_icon'           => 'dashicons-editor-ol',
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'show_in_nav_menus'   => true,
			'show_in_menu'        => 'edit.php?post_type=sp_team',
			'show_in_admin_bar'   => true,
			'show_in_rest'        => false,
		)
	);
}

function sah2h_adding_custom_meta_boxes( $post ) {
	add_meta_box(
		'sah2h-regular-order-meta-box',
		esc_attr__( 'Regular Order', 'advanced-h2h-for-sportspress' ),
		'SAH2H_Tiebreak_Criteria::regular_order_output',
		'sah2h_criteria',
		'normal',
		'high'
	);
	add_meta_box(
		'sah2h-tiebreak-order-meta-box',
		esc_attr__( 'Tiebreak Order', 'advanced-h2h-for-sportspress' ),
		'SAH2H_Tiebreak_Criteria::tiebreak_order_output',
		'sah2h_criteria',
		'normal',
		'high'
	);
	add_meta_box(
		'sah2h-sorting-summary-meta-box',
		esc_attr__( 'Sorting Criteria Summary', 'advanced-h2h-for-sportspress' ),
		'SAH2H_Tiebreak_Criteria::sorting_summary_output',
		'sah2h_criteria',
		'side',
		'low'
	);
}

function sah2h_criteria_save_meta( $post_id ) {

	if ( ! isset( $_POST['sah2h_regular_order_nonce'] ) || ! wp_verify_nonce( $_POST['sah2h_regular_order_nonce'], 'sah2h_save_custom_meta' ) ) {
		return;
	}

	if ( ! isset( $_POST['sah2h_tiebreak_order_nonce'] ) || ! wp_verify_nonce( $_POST['sah2h_tiebreak_order_nonce'], 'sah2h_save_custom_meta' ) ) {
		return;
	}

	// Check the logged in user has permission to edit this post
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['sah2h_column_order'] ) ) {
		update_post_meta( $post_id, 'sah2h_column_order', sp_array_value( $_POST, 'sah2h_column_order', array(), 'text' ) );
	}

	if ( isset( $_POST['sah2h_tiebreak_order'] ) ) {
		update_post_meta( $post_id, 'sah2h_tiebreak_order', sp_array_value( $_POST, 'sah2h_tiebreak_order', array(), 'text' ) );
	}

}
