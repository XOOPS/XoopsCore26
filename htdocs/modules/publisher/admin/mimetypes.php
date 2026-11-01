<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

use Xmf\Module\Helper\Session;
use XoopsModules\Publisher;
use XoopsModules\Publisher\Helper;

/**
 * @copyright       The XUUPS Project http://sourceforge.net/projects/xuups/
 * @license         GNU GPL V2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Admin
 * @subpackage      Action
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @author          The SmartFactory <www.smartfactory.ca>
 * @version         $Id$
 */
require_once __DIR__ . '/admin_header.php';

$start = $limit = 0;
if (isset($_REQUEST['limit'])) {
    $limit = (int)$_REQUEST['limit'];
} else {
    $limit = 15;
}
if (isset($_REQUEST['start'])) {
    $start = (int)$_REQUEST['start'];
}

$aSortBy = [
    'mime_id' => _AM_PUBLISHER_MIME_ID,
    'mime_name' => _AM_PUBLISHER_MIME_NAME,
    'mime_ext' => _AM_PUBLISHER_MIME_EXT,
    'mime_admin' => _AM_PUBLISHER_MIME_ADMIN,
    'mime_user' => _AM_PUBLISHER_MIME_USER,
];
$aOrderBy = ['ASC' => _AM_PUBLISHER_TEXT_ASCENDING, 'DESC' => _AM_PUBLISHER_TEXT_DESCENDING];
$aLimitBy = ['10' => 10, '15' => 15, '20' => 20, '25' => 25, '50' => 50, '100' => 100];
$aSearchBy = ['mime_id' => _AM_PUBLISHER_MIME_ID, 'mime_name' => _AM_PUBLISHER_MIME_NAME, 'mime_ext' => _AM_PUBLISHER_MIME_EXT];

$op = $_REQUEST['op'] ?? 'default';

// Every POST and the two link actions change data, so all need a token.
$xoops = Xoops::getInstance();
if ('POST' === \Xmf\Request::getMethod() || in_array($op, ['delete', 'updateMimeValue'], true)) {
    if (!$xoops->security()->check()) {
        $xoops->redirect(PUBLISHER_ADMIN_URL . '/mimetypes.php', 3, implode('<br />', $xoops->security()->getErrors()));
    }
}

switch ($op) {
    case 'add':
        add();
        break;
    case 'delete':
        delete();
        break;
    case 'edit':
        edit();
        break;
    case 'search':
        search();
        break;
    case 'updateMimeValue':
        updateMimeValue();
        break;
    case 'clearAddSession':
        clearAddSession();
        break;
    case 'clearEditSession':
        clearEditSession();
        break;
    case 'manage':
    default:
        manage();
        break;
}

