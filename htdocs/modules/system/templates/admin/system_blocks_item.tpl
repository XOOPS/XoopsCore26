<{foreach item=item from=$blocks}>
<{if $item.side == $side}>
<div id="blk_<{$item.bid}>" bid="<{$item.bid}>" side="<{$item.side}>" order="<{$item.weight}>" class="xo-block ui-widget ui-widget-content ui-corner-all">
    <div class="xo-blocktitle ui-corner-all">
        <span class="spacer">
            <img class="xo-imgmini" src="<{xoAdminIcons 'block.png'}>" alt="<{translate key='DRAG_OR_SORT_BLOCK' dirname='system'}>" title="<{translate key='DRAG_OR_SORT_BLOCK' dirname='system'}>" />
        </span>
        <{$item.title}>
    </div>
    <div class="xo-blockaction xo-actions">
        <img id="loading_img<{$item.bid}>" src="./images/mimetypes/spinner.gif" style="display:none;" title="<{translate key='LOADING'}>" alt="<{translate key='LOADING'}>" />
        <img class="xo-tooltip" id="img<{$item.bid}>" onclick="system_setStatus( { fct: 'blocksadmin', op: 'display', bid: <{$item.bid}>, visible: <{if $item.visible}>0<{else}>1<{/if}>, XOOPS_TOKEN_REQUEST: XOOPS_TOKEN }, 'img<{$item.bid}>', 'admin.php' )"
             src="<{if $item.visible}><{xoAdminIcons 'success.png'}><{else}><{xoAdminIcons 'cancel.png'}><{/if}>" alt="<{if $item.visible}><{translate key='HIDE_BLOCK' dirname='system'}><{else}><{translate key='DISPLAY_BLOCK' dirname='system'}><{/if}>&nbsp;<{$item.name}>" title="<{if $item.visible}><{translate key='HIDE_BLOCK' dirname='system'}><{else}><{translate key='DISPLAY_BLOCK' dirname='system'}><{/if}>&nbsp;<{$item.name}>" />
        <a class="xo-tooltip" href="admin.php?fct=blocksadmin&amp;op=edit&amp;bid=<{$item.bid}>" title="<{translate key='A_EDIT'}>">
            <img src="<{xoAdminIcons 'edit.png'}>" alt="<{translate key='A_EDIT'}>" />
        </a>
        <{if $item.block_type != 'S'}>
        <a class="xo-tooltip" href="admin.php?fct=blocksadmin&amp;op=delete&amp;bid=<{$item.bid}>" title="<{translate key='A_DELETE'}>">
            <img src="<{xoAdminIcons 'delete.png'}>" alt="<{translate key='A_DELETE'}>" />
        </a>
        <{/if}>
        <a class="xo-tooltip" href="admin.php?fct=blocksadmin&amp;op=clone&amp;bid=<{$item.bid}>" title="<{translate key='A_CLONE'}>">
            <img src="<{xoAdminIcons 'clone.png'}>" alt="<{translate key='A_CLONE'}>" />
        </a>
    </div>
</div>
<{/if}>
<{/foreach}>