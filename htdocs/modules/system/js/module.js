/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

/**
 * Modules Javascript
 *
 * @copyright   XOOPS Project (http://xoops.org)
 * @license     GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author      Andricq Nicolas (AKA MusS)
 * @version     $Id$
 */

$(function() {
    $("a.card-view").click(function(){
        $('.xo-content-table').fadeOut('fast');
        $('.xo-content-table').addClass('hide');
        setTimeout(function(){
            $('.xo-content-card').fadeIn('fast');
            $('.xo-content-card').removeClass('hide');
            $('a.card-view').addClass('disabled');
            $('a.table-view').removeClass('disabled');
            system_eraseCookie('xoopsModsView');
            system_createCookie('xoopsModsView', 'large', 365);
        }, 1500);
        return false;
    });
    $("a.table-view").click(function(){
        $('.xo-content-card').fadeOut('fast');
        $('.xo-content-card').addClass('hide');
        setTimeout(function(){
            $('.xo-content-table').fadeIn('fast');
            $('.xo-content-table').removeClass('hide');
            $('a.table-view').addClass('disabled');
            $('a.card-view').removeClass('disabled');
            system_eraseCookie('xoopsModsView');
            system_createCookie('xoopsModsView', 'list', 365);
        }, 1500);
        return false;
    });

    $(".rename").editable("admin.php?fct=modulesadmin&op=rename", {
        indicator : "<img src='../../media/xoops/images/spinner.gif'>",
        cssclass : 'span2'
    });

    $(".modal-backdrop").click(function(){
        $("#install .update-data").html('');
        $('#install').hide('slow');
        $("#update .update-data").html('');
        $("#update").hide('slow');
        $("#uninstall .update-data").html('');
        $('#uninstall').hide('slow');
        $(".modal-backdrop").hide();
    });
    if('function' == typeof($("").sortable)){
    $('#xo-module-sort').sortable({
        placeholder: 'ui-state-highlight',
        update: function(event, ui) {
            var list = $(this).sortable( 'serialize');
            $.post( 'admin.php?fct=modulesadmin&op=order', list + '&XOOPS_TOKEN_REQUEST=' + XOOPS_TOKEN );
        }}
    );
    $( "#sortable" ).disableSelection();
    };

    var view = system_readCookie('xoopsModsView');
    if (view) module_switchModsView(view);
});

function module_switchModsView (c)
{
    switch(c) {
        case 'large': default:
            $('a.card-view').click();
            break;
        case 'list':
            $('a.table-view').click();
            break;
    }
}

function module_Detail(id){
    var position = $("#mid-"+id).position();
    $('#detail-'+id).css({'position':'absolute','box-shadow':'2px 2px 1px #888','top':position.top+'px','left':position.left+'px'});
    $('#detail-'+id).removeClass('hide');
    $('#detail-'+id).slideDown(600);
}

function module_Disable(id, enable, disable){
    $.post( 'admin.php', { fct: 'modulesadmin', op: 'active', mid: id, XOOPS_TOKEN_REQUEST: XOOPS_TOKEN } ,
    function(reponse, textStatus) {
        if (textStatus=='success') {
            if(reponse){
                $('#active-'+id).html('<span class="icon icon-tick"></span>'+enable);
                $('#active-table-'+id).html('<span class="icon icon-tick">&nbsp;</span>');

            } else {
                $('#active-'+id).html('<span class="icon icon-cross"></span>'+disable);
                $('#active-table-'+id).html('<span class="icon icon-cross">&nbsp;</span>');
            }
        }
    });
}

function module_Hide(id){
    $.post( 'admin.php', { fct: 'modulesadmin', op: 'display_in_menu', mid: id, XOOPS_TOKEN_REQUEST: XOOPS_TOKEN } ,
    function(reponse, textStatus) {
        if (textStatus=='success') {
            if(reponse != 0){
                $('#menu-hide-'+id).html('<span class="cursorpointer icon icon-cross">&nbsp;</span>');
            } else {
                $('#menu-hide-'+id).html('<span class="cursorpointer icon icon-tick">&nbsp;</span>');
            }
        }
    });
}

function module_Install(module){
    $('.modal-backdrop').show();
    $('#install-dir').val(module)
    $('#install .modal-data').html($('#data-'+module+' .module_card').html());
    $('#install').removeClass('hide');
    $('#install').show('slow');
}

function module_Update(id){
    $('.modal-backdrop').show();
    $('#update-id').val(id)
    $('#update .modal-data').html($('#data-'+id+' .module_card').html());
    $('#update').removeClass('hide');
    $('#update').show('slow');
}

function module_Uninstall(id){
    $('.modal-backdrop').show();
    $('#uninstall-id').val(id)
    $('#uninstall .modal-data').html($('#data-'+id+' .module_card').html());
    $('#uninstall').removeClass('hide');
    $('#uninstall').show('slow');
}