function add()
{
    $helper = Helper::getInstance();
    $xoops = Xoops::getInstance();

    global $limit, $start;

    if (!isset($_POST['add_mime'])) {
        Publisher\Utils::cpHeader();

        Publisher\Utils::openCollapsableBar('mimemaddtable', 'mimeaddicon', _AM_PUBLISHER_MIME_ADD_TITLE);

        $session = new Session();
        $mime_type = $session->get('publisher_addMime');
        $mime_errors = $session->get('publisher_addMimeErr');

        //Display any form errors
        if (false === !$mime_errors) {
            Publisher\Utils::renderErrors($mime_errors, Publisher\Utils::makeURI(PUBLISHER_ADMIN_URL . 'mimetypes.php', ['op' => 'clearAddSession']));
        }

        if (false === $mime_type) {
            $mime_ext = '';
            $mime_name = '';
            $mime_types = '';
            $mime_admin = 1;
            $mime_user = 1;
        } else {
            $mime_ext = $mime_type['mime_ext'];
            $mime_name = $mime_type['mime_name'];
            $mime_types = $mime_type['mime_types'];
            $mime_admin = $mime_type['mime_admin'];
            $mime_user = $mime_type['mime_user'];
        }

        // Display add form
        echo "<form action='mimetypes.php?op=add' method='post'>"; echo $xoops->security()->getTokenHTML();
        echo "<table width='100%' cellspacing='1' class='outer'>";
        echo "<tr><th colspan='2'>" . _AM_PUBLISHER_MIME_CREATEF . '</th></tr>';
        echo "<tr valign='top'>
        <td class='head'>" . _AM_PUBLISHER_MIME_EXTF . "</td>
        <td class='even'><input type='text' name='mime_ext' id='mime_ext' value='$mime_ext' size='5'></td>
        </tr>";
        echo "<tr valign='top'>
        <td class='head'>" . _AM_PUBLISHER_MIME_NAMEF . "</td>
        <td class='even'><input type='text' name='mime_name' id='mime_name' value='$mime_name'></td>
        </tr>";
        echo "<tr valign='top'>
        <td class='head'>" . _AM_PUBLISHER_MIME_TYPEF . "</td>
        <td class='even'><textarea name='mime_types' id='mime_types' cols='60' rows='5'>$mime_types</textarea></td>
        </tr>";
        echo "<tr valign='top'>
        <td class='head'>" . _AM_PUBLISHER_MIME_ADMINF . "</td>
        <td class='even'>";
        echo "<input type='radio' name='mime_admin' value='1' " . (1 == $mime_admin ? "checked" : '') . '>' . XoopsLocale::YES;
        echo "<input type='radio' name='mime_admin' value='0' " . (0 == $mime_admin ? "checked" : '') . '>' . XoopsLocale::NO . '
        </td>
        </tr>';
        echo "<tr valign='top'>
        <td class='head'>" . _AM_PUBLISHER_MIME_USERF . "</td>
        <td class='even'>";
        echo "<input type='radio' name='mime_user' value='1'" . (1 == $mime_user ? "checked" : '') . '>' . XoopsLocale::YES;
        echo "<input type='radio' name='mime_user' value='0'" . (0 == $mime_user ? "checked" : '') . '>' . XoopsLocale::NO . '
        </td>
        </tr>';
        echo "<tr valign='top'>
        <td class='head'></td>
        <td class='even'>
        <input type='submit' name='add_mime' id='add_mime' value='" . _AM_PUBLISHER_BUTTON_SUBMIT . "' class='formButton'>
        <input type='button' name='cancel' value='" . _AM_PUBLISHER_BUTTON_CANCEL . "' onclick='history.go(-1)' class='formButton'>
        </td>
        </tr>";
        echo '</table></form>';
        // end of add form

        // Find new mimetypes table
        echo "<form action='http://www.filext.com' method='post'>";
        echo "<table width='100%' cellspacing='1' class='outer'>";
        echo "<tr><th colspan='2'>" . _AM_PUBLISHER_MIME_FINDMIMETYPE . '</th></tr>';

        echo "<tr class='foot'>
        <td colspan='2'><input type='submit' name='find_mime' id='find_mime' value='" . _AM_PUBLISHER_MIME_FINDIT . "' class='formButton'></td>
        </tr>";

        echo '</table></form>';

        Publisher\Utils::closeCollapsableBar('mimeaddtable', 'mimeaddicon');

        $xoops->footer();
    } else {
        $has_errors = false;
        $error = [];
        $mime_ext = $_POST['mime_ext'];
        $mime_name = $_POST['mime_name'];
        $mime_types = $_POST['mime_types'];
        $mime_admin = (int)$_POST['mime_admin'];
        $mime_user = (int)$_POST['mime_user'];

        //Validate Mimetype entry
        if ('' === trim($mime_ext)) {
            $has_errors = true;
            $error['mime_ext'][] = _AM_PUBLISHER_VALID_ERR_MIME_EXT;
        }

        if ('' === trim($mime_name)) {
            $has_errors = true;
            $error['mime_name'][] = _AM_PUBLISHER_VALID_ERR_MIME_NAME;
        }

        if ('' === trim($mime_types)) {
            $has_errors = true;
            $error['mime_types'][] = _AM_PUBLISHER_VALID_ERR_MIME_TYPES;
        }

        if ($has_errors) {
            $session = new Session();
            $mime = [];
            $mime['mime_ext'] = $mime_ext;
            $mime['mime_name'] = $mime_name;
            $mime['mime_types'] = $mime_types;
            $mime['mime_admin'] = $mime_admin;
            $mime['mime_user'] = $mime_user;
            $session->set('publisher_addMime', $mime);
            $session->set('publisher_addMimeErr', $error);
            header('Location: ' . Publisher\Utils::makeURI(PUBLISHER_ADMIN_URL . 'mimetypes.php', ['op' => 'add'], false));
        }

        $mimetype = $helper->getMimetypeHandler()->create();
        $mimetype->setVar('mime_ext', $mime_ext);
        $mimetype->setVar('mime_name', $mime_name);
        $mimetype->setVar('mime_types', $mime_types);
        $mimetype->setVar('mime_admin', $mime_admin);
        $mimetype->setVar('mime_user', $mime_user);

        if (!$helper->getMimetypeHandler()->insert($mimetype)) {
            $xoops->redirect(PUBLISHER_ADMIN_URL . "/mimetypes.php?op=manage&limit=$limit&start=$start", 3, _AM_PUBLISHER_MESSAGE_ADD_MIME_ERROR);
        } else {
            _clearAddSessionVars();
            header('Location: ' . PUBLISHER_ADMIN_URL . "/mimetypes.php?op=manage&limit=$limit&start=$start");
        }
    }
}

