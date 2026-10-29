<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\FixedGroups;
use Xmf\Request;

/**
 * images module
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @since           2.6.0
 * @author          Mage Grégory (AKA Mage)
 * @version         $Id$
 */
include __DIR__ . '/header.php';

// Call Header
$xoops->header('admin:images/images_admin_categories.tpl');

$admin_page = new \Xoops\Module\Admin();
$admin_page->renderNavigation('categories.php');

switch ($op) {
    case 'save':
        if (!$xoops->security()->check()) {
            $xoops->redirect('categories.php', 3, implode('<br />', $xoops->security()->getErrors()));
        }

        $imgcat_id = Request::getInt('imgcat_id', 0);
        if (isset($imgcat_id) && $imgcat_id != 0) {
            $obj = $helper->getHandlerCategories()->get($imgcat_id);
            $isnew = false;
        } else {
            $obj = $helper->getHandlerCategories()->create();
            $isnew = true;
        }
        $obj->setVar('imgcat_name', Request::getString('imgcat_name', ''));
        $obj->setVar('imgcat_maxsize', Request::getInt('imgcat_maxsize', 100000));
        $obj->setVar('imgcat_maxwidth', Request::getInt('imgcat_maxwidth', 128));
        $obj->setVar('imgcat_maxheight', Request::getInt('imgcat_maxheight', 128));
        $obj->setVar('imgcat_display', Request::getBool('imgcat_display', 1));
        $obj->setVar('imgcat_weight', Request::getInt('imgcat_weight', 0));
        $obj->setVar('imgcat_storetype', Request::getString('imgcat_storetype', 'file'));
        $obj->setVar('imgcat_type', 'C');

        if ($imgcat_id = $helper->getHandlerCategories()->insert($obj)) {
            // delete permissions
            if (!$isnew) {
                $criteria = new CriteriaCompo(new Criteria('gperm_itemid', $imgcat_id));
                $criteria->add(new Criteria('gperm_modid', $xoops->module->getVar('mid')));
                $criteria2 = new CriteriaCompo(new Criteria('gperm_name', 'imgcat_write'));
                $criteria2->add(new Criteria('gperm_name', 'imgcat_read'), 'OR');
                $criteria->add($criteria2);
                $xoops->getHandlerGroupPermission()->deleteAll($criteria);
            }
            // Save permissions
            $permissions = array('readgroup' => 'imgcat_read', 'writegroup' => 'imgcat_write');
            foreach ($permissions as $k => $permission) {
                $groups = Request::getArray($k, array(FixedGroups::ADMIN));
                if (!in_array(FixedGroups::ADMIN, $groups)) {
                    array_push($groups, FixedGroups::ADMIN);
                }
                foreach ($groups as $group) {
                    $perm_obj = $xoops->getHandlerGroupPermission()->create();
                    $perm_obj->setVar('gperm_groupid', $group);
                    $perm_obj->setVar('gperm_itemid', $imgcat_id);
                    $perm_obj->setVar('gperm_name', $permission);
                    $perm_obj->setVar('gperm_modid', $xoops->module->getVar('mid'));
                    $xoops->getHandlerGroupPermission()->insert($perm_obj);
                    unset($perm_obj);
                }
            }
            $xoops->redirect('categories.php', 2, _AM_IMAGES_CAT_SAVE);
        }
        $xoops->redirect('categories.php', 2, _AM_IMAGES_CAT_NOTSAVE . '<br />' . implode('<br />', $obj->getHtmlErrors()));
        break;

    case 'add':
        $obj = $helper->getHandlerCategories()->create();
        $form = $helper->getForm($obj, 'category');
        $xoops->tpl()->assign('form', $form->render());
        break;

    case 'edit':
        $imgcat_id = Request::getInt('imgcat_id', 0);
        if ($imgcat_id > 0) {
            $obj = $helper->getHandlerCategories()->get($imgcat_id);
            $form = $helper->getForm($obj, 'category');
            $xoops->tpl()->assign('form', $form->render());
        }
        break;

    case 'del':
        $imgcat_id = Request::getInt('imgcat_id', 0);
        if ($imgcat_id > 0) {
            $ok = Request::getInt('ok', 0);
            $obj = $helper->getHandlerCategories()->get($imgcat_id);

            if ($ok == 1) {
                if (!$xoops->security()->check()) {
                    $xoops->redirect('categories.php', 3, implode(',', $xoops->security()->getErrors()));
                }
                if ($helper->getHandlerCategories()->delete($obj)) {
                    // Delete image datas and files
                    $images = $helper->getHandlerImages()->getByCategory($obj->getVar('imgcat_id'));
                    foreach ($images as $image) {
                        if ($helper->getHandlerImages()->delete($image)) {
                            if ($obj->getVar('imgcat_storetype') === 'db') {
                                $helper->getHandlerImagesBody()->delete($helper->getHandlerImagesBody()->get($image->getVar('image_id')));
                            } else {
                                unlink(\XoopsBaseConfig::get('uploads-path') . '/' . $image->getVar('image_name'));
                            }
                        }
                    }

                    // Delete permissions
                    $criteria = new CriteriaCompo(new Criteria('gperm_itemid', $imgcat_id));
                    $criteria->add(new Criteria('gperm_modid', $xoops->module->getVar('mid')));
                    $criteria2 = new CriteriaCompo(new Criteria('gperm_name', 'imgcat_write'));
                    $criteria2->add(new Criteria('gperm_name', 'imgcat_read'), 'OR');
                    $criteria->add($criteria2);
                    $xoops->getHandlerGroupPermission()->deleteAll($criteria);

                    $xoops->redirect('categories.php', 2, XoopsLocale::S_DATABASE_UPDATED);
                }
            } else {
                echo $xoops->confirm(
                    array('op' => 'del', 'ok' => 1, 'imgcat_id' => $imgcat_id),
                    XOOPS_URL . '/modules/images/admin/categories.php',
                    sprintf(_AM_IMAGES_CAT_DELETE, $obj->getVar('imgcat_name'))
                );
            }
        }
        break;

    case 'display':
        $imgcat_id = Request::getInt('imgcat_id', 0);
        if ($imgcat_id > 0) {
            $imgcat = $helper->getHandlerCategories()->get($imgcat_id);
            $old = $imgcat->getVar('imgcat_display');
            $imgcat->setVar('imgcat_display', !$old);
            if (!$helper->getHandlerCategories()->insert($imgcat)) {
                $error = true;
            }
        }
        break;

    case 'list':
    default:
        $admin_page->addItemButton(_AM_IMAGES_CAT_ADD, 'categories.php?op=add', 'add');
        $admin_page->renderButton();

        $categories = $helper->getHandlerCategories()->getPermittedObjects();
        $xoops->tpl()->assign('token', $xoops->security()->createToken(3600));

        foreach (array_keys($categories) as $i) {
            $imgcat_read = $gperm_handler->checkRight('imgcat_read', $categories[$i]->getVar('imgcat_id'), $groups, $xoops->module->mid());
            $imgcat_write = $gperm_handler->checkRight('imgcat_write', $categories[$i]->getVar('imgcat_id'), $groups, $xoops->module->mid());
            if ($imgcat_read || $imgcat_write) {
                $count = $helper->getHandlerImages()->countByCategory($categories[$i]->getVar('imgcat_id'));
                $cat_images = $categories[$i]->getValues();
                $cat_images['imgcat_count'] = $count;
                $xoops->tpl()->appendByRef('categories', $cat_images);
                unset($cat_images);
            }
        }
        break;
}
$xoops->footer();
