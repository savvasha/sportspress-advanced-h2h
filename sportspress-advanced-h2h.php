<?php
/**
 * Plugin Name: SportsPress Advanced Head 2 Head
 * Description: Give your league managers the option to use more advanced h2h criteria.
 * Version: 1.0.0
 * Author: Savvas
 * Author URI: https://profiles.wordpress.org/savvasha/
 * Requires at least: 5.3
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl.html
 *
 * @package detailed-player-stats-for-sportspress
 * @category Core
 * @author savvasha
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Advanced_H2H_Main_Class' ) ) :

	/**
	 * Main Advanced H2H Class
	 *
	 * @class Advanced_H2H_Main_Class
	 */
	class Advanced_H2H_Main_Class {

		/**
		 * The plugins mode.
		 *
		 * @var string
		 */
		public static $mode;

		/**
		 * Constructor.
		 */
		public function __construct() {

			//self::$mode = get_option( 'SAH2H_player_statistics_mode', 'popup' );

			// Define constants.
			$this->define_constants();

			// Include required files.
			$this->includes();
			
			//Filters
			add_filter( 'sportspress_table_options', array( $this, 'add_settings' ) );
			
			//Actions
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 );
			add_action( 'sportspress_process_sp_column_meta', array( $this, 'save' ), 15, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'h2h_admin_enqueue_assets' ), -99 );


		}


		/**
		 * Define constants
		 */
		private function define_constants() {
			if ( ! defined( 'SAH2H_PLUGIN_BASE' ) ) {
				define( 'SAH2H_PLUGIN_BASE', plugin_basename( __FILE__ ) );
			}

			if ( ! defined( 'SAH2H_PLUGIN_DIR' ) ) {
				define( 'SAH2H_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'SAH2H_PLUGIN_URL' ) ) {
				define( 'SAH2H_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}
		}

		/**
		 * Include required files
		 */
		private function includes() {
			if ( 'h2h-adv' == get_option( 'sportspress_table_tiebreaker', 'none' ) ) {
				// load needed class functions.
				include SAH2H_PLUGIN_DIR . 'includes/class-h2h-league-table.php';
				// Override SportsPress templates.
				add_filter( 'sportspress_locate_template', array( $this, 'shortcode_override' ), 10, 3 );
			}
		}
		
		/**
		 * Add settings.
		 *
		 * @return array
		 */
		public function add_settings( $settings ) {
			  foreach ( $settings as $key => $setting ) {
				  if ( $setting['id'] === 'sportspress_table_tiebreaker' ) {
					  $setting['options']['h2h-adv'] = esc_attr__( 'Advanced Head to head', 'sportspress-advanced-h2h' );
				  }
				  $newsettings[$key] = $setting;
			  }

			  return $newsettings;
		}
		
		/**
		 * Shortcode override
		 *
		 * @param mixed $template The template path plus the name.
		 * @param mixed $template_name The template name.
		 * @param mixed $template_path The template path.
		 * @return string
		 */
		public function shortcode_override( $template = null, $template_name = null, $template_path = null ) {

			if ( 'league-table.php' === $template_name ) {
				$template_path = SAH2H_PLUGIN_DIR . 'templates/';
				$template      = $template_path . $template_name;
			}

			return $template;
		}
		
		/**
		 * Add meta boxes.
		 */
		 
		public function add_meta_boxes() {
		add_meta_box( 'h2h_h2hdiv', __( 'Advanced Head to Head', 'sportspress-advanced-h2h' ), array( $this, 'meta_box' ), 'sp_column', 'side', 'low' );
		}
		
		/**
		 * Output the meta box.
		 */
		public static function meta_box( $post ) {
			$h2h_priority 	= get_post_meta( $post->ID, 'h2h_priority', true );
			$h2h_order     	= get_post_meta( $post->ID, 'h2h_order', true );
			$h2h_only     	= get_post_meta( $post->ID, 'h2h_only', true );
			
			?>
			<p><strong><?php _e( 'H2H Sort Order', 'sportspress-advanced-h2h' ); ?></strong></p>
			<p class="h2h-order-selector">
				<select name="h2h_priority">
					<?php
					$options = array( '0' => esc_attr__( 'Disable', 'sportspress' ) );
					$count   = wp_count_posts( 'sp_column' );
					for ( $i = 1; $i <= $count->publish; $i++ ) :
						$options[ $i ] = $i;
					endfor;
					foreach ( $options as $key => $value ) :
						printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( true, $key == $h2h_priority, false ), esc_html( $value ) );
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
						printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( true, $key == $h2h_order, false ), esc_html( $value ) );
					endforeach;
					?>
				</select>
				<br/>
				<label>
					<input type="checkbox" name="h2h_only" value="1" <?php checked( $h2h_only, 1 ); ?> />
					<?php esc_attr_e( 'H2H Only', 'sportspress-advanced-h2h' ); ?>
				</label>
			</p>
			<?php
		}
	
		/**
		 * Save H2H Priorities and Order rules.
		 */
		public static function save( $post_id, $post ) {
			update_post_meta( $post_id, 'h2h_priority', sp_array_value( $_POST, 'h2h_priority', '' ) );
			update_post_meta( $post_id, 'h2h_order', sp_array_value( $_POST, 'h2h_order', '' ) );
			update_post_meta( $post_id, 'h2h_only', sp_array_value( $_POST, 'h2h_only', null, 'key' ) );
		}
		
		/**
		 * Enqueue needed scripts to the admin site.
		 */
		public function h2h_admin_enqueue_assets( $hook_suffix ) {
			$current_screen = get_current_screen();
			if ( $current_screen && 'sp_column' == $current_screen->id ) {
				wp_enqueue_script( 'h2h-admin', plugin_dir_url( __FILE__ ) . 'assets/js/h2h-admin.js', array(), '1.0.0' );
			}
		}

	}

endif;

new Advanced_H2H_Main_Class();
