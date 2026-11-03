<div>
    <h4>{translate key='PRIVATE_MESSAGES'}</h4>
</div>
{$breadcrumbs|default:''}
{if $message|default:false}
    <form name="{$pmform.name}" id="{$pmform.name}" action="{$pmform.action}" method="{$pmform.method}" {$pmform.extra} >
        <div class="row">
            <div class="col-md-4">
                {if $op==out}{translate key='C_TO'}{else}{translate key="C_FROM"}{/if}
                {if $poster|default:false}
                    <a href='{$xoops_url}/userinfo.php?uid={$poster->getVar("uid")}'>{$poster->getVar("uname")}</a><br />
                    {if ( $poster_avatar != "" ) }
                        <img src='{$poster_avatar}' alt='' /><br />
                    {/if}
                    {if ( $poster->getVar("user_from") != "" ) }
                        {translate key='C_FROM'}{$poster->getVar("user_from")}<br /><br />
                    {/if}
                    {if ( $poster->isOnline() ) }
                        <span class='bold red'>{translate key='ONLINE'}</span><br /><br />
                    {/if}
                {else}
                    {$anonymous}
                {/if}
            </div>
            <div class="col-md-8">
                <h4>{if $message.msg_image != ""}<img src='{$xoops_url}/images/subject/{$message.msg_image|escape:'html'}' alt='' />{/if}
                    {$message.subject}
                </h4>
                <div class="text-muted text-right"><small>{translate key='C_SENT'}&nbsp;{$message.msg_time|datetime}</small></div>
                {$message.msg_text}
            </div>
        </div>
        <div class="row">
            <div class="col-md-8">
                <br />
                {foreach item=element from=$pmform.elements}
                    {$element.body}
                {/foreach}
            </div>
        </div>
        <div class="row">
            <div class="col-md-8">
                <ul class="pager">
                    {if ( $previous >= 0 ) }
                        <li>
                            <a href='readpmsg.php?start={$previous}&amp;total_messages={$total_messages}&amp;op={$op}' title='{translate key="PREVIOUS_MESSAGE"}'>
                                {translate key='PREVIOUS_MESSAGE'}
                            </a>
                        </li>
                    {else}
                        <li class="disabled">
                            <a href="#">{translate key='PREVIOUS_MESSAGE'}</a>
                        </li>
                    {/if}
                    {if ( $next < $total_messages ) }
                        <li>
                            <a href='readpmsg.php?start={$next}&amp;total_messages={$total_messages}&amp;op={$op}' title='{translate key="NEXT_MESSAGE"}'>
                                {translate key="NEXT_MESSAGE"}
                            </a>
                        </li>
                    {else}
                        <li class="disabled">
                            <a href="#">{translate key="NEXT_MESSAGE"}</a>
                        </li>
                    {/if}
                </ul>
            </div>
        </div>
    </form>
{else}
    <br /><br />{translate key='E_YOU_DO_NOT_HAVE_ANY_PRIVATE_MESSAGE'}
{/if}
