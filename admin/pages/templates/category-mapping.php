<div class="wrap">
    <h2>
        <img src="<?=getATCImgPageLogo()?>">
        <?=esc_html(get_admin_page_title());?>
        <a href="<?=admin_url('admin.php?page=' . self::$SLUG . '&action=addcatmap')?>" class="add-new-h2">Add New</a>
        <a style="float: right" href="<?=admin_url('admin-post.php?action=atc_reorganize_mapping')?>" class="add-new-h2">Reorganize contents in categories</a>
    </h2>

    <?=$msg_flash?>

    <?php $listTable->display();?>
</div>
