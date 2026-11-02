<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xmf\Request;

/**
 * User rank Manager
 *
 * @copyright       2013-2019 XOOPS Project (https://xoops.org)
 * @license         GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @package         userrank
 * @author          Cointin Maxime (AKA Kraven30)
 */
include __DIR__ . '/header.php';

// Get main instance
$xoops = Xoops::getInstance();

// Parameters
$nb_rank = $xoops->getModuleConfig('admin:userrank/userrank_pager');
$mimetypes = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png');
$upload_size = 500000;
// Get Action type
$op = Request::getString('op', 'list');
// Get userrank handler
$userrank_Handler = $xoops->getModuleHandler('rank', 'userrank');

// Call Header
$xoops->header('admin:userrank/userrank.tpl');

$admin_page = new \Xoops\Module\Admin();
$admin_page->renderNavigation('userrank.php');

switch ($op) {

    case 'list':
    default:
        $xoops->tpl()->assign('token', $xoops->security()->createToken(3600));
        // Add Scripts
        $xoops->theme()->addScript('media/xoops/xoops.js');

        $admin_page->addTips(_AM_USERRANK_TIPS);
        $admin_page->addItemButton(_AM_USERRANK_ADD, './userrank.php?op=userrank_new', 'add');
        $admin_page->renderTips();
        $admin_page->renderButton();

        // Get start pager
        $start = Request::getInt('start', 0);
        // Criteria
        $criteria = new CriteriaCompo();
        $criteria->setSort("rank_id");
        $criteria->setOrder("ASC");
        $criteria->setStart($start);
        $criteria->setLimit($nb_rank);
        // Count rank
        $userrank_count = $userrank_Handler->getCount($criteria);
        $userrank_arr = $userrank_Handler->getAll($criteria);
        // Assign Template variables
        $xoops->tpl()->assign('userrank_count', $userrank_count);
        if ($userrank_count > 0) {
            foreach (array_keys($userrank_arr) as $i) {
                $rank_id = $userrank_arr[$i]->getVar("rank_id");
                $userrank['rank_id'] = $rank_id;
                $userrank['rank_title'] = $userrank_arr[$i]->getVar("rank_title");
                $userrank['rank_min'] = $userrank_arr[$i]->getVar("rank_min");
                $userrank['rank_max'] = $userrank_arr[$i]->getVar("rank_max");
                $userrank['rank_special'] = $userrank_arr[$i]->getVar("rank_special");
                $rank_img = ($userrank_arr[$i]->getVar("rank_image")) ? $userrank_arr[$i]->getVar("rank_image") : 'blank.gif';
                $userrank['rank_image'] = '<img src="' . \XoopsBaseConfig::get('uploads-url') . '/' . $rank_img . '" alt="" />';
                $xoops->tpl()->appendByRef('userrank', $userrank);
                unset($userrank);
            }
        }
        // Display Page Navigation
        if ($userrank_count > $nb_rank) {
            $nav = new XoopsPageNav($userrank_count, $nb_rank, $start, 'start', 'userrank.php');
            $xoops->tpl()->assign('nav_menu', $nav->renderNav(4));
        }
        break;

    // New userrank
    case 'userrank_new':
        $admin_page->addTips(sprintf(_AM_USERRANK_TIPS_FORM1, implode(', ', $mimetypes)) . sprintf(_AM_USERRANK_TIPS_FORM2, $upload_size / 1000));
        $admin_page->addItemButton(_AM_USERRANK_LIST, './userrank.php', 'application-view-detail');
        $admin_page->renderTips();
        $admin_page->renderButton();
        // Create form
        $obj = $userrank_Handler->create();
        $form = $xoops->getModuleForm($obj, 'ranks');
        $xoops->tpl()->assign('form', $form->render());
        break;

    // Edit userrank
    case 'userrank_edit':
        $admin_page->addTips(sprintf(_AM_USERRANK_TIPS_FORM1, implode(', ', $mimetypes)) . sprintf(_AM_USERRANK_TIPS_FORM2, $upload_size / 1000));
        $admin_page->addItemButton(_AM_USERRANK_ADD, './userrank.php?op=userrank_new', 'add');
        $admin_page->addItemButton(_AM_USERRANK_LIST, './userrank.php', 'application-view-detail');
        $admin_page->renderTips();
        $admin_page->renderButton();
        // Create form
        $obj = $userrank_Handler->get(Request::getInt('rank_id', 0));
        $form = $xoops->getModuleForm($obj, 'ranks');
        $xoops->tpl()->assign('form', $form->render());
        break;

    // Save rank
    case 'userrank_save':
        if (!$xoops->security()->check()) {
            $xoops->redirect("userrank.php", 3, implode(",", $xoops->security()->getErrors()));
        }
        if (isset($_POST["rank_id"])) {
            $obj = $userrank_Handler->get($_POST["rank_id"]);
        } else {
            $obj = $userrank_Handler->create();
        }

        $obj->setVar("rank_title", $_POST["rank_title"]);
        $obj->setVar("rank_min", $_POST["rank_min"]);
        $obj->setVar("rank_max", $_POST["rank_max"]);
        $verif_rank_special = ($_POST["rank_special"] == 1) ? "1" : "0";
        $obj->setVar("rank_special", $verif_rank_special);

        $uploader_rank_img = new XoopsMediaUploader(\XoopsBaseConfig::get('uploads-url') . '/ranks', $mimetypes, $upload_size, null, null);

        if ($uploader_rank_img->fetchMedia("rank_image")) {
            $uploader_rank_img->setPrefix("rank");
            $uploader_rank_img->fetchMedia("rank_image");
            if (!$uploader_rank_img->upload()) {
                $errors = $uploader_rank_img->getErrors();
                $xoops->redirect("javascript:history.go(-1)", 3, $errors);
            } else {
                $obj->setVar("rank_image", 'ranks/' . $uploader_rank_img->getSavedFileName());
            }
        } else {
            $obj->setVar("rank_image", 'ranks/' . $_POST["rank_image"]);
        }

        if ($userrank_Handler->insert($obj)) {
            $xoops->redirect("userrank.php", 2, _AM_USERRANK_SAVE);
        }
        break;

    // Delete userrank
    case 'userrank_delete':
        $admin_page->addItemButton(_AM_USERRANK_ADD, './userrank.php?op=userrank_new', 'add');
        $admin_page->addItemButton(_AM_USERRANK_LIST, './userrank.php', 'list');
        $admin_page->renderButton();
        $rank_id = Request::getInt('rank_id', 0);
        $obj = $userrank_Handler->get($rank_id);
        if (isset($_POST["ok"]) && $_POST["ok"] == 1) {
            if (!$xoops->security()->check()) {
                $xoops->redirect("userrank.php", 3, implode(",", $xoops->security()->getErrors()));
            }
            if ($userrank_Handler->delete($obj)) {
                $urlfile = \XoopsBaseConfig::get('uploads-url') . '/' . $obj->getVar("rank_image");
                if (is_file($urlfile)) {
                    chmod($urlfile, 0777);
                    unlink($urlfile);
                }
                $xoops->redirect("userrank.php", 2, _AM_USERRANK_SAVE);
            } else {
                echo $xoops->alert('error', $obj->getHtmlErrors());
            }
        } else {
            $rank_img = ($obj->getVar("rank_image")) ? $obj->getVar("rank_image") : 'blank.gif';
            echo $xoops->confirm(array(
                "ok" => 1, "rank_id" => $_REQUEST["rank_id"], "op" => "userrank_delete"
            ), $_SERVER["REQUEST_URI"], sprintf(_AM_USERRANK_SUREDEL) . '<br \><img src="' . \XoopsBaseConfig::get('uploads-url') . '/' . $rank_img . '" alt="" /><br \>');
        }
        break;

    // Update userrank status
    case 'userrank_update_special':
        $xoops->security()->requireAjaxToken();
        // Get rank id
        $rank_id = Request::getInt('rank_id', 0);
        if ($rank_id > 0) {
            $obj = $userrank_Handler->get($rank_id);
            $old = $obj->getVar('rank_special');
            $obj->setVar('rank_special', !$old);
            if ($userrank_Handler->insert($obj)) {
                exit;
            }
            http_response_code(500);
            echo $obj->getHtmlErrors();
        }
        break;

}
// Call Footer
$xoops->footer();
