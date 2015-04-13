<?php defined( 'ABSPATH' ) OR exit;
/*
 * Settings page
 * @autor AllTheContent, Vincent Buzzano
 */
if (!class_exists('SettingsPage')) { class SettingsPage {
    /**
     * page slug
     */
    static public $SLUG = 'atc-admin-settings-page';

    /**
     * parent page slug
     */
    public $parentSlug = null;

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options = array();

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
            __('Settings', ATC_TEXT_DOMAIN),
            __('Settings', ATC_TEXT_DOMAIN),
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

        // load optoins
        $this->options = get_option(ATC_OPTIONS);

        // check options, valid and alert
        $this->optionsCheckAndAlert();

        register_setting(
            'atc-admin-settings-group', // Option group
            ATC_OPTIONS, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'atc-admin-settings-importation-section', // ID
            __('ATCML Importation', ATC_TEXT_DOMAIN), // Title
            array( $this, 'print_importation_section_info' ), // Callback
            self::$SLUG // Page
        );

        add_settings_section(
            'atc-admin-settings-error-section', // ID
            __('Error handling', ATC_TEXT_DOMAIN), // Title
            array( $this, 'print_error_section_info' ), // Callback
            self::$SLUG // Page
        );

        add_settings_section(
            'atc-admin-settings-publication_section', // ID
            __('Publication', ATC_TEXT_DOMAIN), // Title
            array( $this, 'print_publication_section_info' ), // Callback
            self::$SLUG // Page
        );

        add_settings_field(
            'import_path', // ID
            __('Import directory path', ATC_TEXT_DOMAIN), // Title
            array( $this, 'import_path_callback' ), // Callback
            self::$SLUG, // Page
            'atc-admin-settings-importation-section' // Section
        );

        add_settings_field(
            'error_email',
            __('A valid email address', ATC_TEXT_DOMAIN),
            array( $this, 'error_email_callback' ),
            self::$SLUG,
            'atc-admin-settings-error-section'
        );

        add_settings_field(
            'publish_auto',
            __('Automatic publication', ATC_TEXT_DOMAIN),
            array( $this, 'publish_auto_callback' ),
            self::$SLUG,
            'atc-admin-settings-publication_section'
        );

    }

    /**
     * Check option and alert admin if there is a problem
     */
    public function optionsCheckAndAlert() {
        $options = $this->options;
        if (!is_array($options) || count($options) == 0) {
            $this->error_notices[] =  sprintf(__(
                "It seems that you have not yet configure the plugin " .
                "AllTheContent. <a href='%s'> click here to do it now</a>."
                , ATC_TEXT_DOMAIN), menu_page_url(self::$SLUG, false)
            );
            return;
        }

        if (!is_dir($options['import_path'])) {
            $this->error_notices[] =  sprintf(__(
                "The import directory '%s' set in the settings " .
                " of the plugin AllTheContent does not exist." .
                "<a href='%s'> click here to do modify it now</a>."
                , ATC_TEXT_DOMAIN)
                , $options['import_path']
                ,menu_page_url(self::$SLUG, false)
            );
        }
    }

    /**
     * Create page
     */
    public function create_page() {
        $params = array();
        $params['options'] = $this->options;
        $this->render_page($params);
    }

    /**
     * Render page
     */
    private function render_page($params) {
        extract($params);
        include('templates/settings.php');
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        $t = sanitize_text_field($input['import_path']);
        if( strlen($t) > 0)
            $new_input['import_path'] = $t;

        $t = sanitize_email($input['error_email']);
        if(strlen($t) > 0)
            $new_input['error_email'] = $t;

        if(isset($input['publish_auto']))
            $new_input['publish_auto'] = sanitize_text_field('true');

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_importation_section_info() {
        printf(__( 'Specify in the field below the path to the import directory. ' .
            'The directory in which you receive ATCML content.<br>' .
            'eg: %s', ATC_TEXT_DOMAIN)
            , dirname(dirname(WP_CONTENT_DIR)) . '/atcml_import'
        );
    }

    /**
     * Print the Section text
     */
    public function print_error_section_info() {
        print(__(
            'To which email address you want to send a notificaton on error.'
            , ATC_TEXT_DOMAIN
        ));
    }

    /**
     * Print the Section text
     */
    public function print_publication_section_info() {
        print(__(
            'Choose here if imported contents are automatically published or not.'
            , ATC_TEXT_DOMAIN
        ));
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function import_path_callback() {
        printf(
            '<input type="text" id="import_path" name="' . ATC_OPTIONS .'[import_path]" value="%s"  class="regular-text" />',
            isset( $this->options['import_path'] ) ? esc_attr( $this->options['import_path']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function error_email_callback() {
        printf(
            '<input type="email" id="error_email" name="' . ATC_OPTIONS .'[error_email]" value="%s" class="regular-text" />',
            isset( $this->options['error_email'] ) ? esc_attr( $this->options['error_email']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function publish_auto_callback() {
        $c = '<input type="checkbox" id="publish_auto" name="' . ATC_OPTIONS .'[publish_auto]" value="true"';
        if (strlen($this->options['publish_auto']) > 0) $c = $c . ' checked ';
        $c = $c . '>';
        print($c);
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