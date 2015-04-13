<?php defined( 'ABSPATH' ) OR exit;

/*
 * ATCAdmin
 * Admin Plugin
 * @autor AllTheContent, Vincent Buzzano
 */
if (!class_exists('ATCAdmin')) { class ATCAdmin {

    static public $SLUG = 'atc-admin-page';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'init_admin'));
        add_action('admin_menu', array($this, 'init_menu'));

        $this->load_page('ImportationLog');
        $this->load_page('ErrorLog');
        $this->load_page('Tools');
        $this->load_page('CategoryMapping');
        $this->load_page('Settings');

        $this->load_meta_box('ContentLicense');
    }


    /**
     * Setup Wordpress Menu
     */
    public function init_menu() {

        add_menu_page(
            __('AllTheContent Plugin', ATC_TEXT_DOMAIN),
            __('AllTheContent', ATC_TEXT_DOMAIN),
            'manage_options',
            self::$SLUG,
            null,//array(new AdminPage(), 'display'),
            'dashicons-allthecontent', //getATCImgMenuLogo(),
            25 //6 //after Post
        );
    }

    /**
     * Setup Admin
     */
    public function init_admin() {
    }

    public function load_page($pagename) {
        require_once(dirname( __FILE__ ) . '/pages/' . $pagename . 'Page.php');
        $cname = $pagename . 'Page';
        new $cname(self::$SLUG);
    }

    public function load_meta_box($boxname) {
        require_once(dirname( __FILE__ ) . '/metaboxes/' . $boxname . 'MetaBox.php');
        $cname = $boxname . 'MetaBox';
        new $cname;
    }

    /**
     * A EFFACER
     */
    public function settingsPage(){
            global $wpdb;
            require( __DIR__.'/../atc-settings.php' );
    }

}}
?>