function delete()
{
    $helper = Helper::getInstance();
    $xoops = Xoops::getInstance();
    global $start, $limit;
    if (!isset($_REQUEST['id'])) {
        $xoops->redirect(PUBLISHER_ADMIN_URL . '/mimetypes.php', 3, _AM_PUBLISHER_MESSAGE_NO_ID);
    }
    $mime_id = (int)$_REQUEST['id'];

    $mimetype = $helper->getMimetypeHandler()->get($mime_id); // Retrieve mimetype object
    if (!$helper->getMimetypeHandler()->delete($mimetype, true)) {
        $xoops->redirect(PUBLISHER_ADMIN_URL . "/mimetypes.php?op=manage&id=$mime_id&limit=$limit&start=$start", 3, _AM_PUBLISHER_MESSAGE_DELETE_MIME_ERROR);
    } else {
        $xoops->redirect(PUBLISHER_ADMIN_URL . "/mimetypes.php?op=manage&limit=$limit&start=$start", 3, 'Deleted');
    }
}

function edit()
{
    $helper = Helper::getInstance();
    $xoops = Xoops::getInstance();
    global $start, $limit, $oAdminButton;

    if (!isset($_REQUEST['id'])) {
        $xoops->redirect(PUBLISHER_ADMIN_URL . '/mimetypes.php', 3, _AM_PUBLISHER_MESSAGE_NO_ID);
    }
    $mime_id = (int)$_REQUEST['id'];

    $mimetype = $helper->getMimetypeHandler()->get($mime_id); // Retrieve mimetype object

    if (!isset($_POST['edit_mime'])) {
        $session = new Session();
        $mime_type = $session->get("publisher_editMime_$mime_id");
        $mime_errors = $session->get("publisher_editMimeErr_$mime_id");

        // Display header
        Publisher\Utils::cpHeader();
        //publisher_adminMenu(4, _AM_PUBLISHER_MIMETYPES . " > " . _AM_PUBLISHER_BUTTON_EDIT);

        Publisher\Utils::openCollapsableBar('mimemedittable', 'mimeediticon', _AM_PUBLISHER_MIME_EDIT_TITLE);

        //Display any form errors
        if (false === !$mime_errors) {
            Publisher\Utils::renderErrors($mime_errors, Publisher\Utils::makeURI(PUBLISHER_ADMIN_URL . '/mimetypes.php', ['op' => 'clearEditSession', 'id' => $mime_id]));
        }

        if (false === $mime_type) {
            $mime_ext = $mimetype->getVar('mime_ext');
            $mime_name = $mimetype->getVar('mime_name', 'e');
            $mime_types = $mimetype->getVar('mime_types', 'e');
            $mime_admin = $mimetype->getVar('mime_admin');
            $mime_user = $mimetype->getVar('mime_user');
        } else {
            $mime_ext = $mime_type['mime_ext'];
            $mime_name = $mime_type['mime_name'];
            $mime_types = $mime_type['mime_types'];
            $mime_admin = $mime_type['mime_admin'];
            $mime_user = $mime_type['mime_user'];
        }

        // Display edit form
        echo "<form action='mimetypes.php?op=edit&amp;id=" . $mime_id . "' method='post'>"; echo $xoops->security()->getTokenHTML();
        echo "<input type='hidden' name='limit' value='" . $limit . "'>";
        echo "<input type='hidden' name='start' value='" . $start . "'>";
        echo "<table width='100%' cellspacing='1' class='outer'>";
        echo "<tr><th colspan='2'>" . _AM_PUBLISHER_MIME_MODIFYF . '</th></tr>';
        echo "<tr valign='top'>
        <td class='head'>" . _AM_PUBLISHER_MIME_EXTF . "</td>
        <td class='even'><input type='text' name='mime_ext' id='mime_ext' value='$mime_ext' size='5'></td>
        </tr>";
        echo "<tr valign='top'>
        <td class='head'>" . _AM_PUBLISHER_MIME_NAMEF . "</td>
        <td class='even'><input type='text' name='mime_name' id='mime_name' value='$mime_name'></td>
        </tr>";
        echo "<tr valign='top'>
        <td class='head'>" . _AM_PUBLISHER_MIME_TYPEF . "</td>
        <td class='even'><textarea name='mime_types' id='mime_types' cols='60' rows='5'>$mime_types</textarea></td>
        </tr>";
        echo "<tr valign='top'>
        <td class='head'>" . _AM_PUBLISHER_MIME_ADMINF . "</td>
        <td class='even'>
        <input type='radio' name='mime_admin' value='1' " . (1 == $mime_admin ? "checked" : '') . '>' . XoopsLocale::YES . "
        <input type='radio' name='mime_admin' value='0' " . (0 == $mime_admin ? "checked" : '') . '>' . XoopsLocale::NO . '
        </td>
        </tr>';
        echo "<tr valign='top'>
        <td class='head'>" . _AM_PUBLISHER_MIME_USERF . "</td>
        <td class='even'>
        <input type='radio' name='mime_user' value='1' " . (1 == $mime_user ? "checked" : '') . '>' . XoopsLocale::YES . "
        <input type='radio' name='mime_user' value='0' " . (0 == $mime_user ? "checked" : '') . '>' . XoopsLocale::NO . '
        </td>
        </tr>';
        echo "<tr valign='top'>
        <td class='head'></td>
        <td class='even'>
        <input type='submit' name='edit_mime' id='edit_mime' value='" . _AM_PUBLISHER_BUTTON_UPDATE . "' class='formButton'>
        <input type='button' name='cancel' value='" . _AM_PUBLISHER_BUTTON_CANCEL . "' onclick='history.go(-1)' class='formButton'>
        </td>
        </tr>";
        echo '</table></form>';
        // end of edit form
        Publisher\Utils::closeCollapsableBar('mimeedittable', 'mimeediticon');
        $xoops->footer();
    } else {
        $mime_admin = 0;
        $mime_user = 0;
        $has_errors = false;
        $error = [];
        if (isset($_POST['mime_admin']) && 1 == $_POST['mime_admin']) {
            $mime_admin = 1;
        }
        if (isset($_POST['mime_user']) && 1 == $_POST['mime_user']) {
            $mime_user = 1;
        }

        //Validate Mimetype entry
        if ('' === trim($_POST['mime_ext'])) {
            $has_errors = true;
            $error['mime_ext'][] = _AM_PUBLISHER_VALID_ERR_MIME_EXT;
        }

        if ('' === trim($_POST['mime_name'])) {
            $has_errors = true;
            $error['mime_name'][] = _AM_PUBLISHER_VALID_ERR_MIME_NAME;
        }

        if ('' === trim($_POST['mime_types'])) {
            $has_errors = true;
            $error['mime_types'][] = _AM_PUBLISHER_VALID_ERR_MIME_TYPES;
        }

        if ($has_errors) {
            $session = new Session();
            $mime = [];
            $mime['mime_ext'] = $_POST['mime_ext'];
            $mime['mime_name'] = $_POST['mime_name'];
            $mime['mime_types'] = $_POST['mime_types'];
            $mime['mime_admin'] = $mime_admin;
            $mime['mime_user'] = $mime_user;
            $session->set('publisher_editMime_' . $mime_id, $mime);
            $session->set('publisher_editMimeErr_' . $mime_id, $error);
            header('Location: ' . Publisher\Utils::makeURI(PUBLISHER_ADMIN_URL . '/mimetypes.php', ['op' => 'edit', 'id' => $mime_id], false));
        }

        $mimetype->setVar('mime_ext', $_POST['mime_ext']);
        $mimetype->setVar('mime_name', $_POST['mime_name']);
        $mimetype->setVar('mime_types', $_POST['mime_types']);
        $mimetype->setVar('mime_admin', $mime_admin);
        $mimetype->setVar('mime_user', $mime_user);

        if (!$helper->getMimetypeHandler()->insert($mimetype, true)) {
            $xoops->redirect(PUBLISHER_ADMIN_URL . "/mimetypes.php?op=edit&id=$mime_id", 3, _AM_PUBLISHER_MESSAGE_EDIT_MIME_ERROR);
        } else {
            _clearEditSessionVars($mime_id);
            header('Location: ' . PUBLISHER_ADMIN_URL . "/mimetypes.php?op=manage&limit=$limit&start=$start");
        }
    }
}

