<?php defined( 'ABSPATH' ) OR exit;
/*
 * Importation Log page
 * @autor AllTheContent, Vincent Buzzano
 */
if (!class_exists('ErrorLogPage')) { class ErrorLogPage {

    /**
     * page slug
     */
    static public $SLUG = 'atc-admin-error-log-page';

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
            __('Error log', ATC_TEXT_DOMAIN),
            __('Error log', ATC_TEXT_DOMAIN),
            'manage_options',
            self::$SLUG, //'atc-admin-importation-log-page',
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

        $listTable = new ATCLogErrorListTable();
        $listTable->prepare_items();
        $params['listTable'] = $listTable;

        $params['lastImportationRun'] = get_option(ATC_IMPORTER_LASTRUN);

        $this->render_page($params);
    }

    /**
     * Render page
     */
    private function render_page($params) {
        extract($params);
        include(dirname(__FILE__) . '/templates/error-log.php');
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

if(!class_exists('ATCLogErrorListTable')) { class ATCLogErrorListTable extends WP_List_Table {

   /**
    * Constructor, we override the parent to pass our own arguments
    * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
    */
    function __construct() {
        parent::__construct( array(
            'singular'=> 'atc-log-error', //Singular label
            'plural'  => 'atc-log-errors', //plural label, also this well be one of the table css class
            'ajax'    => false //We won't support Ajax for this table
        ));
    }

    /**
     * Define the columns that are going to be used in the table
     * @return array $columns, the array of columns to use with the table
     */
    function get_columns() {
        return $columns = array(
            'id'        => __('id', ATC_TEXT_DOMAIN),
            'message'   => __('Message', ATC_TEXT_DOMAIN),
//            'delivery'  => __('Delivery file', ATC_TEXT_DOMAIN),
//            'created'   => __('Imported', ATC_TEXT_DOMAIN)
        );
    }

    /**
     * Decide which columns to activate the sorting functionality on
     * @return array $sortable, the array of columns that can be sorted by the user
     */
    public function get_sortable_columns() {
       return $sortable = array(
//            'message'   => array('created', true),
//            'message'   => array('err_msg', true),
//            'delivery'  => array('dy_id', true),
//            'created'   => array('created', true)
       );
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     */
    function prepare_items() {
        global $wpdb, $_wp_column_headers;
        $screen = get_current_screen();

        /* -- Preparing your query -- */
        $query = "SELECT *  FROM " . $wpdb->prefix . ATCDB::$TABLE_ERROR_LOG_NAME;

        /* -- Ordering parameters -- */
        //Parameters that are going to be used to order the result
        $orderby = !empty($_GET["orderby"]) ? esc_sql($_GET["orderby"]) : '';
        $order = !empty($_GET["order"]) ? esc_sql($_GET["order"]) : 'ASC';

        //if(!empty($orderby))
        //    $query.=' ORDER BY '.$orderby. ' '.$order;
        $query.=' ORDER BY id desc ';

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

    function column_delivery($item){
        return $item->atcml_file;
    }

    function column_created($item){
        return $item->created;
    }

    function column_message($item){
        $msg = "<h3>" . date_i18n(get_option('date_format') . " " . get_option('time_format'), strtotime($item->created)) . "</h3>" .
        "<strong>Error importing file " . $item->atcml_file . "</strong>" .
        "<pre>" . $item->err_msg ."</pre>" . "<hr/><small><pre>" . $item->err_trace . "</pre></small>";
        return $msg;
    }
}}

}
?>