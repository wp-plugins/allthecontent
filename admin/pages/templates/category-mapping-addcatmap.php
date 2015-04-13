<div class="wrap">
    <h2>
        <img src="<?=getATCImgPageLogo()?>">
        <?=esc_html(get_admin_page_title());?>
    </h2>
    <h3><?=__('Add a new mapping', ATC_TEXT_DOMAIN)?></h3>
    <form action="admin-post.php" method="post" id="mappingform">
        <input type="hidden" name="action" value="atc_save_mapping">

        <p><strong>I want to map</strong> ... </p>
        <p>
            <label for="atc_theme_code">AllTheContent Theme :
                <input type="text" value='' name='atc_theme_code' id='atc_theme_code'>
            </label>
        </p>
        <p><strong>to</strong> ... </p>
        <p>
            <label for="wp_cat_id">Wordpress Category :
                <?php wp_dropdown_categories(array(
                    'show_option_all'    => '',
                	'show_option_none'   => '',
                	'orderby'            => 'ID',
                	'order'              => 'ASC',
                	'show_count'         => 0,
                	'hide_empty'         => false,
                	'child_of'           => 0,
                	'exclude'            => '',
                	'echo'               => 1,
                	'selected'           => 0,
                	'hierarchical'       => false,
                	'name'               => 'wp_cat_id',
                	'id'                 => '',
                	'class'              => 'postform',
                	'depth'              => 0,
                	'tab_index'          => 0,
                	'taxonomy'           => 'category',
                	'hide_if_empty'      => false,
                )); ?>
            </label>
        </p>
        <?php submit_button(__('Save'), 'primary', 'save');?>
    </form>

</div>
