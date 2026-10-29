<script type="text/javascript">
    Xoops.setStatusImg('accept', '<{xoAdminIcons 'success.png'}>');
    Xoops.setStatusImg('cancel', '<{xoAdminIcons 'cancel.png'}>');
    Xoops.setStatusText('accept', '<{$smarty.const._AM_SMILIES_OFF}>');
    Xoops.setStatusText('cancel', '<{$smarty.const._AM_SMILIES_ON}>');
</script>
<{include file="admin:system/admin_navigation.tpl"}>
<{include file="admin:system/admin_tips.tpl"}>
<{include file="admin:system/admin_buttons.tpl"}>

<{if $error_message|default:false}>
<div class="alert alert-error" style="text-align:center;">
    <strong><{$error_message}></strong>
</div>
<{/if}>

<{$form|default:''}>

<{$form_category|default:''}>
<{if $images|default:false}>
    <!-- Image list -->
<div class="xo-moduleadmin-infobox outer">
    <div class="xo-window">
        <div class="xo-window-title">
            <img src="<{$xoops_url}>/media/xoops/images/icons/16/content.png" alt="" />&nbsp;
        </div>
        <div class="xo-window-data">
            <{foreach item=img from=$images}>
            <div class="cp-images">
                <div class="xo-thumbimg">
                    <{if !$db_store|default:false}>
                        <img class="xo-tooltip"
                        src="<{thumbnail image="uploads/`$img.image_name`" w=128 h=128}>" alt="<{$img.image_nicename}>" title="<{$img.image_nicename}>" />
                    <{else}>
                        <img class="xo-tooltip" src="<{$xoops_url}>/modules/images/image.php?id=<{$img.image_id}>" alt="<{$img.image_nicename}>" title="<{$img.image_nicename}>" style="max-width:128px; max-height:128px;" />
                    <{/if}>
                </div>
                <div class="xo-actions txtcenter">
                    <div class="spacer bold"><{$img.image_nicename}></div>
                    <img id="loading_img<{$img.image_id}>" src="<{xoAppUrl 'media/xoops/images/spinner.gif'}>" style="display:none;" alt="<{translate key='LOADING'}>" />
                    <img class="cursorpointer xo-tooltip" id="img<{$img.image_id}>" onclick="Xoops.changeStatus('images.php', { op: 'display', image_id: <{$img.image_id}>, XOOPS_TOKEN_REQUEST: '<{$token}>' }, 'img<{$img.image_id}>', 'images.php' )" src="<{if $img.image_display}><{xoAdminIcons 'success.png'}><{else}><{xoAdminIcons 'cancel.png'}><{/if}>" alt="<{$smarty.const._IMGDISPLAY}>" title="<{$smarty.const._IMGDISPLAY}>" />
                    <{if !$db_store|default:false}>
                        <a rel="external" class="lightbox xo-tooltip" href="<{$xoops_upload_url}>/<{$img.image_name}>" title="<{$img.image_nicename}>">
                    <{else}>
                        <a rel="external" class="lightbox xo-tooltip" href="<{$xoops_url}>/image.php?id=<{$img.image_id}>" title="<{$img.image_nicename}>">
                    <{/if}>
                    <img src="<{xoAdminIcons 'display.png'}>" alt="<{$smarty.const._AM_IMAGES_VIEW}>" />
                    </a>
                    <a class="xo-tooltip" href="images.php?op=edit&amp;image_id=<{$img.image_id}>&amp;imgcat_id=<{$imgcat_id}>" title="<{translate key ='A_EDIT'}>">
                        <img src="<{xoAdminIcons 'edit.png'}>" alt="<{translate key ='A_EDIT'}>" />
                    </a>
                    <a class="xo-tooltip" href="images.php?op=del&amp;image_id=<{$img.image_id}>" title="<{translate key ='A_DELETE'}>">
                        <img src="<{xoAdminIcons 'delete.png'}>" alt="<{translate key ='A_DELETE'}>" />
                    </a>
                    <img class="xo-tooltip" onclick="display_dialog(<{$img.image_id}>, true, true, 'slide', 'slide', 120, 350);" src="<{xoAdminIcons 'url.png'}>" alt="<{$smarty.const._AM_IMAGES_IMG_URL}>" title="<{$smarty.const._AM_IMAGES_IMG_URL}>" />
                </div>
                <div id="dialog<{$img.image_id}>" title="<{$img.image_nicename}>" style='display:none;'>
                    <div class="center">
                        <{if !$db_store|default:false}>
                            <{$xoops_upload_url}>/<{$img.image_name}>
                        <{else}>
                            <{$xoops_url}>/image.php?id=<{$img.image_id}>
                        <{/if}>
                    </div>
                </div>
            </div>
            <{/foreach}>
        </div>
    </div>
    <div class="clear"></div>
    <{if $nav_menu|default:false}>
        <{$nav_menu}>
    <{/if}>
</div>
<{/if}>
