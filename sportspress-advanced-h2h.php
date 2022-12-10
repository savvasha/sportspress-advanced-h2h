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

if ( ! class_exists( 'SportsPress_Advanced_H2H' ) ) :

	/**
	 * Main SportsPress Advanced H2H Class
	 *
	 * @class SportsPress_Advanced_H2H
	 */
	class SportsPress_Advanced_H2H {

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
			//add_filter( 'sportspress_meta_boxes', array( $this, 'add_meta_boxes' ) );
			
			//Actions
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 );
			add_action( 'sportspress_process_sp_column_meta', array( $this, 'save' ), 15, 2 );


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
			// load the needed scripts and styles.
			//include SAH2H_PLUGIN_DIR . '/includes/class-SAH2H-scripts.php';
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
		 * Add meta boxes.
		 */
		 
		public function add_meta_boxes() {
		add_meta_box( 'h2h_h2hdiv', __( 'Advanced Head to Head', 'sportspress-advanced-h2h' ), array( $this, 'meta_box' ), 'sp_column', 'side', 'default' );
		}
		
	/**
	 * Output the meta box.
	 */
	public static function meta_box( $post ) {
		$h2h_priority 	= get_post_meta( $post->ID, 'h2h_priority', true );
		$h2h_order     	= get_post_meta( $post->ID, 'h2h_order', true );
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
		</p>
		<?php
	}
	
	/**
	 * Save H2H Priorities and Order rules.
	 */
	public static function save( $post_id, $post ) {
		update_post_meta( $post_id, 'h2h_priority', sp_array_value( $_POST, 'h2h_priority', '' ) );
		update_post_meta( $post_id, 'h2h_order', sp_array_value( $_POST, 'h2h_order', '' ) );
	}

	}

endif;

new SportsPress_Advanced_H2H();
