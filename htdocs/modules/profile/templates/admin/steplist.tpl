<script type="text/javascript">
    Xoops.setStatusImg('accept', '{xoAdminIcons 'success.png'}');
    Xoops.setStatusImg('cancel', '{xoAdminIcons 'cancel.png'}');
    Xoops.setStatusText('accept', '{translate key="NO"}');
    Xoops.setStatusText('cancel', '{translate key="YES"}');
</script>
{include file="admin:system/admin_navigation.tpl"}
{include file="admin:system/admin_buttons.tpl"}
{if $step|default:false}
<table class="outer">
    <thead>
        <tr>
            <th>{$smarty.const._PROFILE_AM_STEPNAME}</th>
            <th class="txtleft">{$smarty.const._PROFILE_AM_DESCRIPTION}</th>
            <th>{$smarty.const._PROFILE_AM_STEPORDER}</th>
            <th>{$smarty.const._PROFILE_AM_STEPSAVE}</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    {foreach item=step from=$steps}
        <tr class="{cycle values='odd, even'}">
            <td class="txtcenter width20">{$step.step_name}</td>
            <td class="txtleft">{$step.step_desc}</td>
            <td class="txtcenter width10">{$step.step_order}</td>
            <td class="xo-actions txtcenter width10">
                <img id="loading_sml{$step.step_id}" src="{xoAppUrl 'media/xoops/images/spinner.gif'}" style="display:none;" alt="{translate key='LOADING'}" />
                <img class="cursorpointer" id="sml{$step.step_id}"
                     onclick="Xoops.changeStatus( 'step.php', { op: 'step_update', id: {$step.step_id}, XOOPS_TOKEN_REQUEST: '{$token}' }, 'sml{$step.step_id}', 'step.php' )"
                     src="{if $step.step_save}{xoAdminIcons 'success.png'}{else}{xoAdminIcons 'cancel.png'}{/if}"
                     alt="{if $step.step_save}{translate key='NO'}{else}{translate key='YES'}{/if}"
                     title="{if $step.step_save}{translate key='NO'}{else}{translate key='YES'}{/if}"/>

            </td>
            <td class="xo-actions txtcenter width5">
                <a href="step.php?op=edit&amp;id={$step.step_id}" title="{translate key ='A_EDIT'}">
                    <img src="{xoAdminIcons 'edit.png'}" alt="{translate key ='A_EDIT'}">
                </a>
                <a href="step.php?op=delete&amp;id={$step.step_id}" title="{translate key ='A_DELETE'}">
                    <img src="{xoAdminIcons 'delete.png'}" alt="{translate key ='A_DELETE'}">
                </a>
            </td>
        </tr>
    {/foreach}
    </tbody>
</table>
{/if}
<!-- Display form (add,edit) -->
{$form|default:''}
