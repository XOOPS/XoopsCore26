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
use XoopsModules\Publisher;
use XoopsModules\Publisher\Form\CategoryForm;
use XoopsModules\Publisher\Helper;

/**
 * @copyright       The XUUPS Project http://sourceforge.net/projects/xuups/
 * @license         GNU GPL V2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Publisher
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @author          The SmartFactory <www.smartfactory.ca>
 * @version         $Id$
 */
require_once __DIR__ . '/admin_header.php';

$xoops = Xoops::getInstance();
$op = Request::getCmd('op');

$op = isset($_POST['editor']) ? 'mod' : $op;
if (isset($_POST['addcategory'])) {
    $op = 'addcategory';
}

// Where do we start ?
$startcategory = Request::getInt('startcategory');
$categoryid = Request::getInt('categoryid');

switch ($op) {
    case 'del':
        /* @var Publisher\Category $categoryObj */
        $categoryObj = $helper->getCategoryHandler()->get($categoryid);
        $confirm = $_POST['confirm'] ?? 0;
        $name = $_POST['name'] ?? '';
        if ($confirm) {
            if (!$xoops->security()->check()) {
                $xoops->redirect('category.php', 3, implode('<br />', $xoops->security()->getErrors()));
            }
            if (!$helper->getCategoryHandler()->delete($categoryObj)) {
                $xoops->redirect('category.php', 1, _AM_PUBLISHER_DELETE_CAT_ERROR);
            }
            $xoops->redirect('category.php', 1, sprintf(_AM_PUBLISHER_COLISDELETED, $name));
        } else {
            $xoops->header();
            echo $xoops->confirm(
                ['op' => 'del', 'categoryid' => $categoryObj->getVar('categoryid'), 'confirm' => 1, 'name' => $categoryObj->getVar('name')],
                'category.php',
                _AM_PUBLISHER_DELETECOL . " '" . $categoryObj->getVar('name') . "'. <br> <br>" . _AM_PUBLISHER_DELETE_CAT_CONFIRM,
                _AM_PUBLISHER_DELETE
            );
            $xoops->footer();
        }
        break;
    case 'mod':
        //Added by fx2024
        $nb_subcats = isset($_POST['nb_subcats']) ? (int)$_POST['nb_subcats'] : 0;
        $nb_subcats = $nb_subcats + (isset($_POST['nb_sub_yet']) ? (int)$_POST['nb_sub_yet'] : 4);
        //end of fx2024 code

        Publisher\Utils::cpHeader();
        publisher_editCat(true, $categoryid, $nb_subcats);
        break;
    case 'addcategory':
        global $modify;
        // keep the token: save errors send the user back to this form to fix and resubmit
        if (!$xoops->security()->check(false)) {
            $xoops->redirect('category.php', 3, implode('<br />', $xoops->security()->getErrors()));
        }

        $parentid = Request::getInt('parentid');
        /* @var Publisher\Category $categoryObj */
        if (0 != $categoryid) {
            $categoryObj = $helper->getCategoryHandler()->get($categoryid);
        } else {
            $categoryObj = $helper->getCategoryHandler()->create();
        }

        // Uploading the image, if any
        // Retreive the filename to be uploaded
        if (isset($_FILES['image_file']['name']) && '' != $_FILES['image_file']['name']) {
            $filename = $_POST['xoops_upload_file'][0];
            if (!empty($filename) || '' != $filename) {
                // TODO : implement publisher mimetype management
                $max_size = $helper->getConfig('maximum_filesize');
                $max_imgwidth = $helper->getConfig('maximum_image_width');
                $max_imgheight = $helper->getConfig('maximum_image_height');
                $allowed_mimetypes = Publisher\Utils::getAllowedImagesTypes();

                if ('' == $_FILES[$filename]['tmp_name'] || !is_readable($_FILES[$filename]['tmp_name'])) {
                    $xoops->redirect('javascript:history.go(-1)', 2, _AM_PUBLISHER_FILEUPLOAD_ERROR);
                }

                $uploader = new XoopsMediaUploader(Publisher\Utils::getImageDir('category'), $allowed_mimetypes, $max_size, $max_imgwidth, $max_imgheight);
                if ($uploader->fetchMedia($filename) && $uploader->upload()) {
                    $categoryObj->setVar('image', $uploader->getSavedFileName());
                } else {
                    $xoops->redirect('javascript:history.go(-1)', 2, _AM_PUBLISHER_FILEUPLOAD_ERROR . $uploader->getErrors());
                }
            }
        } else {
            if (isset($_POST['image'])) {
                $categoryObj->setVar('image', $_POST['image']);
            }
        }
        $categoryObj->setVar('parentid', isset($_POST['parentid']) ? (int)$_POST['parentid'] : 0);

        $applyall = isset($_POST['applyall']) ? (int)$_POST['applyall'] : 0;
        $categoryObj->setVar('weight', isset($_POST['weight']) ? (int)$_POST['weight'] : 1);

        // Groups and permissions
        $grpread = $_POST['groups_read'] ?? [];
        $grpsubmit = $_POST['groups_submit'] ?? [];
        $grpmoderation = $_POST['groups_moderation'] ?? [];

        $categoryObj->setVar('name', $_POST['name']);

        //Added by skalpa: custom template support
        if (isset($_POST['template'])) {
            $categoryObj->setVar('template', $_POST['template']);
        }

        if (isset($_POST['meta_description'])) {
            $categoryObj->setVar('meta_description', $_POST['meta_description']);
        }
        if (isset($_POST['meta_keywords'])) {
            $categoryObj->setVar('meta_keywords', $_POST['meta_keywords']);
        }
        if (isset($_POST['short_url'])) {
            $categoryObj->setVar('short_url', $_POST['short_url']);
        }
        $categoryObj->setVar('moderator', (int)$_POST['moderator']);
        $categoryObj->setVar('description', $_POST['description']);

        if (isset($_POST['header'])) {
            $categoryObj->setVar('header', $_POST['header']);
        }

        if ($categoryObj->isNew()) {
            $redirect_msg = _AM_PUBLISHER_CATCREATED;
            $redirect_to = 'category.php?op=mod';
        } else {
            $redirect_msg = _AM_PUBLISHER_COLMODIFIED;
            $redirect_to = 'category.php';
        }

        if (!$categoryObj->store()) {
            $xoops->redirect('javascript:history.go(-1)', 3, _AM_PUBLISHER_CATEGORY_SAVE_ERROR . Publisher\Utils::formatErrors($categoryObj->getErrors()));
        }
        // TODO : put this function in the category class
        Publisher\Utils::saveCategoryPermissions($grpread, $categoryObj->getVar('categoryid'), 'category_read');
        Publisher\Utils::saveCategoryPermissions($grpsubmit, $categoryObj->getVar('categoryid'), 'item_submit');
        Publisher\Utils::saveCategoryPermissions($grpmoderation, $categoryObj->getVar('categoryid'), 'category_moderation');

        //Added by fx2024
        $parentCat = $categoryObj->getVar('categoryid');
        $sizeof = count($_POST['scname']);
        for ($i = 0; $i < $sizeof; ++$i) {
            if ('' != $_POST['scname'][$i]) {
                $categoryObj = $helper->getCategoryHandler()->create();
                $categoryObj->setVar('name', $_POST['scname'][$i]);
                $categoryObj->setVar('parentid', $parentCat);

                if (!$categoryObj->store()) {
                    $xoops->redirect('javascript:history.go(-1)', 3, _AM_PUBLISHER_SUBCATEGORY_SAVE_ERROR . Publisher\Utils::formatErrors($categoryObj->getErrors()));
                }
                // TODO : put this function in the category class
                Publisher\Utils::saveCategoryPermissions($grpread, $categoryObj->getVar('categoryid'), 'category_read');
                Publisher\Utils::saveCategoryPermissions($grpsubmit, $categoryObj->getVar('categoryid'), 'item_submit');
                Publisher\Utils::saveCategoryPermissions($grpmoderation, $categoryObj->getVar('categoryid'), 'category_moderation');
            }
        }
        //end of fx2024 code
        $xoops->redirect($redirect_to, 2, $redirect_msg);
        break;
    //Added by fx2024

    case 'addsubcats':
        $categoryid = 0;
        $nb_subcats = (int)$_POST['nb_subcats'] + $_POST['nb_sub_yet'];

        $categoryObj = $helper->getCategoryHandler()->create();
        $categoryObj->setVar('name', $_POST['name']);
        $categoryObj->setVar('description', $_POST['description']);
        $categoryObj->setVar('weight', $_POST['weight']);
        if (isset($parentCat)) {
            $categoryObj->setVar('parentid', $parentCat);
        }

        Publisher\Utils::cpHeader();
        publisher_editCat(true, $categoryid, $nb_subcats, $categoryObj);
        exit();

        break;
    //end of fx2024 code

    case 'cancel':
        $xoops->redirect('category.php', 1, sprintf(_AM_PUBLISHER_BACK2IDX, ''));
        break;
    case 'default':
    default:
        Publisher\Utils::cpHeader();
        //publisher_adminMenu(1, _AM_PUBLISHER_CATEGORIES);

        echo "<br>\n";
        echo '<form><div style="margin-bottom: 12px;">';
        echo "<input type='button' name='button' onclick=\"location='category.php?op=mod'\" value='" . _AM_PUBLISHER_CATEGORY_CREATE . "'>&nbsp;&nbsp;";
        //echo "<input type='button' name='button' onclick=\"location='item.php?op=mod'\" value='" . _AM_PUBLISHER_CREATEITEM . "'>&nbsp;&nbsp;";
        echo '</div></form>';

        // Creating the objects for top categories
        $categoriesObj = $helper->getCategoryHandler()->getCategories($helper->getConfig('idxcat_perpage'), $startcategory, 0);

        Publisher\Utils::openCollapsableBar('createdcategories', 'createdcategoriesicon', _AM_PUBLISHER_CATEGORIES_TITLE, _AM_PUBLISHER_CATEGORIES_DSC);

        echo "<table width='100%' cellspacing=1 cellpadding=3 border=0 class = outer>";
        echo '<tr>';
        echo "<td class='bg3' align='left'><strong>" . _AM_PUBLISHER_ITEMCATEGORYNAME . '</strong></td>';
        echo "<td width='60' class='bg3' width='65' align='center'><strong>" . _CO_PUBLISHER_WEIGHT . '</strong></td>';
        echo "<td width='60' class='bg3' align='center'><strong>" . _AM_PUBLISHER_ACTION . '</strong></td>';
        echo '</tr>';
        $totalCategories = $helper->getCategoryHandler()->getCategoriesCount(0);
        if (count($categoriesObj) > 0) {
            foreach ($categoriesObj as $key => $thiscat) {
                publisher_displayCategory($thiscat);
            }
        } else {
            echo '<tr>';
            echo "<td class='head' align='center' colspan= '7'>" . _AM_PUBLISHER_NOCAT . '</td>';
            echo '</tr>';
            $categoryid = '0';
        }
        echo "</table>\n";
        $pagenav = new XoopsPageNav($totalCategories, $helper->getConfig('idxcat_perpage'), $startcategory, 'startcategory');
        echo '<div style="text-align:right;">' . $pagenav->renderNav() . '</div>';
        echo '<br>';
        Publisher\Utils::closeCollapsableBar('createdcategories', 'createdcategoriesicon');
        echo '<br>';
        //editcat(false);
        break;
}

