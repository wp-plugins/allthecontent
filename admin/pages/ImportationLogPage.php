<?php defined( 'ABSPATH' ) OR exit;
/*
 * Importation Log page
 * @autor AllTheContent, Vincent Buzzano
 */
if (!class_exists('ImportationLogPage')) { class ImportationLogPage {

    /**
     * page slug
     */
    //'atc-admin-importation-log-page'
    static public $SLUG = 'atc-admin-page';

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
            __('Importation log', ATC_TEXT_DOMAIN),
            __('Importation log', ATC_TEXT_DOMAIN),
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

        $listTable = new ATCLogImportationListTable();
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
        include(dirname(__FILE__) . '/templates/importation-log.php');
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

if(!class_exists('ATCLogImportationListTable')) { class ATCLogImportationListTable extends WP_List_Table {

   /**
    * Constructor, we override the parent to pass our own arguments
    * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
    */
    function __construct() {
        parent::__construct( array(
            'singular'=> 'atc-imp-log', //Singular label
            'plural'  => 'atc-imp-logs', //plural label, also this well be one of the table css class
            'ajax'    => false //We won't support Ajax for this table
        ));
    }

    /**
     * Define the columns that are going to be used in the table
     * @return array $columns, the array of columns to use with the table
     */
    function get_columns() {
        return $columns = array(
            'id'            => __('id', ATC_TEXT_DOMAIN),
            'title'         => __('Title', ATC_TEXT_DOMAIN),
            'version'       => __('Version', ATC_TEXT_DOMAIN),
            'delivery'      => __('Delivery #', ATC_TEXT_DOMAIN),
            'delivered'     => __('Delivered', ATC_TEXT_DOMAIN),
            'created'       => __('Imported', ATC_TEXT_DOMAIN)
        );
    }

    /**
     * Decide which columns to activate the sorting functionality on
     * @return array $sortable, the array of columns that can be sorted by the user
     */
    public function get_sortable_columns() {
       return $sortable = array(
            'title'     => array('c_title', true),
            'version'   => array('c_version', true),
            'delivery'  => array('dy_id', true),
            'delivered' => array('dy_delivered', true),
            'created'   => array('created', true)


       );
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     */
    function prepare_items() {
        global $wpdb, $_wp_column_headers;
        $screen = get_current_screen();

        /* -- Preparing your query -- */
        $query = "SELECT *  FROM " . $wpdb->prefix . ATCDB::$TABLE_IMPORTATION_LOG_NAME;

        /* -- Ordering parameters -- */
        //Parameters that are going to be used to order the result
        $orderby = !empty($_GET["orderby"]) ? esc_sql($_GET["orderby"]) : '';
        $order = !empty($_GET["order"]) ? esc_sql($_GET["order"]) : 'ASC';

        if(!empty($orderby))
            $query.=' ORDER BY '.$orderby. ' '.$order;
        else
            $query.=' ORDER BY created DESC ';

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
        return $item->dy_id;
    }

    function column_delivery($item){
        return $item->dy_id;
    }

    function column_delivered($item){
        return date_i18n(get_option('date_format') . " " . get_option('time_format'), strtotime($item->dy_delivered), true);
    }

    function column_created($item){
        return date_i18n(get_option('date_format') . " " . get_option('time_format'), strtotime($item->created));
    }

    function column_title($item){
        return "<a title='View post' href='" . site_url() . "?p=" . $item->post_id . "' target='_blank'>" . $item->c_title . "</a>";
    }

    function column_version($item){
        return $item->c_version;
    }

}}

}
?>