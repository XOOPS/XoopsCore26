<script type="text/javascript">
    Xoops.setStatusImg('accept', '<{xoAdminIcons 'success.png'}>');
    Xoops.setStatusImg('cancel', '<{xoAdminIcons 'cancel.png'}>');
    Xoops.setStatusText('accept', '<{translate key="DISPLAY_IN_FORM"}>');
    Xoops.setStatusText('cancel', '<{translate key="DO_NOT_DISPLAY_IN_FORM"}>');
</script>
<{include file="admin:system/admin_navigation.tpl"}>
<{include file="admin:system/admin_tips.tpl"}>
<{include file="admin:system/admin_buttons.tpl"}>

<{$info_msg|default:''}>
<{$error_msg|default:''}>

<{if $avatar_count|default:false}>
<div class="xo-moduleadmin-infobox outer">
    <div class="xo-window">
        <div class="xo-window-title">
            <img src="<{$xoops_url}>/media/xoops/images/icons/16/avatar_system.png" alt="" />&nbsp;<{translate key="SYSTEM" dirname="avatars"}>
            <a class="down" href="javascript:;">&nbsp;</a>
        </div>
        <div class="xo-window-data">
                <{foreach item=avatar from=$avatars_list}>
                <div class="cp-avatar">
                    <div class="xo-thumbimg">
                        <img class="xo-tooltip" src="<{thumbnail image="uploads/`$avatar.avatar_file`" w=128 h=128}>" alt="<{$avatar.avatar_name}>" title="<{$avatar.avatar_name}>"/>
                    </div>
                    <div class="xo-actions txtcenter">
                        <div class="spacer bold"><{$avatar.avatar_name}></div>
                        <img id="loading_avt<{$avatar.avatar_id}>" src="<{xoAppUrl 'media/xoops/images/spinner.gif'}>" style="display:none;" alt="<{translate key='LOADING'}>" />
                        <img class="cursorpointer" id="avt<{$avatar.avatar_id}>" onclick="Xoops.changeStatus( 'avatar_system.php', { op: 'update_display', avatar_id: <{$avatar.avatar_id}>, XOOPS_TOKEN_REQUEST: '<{$token}>' }, 'avt<{$avatar.avatar_id}>', 'avatar_system.php' )" src="<{if $avatar.avatar_display}><{xoAdminIcons 'success.png'}><{else}><{xoAdminIcons 'cancel.png'}><{/if}>"
                             alt="<{if $avatar.avatar_display}><{translate key='DISPLAY_IN_FORM'}><{else}><{translate key='DO_NOT_DISPLAY_IN_FORM'}><{/if}>" title="<{if $avatar.avatar_display}><{translate key='DISPLAY_IN_FORM'}><{else}><{translate key='DO_NOT_DISPLAY_IN_FORM'}><{/if}>" />
                        <img class="cursorhelp xo-tooltip" src="<{xoAdminIcons 'forum.png'}>" alt="<{$avatar.count}> <{translate key='USERS' dirname='avatars'}>" title="<{$avatar.count}> <{translate key='USERS' dirname='avatars'}>" />
                        <a class="xo-tooltip" href="avatar_system.php?op=edit&amp;avatar_id=<{$avatar.avatar_id}>" title="<{translate key='A_EDIT'}>">
                            <img src="<{xoAdminIcons 'edit.png'}>" alt="<{translate key='A_EDIT'}>" />
                        </a>
                        <a class="xo-tooltip" href="avatar_system.php?op=delete&amp;avatar_id=<{$avatar.avatar_id}>" title="<{translate key='A_DELETE'}>">
                            <img src="<{xoAdminIcons 'delete.png'}>" alt="<{translate key='A_DELETE'}>" />
                        </a>
                    </div>
                </div>
                <{/foreach}>
        </div>
    </div>
    <div class="clear"></div>
</div>
<{if $nav_menu|default:false}>
<{$nav_menu}>
<{/if}>
<{/if}>
<!-- Display Avatar form (add,edit) -->
<{$form|default:''}>