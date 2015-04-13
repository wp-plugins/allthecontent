<?php defined( 'ABSPATH' ) OR exit;
/*
 * Settings page
 * @autor AllTheContent, Vincent Buzzano
 */
if (!class_exists('ToolsPage')) { class ToolsPage {

    /**
     * page slug
     */
    static public $SLUG = 'atc-admin-tools-page';

    /**
     * parent page slug
     */
    public $parentSlug = null;

    /**
     * Admin notice message
     */
    private $success_notices = array();

    /**
     * Admin notice message
     */
    private $error_notices = array();

    /**
     * Start up
     */
    public function __construct($parent_slug) {
        $this->parentSlug = $parent_slug;

        add_action('admin_menu', array( $this, 'init_menu'));
        add_action('admin_init', array( $this, 'init_page'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }

    /**
     * Init menu
     */
    public function init_menu() {

        add_submenu_page(
            $this->parentSlug,
            __('Tools', ATC_TEXT_DOMAIN),
            __('Tools', ATC_TEXT_DOMAIN),
            'manage_options',
            self::$SLUG,
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


        $action = isset($_GET['action']) ? $_GET['action'] : null;

        switch($action) {
            case 'importnow':
                // run importer
                $scheduler = new ATCScheduler();
                $scheduler->run_importer();
                $params['msg_flash'] = "<div class='updated'><p>The import was successful!</p></div>";
                break;

            case 'clearimplog':
                // clear log
                ATCDB::clearImportationLog();
                $params['msg_flash'] = "<div class='updated'><p>Importation log has been cleared!</p></div>";
                break;

            case 'clearerrlog':
                // clear log
                ATCDB::clearErrorLog();
                $this->success_notices[] = 'Error log has been cleared!';
                $params['msg_flash'] = "<div class='updated'><p>Error log has been cleared!</p></div>";
                break;

            default:
                $params['msg_flash'] = "";
        }

        $params['options'] = get_option(ATC_OPTIONS);
        $this->render_page($params);
    }

    /**
     * Render page
     */
    private function render_page($params) {
        extract($params);
        include('templates/tools.php');
    }

    /**
     * Display admin notification
     */
    function admin_notices() {
        foreach($this->error_notices as $err)
            if (strlen($err) > 0)
                echo "<div class='error'><p>$err</p></div>";

        foreach($this->success_notices as $err)
            if (strlen($err) > 0)
                echo "<div class='updated'><p>$err</p></div>";
    }

}}
?>