<?php defined( 'ABSPATH' ) OR exit;
/*
 * Caterogy mapping page
 * @autor AllTheContent, Vincent Buzzano
 */
if (!class_exists('CategoryMappingPage')) { class CategoryMappingPage {

    /**
     * page slug
     */
    static public $SLUG = 'atc-admin-catmap-page';

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

    private $logs = array();

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
            __('Category mapping', ATC_TEXT_DOMAIN),
            __('Category mapping', ATC_TEXT_DOMAIN),
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
        add_action('admin_post_atc_save_mapping', array($this, 'on_atc_save_mapping'));
        add_action('admin_post_atc_delete_mapping', array($this, 'on_atc_delete_mapping'));
        add_action('admin_post_atc_reorganize_mapping', array($this, 'on_atc_reorganize_mapping'));
    }

    function on_atc_reorganize_mapping() {
        $qargs = array(
        	'post_type'   => 'post',
        	'post_status' => 'any',
        	'nopaging'    => true,
        	'meta_query'  => array(
                'relation' => 'AND', // Optional, defaults to "AND", or set it to "OR"
                array(
                    'key'     => 'atc_themes',
                    //'value'   => $content['refid'],
                    'compare' => 'EXISTS'
                )
        	),
        );
        $query = new WP_Query($qargs);
        while($query->have_posts()) {
            $post = $query->next_post();
            $themes = get_post_meta($post->ID, 'atc_themes', true);
            $categories = ATCDB::getWPCategoryIdsFor(explode('|', $themes));
            wp_update_post(array(
                'ID'            => $post->ID,
                'post_category'	=> $categories,
            ));
        }
        wp_redirect(admin_url('admin.php?page=' . self::$SLUG . '&m='. 40));
    }

    function on_atc_save_mapping() {
        global $wpdb;
        $id = isset($_POST['id']) ? esc_sql($_POST['id']) : 0;
        $errcode = 0;

        if (intval($id) > 0) {
            $wp_category_id = esc_sql($_POST['wp_cat_id']);

            // update
            if (empty($wp_category_id) || intval($wp_category_id) < 1) {
                $errcode = 31;
            } else {
                $sql = "UPDATE " . $wpdb->prefix . ATCDB::$TABLE_CATEGORY_MAPPING_NAME .
                       " SET wp_category_id = " . intval($wp_category_id) .
                       " WHERE id = " . intval($id);
                $res = $wpdb->query($sql);
                if ($res > 0)
                    $errcode = 30;
                else
                    $errcode = 31;
            }
        } else {
            $atc_theme_code = esc_sql($_POST['atc_theme_code']);
            $wp_category_id = esc_sql($_POST['wp_cat_id']);

            // create
            if (empty($atc_theme_code) || empty($wp_category_id)) {
                $errcode = 11;
            } else {
                $sql = "INSERT INTO " . $wpdb->prefix . ATCDB::$TABLE_CATEGORY_MAPPING_NAME .
                       "(atc_theme_code, atc_theme_name, wp_category_id) " .
                       "values ('" . $atc_theme_code . "', '" . $atc_theme_code . "', " . $wp_category_id . ");";
                $res = $wpdb->query($sql);

                if ($res)
                    $errcode = 10;
                else
                    $errcode = 11;
            }
        }

        wp_redirect(admin_url('admin.php?page=' . self::$SLUG . '&m='. $errcode));
        exit;
    }

    function on_atc_delete_mapping() {
        global $wpdb;
        $id = intval(esc_sql($_REQUEST['id']));
        if (isset($id) && $id > 0) {
            $sql = "DELETE FROM " . $wpdb->prefix . ATCDB::$TABLE_CATEGORY_MAPPING_NAME .
                   " WHERE id = " . $id;
            $res = $wpdb->query($sql);
            if ($res)
                $errcode = 20;
            else
                $errcode = 21;
        }
        wp_redirect(admin_url('admin.php?page=' . self::$SLUG . '&m='. $errcode));
        exit;
    }

    /**
     * Create page
     */
    public function create_page() {
        $params = array();

        if (isset($_GET["m"])) $m = intval($_GET["m"]);
        else $m = 0;
        switch($m) {
            case 10:
                $params['msg_flash'] = "<div class='updated fade'><p><strong>You have successfully created a new category mapping.</strong></p></div>";
                break;

            case 11:
                $params['msg_flash'] = "<div class='error fade'><p><strong>An error occured while creating a new category mapping.</strong></p></div>";
                break;

            case 20:
                $params['msg_flash'] = "<div class='updated fade'><p><strong>You have successfully removed category mapping.</strong></p></div>";
                break;

            case 21:
                $params['msg_flash'] = "<div class='error fade'><p><strong>An error occured while removing category mapping.</strong></p></div>";
                break;

            case 30:
                $params['msg_flash'] = "<div class='updated fade'><p><strong>You have successfully updated category mapping.</strong></p></div>";
                break;

            case 31:
                $params['msg_flash'] = "<div class='error fade'><p><strong>An error occured while updating category mapping.</strong></p></div>";
                break;

            case 40:
                $params['msg_flash'] = "<div class='updated fade'><p><strong>Reorganization of posts is a success.</strong></p></div>";
                break;

            default:
                $params['msg_flash'] = null;

        }


        if (isset($_GET["action"])) $action = $_GET["action"];
        else $action = "";
        switch($action) {
            case 'addcatmap':
                //$this->action_addcatmap();
                $this->render_page('category-mapping-addcatmap', $params);
                break;

            case 'editcatmap':
                $id = $_REQUEST['id'];
                $catmap = ATCDB::findCategoryMappingById($id);
                if (isset($catmap)) {
                    $params['catmap'] = $catmap;
                    $this->render_page('category-mapping-editcatmap', $params);
                    break;
                }

            default:

                $listTable = new ATCMappCatListTable();
                $listTable->prepare_items();
                $params['listTable'] = $listTable;

                $this->render_page('category-mapping', $params);
        }

    }

    /**
     * Render page
     */
    private function render_page($tpl, $params) {
        extract($params);
        include(dirname(__FILE__) . '/templates/' . $tpl . '.php');
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
}

/**
 * Check that 'class-wp-list-table.php' is available
 */
if(!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

if(!class_exists('ATCMappCatListTable')) { class ATCMappCatListTable extends WP_List_Table {

   /**
    * Constructor, we override the parent to pass our own arguments
    * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
    */
    function __construct() {
        parent::__construct( array(
            'singular'=> 'atc-mapping', //Singular label
            'plural'  => 'atc-mappings', //plural label, also this well be one of the table css class
            'ajax'    => false //We won't support Ajax for this table
        ));
    }

    /**
     * Define the columns that are going to be used in the table
     * @return array $columns, the array of columns to use with the table
     */
    function get_columns() {
        return $columns = array(
            'id'         => __('id', ATC_TEXT_DOMAIN),
            'theme'      => __('AllTheContent Themes', ATC_TEXT_DOMAIN),
            'category'   => __('Wordpress Post Categories'),
            'action'   => __('Actions')
        );
    }

    /**
     * Decide which columns to activate the sorting functionality on
     * @return array $sortable, the array of columns that can be sorted by the user
     */
    public function get_sortable_columns() {
       return $sortable = array(
            'theme'     => array('atc_theme_name', true),
            'category'  => array('wp_category_id', true)
       );
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     */
    function prepare_items() {
        global $wpdb, $_wp_column_headers;
        $screen = get_current_screen();

        /* -- Preparing your query -- */
        $query = "SELECT *  FROM " . $wpdb->prefix . ATCDB::$TABLE_CATEGORY_MAPPING_NAME;

        /* -- Ordering parameters -- */
        //Parameters that are going to be used to order the result
        $orderby = !empty($_GET["orderby"]) ? esc_sql($_GET["orderby"]) : '';
        $order = !empty($_GET["order"]) ? esc_sql($_GET["order"]) : 'ASC';

        if(!empty($orderby))
            $query.=' ORDER BY '.$orderby. ' '.$order;

        /* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems = $wpdb->query($query); //return the total number of affected rows

        //How many to display per page?
        $perpage = 15;

        //Which page is this?
        $paged = !empty($_GET["paged"]) ? esc_sql($_GET["paged"]) : '';

        //Page Number
        if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }

        //How many pages do we have in total?
        $totalpages = ceil($totalitems/$perpage);

            //adjust the query to take pagination into account
        if(!empty($paged) && !empty($perpage)){
            $offset=($paged-1)*$perpage;
            $query.=' LIMIT '.(int)$offset.','.(int)$perpage;
        }

       /* -- Register the pagination -- */
        $this->set_pagination_args( array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ));
        //The pagination links are automatically built according to those parameters

        /* -- Register the Columns -- */
        $columns = $this->get_columns();
        $hidden = array('id');
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        /* -- Fetch the items -- */
        $this->items = $wpdb->get_results($query);
    }

    function column_id($item){
        return $item->id;
    }

    function column_theme($item){
        return $item->atc_theme_name;
    }

    function column_action($item){
        return "<a href='" . admin_url('admin.php?page=' . CategoryMappingPage::$SLUG . '&action=editcatmap&id=' . $item->id) . "' class='button-primary'>modify</a> " .
               "<a href='" . admin_url('admin-post.php?action=atc_delete_mapping&id=' . $item->id) . "' class='button-secondary'>delete</a>";
    }

    function column_category($item){
        return get_cat_name($item->wp_category_id);
    }
}}

}
?>