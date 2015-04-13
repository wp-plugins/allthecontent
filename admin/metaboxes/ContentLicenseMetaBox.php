<?php defined( 'ABSPATH' ) OR exit;

/*
 * Content Metabox
 * @autor AllTheContent, Vincent Buzzano
 */
if (!class_exists('ContentLicenseMetaBox')) { class ContentLicenseMetaBox {

	private $contentLicense = null;

	public function __construct()	{
        if (is_admin()){
            add_action( 'load-post.php', array($this, 'on_load_post'));
	        add_action('add_meta_boxes', array($this, 'on_add_meta_boxes'));
        }
	}

    /**
     * Set License Meta data for new imported content
     */
    public function on_load_post() {

        $post_id =  $_GET['post'];
        $modif   = get_post_meta( $post_id, "atc_license_modification", true );
        $multi   = get_post_meta( $post_id, "atc_license_multimediatisation", true );
        $local   = get_post_meta( $post_id, "atc_license_localisation", true );
        $distrib = get_post_meta( $post_id, "atc_license_distribution", true );

        $license = array (
            __("License for modification", ATC_TEXT_DOMAIN)       => $modif,
            __("License for multimediatisation", ATC_TEXT_DOMAIN) => $multi,
            __("License for localisation", ATC_TEXT_DOMAIN)       => $local,
            __("License for distribution", ATC_TEXT_DOMAIN)       => $distrib
		);

		$this->contentLicense = $license;
    }

	public function on_add_meta_boxes()	{

        add_meta_box(
            'atc-License'
	        , __('AllTheContent license', ATC_TEXT_DOMAIN)
		    , array($this, 'render_meta_box_contentLicense')
		    , 'post'
		    , 'advanced'
		    , 'high'
	  );
	}

	/**
 	 * Render Meta Box content
     */
	public function render_meta_box_contentLicense() {
        if($this->contentLicense != null) {
		    echo "<table>";
		    foreach ( $this->contentLicense as $type => $value ){
			    echo "<tr><td>".$type."</td><td>&nbsp;&nbsp;".$value."</td><tr/>";
			}
		    echo "</table>";
		}
	}
}}

?>