function manage()
{
    $xoops = Xoops::getInstance();
    $helper = Helper::getInstance();

    global $start, $limit, $aSortBy, $aOrderBy, $aLimitBy, $aSearchBy;
    $linkToken = $xoops->security()->createToken();

    if (isset($_POST['deleteMimes'])) {
        $aMimes = $_POST['mimes'];

        $crit = new Criteria('mime_id', '(' . implode(',', array_map('intval', (array) $aMimes)) . ')', 'IN');

        if ($helper->getMimetypeHandler()->deleteAll($crit)) {
            header('Location: ' . PUBLISHER_ADMIN_URL . "/mimetypes.php?limit=$limit&start=$start");
        } else {
            $xoops->redirect(PUBLISHER_ADMIN_URL . "/mimetypes.php?limit=$limit&start=$start", 3, _AM_PUBLISHER_MESSAGE_DELETE_MIME_ERROR);
        }
    }
    if (isset($_POST['add_mime'])) {
        header('Location: ' . PUBLISHER_ADMIN_URL . "/mimetypes.php?op=add&start=$start&limit=$limit");
        exit();
    }
    if (isset($_POST['mime_search'])) {
        header('Location: ' . PUBLISHER_ADMIN_URL . '/mimetypes.php?op=search');
        exit();
    }

    Publisher\Utils::cpHeader();
    ////publisher_adminMenu(4, _AM_PUBLISHER_MIMETYPES);
    Publisher\Utils::openCollapsableBar('mimemanagetable', 'mimemanageicon', _AM_PUBLISHER_MIME_MANAGE_TITLE, _AM_PUBLISHER_MIME_INFOTEXT);
    $crit = new CriteriaCompo();
    $order = $_REQUEST['order'] ?? 'ASC';
    $sort = $_REQUEST['sort'] ?? 'mime_ext';
    $crit->setOrder($order);
    $crit->setStart($start);
    $crit->setLimit($limit);
    $crit->setSort($sort);
    $mimetypes = $helper->getMimetypeHandler()->getObjects($crit); // Retrieve a list of all mimetypes
    $mime_count = $helper->getMimetypeHandler()->getCount();
    $nav = new XoopsPageNav($mime_count, $limit, $start, 'start', "op=manage&amp;limit=$limit");

    echo "<table width='100%' cellspacing='1' class='outer'>";
    echo "<tr><td colspan='6' align='right'>";
    echo "<form action='" . PUBLISHER_ADMIN_URL . "/mimetypes.php?op=search' style='margin:0; padding:0;' method='post'>"; echo $xoops->security()->getTokenHTML();
    echo '<table>';
    echo '<tr>';
    echo "<td align='right'>" . _AM_PUBLISHER_TEXT_SEARCH_BY . '</td>';
    echo "<td align='left'><select name='search_by'>";
    foreach ($aSearchBy as $value => $text) {
        ($sort == $value) ? $selected = "selected='selected'" : $selected = '';
        echo "<option value='$value' $selected>$text</option>";
    }
    echo '</select></td>';
    echo "<td align='right'>" . _AM_PUBLISHER_TEXT_SEARCH_TEXT . '</td>';
    echo "<td align='left'><input type='text' name='search_text' id='search_text' value=''></td>";
    echo "<td><input type='submit' name='mime_search' id='mime_search' value='" . _AM_PUBLISHER_BUTTON_SEARCH . "'></td>";
    echo '</tr></table></form></td></tr>';

    echo "<tr><td colspan='6'>";
    echo "<form action='" . PUBLISHER_ADMIN_URL . "/mimetypes.php?op=manage' style='margin:0; padding:0;' method='post'>"; echo $xoops->security()->getTokenHTML();
    echo "<table width='100%'>";
    echo "<tr><td align='right'>" . _AM_PUBLISHER_TEXT_SORT_BY . "
    <select name='sort'>";
    foreach ($aSortBy as $value => $text) {
        ($sort == $value) ? $selected = "selected='selected'" : $selected = '';
        echo "<option value='$value' $selected>$text</option>";
    }
    echo '</select>
    &nbsp;&nbsp;&nbsp;
    ' . _AM_PUBLISHER_TEXT_ORDER_BY . "
    <select name='order'>";
    foreach ($aOrderBy as $value => $text) {
        ($order == $value) ? $selected = "selected='selected'" : $selected = '';
        echo "<option value='$value' $selected>$text</option>";
    }
    echo '</select>
    &nbsp;&nbsp;&nbsp;
    ' . _AM_PUBLISHER_TEXT_NUMBER_PER_PAGE . "
    <select name='limit'>";
    foreach ($aLimitBy as $value => $text) {
        ($limit == $value) ? $selected = "selected='selected'" : $selected = '';
        echo "<option value='$value' $selected>$text</option>";
    }
    echo "</select>
    <input type='submit' name='mime_sort' id='mime_sort' value='" . _AM_PUBLISHER_BUTTON_SUBMIT . "'>
    </td>
    </tr>";
    echo '</table>';
    echo '</td></tr>';
    echo "<tr><th colspan='6'>" . _AM_PUBLISHER_MIME_MANAGE_TITLE . '</th></tr>';
    echo "<tr class='head'>
    <td>" . _AM_PUBLISHER_MIME_ID . '</td>
    <td>' . _AM_PUBLISHER_MIME_NAME . '</td>
    <td>' . _AM_PUBLISHER_MIME_EXT . '</td>
    <td>' . _AM_PUBLISHER_MIME_ADMIN . '</td>
    <td>' . _AM_PUBLISHER_MIME_USER . '</td>
    <td>' . _AM_PUBLISHER_MINDEX_ACTION . '</td>
    </tr>';
    foreach ($mimetypes as $mime) {
        echo mimeTableRow($mime, $limit, $start, $linkToken);
    }
    echo "<tr class='foot'>
    <td colspan='6' valign='top'>
    <a href='http://www.filext.com' style='float: right' target='_blank'>" . _AM_PUBLISHER_MIME_FINDMIMETYPE . "</a>
    <input type='checkbox' name='checkAllMimes' value='0' onclick='selectAll(this.form,\"mimes[]\",this.checked);'>
    <input type='submit' name='deleteMimes' id='deleteMimes' value='" . _AM_PUBLISHER_BUTTON_DELETE . "'>
    <input type='submit' name='add_mime' id='add_mime' value='" . _AM_PUBLISHER_MIME_CREATEF . "' class='formButton'>
    </td>
    </tr>";
    echo '</table>';
    echo "<div id='staff_nav'>" . $nav->renderNav() . '</div>';

    Publisher\Utils::closeCollapsableBar('mimemanagetable', 'mimemanageicon');

    $xoops->footer();
}

