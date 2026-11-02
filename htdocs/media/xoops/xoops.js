/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * Xoops Javascript class
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         media
 * @since           2.6.0
 * @author          Andricq Nicolas (AKA MusS)
 * @version         $Id$
 */

var Xoops = {

    ImgAccept:'',
    ImgCancel:'',
    TextAccept:'',
    TextCancel:'',

    /**
     * Function for send an ajax request and change status icon and text
     * @param post
     * @param data
     * @param id
     */
    changeStatus:function (post, data, id) {
        $.post(post, data,
            function (reponse, textStatus) {
                if (textStatus == 'success') {
                    $('img#' + id).hide();
                    $('#loading_' + id).show();
                    setTimeout(function () {
                        $('#loading_' + id).hide();
                        $('img#' + id).fadeIn('fast');
                        // Change image attribute
                        if ($('img#' + id).attr("src") == Xoops.ImgAccept) {
                            $('img#' + id).attr("src", Xoops.ImgCancel);
                            $('img#' + id).attr("alt", Xoops.TextCancel);
                            $('img#' + id).attr("title", Xoops.TextCancel);
                        } else {
                            $('img#' + id).attr("src", Xoops.ImgAccept);
                            $('img#' + id).attr("alt", Xoops.TextAccept);
                            $('img#' + id).attr("title", Xoops.TextAccept);
                        }
                    }, 500);

                }
            });
    },

    msgQuestion:function(id) {
        $( "#"+id ).dialog({
        			resizable: false,
        			height:140,
        			modal: true,
        			buttons: {
        				"Delete all items": function() {
        					$( this ).dialog( "close" );
        				},
        				Cancel: function() {
        					$( this ).dialog( "close" );
        				}
        			}
        		});
    },

    /**
     * Define image status URL
     * @param type
     * @param url
     */
    setStatusImg:function (type, url) {
        switch (type) {
            case 'accept':
                this.ImgAccept = url;
                break;
            case 'cancel':
                this.ImgCancel = url;
                break;
        }
    },

    setStatusText:function (type, url) {
        switch (type) {
            case 'accept':
                this.TextAccept = url;
                break;
            case 'cancel':
                this.TextCancel = url;
                break;
        }
    }

};

// An admin toggle was refused by Security::requireAjaxToken(): its page token has
// expired, so nothing was saved. Reload to show the real state and get a fresh
// token, instead of leaving a changed icon or sorted list on screen.
if (window.jQuery) {
    jQuery(document).ajaxError(function (event, xhr) {
        if (xhr.status === 403 && xhr.getResponseHeader('X-Xoops-Token') === 'rejected') {
            window.location.reload();
        }
    });
}
