<?php defined( 'ABSPATH' ) OR exit;

require_once(dirname(__FILE__). "/../atcml_toolkit/lib/ATCMLImporter.php");

/*
 * ATCMLImporter
 * Importer
 * @autor AllTheContent, Vincent Buzzano
 */
if (!class_exists('ATCImporterProcess')) { class ATCImporterProcess {

    private $importer;

    public function __construct() {

    }

    private $options = array();
    public $errorCount = 0;
    public $successCount = 0;

    public function error($message) {
        error_log($message);
    }

    public function run() {
        $autoclean = true;
//        delete_option('atc-importer-processing');

        $processing = intval(get_option('atc-importer-processing'));
        if ($processing && $processing > time()) return;

        // get and check options
        $this->options = get_option(ATC_OPTIONS);
        if (empty($this->options['import_path'])) {
            $this->error("Import directory path is not set !");
            return;
        }

        // set as running for 480 secondes
        update_option('atc-importer-processing', time() + 1800);

        // Prepare importation
        $this->importer = new ATCMLImporter();
        $this->importer->addErrorCallback(array($this, 'on_error'));
        $this->importer->addSuccessCallback(array($this, 'on_success'));

        // parse and import
        $this->importer->parseDirectory($this->options['import_path'], $autoclean);

        // update last run
        update_option('atc_importer_last_run', ""+time());

        // set as not running
        delete_option('atc-importer-processing');

        //echo $this->errorCount ." / " . $this->successCount;
    }

    /**
     * Handle importation errors
     */
    public function on_error($file, $message, $exception = null) {

        // insert error log
        ATCDB::createErrorLog($file, $message, $exception);

        // send email alert
        $error_email = $this->options['error_email'];
        if (strlen($error_email) > 0) {
            // headers not used yet
            //$headers[] = 'From: Dr Who <dr@who.net>';

            // compose subject
            $subject = wp_title('', false) . " AllTheContent Error";

            // compose message
            $message = __("An error occurred while importing content.") .
                       "\n\n" . $file . "\n\n" . $message . "\n\n";
            if (is_object($exception))
                $message .= $exception->getTraceAsString() ."\n\n";
            $message .= "--\nAllTheContent WP Plugin\n" . wp_title('', false);

            // send mail
            wp_mail( $error_email, $subject, $message); //, $headers );
        }
        $this->errorCount++;
    }

    /**
     * Handle importation success
     */
    function on_success($content, $file) {
        global $wpdb;

        // check if the content delivery has already been imported
        $exists = $this->find_existing_post_for_delivery($content['_deliveryId']);
        if (isset($exists))
            return;

        ATCDB::create_default_mapping_for_themes($content['themes']);

        // improve content credits
        $content['credits'] = $this->format_content_credits($content);

        // try to find if the post already exists
        $existingPost = $this->find_existing_post($content);
        if (is_null($existingPost)) {
            // create a new post
            $content['_postId'] = $this->create_post($content);
        } else {
            // check if we need to update
            $existingVersion = intval(get_post_meta( $existingPost->ID, 'atc_version', true));
            if ($existingVersion >= intval($content['version']))
                return;

            // update existing post
            $content['_postId'] = $this->update_post($existingPost->ID, $content);
        }

        // insert importation log
        ATCDB::createImportationLog($content);

        // increment success count
        $this->successCount++;
    }

    /**
     * Post content
     *
     * @param $content array
     * @param $importPathAbs
     */
    public function create_post(array $content) {
        global $wpdb;

        $autopublish =
            $this->options['publish_auto'] == 'true' ? true : false;
        $postCategoryIds = ATCDB::getWPCategoryIdsFor($content['themes']);

        // create post array
        $post = array(
            'post_author'   => 1,
            'post_title'    => $content['title'],
            'post_content'  => '', // we set the content later with method (attachMediaAndGeneratePostContent)
            // todo fusionné les keywords avec les indexes
            'tags_input'    => $content['keywords'] ,
            'post_excerpt'  => $content['description'],
            'post_category' => $postCategoryIds,
            'post_status'   => ($autopublish?'publish':'pending')
        );

        // post date
        if (isset($content['_deliveryDate']))
            $post['post_date'] = $content['_deliveryDate']->format('Y-m-d H:i:s');
        else $post['post_date'] = date('Y-m-d H:i:s', time());

        // create the post and get back the id
        $post_id = wp_insert_post($post);

        // post's meta data
        if (isset($content['_deliveryId']))
            add_post_meta( $post_id, 'atc_delivery_id', $content['_deliveryId'], true );

        if (isset($content['refid'])) {
            add_post_meta( $post_id, 'atc_refid', $content['refid'], true );
            if (isset($content['version'])) {
                add_post_meta( $post_id, 'atc_version', $content['version'], true );
            } else {
                add_post_meta( $post_id, 'atc_version', 1, true );
            }
        }
        if (isset($content['lang']))
            add_post_meta( $post_id, 'lang', $content['lang'], true );

        if (isset($content['characters']))
            add_post_meta( $post_id, 'characters_count', $content['characters'], true );
        if (isset($content['words']))
            add_post_meta( $post_id, 'words_count', $content['words'], true );

        if (is_array($content['license'])) {
            // hidden meta data for license useful for the MetaBox class
            foreach( $content['license'] as $type => $value )
                add_post_meta( $post_id, 'atc_license_'.$type, $value, true );
        }

        if (is_array($content['themes'])) {
            $str_themes = '';
            foreach($content['themes'] as $theme)
                $str_themes .= (strlen($str_themes) >0?'|':'') . $theme;
            add_post_meta( $post_id, 'atc_themes', $str_themes, true );
        }

        // create attachment and get image html tag generation
        $captions = $this->create_attachments($post_id, $content);

        // get fullcontent
        $formated = $this->format_content($post_id, $content, $captions);

        wp_update_post(array(
            'ID'           => $post_id,
            'post_content' => $formated
        ));

        return $post_id;
    }

    /**
     * Post content
     *
     * @param $post_id
     * @param $content array
     * @param $importPathAbs
     */
    public function update_post($post_id, array $content) {
        global $wpdb;


        $postCategoryIds = ATCDB::getWPCategoryIdsFor($content['themes']);

        // create post array
        $post = array(
            'ID'            => $post_id,
            'post_author'   => 1,
            'post_title'    => $content['title'],
            'post_content'  => '', // we set the content later
            // todo fusionné les keywords avec les indexes
            'tags_input'    => $content['keywords'] ,
            'post_excerpt'  => $content['description'],
            'post_category' => $postCategoryIds
        );

        if (isset($content['_deliveryId']))
            update_post_meta($post_id, 'atc_delivery_id', $content['_deliveryId']);

        if (isset($content['refid'])) {
            update_post_meta($post_id, 'atc_refid', $content['refid']);
            if (isset($content['version'])) {
                update_post_meta($post_id, 'atc_version', $content['version']);
            }
        }
        if (isset($content['lang']))
            update_post_meta( $post_id, 'lang', $content['lang']);

        if (isset($content['characters']))
            update_post_meta($post_id, 'characters_count', $content['characters']);
        if (isset($content['words']))
            update_post_meta($post_id, 'words_count', $content['words']);

        if (is_array($content['license'])) {
            // hidden meta data for license useful for the MetaBox class
            foreach( $content['license'] as $type => $value )
                update_post_meta($post_id, 'atc_license_'.$type, $value);
        }

        if (is_array($content['themes'])) {
            $str_themes = '';
            foreach($content['themes'] as $theme)
                $str_themes .= (strlen($str_themes) >0?'|':'') . $theme;
            add_post_meta( $post_id, 'atc_themes', $str_themes, true );
        }

        // create attachment and get image html tag generation
        $captions = $this->create_attachments($post_id, $content);

        // get fullcontent
        $formated = $this->format_content($post_id, $content, $captions);

        $post['post_content'] = $formated;
        wp_update_post($post);

        return $post_id;
    }

    /**
     * Create attachment for the post
     * @param int post_id
     * @param array content
     * @return array $captions
     */
    public function create_attachments($post_id, array$content) {
        $atts = $content['attachments'];
        $captions = array();
        if (!is_array($atts) || count($atts) == 0)
            return $captions;

        $counter = 0;
        foreach($atts as $att ){
            $counter++;

            // create attachment meta
            $att_title = $content['title'] . ' - ' . $att['type'] . ' ' . $counter;
            $att_caption = $att['credits'] . " - " . str_replace($att['credits'], '', $att['description']);
            $att_desc = str_replace($att['credits'], '', $att['description']) .
                        "\n\n" . $att['credits'];


            // try to find if the attachment has already been imported
            $existingAtt = $this->find_existing_attachment($att);
            if (isset($existingAtt)) {
                // use existing attachment
                $att_id = $existingAtt->ID;

            } else {
                // create new attachment
                $filename = $att['filename'];
                $attpath = $content['_path'] . '/' . $filename;

                /* create upload path */
                // get upload directory
                $upload_dir = wp_upload_dir();
                $upload_path = $upload_dir['basedir'] . '/allthecontent' . $upload_dir['subdir'];
                // create upload dir
                wp_mkdir_p( $upload_path);
                // create destination path
                $filepath = $upload_path . '/' . strtolower($filename);
                // copy file to upload dir path
                copy($attpath, $filepath);

                // get wp_filetype
                $wp_filetype = wp_check_filetype($filename);

                // create attachment array
                $attachment = array(
                    'post_author'       => 1,
                    'post_name'         => sanitize_text_field($att['uid']),
                    'post_mime_type'    => $wp_filetype['type'],
                    'post_title'        => sanitize_text_field($att_title),
                    'post_excerpt'      => $att_caption,
                    'post_content'      => $att_desc,
                    'post_status'       => 'inherit'
                    );

                $att_id = wp_insert_attachment($attachment, $filepath, $post_id);

                if ($this->importer->isImage($att['mimetype'])) {
                    require_once( ABSPATH . 'wp-admin/includes/image.php' );
                    $att_data = wp_generate_attachment_metadata($att_id, $filepath);
                    wp_update_attachment_metadata($att_id, $att_data );
                }

            }

            // get attachment url
            $att_url = wp_get_attachment_url($att_id);

            if ($counter == 1) {
                // the first one is set as featured image
                set_post_thumbnail($post_id, $att_id);
            }

            $captions [] = array(
                'id'          => $att_id,
                'url'         => $att_url,
                'caption'     => $att_caption,
                'title'       => $att_title,
                'alt'         => $att_alt,
                'description' => $att_desc
            );
        }

        return $captions;
    }

    /**
     * Format content
     * add captions at end
     */
    public function format_content($post_id, array $content, $captions) {
        $formated = '';
        $content_text = null;
        if ($this->importer->isText($content)) {
            $content_text = $this->importer->loadText($content);
        }

        // use template file
        $format_file = $this->get_formater_file();

        if (!is_null($format_file) && file_exists($format_file)) {
            ob_start();
            include($format_file);
            $formated = ob_get_contents();
            ob_end_clean();
            return $formated;
        }

        // default rendering

        // check for reuters content
        if (startsWith($content['refid'], 'tag:reuters')
            || startsWith($content['credits'], '(c) Copyright Thomson Reuters')
            || startsWith($content['credits'], '(c) Copyright Reuters')) {
            // insert reuters logo
            $formated .= "<img src='http://thomsonreuters.com/business-unit/reuters/reuters-brand/images/png/rtr_ahz_rgb_pos.png' width=164 alt='REUTERS'>";
        }

        if (count($captions) > 0) {
            $caption = $captions[0];
            $formated .=
                "[caption id='attachment_" . $caption['id'] . "' " .
                " align='alignleft' width='256' " .
                " caption='" . str_replace("'", "\'", $caption['caption']) . "' ]" .
                "     <img title='" . $caption['title'] ."' " .
                "          src='" . $caption['url'] . "' " .
                "          alt='" . $caption['alt'] . "' width='256' />" .
                "[/caption]";
        }

        if (!is_null($content_text))
            $formated .= $content_text;

        // captions
        if (count($captions > 1)) {
            $formated .= "\n\n<div class='container'>";
            for($i = 1; $i < count($captions); $i++) {
                $caption = $captions[$i];
                $formated .=
                    "[caption id='attachment_" . $caption['id'] . "' " .
                    " align='alignleft' width='256' " .
                    " caption='" . str_replace("'", "\'", $caption['caption']) . "' ]" .
                    "     <img title='" . str_replace("'", "\'", $caption['title']) ."' " .
                    "          src='" . $caption['url'] . "' " .
                    "          alt='" . $caption['alt'] . "' width='256' />" .
                    "[/caption]";
            }
            $formated .= "</div>";
        }

        // links
        if (is_array($content['links']) && count($content['links']) > 0) {
            $formated .= "\n<ul>";
            foreach($content['links'] as $key => $value ) {
                $formated .= '<li><a href="'.$key.'">'.$value.'</a></li>';
            }
            $formated .= "</ul>";
        }

        // add credits
        $formated .= "\n\n" . $content['credits'];

        return $formated;
    }

    public function format_content_credits(array $content) {
        if (empty($content['credits']))
            return '';

        $credits = $content['credits'];

        if (startsWith($credits, "(c) Copyright Thomson Reuters")) {
            $reg_exUrl = "/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
            $url = array();
            $credits = str_replace(' -', '', $credits);
            if(preg_match($reg_exUrl, $credits, $url)) {
                $credits = trim(str_replace($url[0], '', $credits));
                $credits = "<a href=\"" .$url[0] ."\" target=\"_blank\">" . $credits . "</a>";
            }

        } else {
            $sr = "<a href=\"http://www.allthecontent.com\" target=\"_blank\">/AllTheContent</a>";
            $credits = str_replace("/ATCNA", $sr, $credits);
            $credits = str_replace("/ATC", $sr, $credits);
            $credits = str_replace("/AllTheContent", $sr, $credits);
            $credits = str_replace("/AllTheContent News Agency", $sr, $credits);
        }

        return $credits;
    }

    /**
     * Find existing post in wordpress
     */
    public function find_existing_post(array $content) {
        $qargs = array(
            'post_type'   => 'post',
            'post_status' => 'any',
            'meta_query'  => array(
                'relation' => 'AND', // Optional, defaults to "AND", or set it to "OR"
                array(
                    'key'     => 'atc_refid',
                    'value'   => $content['refid'],
                    'compare' => '='
                )
            ),
        );
        $query = new WP_Query($qargs);
        if ($query->have_posts())
            return $query->next_post();
        else return null;
    }

    /**
     * Find existing post in wordpress
     */
    public function find_existing_post_for_delivery($delivery_id) {
        $qargs = array(
            'post_type'   => 'post',
            'post_status' => 'any',
            'meta_query'  => array(
                'relation' => 'AND', // Optional, defaults to "AND", or set it to "OR"
                array(
                    'key'     => 'atc_delivery_id',
                    'value'   => $delivery_id,
                    'compare' => '='
                )
            ),
        );
        $query = new WP_Query($qargs);
        if ($query->have_posts())
            return $query->next_post();
        else return null;
    }

    /**
     * Find existing attachment in wordpress
     */
    public function find_existing_attachment(array $att) {
        $qargs = array(
            'post_type'  => 'attachment',
            'post_status' => 'any',
            'name'  => sanitize_text_field($att['uid']),
        );
        $query = new WP_Query($qargs);
        if ($query->have_posts())
            return $query->next_post();
        else return null;
    }

    /**
     * Get the location of the formater file or null
     */
    public function get_formater_file() {
        // check for formater in the themes directory
        $format_file = get_template_directory() . '/atcml-format.php';

        // if it does not exists check for the formater in the child directory
        if (!file_exists($format_file))
            $format_file = get_stylesheet_directory() . '/atcml-format.php';

        // if it does not exists return null
        if (!file_exists($format_file))
            return null;
        else return $format_file;
    }
}}
?>