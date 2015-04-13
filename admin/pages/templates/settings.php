
<div class="wrap">
    <h2>
        <img src="<?=getATCImgPageLogo()?>">
        <?=esc_html(get_admin_page_title());?>
    </h2>
    <form method="post" action="options.php">
        <?php
        // This prints out all hidden setting fields
        settings_fields( 'atc-admin-settings-group' );
        do_settings_sections( 'atc-admin-settings-page' );
        ?>
        <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">

        <?php if (!$this->error_notices):?>
            <a target="_blank" class="button" href="mailto:support@allthecontent.com?subject=AllTheContent%20Delivery%20FTP%20infos&body=To%20complete%20the%20installation:%0D%0DThank%20you%20for%20a%20few%20moments%20to%20fill%20out%20the%20form%20below:%0D%0DFTP%20server%20adress:%0DFTP%20server%20port:%2021%0DFTP%20root%20directory:%20%2F%0DFTP%20Username:%0DFTP%20paswword:%0D%0DClick%20on%20the%20button%20%22Send%22%20to%20send%20us%20the%20FTP%20information.%0D%0DYou%20will%20receive%20within%2048%20hours%20a%20confirmation%20email.%0D%0DThank%20you%20for%20your%20confidence.">
               Send us FTP infos
            </a>
        <?php endif ?>
        <?php
        //submit_button();
        ?>
    </form>
</div>
