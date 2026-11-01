<script type="text/javascript">
    Xoops.setStatusImg('accept', '{xoAdminIcons 'success.png'}');
    Xoops.setStatusImg('cancel', '{xoAdminIcons 'cancel.png'}');
    Xoops.setStatusText('accept', '{$smarty.const._AM_SMILIES_OFF}');
    Xoops.setStatusText('cancel', '{$smarty.const._AM_SMILIES_ON}');
</script>
{include file="admin:system/admin_navigation.tpl"}
{include file="admin:system/admin_tips.tpl"}
{include file="admin:system/admin_buttons.tpl"}

{$info_msg|default:''}
{$error_msg|default:''}

{if $smilies_count|default:0 > 0}
    <table id="xo-smilies-sorter" class="outer tablesorter">
        <thead>
        <tr>
            <th class="txtcenter">{$smarty.const._AM_SMILIES_CODE}</th>
            <th class="txtcenter">{$smarty.const._AM_SMILIES_SMILIE}</th>
            <th class="txtleft">{$smarty.const._AM_SMILIES_DESCRIPTION}</th>
            <th class="txtcenter">{$smarty.const._AM_SMILIES_DISPLAY}</th>
            <th class="txtcenter">{$smarty.const._AM_SMILIES_ACTION}</th>
        </tr>
        </thead>
        <tbody>
        {foreach item=smiley from=$smilies}
            <tr class="{cycle values='even,odd'} alignmiddle">
                <td class="txtcenter width10">{$smiley.smiley_code}</td>
                <td class="txtcenter width10">
                    <img src="{$xoops_upload_url}/{$smiley.smiley_url}" alt="{$smiley.smiley_emotion}" />
                </td>
                <td class="txtleft">{$smiley.smiley_emotion}</td>
                <td class="xo-actions txtcenter width5">
                    <img id="loading_sml{$smiley.smiley_id}" src="{xoAppUrl 'media/xoops/images/spinner.gif'}" style="display:none;" alt="{translate key='LOADING'}" />
                    <img class="cursorpointer" id="sml{$smiley.smiley_id}" onclick="Xoops.changeStatus( 'smilies.php', { op: 'smilies_update_display', smiley_id: {$smiley.smiley_id}, XOOPS_TOKEN_REQUEST: '{$token}' }, 'sml{$smiley.smiley_id}', 'smilies.php' )" src="{if $smiley.smiley_display}{xoAdminIcons 'success.png'}{else}{xoAdminIcons 'cancel.png'}{/if}" alt="{if $smiley.smiley_display}{$smarty.const._AM_SMILIES_OFF}{else}{$smarty.const._AM_SMILIES_ON}{/if}" title="{if $smiley.smiley_display}{$smarty.const._AM_SMILIES_OFF}{else}{$smarty.const._AM_SMILIES_ON}{/if}" />
                </td>
                <td class="xo-actions txtcenter width10">
                    <a href="smilies.php?op=edit&amp;smiley_id={$smiley.smiley_id}" title="{$smarty.const._AM_SMILIES_EDIT}">
                    <img src="{xoAdminIcons 'edit.png'}" alt="{$smarty.const._AM_SMILIES_EDIT}">
                    </a>
                    <a href="smilies.php?op=del&amp;smiley_id={$smiley.smiley_id}" title="{$smarty.const._AM_SMILIES_DELETE}">
                    <img src="{xoAdminIcons 'delete.png'}" alt="{$smarty.const._AM_SMILIES_DELETE}">
                    </a>
                </td>
            </tr>
        {/foreach}
    </tbody>
    </table>

    <!-- Display smilies navigation -->
    <div class="clear spacer"></div>
    {if $nav_menu|default:false}
        <div class="xo-smilies-pagenav floatright">{$nav_menu}</div>
        <div class="clear spacer"></div>
    {/if}
{/if}

<!-- Display smilies form (add,edit) -->
{$form|default:''}
