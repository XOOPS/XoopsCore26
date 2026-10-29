<{include file="admin:system/admin_breadcrumb.tpl"}>
<{include file="admin:system/admin_tips.tpl"}>
<{if $users_display|default:false}>
<!--Display form sort-->
<div class="xo-headercontent spacer">
    <div class="pull-left"><{$form_sort}></div>
    <{include file="admin:system/admin_buttons.tpl"}>
</div>
<div class="clear spacer">&nbsp;</div>
    <form name='memberslist' id='memberslist' action='<{$php_selft}>' method='POST'>
        <table id="xo-users-sorter" class="outer tablesorter">
            <thead>
            <tr>
                <th class="txtcenter width3">
                    <input name='allbox' id='allbox' onclick='xoopsCheckAll("memberslist", "allbox");'  type='checkbox' value='Check All' />
                </th>
                <th class="txtcenter width5"><{translate key='STATUS'}></th>
                <th class="txtcenter"><{translate key='USER_NAME'}></th>
                <th class="txtcenter"><{translate key='EMAIL'}></th>
                <th class="txtcenter"><{translate key='REGISTRATION_DATE'}></th>
                <th class="txtcenter"><{translate key='LAST_LOGIN'}></th>
                <th class="txtcenter"><{translate key='COMMENTS_POSTS'}></th>
                <th class="txtcenter width10"><{translate key='ACTION'}></th>
            </tr>
            </thead>
            <!--Display data-->
            <{if $users_count == true}>
            <tbody>
            <{foreach item=user from=$users}>
            <tr class="<{cycle values='even,odd'}> alignmiddle">
                <td class="txtcenter"><{if $user.checkbox_user}><input type='checkbox' name='memberslist_id[]' id='memberslist_id[]' value='<{$user.uid}>' /><{/if}></td>
                <td class="txtcenter"><img class="xo-imgmini" src="<{$user.group}>" alt="" /></td>
                <td class="txtcenter"><a title="<{$user.uname}>" href="<{$xoops_url}>/userinfo.php?uid=<{$user.uid}>" ><{$user.uname}></a></td>
                <td class="txtcenter"><{$user.email}></td>
                <td class="txtcenter"><{$user.reg_date}></td>
                <td class="txtcenter"><{$user.last_login}></td>
                <td class="txtcenter"><div id="display_post_<{$user.uid}>"><{$user.posts}></div><div id='loading_<{$user.uid}>' class="txtcenter" style="display:none;"><img src="./images/mimetypes/spinner.gif" title="Loading" alt="Loading"/></div></td>
                <td class="xo-actions txtcenter">
                    <{if $user.user_level > 0}>
                    <img class="xo-tooltip" onclick="display_post('<{$user.uid}>');" src="<{xoAdminIcons 'reload.png'}>" alt="<{translate key='A_SYNCHRONIZE'}>" title="<{translate key='A_SYNCHRONIZE'}>" />
                    <img class="xo-tooltip" onclick="display_dialog('<{$user.uid}>', true, true, 'slide', 'slide', 300, 400);" src="<{xoAdminIcons 'display.png'}>" alt="<{translate key='VIEW_USER_INFO' dirname='system'}>" title="<{translate key='VIEW_USER_INFO' dirname='system'}>" />
                    <a class="xo-tooltip" href="admin.php?fct=users&amp;op=users_edit&amp;uid=<{$user.uid}>" title="<{translate key='EDIT_USER' dirname='system'}>">
                        <img src="<{xoAdminIcons 'user_edit.png'}>" alt="<{translate key='EDIT_USER' dirname='system'}>" />
                    </a>
                    <a class="xo-tooltip" href="admin.php?fct=users&amp;op=users_delete&amp;uid=<{$user.uid}>" title="<{translate key='DELETE_USER' dirname='system'}>">
                        <img src="<{xoAdminIcons 'user_delete.png'}>" alt="<{translate key='DELETE_USER' dirname='system'}>" />
                    </a>
                    <{else}>
                    <a class="xo-tooltip" href="admin.php?fct=users&amp;op=users_active&amp;uid=<{$user.uid}>&amp;XOOPS_TOKEN_REQUEST=<{$token_id}>" title="<{translate key='ONLY_ACTIVE_USERS' dirname='system'}>">
                        <img src="<{xoAdminIcons 'xoops/active_user.png'}>" alt="<{translate key='ONLY_ACTIVE_USERS' dirname='system'}>" />
                    </a>
                    <img class="xo-tooltip" onclick="display_dialog('<{$user.uid}>', true, true, 'slide', 'slide', 300, 400);" src="<{xoAdminIcons 'display.png'}>" alt="<{translate key='VIEW_USER_INFO' dirname='system'}>" title="<{translate key='VIEW_USER_INFO' dirname='system'}>" />
                    <a class="xo-tooltip" href="admin.php?fct=users&amp;op=users_edit&amp;uid=<{$user.uid}>" title="<{translate key='EDIT_USER' dirname='system'}>">
                        <img src="<{xoAdminIcons 'user_edit.png'}>" alt="<{translate key='EDIT_USER' dirname='system'}>" />
                    </a>
                    <a class="xo-tooltip" href="admin.php?fct=users&amp;op=users_delete&amp;uid=<{$user.uid}>" title="<{translate key='DELETE_USER' dirname='system'}>">
                        <img src="<{xoAdminIcons 'user_delete.png'}>" alt="<{translate key='DELETE_USER' dirname='system'}>" />
                    </a>
                    <{/if}>
                </td>
            </tr>
            <{/foreach}>
            </tbody>
            <tr>
                <td class='txtleft' colspan='8'>
                    <select name='fct' onChange='changeDisplay (this.value, "groups", "edit_group")'>
                        <option value=''>---------</option>
                        <option value='mailusers'><{translate key='SEND_EMAIL'}></option>
                        <option value='groups'><{translate key='EDIT_GROUPS' dirname='system'}></option>
                        <option value='users'><{translate key='A_DELETE'}></option>
                    </select>&nbsp;
                    <select name='edit_group' id='edit_group' onChange='changeDisplay (this.value, this.value, "selgroups")' style="display:none;">
                        <option value=''>---------</option>
                        <option value='add_group'><{translate key='ADD_GROUP' dirname='system'}></option>
                        <option value='delete_group'><{translate key='DELETE_GROUP' dirname='system'}></option>
                    </select>
                    <{$form_select_groups}>
                    <input type="hidden" name="op" value="action_group">
                    <{$token}>
                    <input class="btn danger" type='submit' name='Submit' />
                </td>
            </tr>
            <{/if}>
            <!--No found-->
            <{if $users_no_found|default:false}>
            <tr class="<{cycle values='even,odd'}> alignmiddle">
                <td colspan='8' class="txtcenter"><{translate key='E_USERS_NOT_FOUND'}></td>
            </tr>
            <{/if}>
        </table>
    </form>
    <!--Pop-pup-->
    <{if $users_count == true}>
        <{foreach item=users from=$users_popup}>
            <div id="dialog<{$users.uid}>" title="<{$users.uname}>" style='display:none;'>
                <table>
                    <tr>
                        <td class="txtcenter">
                            <img src="<{$users.user_avatar}>" alt="<{$users.uname}>" title="<{$users.uname}>" />
                        </td>
                        <td class="txtcenter">
                            <a href='mailto:<{$users.email}>'><img src="<{xoAdminIcons 'mail_send.png'}>" alt="" title="<{translate key='EMAIL'}>" /></a>
                            <a href='javascript:openWithSelfMain("<{$xoops_url}>/pmlite.php?send2=1&amp;to_userid=<{$users.uid}>","pmlite",450,370);'><img src="<{xoAdminIcons 'pm.png'}>" alt="" title="<{translate key='PM'}>" /></a>
                            <a href='<{$users.url}>' rel='external'><img src="<{xoAdminIcons 'url.png'}>" alt="" title="<{translate key='WEB_URL'}>" ></a>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <ul style="border: 1px solid #666; padding: 8px;">
                                <{if $users.user_name|default:false}><li><span class="bold"><{translate key='NAME'}></span>&nbsp;:&nbsp;<{$users.name}></li><{/if}>
                                <li><span class="bold"><{translate key='USER_NAME'}></span>&nbsp;:&nbsp;<{$users.uname}></li>
                                <li><span class="bold"><{translate key='EMAIL'}></span>&nbsp;:&nbsp;<{$users.email}></li>
                                <{if $users.user_url|default:false}><li><span class="bold"><{translate key='WEB_URL'}></span>&nbsp;:&nbsp;<{$users.url}> </li><{/if}>
                                <{if $users.user_icq|default:false}><li><span class="bold"><{translate key='ICQ'}></span>&nbsp;:&nbsp;<{$users.user_icq}></li><{/if}>
                                <{if $users.user_aim|default:false}><li><span class="bold"><{translate key='AIM'}></span>&nbsp;:&nbsp;<{$users.user_aim}></li><{/if}>
                                <{if $users.user_yim|default:false}><li><span class="bold"><{translate key='YIM'}></span>&nbsp;:&nbsp;<{$users.user_yim}></li><{/if}>
                                <{if $users.user_msnm|default:false}><li><span class="bold"><{translate key='MSNM'}></span>&nbsp;:&nbsp;<{$users.user_msnm}> </li><{/if}>
                            </ul>
                        </td>
                    </tr>
                </table>
            </div>
        <{/foreach}>
    <{/if}>
    <!--Pop-pup-->
    <div class='txtright'><{$nav|default:''}></div>
<{/if}>
<br />
<!-- Display Avatar form (add,edit) -->
<{$form|default:''}>