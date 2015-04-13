<?php defined( 'ABSPATH' ) OR exit;

define("ATC_DB_VERSION", 'atc_db_version');

/*
 * ATCDB
 * Database Helper
 * @autor AllTheContent, Vincent Buzzano
 */
if (!class_exists('ATCDB')) { class ATCDB {

    // DB version
    public static $DB_VERSION = 1;

    // table names
    public static $TABLE_IMPORTATION_LOG_NAME = "atc_log_importation";
    public static $TABLE_ERROR_LOG_NAME = "atc_log_error";
    public static $TABLE_CATEGORY_MAPPING_NAME = "atc_category_mapping";

    /**
     * Setup Plugin Database related tables and options
     */
    static public function install() {
        global $wpdb;
        //delete_option(ATC_DB_VERSION);

        $db_installed_version = get_option(ATC_DB_VERSION);
        if ($db_installed_version != self::$DB_VERSION ) {
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

            // Create / Update Importation log
            $table = $wpdb->prefix . self::$TABLE_IMPORTATION_LOG_NAME;
            $sql = "CREATE TABLE $table (
                id               BIGINT NOT NULL AUTO_INCREMENT,
                post_id          BIGINT NOT NULL,
                created          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                dy_id            VARCHAR(24),
                dy_created       TIMESTAMP NULL,
                dy_delivered     TIMESTAMP NULL,
                c_uid            VARCHAR(64) NULL,
                c_refid          VARCHAR(255) NULL,
                c_version        INT DEFAULT 1,
                c_title          VARCHAR(500),
                PRIMARY KEY (id)
            );";
            dbDelta($sql);

            // Create / Update Error log
            $table = $wpdb->prefix . self::$TABLE_ERROR_LOG_NAME;
            $sql = "CREATE TABLE $table (
                id               BIGINT NOT NULL AUTO_INCREMENT,
                created          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atcml_file       VARCHAR(255),
                err_msg          TEXT NULL,
                err_trace        TEXT NULL,
                PRIMARY KEY (id)
            );";
            dbDelta($sql);

            // Create / Update Category Mapping
            $table = $wpdb->prefix . self::$TABLE_CATEGORY_MAPPING_NAME;
            $sql = "CREATE TABLE $table(
                        id              BIGINT NOT NULL AUTO_INCREMENT,
                        atc_theme_code  VARCHAR(32),
                        atc_theme_name  VARCHAR(100),
                        wp_category_id  BIGINT(20),
                        PRIMARY KEY (id)
                    );";
            dbDelta($sql);

            // update db version option
            update_option(ATC_DB_VERSION, self::$DB_VERSION);
        }
    }

    /**
     * Remove Plugins Database related options and tables
     */
    static public function uninstall() {
        global $wpdb;

        // remove options
        delete_option(ATC_DB_VERSION);

        // drop tables
        $table = $wpdb->prefix . self::$TABLE_IMPORTATION_LOG_NAME;
        $sql   = "DROP TABLE IF EXISTS $table";
        $wpdb->query($sql);

        $table = $wpdb->prefix . self::$TABLE_ERROR_LOG_NAME;
        $sql   = "DROP TABLE IF EXISTS $table";
        $wpdb->query($sql);

        $table = $wpdb->prefix . self::$TABLE_CATEGORY_MAPPING_NAME;
        $sql   = "DROP TABLE IF EXISTS $table";
        $wpdb->query($sql);
    }

    /**
     * Clear importation log
     */
    static public function clearImportationLog() {
        global $wpdb;
        $sql = "DELETE FROM " . $wpdb->prefix . self::$TABLE_IMPORTATION_LOG_NAME;
        $wpdb->query($sql);
    }

    /**
     * Clear error log
     */
    static public function clearErrorLog() {
        global $wpdb;
        $sql = "DELETE FROM " . $wpdb->prefix . self::$TABLE_ERROR_LOG_NAME;
        $wpdb->query($sql);
    }

    static public function getWPCategoryIdsFor(array $themes) {
        global $wpdb;
        $def_cat_id = 1;
        $inthemes = null;
        foreach ($themes as $theme) {
            if (strlen($inthemes) > 0)
                $inthemes = $inthemes . ',';
            $inthemes = $inthemes . "'" . esc_sql($theme) . "'";
        }

        $ids = array();
        if (count($inthemes) > 0) {
            $sql = "SELECT wp_category_id FROM " . $wpdb->prefix . self::$TABLE_CATEGORY_MAPPING_NAME .
                   " WHERE atc_theme_code in (" . $inthemes . ");";

            $ret = $wpdb->get_results($sql);

            $ids = array();
            foreach($ret as $e) {
                $ids[] = intval($e->wp_category_id);
            }
        }

        if (count($ids) == 0)
            $ids[] =  $def_cat_id;

        return $ids;
    }

    static public function findCategoryMappingById($id) {
        global $wpdb;
        $sql = "SELECT * FROM " . $wpdb->prefix . self::$TABLE_CATEGORY_MAPPING_NAME .
               " WHERE id = " . intval($id);
        $ret = $wpdb->get_results($sql);
        $ids = array();
        if (count($ret) > 0)
            return $ret[0];
        else
            return null;
    }

    /**
     * Create an importation log
     * @param array $content
     */
    static public function createImportationLog($content) {
        global $wpdb;

        $sql = "INSERT INTO " . $wpdb->prefix . self::$TABLE_IMPORTATION_LOG_NAME .
             "(post_id, created, dy_id, dy_created, dy_delivered, c_uid, c_refid, c_version, c_title) " .
             "values (" .
             $content['_postId'] .", " .
             "'" . current_time( 'mysql' ) . "', " .
             "'" . esc_sql($content['_deliveryId']) . "', " .
             "'" . $content['_atcmlCreated']->format("Y-m-d H:i:s") . "', " .
             "'" . $content['_deliveryDate']->format("Y-m-d H:i:s") . "', " .
             "'" . esc_sql($content['uid']) . "', " .
             "'" . esc_sql($content['refid']) . "', " .
             intval($content['version']) . "," .
             "'" . esc_sql($content['title']) . "'" .
             ")";
        $wpdb->query($sql);
    }


    static public function createErrorLog($file, $message, $exception) {
        global $wpdb;
        $trace = 'NULL';
        if (is_object($exception))
            $trace = "'" . esc_sql($exception->getTraceAsString()) . "'";
        $sql = "INSERT INTO " . $wpdb->prefix . self::$TABLE_ERROR_LOG_NAME .
             "(created, atcml_file, err_msg, err_trace) " .
             "values (" .
               "'" . current_time( 'mysql' ) . "', " .
               "'" . esc_sql($file) . "', " .
               "'" . esc_sql($message) . "', " .
               $trace .
            ")";
        $wpdb->query($sql);
    }


    static public function create_default_mapping_for_themes($themes) {
        if (!is_array($themes)) return;
        foreach($themes as $theme) {
            self::create_default_mapping_for_theme($theme);
        }
    }

    static public function create_default_mapping_for_theme($theme) {
        global $wpdb;

        $sql = "SELECT * FROM " . $wpdb->prefix . self::$TABLE_CATEGORY_MAPPING_NAME .
                   " WHERE atc_theme_code = '" . esc_sql($theme) . "';";
        $ret = $wpdb->get_results($sql);
        if (count($ret) == 0) {
            // create default mapping
            $sql = "INSERT INTO " . $wpdb->prefix . self::$TABLE_CATEGORY_MAPPING_NAME .
                   " (atc_theme_code, atc_theme_name, wp_category_id) " .
                   " values ('" . esc_sql($theme) . "', '" . esc_sql($theme) . "', " . 1 . ");";
            $wpdb->query($sql);
        }

    }
}}
?>