function search()
{
    $helper = Helper::getInstance();
    $xoops = Xoops::getInstance();

    global $limit, $start, $aSearchBy, $aOrderBy, $aLimitBy, $aSortBy;
    $linkToken = $xoops->security()->createToken();

    if (isset($_POST['deleteMimes'])) {
        $aMimes = $_POST['mimes'];

        $crit = new Criteria('mime_id', '(' . implode(',', array_map('intval', (array) $aMimes)) . ')', 'IN');

        if ($helper->getMimetypeHandler()->deleteAll($crit)) {
            header('Location: ' . PUBLISHER_ADMIN_URL . "/mimetypes.php?limit=$limit&start=$start");
        } else {
            $xoops->redirect(PUBLISHER_ADMIN_URL . "/mimetypes.php?limit=$limit&start=$start", 3, _AM_PUBLISHER_MESSAGE_DELETE_MIME_ERROR);
        }
    }
    if (isset($_POST['add_mime'])) {
        header('Location: ' . PUBLISHER_ADMIN_URL . "/mimetypes.php?op=add&start=$start&limit=$limit");
        exit();
    }
    $order = $_REQUEST['order'] ?? 'ASC';
    $sort = $_REQUEST['sort'] ?? 'mime_name';

    Publisher\Utils::cpHeader();
    //publisher_adminMenu(4, _AM_PUBLISHER_MIMETYPES . " > " . _AM_PUBLISHER_BUTTON_SEARCH);

    Publisher\Utils::openCollapsableBar('mimemsearchtable', 'mimesearchicon', _AM_PUBLISHER_MIME_SEARCH);

    if (!isset($_REQUEST['mime_search'])) {
        echo "<form action='mimetypes.php?op=search' method='post'>"; echo $xoops->security()->getTokenHTML();
        echo "<table width='100%' cellspacing='1' class='outer'>";
        echo "<tr><th colspan='2'>" . _AM_PUBLISHER_TEXT_SEARCH_MIME . '</th></tr>';
        echo "<tr><td class='head' width='20%'>" . _AM_PUBLISHER_TEXT_SEARCH_BY . "</td>
        <td class='even'>
        <select name='search_by'>";
        foreach ($aSortBy as $value => $text) {
            echo "<option value='$value'>$text</option>";
        }
        echo '</select>
        </td>
        </tr>';
        echo "<tr><td class='head'>" . _AM_PUBLISHER_TEXT_SEARCH_TEXT . "</td>
        <td class='even'>
        <input type='text' name='search_text' id='search_text' value=''>
        </td>
        </tr>";
        echo "<tr class='foot'>
        <td colspan='2'>
        <input type='submit' name='mime_search' id='mime_search' value='" . _AM_PUBLISHER_BUTTON_SEARCH . "'>
        </td>
        </tr>";
        echo '</table></form>';
    } else {
        $search_field = $_REQUEST['search_by'];
        $search_text = $_REQUEST['search_text'];

        $crit = new Criteria($search_field, "%$search_text%", 'LIKE');
        $crit->setSort($sort);
        $crit->setOrder($order);
        $crit->setLimit($limit);
        $crit->setStart($start);
        $mime_count = $helper->getMimetypeHandler()->getCount($crit);
        $mimetypes = $helper->getMimetypeHandler()->getObjects($crit);
        $nav = new XoopsPageNav($mime_count, $limit, $start, 'start', "op=search&amp;limit=$limit&amp;order=$order&amp;sort=$sort&amp;mime_search=1&amp;search_by=$search_field&amp;search_text=$search_text");
        // Display results
        echo '<script type="text/javascript" src="' . PUBLISHER_URL . '/include/functions.js"></script>';

        echo "<table width='100%' cellspacing='1' class='outer'>";
        echo "<tr><td colspan='6' align='right'>";
        echo "<form action='" . PUBLISHER_ADMIN_URL . "/mimetypes.php?op=search' style='margin:0; padding:0;' method='post'>"; echo $xoops->security()->getTokenHTML();
        echo '<table>';
        echo '<tr>';
        echo "<td align='right'>" . _AM_PUBLISHER_TEXT_SEARCH_BY . '</td>';
        echo "<td align='left'><select name='search_by'>";
        foreach ($aSearchBy as $value => $text) {
            ($search_field == $value) ? $selected = "selected='selected'" : $selected = '';
            echo "<option value='$value' $selected>$text</option>";
        }
        echo '</select></td>';
        echo "<td align='right'>" . _AM_PUBLISHER_TEXT_SEARCH_TEXT . '</td>';
        echo "<td align='left'><input type='text' name='search_text' id='search_text' value='$search_text'></td>";
        echo "<td><input type='submit' name='mime_search' id='mime_search' value='" . _AM_PUBLISHER_BUTTON_SEARCH . "'></td>";
        echo '</tr></table></form></td></tr>';

        echo "<tr><td colspan='6'>";
        echo "<form action='" . PUBLISHER_ADMIN_URL . "/mimetypes.php?op=search' style='margin:0; padding:0;' method='post'>"; echo $xoops->security()->getTokenHTML();
        echo "<table width='100%'>";
        echo "<tr><td align='right'>" . _AM_PUBLISHER_TEXT_SORT_BY . "
        <select name='sort'>";
        foreach ($aSortBy as $value => $text) {
            ($sort == $value) ? $selected = "selected='selected'" : $selected = '';
            echo "<option value='$value' $selected>$text</option>";
        }
        echo '</select>
        &nbsp;&nbsp;&nbsp;
        ' . _AM_PUBLISHER_TEXT_ORDER_BY . "
        <select name='order'>";
        foreach ($aOrderBy as $value => $text) {
            ($order == $value) ? $selected = "selected='selected'" : $selected = '';
            echo "<option value='$value' $selected>$text</option>";
        }
        echo '</select>
        &nbsp;&nbsp;&nbsp;
        ' . _AM_PUBLISHER_TEXT_NUMBER_PER_PAGE . "
        <select name='limit'>";
        foreach ($aLimitBy as $value => $text) {
            ($limit == $value) ? $selected = "selected='selected'" : $selected = '';
            echo "<option value='$value' $selected>$text</option>";
        }
        echo "</select>
        <input type='submit' name='mime_sort' id='mime_sort' value='" . _AM_PUBLISHER_BUTTON_SUBMIT . "'>
        <input type='hidden' name='mime_search' id='mime_search' value='1'>
        <input type='hidden' name='search_by' id='search_by' value='$search_field'>
        <input type='hidden' name='search_text' id='search_text' value='$search_text'>
        </td>
        </tr>";
        echo '</table>';
        echo '</td></tr>';
        if (count($mimetypes) > 0) {
            echo "<tr><th colspan='6'>" . _AM_PUBLISHER_TEXT_SEARCH_MIME . '</th></tr>';
            echo "<tr class='head'>
            <td>" . _AM_PUBLISHER_MIME_ID . '</td>
            <td>' . _AM_PUBLISHER_MIME_NAME . '</td>
            <td>' . _AM_PUBLISHER_MIME_EXT . '</td>
            <td>' . _AM_PUBLISHER_MIME_ADMIN . '</td>
            <td>' . _AM_PUBLISHER_MIME_USER . '</td>
            <td>' . _AM_PUBLISHER_MINDEX_ACTION . '</td>
            </tr>';
            foreach ($mimetypes as $mime) {
                echo mimeTableRow($mime, $limit, $start, $linkToken);
            }
            echo "<tr class='foot'>
            <td colspan='6' valign='top'>
            <a href='http://www.filext.com' style='float: right' target='_blank'>" . _AM_PUBLISHER_MIME_FINDMIMETYPE . "</a>
            <input type='checkbox' name='checkAllMimes' value='0' onclick='selectAll(this.form,\"mimes[]\",this.checked);'>
            <input type='submit' name='deleteMimes' id='deleteMimes' value='" . _AM_PUBLISHER_BUTTON_DELETE . "'>
            <input type='submit' name='add_mime' id='add_mime' value='" . _AM_PUBLISHER_MIME_CREATEF . "' class='formButton'>
            </td>
            </tr>";
        } else {
            echo '<tr><th>' . _AM_PUBLISHER_TEXT_SEARCH_MIME . '</th></tr>';
            echo "<tr class='even'>
            <td>" . _AM_PUBLISHER_TEXT_NO_RECORDS . '</td>
            </tr>';
        }
        echo '</table>';
        echo "<div id='pagenav'>" . $nav->renderNav() . '</div>';
    }
    Publisher\Utils::closeCollapsableBar('mimesearchtable', 'mimesearchicon');
    $xoops->footer();
}