$xoops->footer();

/**
 * @param \XoopsModules\Publisher\Category $categoryObj
 * @param int                              $level
 */
/**
 * @param \XoopsModules\Publisher\Category $categoryObj
 * @param int                              $level
 */
function publisher_displayCategory(Publisher\Category $categoryObj, $level = 0)
{
    $helper = Helper::getInstance();

    $description = $categoryObj->getVar('description');
    if (!XoopsLocale::isMultiByte()) {
        if (mb_strlen($description) >= 100) {
            $description = mb_substr($description, 0, 100 - 1) . '...';
        }
    }
    $modify = "<a href='category.php?op=mod&amp;categoryid=" . $categoryObj->getVar('categoryid') . '&amp;parentid=' . $categoryObj->getVar('parentid') . "'><img src='" . PUBLISHER_URL . "/images/links/edit.gif' title='" . _AM_PUBLISHER_EDITCOL . "' alt='" . _AM_PUBLISHER_EDITCOL . "'></a>";
    $delete = "<a href='category.php?op=del&amp;categoryid=" . $categoryObj->getVar('categoryid') . "'><img src='" . PUBLISHER_URL . "/images/links/delete.png' title='" . _AM_PUBLISHER_DELETECOL . "' alt='" . _AM_PUBLISHER_DELETECOL . "'></a>";

    $spaces = '';
    for ($j = 0; $j < $level; ++$j) {
        $spaces .= '&nbsp;&nbsp;&nbsp;';
    }

    echo '<tr>';
    echo "<td class='even' align='left'>" . $spaces . "<a href='" . PUBLISHER_URL . '/category.php?categoryid=' . $categoryObj->getVar('categoryid') . "'><img src='" . PUBLISHER_URL . "/images/links/subcat.gif' alt=''>&nbsp;" . $categoryObj->getVar('name') . '</a></td>';
    echo "<td class='even' align='center'>" . $categoryObj->getVar('weight') . '</td>';
    echo "<td class='even' align='center'> $modify $delete </td>";
    echo '</tr>';
    $subCategoriesObj = $helper->getCategoryHandler()->getCategories(0, 0, $categoryObj->getVar('categoryid'));
    if (count($subCategoriesObj) > 0) {
        ++$level;
        foreach ($subCategoriesObj as $thiscat) {
            publisher_displayCategory($thiscat, $level);
        }
    }
    unset($categoryObj);
}

