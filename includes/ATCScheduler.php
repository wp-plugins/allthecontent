<?php defined( 'ABSPATH' ) OR exit;

/*
 * ATCScheduler
 * Cron / Scheuler Helper
 * @autor AllTheContent, Vincent Buzzano
 */
if (!class_exists('ATCScheduler')) { class ATCScheduler {

    // DB version
    static public $SCHEDULER_IMPORTER = 'atc_import_atcml_contents';

    /**
     * Setup Plugin cron
     */
    static public function install() {
        wp_schedule_event(time(), 'atc_every_fifteen_minutes', self::$SCHEDULER_IMPORTER);
    }

    /**
     * Remove cron
     */
    static public function uninstall() {

        // clear importer
        wp_clear_scheduled_hook( self::$SCHEDULER_IMPORTER );

        // clear option
        delete_option(ATC_IMPORTER_LASTRUN);
    }

    public function __construct() {
        // add scheduler action
        add_action(self::$SCHEDULER_IMPORTER, array($this, 'run_importer'));
    }

    /**
     * RUN Importer
     */
    public function run_importer() {
        require_once("ATCImporterProcess.php");
        $atclImporter = new ATCImporterProcess();
        $atclImporter->run();
    }

}

function cron_add_schedule_recurences($schedules) {
    // add atc schedule recurences for Every Fifteen Minutes
    $schedules['atc_every_fifteen_minutes'] = array(
        'interval' => 900,
        'display' => __('Every Fifteen Minutes')
    );
 	return $schedules;
}

add_filter('cron_schedules', 'cron_add_schedule_recurences');

}
?>