/**
 * Render one row of the mime type table with its toggle, edit and delete links
 *
 * @param \XoopsObject $mime      mime type
 * @param int          $limit     page size
 * @param int          $start     page offset
 * @param string       $linkToken token for the toggle and delete links
 *
 * @return string
 */
function mimeTableRow($mime, $limit, $start, $linkToken)
{
    static $imagearray = null;
    if (null === $imagearray) {
        $helper = Helper::getInstance();
        $imagearray = [
            'editimg' => "<img src='" . $helper->url('images/button_edit.png') . "' alt='" . _AM_PUBLISHER_ICO_EDIT . "' align='middle'>",
            'deleteimg' => "<img src='" . $helper->url('images/button_delete.png') . "' alt='" . _AM_PUBLISHER_ICO_DELETE . "' align='middle'>",
            'online' => "<img src='" . $helper->url('images/on.png') . "' alt='" . _AM_PUBLISHER_ICO_ONLINE . "' align='middle'>",
            'offline' => "<img src='" . $helper->url('images/off.png') . "' alt='" . _AM_PUBLISHER_ICO_OFFLINE . "' align='middle'>",
        ];
    }

    return "<tr class='even'>
    <td><input type='checkbox' name='mimes[]' value='" . $mime->getVar('mime_id') . "'>" . $mime->getVar('mime_id') . '</td>
    <td>' . $mime->getVar('mime_name') . '</td>
    <td>' . $mime->getVar('mime_ext') . "</td>
    <td>
    <a href='" . PUBLISHER_ADMIN_URL . '/mimetypes.php?op=updateMimeValue&amp;XOOPS_TOKEN_REQUEST=' . $linkToken . '&amp;id=' . $mime->getVar('mime_id') . '&amp;mime_admin=' . $mime->getVar('mime_admin') . '&amp;limit=' . $limit . '&amp;start=' . $start . "'>
    " . ($mime->getVar('mime_admin') ? $imagearray['online'] : $imagearray['offline']) . "</a>
    </td>
    <td>
    <a href='" . PUBLISHER_ADMIN_URL . '/mimetypes.php?op=updateMimeValue&amp;XOOPS_TOKEN_REQUEST=' . $linkToken . '&amp;id=' . $mime->getVar('mime_id') . '&amp;mime_user=' . $mime->getVar('mime_user') . '&amp;limit=' . $limit . '&amp;start=' . $start . "'>
    " . ($mime->getVar('mime_user') ? $imagearray['online'] : $imagearray['offline']) . "</a>
    </td>
    <td>
    <a href='" . PUBLISHER_ADMIN_URL . '/mimetypes.php?op=edit&amp;id=' . $mime->getVar('mime_id') . '&amp;limit=' . $limit . '&amp;start=' . $start . "'>" . $imagearray['editimg'] . "</a>
    <a href='" . PUBLISHER_ADMIN_URL . '/mimetypes.php?op=delete&amp;XOOPS_TOKEN_REQUEST=' . $linkToken . '&amp;id=' . $mime->getVar('mime_id') . '&amp;limit=' . $limit . '&amp;start=' . $start . "'>" . $imagearray['deleteimg'] . '</a>
    </td>
    </tr>';
}

