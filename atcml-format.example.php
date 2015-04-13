<?php
/*
 *
 * Example of a content formater
 * place this file into your theme folder
 *
 * variables
 * - $content (array of value)
 * - $content_text (string) content text
 * - $captions (array of available captions)
 * - $post_id post_id
 */
?>

<!-- head caption -->
<?php if (count($captions) > 0) : ?>
[caption id='attachment_<?=$captions[0]['id']?>' align='alignleft' width='256']
    <img title='<?=str_replace("'", "\'", $captions[0]['title'])?>' src='<?=$captions[0]['url']?>' alt='<?=str_replace("'", "\'", $captions[0]['alt'])?>' width='256' />
    <?=str_replace("'", "\'", $captions[0]['caption'])?>
[/caption]
<?php endif ?>

<!-- content -->
<?=$content_text?>

<!-- captions -->
<?php if (count($captions > 1)) : ?>



<div class='container'>
    <?php for($i = 1; $i < count($captions); $i++) : ?>
    <?php $caption = $captions[$i] ?>
        [caption id='attachment_<?=$caption['id']?>' align='alignleft' width='256']
            <img title='<?=str_replace("'", "\'", $caption['title'])?>' src='<?=$caption['url']?>' alt='<?=str_replace("'", "\'", $caption['alt'])?>' width='256' />
            <?=str_replace("'", "\'", $caption['caption'])?>
        [/caption]
    <?php endfor ?>
</div>
<?php endif ?>

<!-- links -->
<?php if (is_array($content['links']) && count($content['links']) > 0) : ?>
<ul>
    <?php foreach($content['links'] as $key => $value ) : ?>
        <li><a href="<?=$key?>" target="_blank"><?=$value?></a></li>
    <?php endforeach ?>
</ul>
<?php endif ?>

<!-- credits -->
<?=$content['credits']?>
