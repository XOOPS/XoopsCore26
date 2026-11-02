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
 * page module
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         page
 * @since           2.6.0
 * @author          Mage Grégory (AKA Mage)
 * @version         $Id$
 */
include __DIR__ . '/header.php';

// Call header
$xoops->header('admin:page/page_admin_related.tpl');

$admin_page = new \Xoops\Module\Admin();
$admin_page->renderNavigation('related.php');

switch ($op) {

    case 'list':
    default:
        $xoops->tpl()->assign('token', $xoops->security()->createToken(3600));
        $admin_page->addTips(PageLocale::RELATED_TIPS);
        $admin_page->addItemButton(PageLocale::A_ADD_RELATED, 'related.php?op=new', 'add');
        $admin_page->renderTips();
        $admin_page->renderButton();

        $related_count = $related_Handler->countRelated($start, $nb_limit);
        $related_arr = $related_Handler->getRelated($start, $nb_limit);

        $xoops->tpl()->assign('related_count', $related_count);
        $xoops->tpl()->assign('related', $related_arr);

        if ($related_count > 0) {
            // Display Page Navigation
            if ($related_count > $nb_limit) {
                $nav = new XoopsPageNav($related_count, $nb_limit, $start, 'start');
                $xoops->tpl()->assign('nav_menu', $nav->renderNav(4));
            }
        } else {
            $xoops->tpl()->assign('error_message', PageLocale::E_NO_RELATED);
        }
        break;

    case 'new':
        if ($related_Handler->getCount() == $content_Handler->getCount()) {
            $xoops->tpl()->assign('error_message', PageLocale::E_NO_FREE_CONTENT);
        } else {
            $admin_page->addItemButton(PageLocale::A_LIST_CONTENT, 'related.php', 'application-view-detail');
            $admin_page->renderButton();
            $obj = $related_Handler->create();
            $form = $helper->getForm($obj, 'page_related');
            $xoops->tpl()->assign('form', $form->render());
        }
        break;

    case 'edit':
        $admin_page->addItemButton(PageLocale::A_LIST_CONTENT, 'related.php', 'application-view-detail');
        $admin_page->addItemButton(PageLocale::A_ADD_CONTENT, 'related.php?op=new', 'add');
        $admin_page->renderButton();
        // Create form
        $related_id = Request::getInt('related_id', 0);
        $obj = $related_Handler->get($related_id);
        $form = $helper->getForm($obj, 'page_related');
        $xoops->tpl()->assign('form', $form->render());
        break;

    case 'save':
        if (!$xoops->security()->check()) {
            $xoops->redirect('related.php', 3, implode(',', $xoops->security()->getErrors()));
        }

        $related_id = Request::getInt('related_id', 0);
        if ($related_id > 0) {
            $obj = $related_Handler->get($related_id);
        } else {
            $obj = $related_Handler->create();
        }

        //main
        $obj->setVar('related_name', Request::getString('related_name', ''));
        $obj->setVar('related_domenu', Request::getInt('related_domenu', 1));
        $obj->setVar('related_navigation', Request::getInt('related_navigation', 1));

        if ($related_newid = $related_Handler->insert($obj)) {
            $related_id = $related_id != 0 ? $related_id : $related_newid;
            $datas = Request::getArray('datas');
            $datas_exists = $link_Handler->getContentByRelated($related_newid);
            $datas_delete = array_diff(array_values($datas_exists), $datas);
            $datas_add = array_diff($datas, array_values($datas_exists));

            // delete
            if (count($datas_delete) != 0) {
                $criteria = $criteria = new CriteriaCompo();
                $criteria->add(new Criteria('link_related_id', $related_id));
                $criteria->add(new Criteria('link_content_id', '(' . implode(', ', $datas_delete) . ')', 'IN'));
                $links_ids =  $link_Handler->getIds($criteria);
                if (!$link_Handler->DeleteByIds($links_ids)) {
                }
            }
            // Add
            if (count($datas_add) != 0) {
                foreach ($datas_add as $weight => $content_id) {
                    $obj = $link_Handler->create();
                    $obj->setVar('link_related_id', $related_id);
                    $obj->setVar('link_content_id', $content_id);
                    $obj->setVar('link_weight', $weight);
                    if (!$link_Handler->insert($obj)) {
                    }
                }
            }
            //update
            if (count($datas) != 0) {
                foreach ($datas as $weight => $content_id) {
                    $criteria = $criteria = new CriteriaCompo();
                    $criteria->add(new Criteria('link_related_id', $related_id));
                    $criteria->add(new Criteria('link_content_id', $content_id));
                    $links_ids = $link_Handler->getIds($criteria);

                    $obj = $link_Handler->get($links_ids[0]);
                    $obj->setVar('link_weight', $weight);
                    if (!$link_Handler->insert($obj)) {
                    }
                }
            }

            $xoops->redirect('related.php', 2, XoopsLocale::S_DATABASE_UPDATED);
        } else {
            echo $xoops->alert('error', $obj->getHtmlErrors());
        }
        $form = $helper->getForm($obj, 'page_related');
        $xoops->tpl()->assign('form', $form->render());
        break;

    case 'delete':
        $admin_page->addItemButton(PageLocale::A_LIST_CONTENT, 'related.php', 'application-view-detail');
        $admin_page->addItemButton(PageLocale::A_ADD_CONTENT, 'related.php?op=new', 'add');
        $admin_page->renderButton();

        $related_id = Request::getInt('related_id', 0);
        $ok = Request::getInt('ok', 0);

        $obj = $related_Handler->get($related_id);
        if ($ok == 1) {
            if (!$xoops->security()->check()) {
                $xoops->redirect('related.php', 3, implode(',', $xoops->security()->getErrors()));
            }
            // Deleting the related
            if ($related_Handler->delete($obj)) {
                $criteria = new CriteriaCompo();
                $criteria->add(new Criteria('link_related_id', $related_id));
                $link_Handler->deleteAll($criteria);
                $xoops->redirect('related.php', 2, XoopsLocale::S_DATABASE_UPDATED);
            } else {
                echo $xoops->alert('error', $obj->getHtmlErrors());
            }
        } else {
            echo $xoops->confirm(
                array('ok' => 1, 'related_id' => $related_id, 'op' => 'delete'),
                'related.php',
                XoopsLocale::Q_ARE_YOU_SURE_YOU_WANT_TO_DELETE_THIS_ITEM
                . '<br /><span class="red">' . $obj->getvar('related_name') . '<span>'
            );
        }
        break;

    case 'update_status':
        $xoops->security()->requireAjaxToken();
        $related_id = Request::getInt('related_id', 0);
        if ($related_id > 0) {
            $obj = $related_Handler->get($related_id);
            $old = $obj->getVar('related_domenu');
            $obj->setVar('related_domenu', !$old);
            if ($related_Handler->insert($obj)) {
                exit;
            }
            http_response_code(500);
            echo $obj->getHtmlErrors();
        }
        break;
}
$xoops->footer();
