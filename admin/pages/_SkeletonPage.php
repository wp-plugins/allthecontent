<?php defined( 'ABSPATH' ) OR exit;
/*
 * Skeleton page
 * @autor AllTheContent, Vincent Buzzano
 */
if (!class_exists('SkeletonPage')) { class SkeletonPage {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array( $this, 'init_menu'));
        add_action('admin_init', array( $this, 'init_page'));
    }

    /**
     * Init menu and options
     */
    public function init_menu() {

        add_submenu_page(
            'atc-admin-page', 
            'AllTheContent Settings', 
            'Settings',
            'manage_options',
            'atc-admin-setting-page',
            array($this, 'create_page')
        );

    }

    /**
     * Init page
     * Register and add settings
     */
    public function init_page() {        
    }

    /**
     * Create page
     */
    public function create_page() {
        $params = array();
        $this->render_page($params);
    }

    /**
     * Render page
     */
    private function render_page($params) {
        extract($params);
        include('templates/settings.php');
    }
}}

?>