/**
 * @param bool $showmenu
 * @param int  $categoryid
 * @param int  $nb_subcats
 * @param null $categoryObj
 */
/**
 * @param bool $showmenu
 * @param int  $categoryid
 * @param int  $nb_subcats
 * @param null $categoryObj
 */
function publisher_editCat($showmenu = false, $categoryid = 0, $nb_subcats = 4, $categoryObj = null)
{
    $xoops = Xoops::getInstance();
    $helper = Helper::getInstance();
    /* @var Publisher\Category $categoryObj */

    // if there is a parameter, and the id exists, retrieve data: we're editing a category
    if (0 != $categoryid) {
        // Creating the category object for the selected category
        $categoryObj = $helper->getCategoryHandler()->get($categoryid);
        if ($categoryObj->notLoaded()) {
            $xoops->redirect('category.php', 1, _AM_PUBLISHER_NOCOLTOEDIT);
        }
    } else {
        if (!$categoryObj) {
            $categoryObj = $helper->getCategoryHandler()->create();
        }
    }

    if (0 != $categoryid) {
        if ($showmenu) {
            //publisher_adminMenu(1, _AM_PUBLISHER_CATEGORIES . " > " . _AM_PUBLISHER_EDITING);
        }
        echo "<br>\n";
        Publisher\Utils::openCollapsableBar('edittable', 'edittableicon', _AM_PUBLISHER_EDITCOL, _AM_PUBLISHER_CATEGORY_EDIT_INFO);
    } else {
        if ($showmenu) {
            //publisher_adminMenu(1, _AM_PUBLISHER_CATEGORIES . " > " . _AM_PUBLISHER_CREATINGNEW);
        }
        Publisher\Utils::openCollapsableBar('createtable', 'createtableicon', _AM_PUBLISHER_CATEGORY_CREATE, _AM_PUBLISHER_CATEGORY_CREATE_INFO);
    }

    /* @var Publisher\Form\CategoryForm $sform */
    //    $sform = $helper->getForm($categoryObj, 'Category');
    $sform = new  CategoryForm($categoryObj);
    $sform->setSubCatsCount($nb_subcats);
    $sform->display();

    if (!$categoryid) {
        Publisher\Utils::closeCollapsableBar('createtable', 'createtableicon');
    } else {
        Publisher\Utils::closeCollapsableBar('edittable', 'edittableicon');
    }

    //Added by fx2024
    if ($categoryid) {
        $sel_cat = $categoryid;

        Publisher\Utils::openCollapsableBar('subcatstable', 'subcatsicon', _AM_PUBLISHER_SUBCAT_CAT, _AM_PUBLISHER_SUBCAT_CAT_DSC);
        // Get the total number of sub-categories
        $categoriesObj = $helper->getCategoryHandler()->get($sel_cat);
        $totalsubs = $helper->getCategoryHandler()->getCategoriesCount($sel_cat);
        // creating the categories objects that are published
        $subcatsObj = $helper->getCategoryHandler()->getCategories(0, 0, $categoriesObj->getVar('categoryid'));

        echo "<table width='100%' cellspacing=1 cellpadding=3 border=0 class = outer>";
        echo '<tr>';
        echo "<td width='60' class='bg3' align='left'><strong>" . _AM_PUBLISHER_CATID . '</strong></td>';
        echo "<td width='20%' class='bg3' align='left'><strong>" . _AM_PUBLISHER_CATCOLNAME . '</strong></td>';
        echo "<td class='bg3' align='left'><strong>" . _AM_PUBLISHER_SUBDESCRIPT . '</strong></td>';
        echo "<td width='60' class='bg3' align='right'><strong>" . _AM_PUBLISHER_ACTION . '</strong></td>';
        echo '</tr>';
        if ($totalsubs > 0) {
            /* @var Publisher\Category $subcat */
            foreach ($subcatsObj as $subcat) {
                $modify = "<a href='category.php?op=mod&amp;categoryid="
                          . $subcat->getVar('categoryid')
                          . "'><img src='"
                          . XoopsBaseConfig::get('url')
                          . '/modules/'
                          . $helper->getModule()->dirname()
                          . "/images/links/edit.gif' title='"
                          . _AM_PUBLISHER_MODIFY
                          . "' alt='"
                          . _AM_PUBLISHER_MODIFY
                          . "'></a>";
                $delete = "<a href='category.php?op=del&amp;categoryid="
                          . $subcat->getVar('categoryid')
                          . "'><img src='"
                          . XoopsBaseConfig::get('url')
                          . '/modules/'
                          . $helper->getModule()->dirname()
                          . "/images/links/delete.png' title='"
                          . _AM_PUBLISHER_DELETE
                          . "' alt='"
                          . _AM_PUBLISHER_DELETE
                          . "'></a>";
                echo '<tr>';
                echo "<td class='head' align='left'>" . $subcat->getVar('categoryid') . '</td>';
                echo "<td class='even' align='left'><a href='" . XoopsBaseConfig::get('url') . '/modules/' . $helper->getModule()->dirname() . '/category.php?categoryid=' . $subcat->getVar('categoryid') . '&amp;parentid=' . $subcat->getVar('parentid') . "'>" . $subcat->getVar('name') . '</a></td>';
                echo "<td class='even' align='left'>" . $subcat->getVar('description') . '</td>';
                echo "<td class='even' align='right'> {$modify} {$delete} </td>";
                echo '</tr>';
            }
        } else {
            echo '<tr>';
            echo "<td class='head' align='center' colspan= '7'>" . _AM_PUBLISHER_NOSUBCAT . '</td>';
            echo '</tr>';
        }
        echo "</table>\n";
        echo "<br>\n";
        Publisher\Utils::closeCollapsableBar('subcatstable', 'subcatsicon');

        Publisher\Utils::openCollapsableBar('bottomtable', 'bottomtableicon', _AM_PUBLISHER_CAT_ITEMS, _AM_PUBLISHER_CAT_ITEMS_DSC);
        $startitem = Request::getInt('startitem');
        // Get the total number of published ITEMS
        $totalitems = $helper->getItemHandler()->getItemsCount($sel_cat, [_PUBLISHER_STATUS_PUBLISHED]);
        // creating the items objects that are published
        $itemsObj = $helper->getItemHandler()->getAllPublished($helper->getConfig('idxcat_perpage'), $startitem, $sel_cat);
        $allcats = $helper->getCategoryHandler()->getObjects(null, true);
        echo "<table width='100%' cellspacing=1 cellpadding=3 border=0 class = outer>";
        echo '<tr>';
        echo "<td width='40' class='bg3' align='center'><strong>" . _AM_PUBLISHER_ITEMID . '</strong></td>';
        echo "<td width='20%' class='bg3' align='left'><strong>" . _AM_PUBLISHER_ITEMCOLNAME . '</strong></td>';
        echo "<td class='bg3' align='left'><strong>" . _AM_PUBLISHER_ITEMDESC . '</strong></td>';
        echo "<td width='90' class='bg3' align='center'><strong>" . _AM_PUBLISHER_CREATED . '</strong></td>';
        echo "<td width='60' class='bg3' align='center'><strong>" . _AM_PUBLISHER_ACTION . '</strong></td>';
        echo '</tr>';
        if ($totalitems > 0) {
            foreach ($itemsObj as $iValue) {
                $categoryObj = $allcats[$iValue->getVar('categoryid')];
                $modify = "<a href='item.php?op=mod&amp;itemid="
                               . $iValue->getVar('itemid')
                               . "'><img src='"
                               . XoopsBaseConfig::get('url')
                               . '/modules/'
                               . $helper->getModule()->dirname()
                               . "/images/links/edit.gif' title='"
                               . _AM_PUBLISHER_EDITITEM
                               . "' alt='"
                               . _AM_PUBLISHER_EDITITEM
                               . "'></a>";
                $delete = "<a href='item.php?op=del&amp;itemid="
                               . $iValue->getVar('itemid')
                               . "'><img src='"
                               . XoopsBaseConfig::get('url')
                               . '/modules/'
                               . $helper->getModule()->dirname()
                               . "/images/links/delete.png' title='"
                               . _AM_PUBLISHER_DELETEITEM
                               . "' alt='"
                               . _AM_PUBLISHER_DELETEITEM
                               . "'></a>";
                echo '<tr>';
                echo "<td class='head' align='center'>" . $iValue->getVar('itemid') . '</td>';
                echo "<td class='even' align='left'>" . $categoryObj->getVar('name') . '</td>';
                echo "<td class='even' align='left'>" . $iValue->getitemLink() . '</td>';
                echo "<td class='even' align='center'>" . $iValue->datesub('s') . '</td>';
                echo "<td class='even' align='center'> $modify $delete </td>";
                echo '</tr>';
            }
        } else {
            echo '<tr>';
            echo "<td class='head' align='center' colspan= '7'>" . _AM_PUBLISHER_NOITEMS . '</td>';
            echo '</tr>';
        }
        echo "</table>\n";
        echo "<br>\n";
        $parentid = Request::getInt('parentid');
        $pagenav_extra_args = "op=mod&categoryid=$sel_cat&parentid=$parentid";
        $pagenav = new XoopsPageNav($totalitems, $helper->getConfig('idxcat_perpage'), $startitem, 'startitem', $pagenav_extra_args);
        echo '<div style="text-align:right;">' . $pagenav->renderNav() . '</div>';
        echo "<input type='button' name='button' onclick=\"location='item.php?op=mod&categoryid=" . $sel_cat . "'\" value='" . _AM_PUBLISHER_CREATEITEM . "'>&nbsp;&nbsp;";
        echo '</div>';
    }
    //end of fx2024 code
}
