{$profile_breadcrumbs}

<div>
    {if $avatar}
        <div class="floatleft pad5">
            <img src="{$avatar}" alt="{$uname}" />
        </div>
    {/if}
    <div class="floatleft pad10 block">
        <strong>{$uname}</strong>
        {if $email}
            {$email} <br />
        {/if}
        {if !$user_ownpage && $xoops_isuser == true}
        <form name="usernav" action="user.php" method="post">
            <input class="btn btn-default" type="button" value="{$smarty.const._PROFILE_MA_SENDPM}" onclick="javascript:openWithSelfMain('{$xoops_url}/pmlite.php?send2=1&amp;to_userid={$user_uid}', 'pmlite', 450, 380);" />
        </form>
        {/if}
    </div>
</div>
<br class="clear"/>

{if $user_ownpage == true}
<div class="floatleft pad5">
    <form name="usernav" action="user.php" method="post">
        <input class="btn btn-default" type="button" value="{$lang_editprofile}" onclick="location='{$xoops_url}/modules/{$xoops_dirname}/edituser.php'" />
        <input class="btn btn-default" type="button" value="{$lang_changepassword}" onclick="location='{$xoops_url}/modules/{$xoops_dirname}/changepass.php'" />
        {if $user_changeemail}
            <input class="btn btn-default" type="button" value="{$smarty.const._PROFILE_MA_CHANGEMAIL}" onclick="location='{$xoops_url}/modules/{$xoops_dirname}/changemail.php'" />
        {/if}

        {if $user_candelete == true}
            <form method="post" action="{$xoops_url}/modules/{$xoops_dirname}/user.php">
                <input type="hidden" name="op" value="delete">
                <input type="hidden" name="uid" value="{$user_uid}">
                <input class="btn btn-danger" type="button" value="{$lang_deleteaccount}" onclick="submit();" />
            </form>
        {/if}
        {foreach item=button from=$btn}
        <input class="btn btn-default" type="button" value="{$button.title}" onclick="location='{$button.link}'" />
        {/foreach}
        <input class="btn btn-default" type="button" value="{$lang_inbox}" onclick="location='{$xoops_url}/viewpmsg.php'" />
        <input class="btn btn-default" type="button" value="{$lang_logout}" onclick="location='{$xoops_url}/modules/{$xoops_dirname}/user.php?op=logout'" />
    </form>
</div>
{elseif $xoops_isadmin != false}
<div class="floatleft pad5">
        <form method="post" action="{$xoops_url}/modules/{$xoops_dirname}/admin/deactivate.php">
        <input class="btn btn-default" type="button" value="{$lang_editprofile}" onclick="location='{$xoops_url}/modules/{$xoops_dirname}/admin/user.php?op=edit&amp;id={$user_uid}'" />
        <input type="hidden" name="uid" value="{$user_uid}" />
        {$token}
        {if $userlevel == 1}
            <input type="hidden" name="level" value="0" />
            <input class="btn btn-default" type="button" value="{$smarty.const._PROFILE_MA_DEACTIVATE}" onclick="submit();" />
        {else}
            <input type="hidden" name="level" value="1" />
            <input class="btn btn-default" type="button" value="{$smarty.const._PROFILE_MA_ACTIVATE}" onclick="submit();" />
        {/if}
        </form>
</div>
{/if}

<br class="clear"/>

{foreach item=category from=$categories}
    {if isset($category.fields)}
        <div class="profile-list-category" id="profile-category-{$category.cat_id}">
            <table class="outer">
                <tr>
                  <th class="txtcenter" colspan="2">{$category.cat_title}</th>
                </tr>
                {foreach item=field from=$category.fields}
                    <tr>
                        <td class="head">{$field.title}</td>
                        <td class="even">{$field.value}</td>
                    </tr>
                {/foreach}
            </table>
        </div>
    {/if}
{/foreach}

{if $modules|default:false}
<br class="clear" />
<div class="profile-list-activity">
    <h2>{$recent_activity}</h2>
    <!-- start module search results loop -->
    {foreach item=module from=$modules}

    <h4>{$module.name}</h4>

      <!-- start results item loop -->
          {foreach item=result from=$module.results}

          <img src="{$result.image}" alt="{$module.name}" />&nbsp;<strong><a href="{$result.link}">{$result.title}</a></strong><br /><span class="x-small">({$result.time})</span><br />

          {/foreach}
          <!-- end results item loop -->

    {$module.showall_link}

    {/foreach}
    <!-- end module search results loop -->
</div>
{/if}
