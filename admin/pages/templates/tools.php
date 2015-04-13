<div class="wrap">
    <h2>
        <img src="<?=getATCImgPageLogo()?>">
        <?=esc_html(get_admin_page_title());?>
    </h2>
    <?=$msg_flash?>
    <h3>ATCML Importer</h3>
    <p>Here you can run the importer manualy and import ATCM files located in the directory: <strong>'<?=$options['import_path']?>'</strong>. Just click on the button 'Run importer now'</p>
    <a href='<?=admin_url('admin.php?page=' . self::$SLUG . '&action=importnow')?>' class="button">Run importer now</a>

    <h3>Erase importation log</h3>
    <p>Here you can erase importation log . Just click on the button 'Clear importation log'</p>
    <a href='<?=admin_url('admin.php?page=' . self::$SLUG . '&action=clearimplog')?>' class="button">Clear importation log</a>

    <h3>Erase error log</h3>
    <p>Here you can erase error log . Just click on the button 'Clear error log'</p>
    <a href='<?=admin_url('admin.php?page=' . self::$SLUG . '&action=clearerrlog')?>' class="button">Clear error log</a>

</div>
