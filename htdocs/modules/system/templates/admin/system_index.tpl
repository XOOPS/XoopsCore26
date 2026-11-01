<{include file="admin:system/admin_breadcrumb.tpl"}>
<{include file="admin:system/admin_tips.tpl"}>
<{include file="admin:system/admin_buttons.tpl"}>
<script type="text/javascript">
    IMG_ON = '<{xoAdminIcons 'success.png'}>';
    IMG_OFF = '<{xoAdminIcons 'cancel.png'}>';
</script>
<table class="outer">
    <thead>
        <tr>
            <th class="txtcenter"><{translate key='SECTION'}></th>
            <th class="txtcenter"><{translate key='DESCRIPTION'}></th>
            <th class="txtcenter">&nbsp;</th>
            <th class="txtcenter">&nbsp;</th>
        </tr>
    </thead>

    <tbody>
    <{foreach item=menuitem from=$menu}>
    <tr class="<{cycle values='even,odd'}>">
        <td class="bold width15">
            <a class="xo-tooltip" href="admin.php?fct=<{$menuitem.file}>" title="<{translate key='GO_TO'}>: <{$menuitem.title}>">
                <img class="xo-imgmini" src='<{$theme_icons}>/<{$menuitem.icon}>' alt="<{$menuitem.title}>" />
                <{$menuitem.title}>
            </a>
        </td>
        <td class=""><{$menuitem.desc}></td>
        <td class="width15"><{$menuitem.infos}></td>
        <td class="xo-actions width2">
            <{if $menuitem.used}>
                <img id="loading_<{$menuitem.file}>" src="images/spinner.gif" style="display:none;" alt="<{translate key='LOADING'}>" />
                <img class="xo-tooltip" id="<{$menuitem.file}>" onclick="system_setStatus( { op: 'system_activate', type: '<{$menuitem.file}>', XOOPS_TOKEN_REQUEST: XOOPS_TOKEN }, '<{$menuitem.file}>', 'admin.php' )" src="<{if $menuitem.status}><{xoAdminIcons 'success.png'}><{else}><{xoAdminIcons 'cancel.png'}><{/if}>" alt="<{translate key='CHANGE_STATUS'}>" title="<{translate key='CHANGE_STATUS'}>" />
            <{/if}>
        </td>
    </tr>
    <{/foreach}>
    </tbody>
</table>