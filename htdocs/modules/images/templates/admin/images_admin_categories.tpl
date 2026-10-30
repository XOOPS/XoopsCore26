<script type="text/javascript">
    Xoops.setStatusImg('accept', '<{xoAdminIcons 'success.png'}>');
    Xoops.setStatusImg('cancel', '<{xoAdminIcons 'cancel.png'}>');
    Xoops.setStatusText('accept', '<{$smarty.const._AM_IMAGES_CAT_OFF}>');
    Xoops.setStatusText('cancel', '<{$smarty.const._AM_IMAGES_CAT_ON}>');
</script>
<{include file="admin:system/admin_navigation.tpl"}>
<{include file="admin:system/admin_tips.tpl"}>
<{include file="admin:system/admin_buttons.tpl"}>

<{$form|default:''}>

<{if $categories|default:false}>
    <table class="outer">
        <thead>
            <tr>
                <th class="txtleft"><{$smarty.const._AM_IMAGES_NAME}></th>
                <th><{$smarty.const._AM_IMAGES_CAT_NBIMAGES}></th>
                <th><{$smarty.const._AM_IMAGES_CAT_MAXSIZE}></th>
                <th><{$smarty.const._AM_IMAGES_CAT_MAXWIDTH}></th>
                <th><{$smarty.const._AM_IMAGES_CAT_MAXHEIGHT}></th>
                <th><{$smarty.const._AM_IMAGES_DISPLAY}></th>
                <th><{$smarty.const._AM_IMAGES_ACTIONS}></th>
            </tr>
        </thead>
        <tbody>
            <{foreach item=category from=$categories}>
                <tr>
                <td class="txtleft">
                    <a class="xo-tooltip" href="images.php?op=list&amp;imgcat_id=<{$category.imgcat_id}>" title="<{$smarty.const._AM_IMAGES_VIEW}>">
                        <{$category.imgcat_name}>
                    </a>
                </td>
                <td class="txtcenter width10"><{$category.imgcat_count}></td>
                <td class="txtcenter width10"><{$category.imgcat_maxsize}></td>
                <td class="txtcenter width10"><{$category.imgcat_maxwidth}></td>
                <td class="txtcenter width10"><{$category.imgcat_maxheight}></td>
                <td class="xo-actions txtcenter width10">
                    <img id="loading_cat<{$category.imgcat_id}>" src="<{xoAppUrl 'media/xoops/images/spinner.gif'}>" style="display:none;" alt="<{translate key='LOADING'}>" />
                    <img class="cursorpointer" id="cat<{$category.imgcat_id}>" onclick="Xoops.changeStatus( 'categories.php', { op: 'display', imgcat_id: <{$category.imgcat_id}>, XOOPS_TOKEN_REQUEST: '<{$token}>' }, 'cat<{$category.imgcat_id}>', 'categories.php' )" src="<{if $category.imgcat_display}><{xoAdminIcons 'success.png'}><{else}><{xoAdminIcons 'cancel.png'}><{/if}>" alt="" title="<{if $category.imgcat_display}><{$smarty.const._AM_IMAGES_CAT_OFF}><{else}><{$smarty.const._AM_IMAGES_CAT_ON}><{/if}>" />
                </td>
                <td class="xo-actions txtcenter width10">
                    <a class="xo-tooltip" href="images.php?op=list&amp;imgcat_id=<{$category.imgcat_id}>" title="<{$smarty.const._AM_IMAGES_VIEW}>">
                        <img src="<{xoAdminIcons 'view.png'}>" alt="<{$smarty.const._AM_IMAGES_VIEW}>" />
                    </a>
                    <{if $xoops_isadmin|default:false}>
                        <a class="xo-tooltip" href="categories.php?op=edit&amp;imgcat_id=<{$category.imgcat_id}>" title="<{translate key ='A_EDIT'}>">
                            <img src="<{xoAdminIcons 'edit.png'}>" alt="<{translate key ='A_EDIT'}>" />
                        </a>
                        <a class="xo-tooltip" href="categories.php?op=del&amp;imgcat_id=<{$category.imgcat_id}>" title="<{translate key ='A_DELETE'}>">
                            <img src="<{xoAdminIcons 'delete.png'}>" alt="" />
                        </a>
                    <{/if}>
                </td>
                </tr>
            <{/foreach}>
        </tbody>
    </table>

    <!-- Nav menu -->
    <{if $nav_menu|default:false}>
        <{$nav_menu}>
    <{/if}>
<{/if}>