function updateMimeValue()
{
    $xoops = Xoops::getInstance();
    $helper = Helper::getInstance();
    $start = $limit = 0;

    if (isset($_GET['limit'])) {
        $limit = (int)$_GET['limit'];
    }
    if (isset($_GET['start'])) {
        $start = (int)$_GET['start'];
    }

    if (!isset($_REQUEST['id'])) {
        $xoops->redirect(PUBLISHER_ADMIN_URL . '/mimetypes.php', 3, _AM_PUBLISHER_MESSAGE_NO_ID);
    }
    $mime_id = (int)$_REQUEST['id'];

    $mimetype = $helper->getMimetypeHandler()->get($mime_id);

    if (isset($_REQUEST['mime_admin'])) {
        $mime_admin = (int)$_REQUEST['mime_admin'];
        $mime_admin = _changeMimeValue($mime_admin);
        $mimetype->setVar('mime_admin', $mime_admin);
    }
    if (isset($_REQUEST['mime_user'])) {
        $mime_user = (int)$_REQUEST['mime_user'];
        $mime_user = _changeMimeValue($mime_user);
        $mimetype->setVar('mime_user', $mime_user);
    }
    if ($helper->getMimetypeHandler()->insert($mimetype, true)) {
        header('Location: ' . PUBLISHER_ADMIN_URL . "/mimetypes.php?limit=$limit&start=$start");
    } else {
        $xoops->redirect(PUBLISHER_ADMIN_URL . "/mimetypes.php?limit=$limit&start=$start", 3);
    }
}

/**
 * @param $mime_value
 * @return int
 */
/**
 * @param $mime_value
 * @return int
 */
function _changeMimeValue($mime_value)
{
    if (1 == $mime_value) {
        $mime_value = 0;
    } else {
        $mime_value = 1;
    }

    return $mime_value;
}

function _clearAddSessionVars()
{
    $session = new Session();
    $session->del('publisher_addMime');
    $session->del('publisher_addMimeErr');
}

function clearAddSession()
{
    _clearAddSessionVars();
    header('Location: ' . Publisher\Utils::makeURI(PUBLISHER_ADMIN_URL . '/mimetypes.php', ['op' => 'add'], false));
}

/**
 * @param $id
 */
/**
 * @param $id
 */
function _clearEditSessionVars($id)
{
    $id = (int)$id;
    $session = new Session();
    $session->del("publisher_editMime_$id");
    $session->del("publisher_editMimeErr_$id");
}

function clearEditSession()
{
    $mimeid = $_REQUEST['id'];
    _clearEditSessionVars($mimeid);
    header('Location: ' . Publisher\Utils::makeURI(PUBLISHER_ADMIN_URL . '/mimetypes.php', ['op' => 'edit', 'id' => $mimeid], false));
}
