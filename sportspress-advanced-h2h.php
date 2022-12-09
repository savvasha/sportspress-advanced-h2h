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

	}

endif;

new SportsPress_Advanced_H2H();
