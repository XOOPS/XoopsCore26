<script type="text/javascript">
    Xoops.setStatusImg('accept', '<{xoAdminIcons 'success.png'}>');
    Xoops.setStatusImg('cancel', '<{xoAdminIcons 'cancel.png'}>');
    Xoops.setStatusText('accept', '<{translate key="A_DISABLE"}>');
    Xoops.setStatusText('cancel', '<{translate key="A_ENABLE"}>');
</script>
<{include file="admin:system/admin_navigation.tpl"}>
<{include file="admin:system/admin_tips.tpl"}>
<{include file="admin:system/admin_buttons.tpl"}>

<!--Page related-->
<{if $related_count == true}>
    <table id="xo-smilies-sorter" class="outer tablesorter">
        <thead>
        <tr>
            <th class="txtleft"><{translate key="RELATED_NAME" dirname="page"}></th>
            <th class="txtleft"><{translate key="RELATED_MAIN" dirname="page"}></th>
            <th class="txtcenter"><{translate key="RELATED_NAVIGATION" dirname="page"}></th>
            <th class="txtcenter"><{translate key="RELATED_MENU" dirname="page"}></th>
            <th class="txtcenter"><{translate key="ACTION"}></th>
        </tr>
        </thead>
        <tbody>
        <{foreach item=item_r from=$related}>
            <tr class="<{cycle values='even,odd'}> alignmiddle">
                <td class="txtleft"><{$item_r.related_name}></td>
                <td class="txtleft width15">
                    <{foreach item=links from=$item_r.related_links}>
                        <a href="content.php?op=edit&amp;content_id=<{$links.content_id}>" title="<{translate key="A_EDIT"}>">
                            <{$links.content_title}>
                        </a><br />
                    <{/foreach}>
                </td>
                <td class="txtcenter width15"><{$item_r.navigation}></td>
                <td class="xo-actions txtcenter width5">
                    <img id="loading_sml<{$item_r.related_id}>" src="<{xoAppUrl 'media/xoops/images/spinner.gif'}>" style="display:none;" alt="<{translate key="LOADING"}>" />
                    <img class="cursorpointer" id="sml<{$item_r.related_id}>" onclick="Xoops.changeStatus( 'related.php', { op: 'update_status', related_id: <{$item_r.related_id}>, XOOPS_TOKEN_REQUEST: '<{$token}>' }, 'sml<{$item_r.related_id}>', 'related.php' )" src="<{if $item_r.related_domenu}><{xoAdminIcons 'success.png'}><{else}><{xoAdminIcons 'cancel.png'}><{/if}>" alt="<{if $item_r.related_domenu}><{translate key="A_DISABLE"}><{else}><{translate key="A_ENABLE"}><{/if}>" title="<{if $item_r.related_domenu}><{translate key="A_DISABLE"}><{else}><{translate key="A_ENABLE"}><{/if}>" />
                </td>
                <td class="xo-actions txtcenter width10">
                    <a href="related.php?op=edit&amp;related_id=<{$item_r.related_id}>" title="<{translate key="A_EDIT"}>">
                        <img src="<{xoAdminIcons 'edit.png'}>" alt="<{translate key="A_EDIT"}>">
                    </a>
                    <a href="related.php?op=delete&amp;related_id=<{$item_r.related_id}>" title="<{translate key="A_DELETE"}>">
                        <img src="<{xoAdminIcons 'delete.png'}>" alt="<{translate key="A_DELETE"}>">
                    </a>
                </td>
            </tr>
        <{/foreach}>
        </tbody>
    </table>

    <div class="clear spacer"></div>

    <{if $nav_menu|default:false}>
        <div class="xo-avatar-pagenav floatright"><{$nav_menu}></div><div class="clear spacer"></div>
    <{/if}>
<{/if}>

<{if $error_message|default:false}>
    <div class="alert alert-error">
        <strong><{$error_message}></strong>
    </div>
<{/if}>

<{$form|default:''}>