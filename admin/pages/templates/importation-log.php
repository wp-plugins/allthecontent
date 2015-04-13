<div class="wrap">
    <h2>
        <img src="<?=getATCImgPageLogo()?>">
        <?=esc_html(get_admin_page_title());?>
    </h2>

    <?php $listTable->display();?>

    <hr/>
    <div class=""><p>
        Importer last run: <?= date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $lastImportationRun) ?>
    </p></div>

</div>
