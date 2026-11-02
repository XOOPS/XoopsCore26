<script type="text/javascript">
    Xoops.setStatusImg('accept', '<{xoAdminIcons 'success.png'}>');
    Xoops.setStatusImg('cancel', '<{xoAdminIcons 'cancel.png'}>');
    Xoops.setStatusText('accept', '<{$smarty.const._AM_USERRANK_OFF}>');
    Xoops.setStatusText('cancel', '<{$smarty.const._AM_USERRANK_ON}>');
</script>
<{include file="admin:system/admin_navigation.tpl"}>
<{include file="admin:system/admin_tips.tpl"}>
<{include file="admin:system/admin_buttons.tpl"}>
<{if $userrank_count == true}>
<!--User rank-->
<table id="xo-rank-sorter" cellspacing="1" class="outer tablesorter">
    <thead>
    <tr>
        <th class="txtcenter"><{$smarty.const._AM_USERRANK_IMAGE}></th>
        <th class="txtcenter"><{$smarty.const._AM_USERRANK_TITLE}></th>
        <th class="txtcenter"><{$smarty.const._AM_USERRANK_MINPOST}></th>
        <th class="txtcenter"><{$smarty.const._AM_USERRANK_MAXPOST}></th>
        <th class="txtcenter"><{$smarty.const._AM_USERRANK_SPECIAL}></th>
        <th class="txtcenter width10"><{$smarty.const._AM_USERRANK_ACTION}></th>
    </tr>
    </thead>
    <tbody>
    <{foreach item=urank from=$userrank}>
    <tr class="<{cycle values='even,odd'}> alignmiddle">
        <td class="txtcenter"><{$urank.rank_image}></td>
        <td class="txtcenter"><{$urank.rank_title}></td>
        <td class="txtcenter"><{$urank.rank_min}></td>
        <td class="txtcenter"><{$urank.rank_max}></td>
        <td class="xo-actions txtcenter">
            <img id="loading_sml<{$urank.rank_id}>" src="<{xoAppUrl 'media/xoops/images/spinner.gif'}>" style="display:none;" alt="<{translate key='LOADING'}>" />
            <img class="cursorpointer xo-tooltip" id="sml<{$urank.rank_id}>" onclick="Xoops.changeStatus( 'userrank.php', { op: 'userrank_update_special', rank_id: <{$urank.rank_id}>, XOOPS_TOKEN_REQUEST: '<{$token}>' }, 'sml<{$urank.rank_id}>' )" src="<{if $urank.rank_special}><{xoAdminIcons 'success.png'}><{else}><{xoAdminIcons 'cancel.png'}><{/if}>" alt="<{if $urank.rank_special}><{$smarty.const._AM_USERRANK_OFF}><{else}><{$smarty.const._AM_USERRANK_ON}><{/if}>" title="<{if $urank.rank_special}><{$smarty.const._AM_USERRANK_OFF}><{else}><{$smarty.const._AM_USERRANK_ON}><{/if}>" />
        </td>
        <td class="xo-actions txtcenter">
            <a class="xo-tooltip" href="./userrank.php?op=userrank_edit&amp;rank_id=<{$urank.rank_id}>" title="<{$smarty.const._AM_USERRANK_EDIT}>">
                <img src="<{xoAdminIcons 'edit.png'}>" alt="<{$smarty.const._AM_USERRANK_EDIT}>" />
            </a>
            <a class="xo-tooltip" href="./userrank.php?op=userrank_delete&amp;rank_id=<{$urank.rank_id}>" title="<{$smarty.const._AM_USERRANK_DELETE}>">
                <img src="<{xoAdminIcons 'delete.png'}>" alt="<{$smarty.const._AM_USERRANK_DELETE}>" />
            </a>
        </td>
    </tr>
    <{/foreach}>
    </tbody>
</table>
<!-- Display rank navigation -->
<div class="clear spacer"></div>
<{if $nav_menu|default:false}>
<div class="xo-avatar-pagenav floatright"><{$nav_menu}></div>
<div class="clear spacer"></div>
<{/if}>
<{/if}>
<!--Display rank form (add,edit)-->
<{$form|default:''}>

