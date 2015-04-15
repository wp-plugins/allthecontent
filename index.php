<?php defined( 'ABSPATH' ) OR exit;

/**
 * Plugin Name: AllTheContent
 * Plugin URI: http://www.allthecontent.com/
 * Description: AllTheContent for wordpress is an importer for ATCML format. Get all your content imported into your Wordpress automatically.
 * Version: 0.1.1
 * Author: AllTheContent, Vincent Buzzano
 * Author URI: http://www.allthecontent.com/
 * License: GPL2
*/

define("ATC_OPTIONS", 'atc_options');
define("ATC_IMPORTER_LASTRUN", 'atc_importer_last_run');
define("ATC_TEXT_DOMAIN", 'AllTheContent');

function getATCImgMenuLogo() {
    return plugin_dir_url(__FILE__) . '/images/logo-atc-wp-white.png';
}

function getATCImgPageLogo() {
    return plugin_dir_url(__FILE__) . '/images/logo-atc-wp.png';
}

require_once(dirname(__FILE__) . '/includes/ATCDB.php');
require_once(dirname(__FILE__) . '/includes/ATCScheduler.php');

/*
 * AllTheContentPlugin
 * Plugins Setup
 * @autor AllTheContent, Vincent Buzzano
 */

if (!class_exists('ATCPlugin')) { class ATCPlugin {

    // available options within the plugin
    static $OPTIONS = array(
        ATC_DB_VERSION,
        ATC_OPTIONS,
        ATC_IMPORTER_LASTRUN,
        'atc-importer-processing'
    );

    private $scheduler = null;

    /**
     * Constructor
     */
    public function __construct() {

        // register activation hook
        register_activation_hook
            (__FILE__, array($this, 'on_activation'));

        // register deactivation hook
        register_deactivation_hook
            (__FILE__, array($this, 'on_deactivation'));

        // register uninstall
        register_uninstall_hook(__FILE__, 'allthecontent_uninstall');

        // init
        //add_action('plugins_loaded', array($this, 'init'));
        $this->init();
    }

    /*
     * init plugin
     */
    public function init() {

        // init scheduler
        $this->scheduler = new ATCScheduler();

        if(is_admin()) {

            // enqueu scripts action
            add_action('admin_enqueue_scripts', array($this, 'on_admin_enqueue_scripts'));

            // register admin plugin
            require_once(dirname( __FILE__ ) . '/admin/ATCAdmin.php');
            new ATCAdmin();
        }
    }

    /**
     * On activation
     */
    public function on_activation() {

        // install database
        ATCDB::install();

        // uninstall cron
        ATCScheduler::install();
    }

    /**
     * Deactivation
     */
    public function on_deactivation() {

        // uninstall cron
        ATCScheduler::uninstall();

        //ATCDB::uninstall();

    }

    /**
     * append some style and scripts
     */
    public function on_admin_enqueue_scripts() {
    	$style = plugin_dir_url(__FILE__) .'css/style.css';
    	wp_enqueue_style('allthecontent-css', $style);
    }
}

/**
 * Uninstall plugin
 */
function allthecontent_uninstall() {

    // uninstall cron
    ATCScheduler::uninstall();

    // uninstall database
    ATCDB::uninstall();

    // remove all options
    foreach (ATCPlugin::$OPTIONS as $option)
        delete_option($option);
}


new ATCPlugin();

}

?>
