<script type="text/javascript">
    Xoops.setStatusImg('accept', '<{xoAdminIcons 'success.png'}>');
    Xoops.setStatusImg('cancel', '<{xoAdminIcons 'cancel.png'}>');
    Xoops.setStatusText('accept', '<{translate key="A_DISABLE"}>');
    Xoops.setStatusText('cancel', '<{translate key="A_ENABLE"}>');
</script>
<{include file="admin:system/admin_navigation.tpl"}>
<{include file="admin:system/admin_tips.tpl"}>
<{include file="admin:system/admin_buttons.tpl"}>

<!--Page content-->
<{if $content_count|default:false}>
    <table id="xo-smilies-sorter" class="outer tablesorter">
        <thead>
        <tr>
            <th class="txtcenter"><{translate key="ID"}></th>
            <th class="txtleft"><{translate key="TITLE"}></th>
            <th class="txtcenter"><{translate key="HOME_PAGE"}></th>
            <th class="txtcenter"><{translate key="STATUS"}></th>
            <th class="txtcenter"><{translate key="WEIGHT"}></th>
            <th class="txtcenter"><{translate key="HITS"}></th>
            <th class="txtcenter"><{translate key="RATING"}></th>
            <th class="txtcenter"><{translate key="ACTION"}></th>
        </tr>
        </thead>
        <tbody>
        <{foreach item=pagecontent from=$content}>
        <tr class="<{cycle values='even,odd'}> alignmiddle">
            <td class="txtcenter width5">
                <a href="../viewpage.php?id=<{$pagecontent.content_id}>" title="<{$pagecontent.content_title}>"><{$pagecontent.content_id}></a>
            </td>
            <td class="txtleft width35"><{$pagecontent.content_title}></td>
            <td class="xo-actions txtcenter width15">
                <img id="loading_display<{$pagecontent.content_id}>" src="<{xoAppUrl 'media/xoops/images/spinner.gif'}>" style="display:none;" alt="<{translate key="LOADING"}>" />
                <img class="cursorpointer" id="display<{$pagecontent.content_id}>" onclick="Xoops.changeStatus( 'content.php', { op: 'update_display', content_id: <{$pagecontent.content_id}>, XOOPS_TOKEN_REQUEST: '<{$token}>' }, 'display<{$pagecontent.content_id}>', 'content.php' )" src="<{if $pagecontent.content_maindisplay}><{xoAdminIcons 'success.png'}><{else}><{xoAdminIcons 'cancel.png'}><{/if}>" alt="<{if $pagecontent.content_maindisplay}><{translate key="A_DISABLE"}><{else}><{translate key="A_ENABLE"}><{/if}>" title="<{if $pagecontent.content_maindisplay}><{translate key="A_DISABLE"}><{else}><{translate key="A_ENABLE"}><{/if}>" />
            </td>
            <td class="xo-actions txtcenter width5">
                <img id="loading_sml<{$pagecontent.content_id}>" src="<{xoAppUrl 'media/xoops/images/spinner.gif'}>" style="display:none;" alt="<{translate key="LOADING"}>" />
                <img class="cursorpointer" id="sml<{$pagecontent.content_id}>" onclick="Xoops.changeStatus( 'content.php', { op: 'update_status', content_id: <{$pagecontent.content_id}>, XOOPS_TOKEN_REQUEST: '<{$token}>' }, 'sml<{$pagecontent.content_id}>', 'content.php' )" src="<{if $pagecontent.content_status}><{xoAdminIcons 'success.png'}><{else}><{xoAdminIcons 'cancel.png'}><{/if}>" alt="<{if $pagecontent.content_status}><{translate key="A_DISABLE"}><{else}><{translate key="A_ENABLE"}><{/if}>" title="<{if $pagecontent.content_status}><{translate key="A_DISABLE"}><{else}><{translate key="A_ENABLE"}><{/if}>" />
            </td>
            <td class="txtcenter width5"><{$pagecontent.content_weight}></td>
            <td class="txtcenter width5"><{$pagecontent.content_hits}></td>
            <td class="txtcenter width5"><{$pagecontent.content_rating}></td>
            <td class="xo-actions txtcenter width10">
                <a href="content.php?op=clone&amp;content_id=<{$pagecontent.content_id}>" title="<{translate key="A_CLONE"}>">
                    <img src="<{xoAdminIcons 'clone.png'}>" alt="<{translate key="A_CLONE"}>">
                </a>
                <a href="content.php?op=edit&amp;content_id=<{$pagecontent.content_id}>" title="<{translate key="A_EDIT"}>">
                    <img src="<{xoAdminIcons 'edit.png'}>" alt="<{translate key="A_EDIT"}>">
                </a>
                <a href="content.php?op=delete&amp;content_id=<{$pagecontent.content_id}>" title="<{translate key="A_DELETE"}>">
                    <img src="<{xoAdminIcons 'delete.png'}>" alt="<{translate key="A_DELETE"}>">
                </a>
            </td>
        </tr>
        <{/foreach}>
        </tbody>
    </table>

    <div class="clear spacer"></div>

    <{if $nav_menu|default:false}>
        <{$nav_menu}>
    <{/if}>
<{/if}>

<!-- Display form (add,edit) -->
<{if $error_message|default:false}>
    <div class="alert alert-error">
        <strong><{$error_message}></strong>
    </div>
<{/if}>

<{$form|default:''}>