<?php
/**
 * Plugin Name: Disciple Tools - Network Dashboard Remote
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools-network-dashboard-remote
 * Description: Disciple Tools - Network Dashboard Remote is an extension for non-disciple.tools wordpress sites for submitting activity to the network dashboard
 * Version:  1.1
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools-network-dashboard-remote
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.4
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Gets the instance of the `DT_ND_Remote` class.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function dt_nd_remote() {
    $wp_theme = wp_get_theme();

    $is_theme_dt = strpos( $wp_theme->get_template(), "disciple-tools-theme" ) !== false || $wp_theme->name === "Disciple Tools";
    if ( ! $is_theme_dt ) {
        return DT_ND_Remote::get_instance();
    }
    return false;
}
add_action( 'after_setup_theme', 'dt_nd_remote' );

/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class DT_ND_Remote {

    /**
     * Declares public variables
     *
     * @since  0.1
     * @access public
     * @return object
     */
    public $token;
    public $version;
    public $dir_path = '';
    public $dir_uri = '';
    public $img_uri = '';
    public $includes_path;

    /**
     * Returns the instance.
     *
     * @since  0.1
     * @access public
     * @return object
     */
    public static function get_instance() {

        static $instance = null;

        if ( is_null( $instance ) ) {
            $instance = new dt_nd_remote();
            $instance->setup();
            $instance->includes();
            $instance->setup_actions();
        }
        return $instance;
    }

    /**
     * Constructor method.
     *
     * @since  0.1
     * @access private
     * @return void
     */
    private function __construct() {
    }

    /**
     * Loads files needed by the plugin.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function includes() {
        require_once( 'includes/site-link-post-type.php' );
        require_once( 'includes/site-link-customization.php' );
        require_once( 'includes/log.php' );
        require_once( 'includes/rest-api.php' );

        require_once( 'includes/header-script.php' );
        require_once( 'includes/shortcode.php' );
        require_once( 'includes/site-profile.php' );

    }

    /**
     * Sets up globals.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function setup() {

        // Admin and settings variables
        $this->token             = 'dt_nd_remote';
        $this->version             = '1.1';

    }

    /**
     * Sets up main plugin actions and filters.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function setup_actions() {

        if ( is_admin() ){
            // Check for plugin updates
            if ( ! class_exists( 'Puc_v4_Factory' ) ) {
                require( 'includes/admin/plugin-update-checker/plugin-update-checker.php' );
            }

            $hosted_json = "https://raw.githubusercontent.com/DiscipleTools/disciple-tools-network-dashboard-remote/master/includes/admin/version-control.json";
            Puc_v4_Factory::buildUpdateChecker(
                $hosted_json,
                __FILE__,
                'disciple-tools-network-dashboard-remote'
            );
        }

        // Internationalize the text strings used.
        add_action( 'init', array( $this, 'i18n' ), 2 );

        if ( is_admin() ) {
            // adds links to the plugin description area in the plugin admin list.
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 );
        }
    }

    /**
     * Filters the array of row meta for each/specific plugin in the Plugins list table.
     * Appends additional links below each/specific plugin on the plugins page.
     *
     * @access  public
     * @param   array       $links_array            An array of the plugin's metadata
     * @param   string      $plugin_file_name       Path to the plugin file
     * @param   array       $plugin_data            An array of plugin data
     * @param   string      $status                 Status of the plugin
     * @return  array       $links_array
     */
    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
            // You can still use `array_unshift()` to add links at the beginning.

            $links_array[] = '<a href="https://disciple.tools">Disciple.Tools Community</a>';
            $links_array[] = '<a href="https://github.com/DiscipleTools/disciple-tools-network-dashboard">Network Dashboard Plugin</a>';
            $links_array[] = '<a href="https://github.com/DiscipleTools/disciple-tools-network-dashboard-remote">Github Project</a>';

            // add other links here
        }

        return $links_array;
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {

        // Confirm 'Administrator' has 'manage_dt' privilege. This is key in 'remote' configuration when
        // Disciple Tools theme is not installed, otherwise this will already have been installed by the Disciple Tools Theme
        $role = get_role( 'administrator' );
        if ( !empty( $role ) ) {
            $role->add_cap( 'manage_dt' ); // gives access to dt plugin options
        }

    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function deactivation() {
        delete_option( 'dismissed-dt-nd-remote' );
    }

    /**
     * Loads the translation files.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function i18n() {
        load_plugin_textdomain( 'dt_nd_remote', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ). 'languages' );
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return 'dt_nd_remote';
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        _doing_it_wrong( "dt_nd_remote::" . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}
// end main plugin class

// Register activation hook.
register_activation_hook( __FILE__, [ 'DT_ND_Remote', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'DT_ND_Remote', 'deactivation' ] );
