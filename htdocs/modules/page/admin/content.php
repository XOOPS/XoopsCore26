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
$xoops->header('admin:page/page_admin_content.tpl');

$admin_page = new \Xoops\Module\Admin();
$admin_page->renderNavigation('content.php');

switch ($op) {

    case 'list':
    default:
        $xoops->tpl()->assign('token', $xoops->security()->createToken(3600));
        $admin_page->addTips(PageLocale::CONTENT_TIPS);
        $admin_page->addItemButton(PageLocale::A_ADD_CONTENT, 'content.php?op=new', 'add');
        $admin_page->renderTips();
        $admin_page->renderButton();

        // Content
        $content_count = $content_Handler->countPage();
        $content_arr = $content_Handler->getPage($start, $nb_limit);

        // Assign Template variables
        $xoops->tpl()->assign('content_count', $content_count);
        if ($content_count > 0) {
            foreach (array_keys($content_arr) as $i) {
                $content = $content_arr[$i]->getValues();
                $xoops->tpl()->appendByRef('content', $content);
                unset($content);
            }
            // Display Page Navigation
            if ($content_count > $nb_limit) {
                $nav = new XoopsPageNav($content_count, $nb_limit, $start, 'start');
                $xoops->tpl()->assign('nav_menu', $nav->renderNav(4, 'small'));
            }
        } else {
            $xoops->tpl()->assign('error_message', PageLocale::E_NO_CONTENT);
        }
        break;

    case 'new':
        $admin_page->addItemButton(PageLocale::A_LIST_CONTENT, 'content.php', 'application-view-detail');
        $admin_page->renderButton();
        $obj = $content_Handler->create();
        $form = $helper->getForm($obj, 'page_content');
        $xoops->tpl()->assign('form', $form->render());
        break;

    case 'edit':
        $admin_page->addItemButton(PageLocale::A_LIST_CONTENT, 'content.php', 'application-view-detail');
        $admin_page->addItemButton(PageLocale::A_ADD_CONTENT, 'content.php?op=new', 'add');
        $admin_page->renderButton();
        // Create form
        $content_id = Request::getInt('content_id', 0);
        $obj = $content_Handler->get($content_id);
        $form = $helper->getForm($obj, 'page_content');
        $xoops->tpl()->assign('form', $form->render());
        break;

    case 'save':
        if (!$xoops->security()->check()) {
            $xoops->redirect('content.php', 3, implode(',', $xoops->security()->getErrors()));
        }

        $content_id = Request::getInt('content_id', 0);
        if ($content_id > 0) {
            $obj = $content_Handler->get($content_id);
        } else {
            $obj = $content_Handler->create();
        }

        $error_message = '';
        $error = false;

        $obj->setVar('content_title', Request::getString('content_title', ''));
        $obj->setVar('content_shorttext', Request::getString('content_shorttext', ''));
        $obj->setVar('content_text', Request::getString('content_text', ''));
        $obj->setVar('content_mkeyword', Request::getString('content_mkeyword', ''));
        $obj->setVar('content_mdescription', Request::getString('content_mdescription', ''));

        $date_create = Request::getArray('content_create', array());
        if (count($date_create) == 1) {
            $content_create = strtotime($date_create['date']);
        } elseif (count($date_create) == 2) {
            $content_create = strtotime($date_create['date']) + $date_create['time'];
        } else {
            $content_create = time();
        }
        $obj->setVar('content_create', $content_create);

        $obj->setVar('content_author', Request::getInt('content_author', $helper->xoops()->user->getVar('uid')));
        $obj->setVar('content_status', Request::getInt('content_status', 1));
        $obj->setVar('content_maindisplay', Request::getInt('content_maindisplay', 1));

        $content_option = Request::getArray('content_option', array());
        $obj->setVar('content_dopdf', in_array('pdf', $content_option));
        $obj->setVar('content_doprint', in_array('print', $content_option));
        $obj->setVar('content_domail', in_array('mail', $content_option));
        $obj->setVar('content_doauthor', in_array('author', $content_option));
        $obj->setVar('content_dodate', in_array('date', $content_option));
        $obj->setVar('content_dohits', in_array('hits', $content_option));
        $obj->setVar('content_dorating', in_array('rating', $content_option));
        $obj->setVar('content_doncoms', in_array('ncoms', $content_option));
        $obj->setVar('content_docoms', in_array('coms', $content_option));
        $obj->setVar('content_dosocial', in_array('social', $content_option));
        $obj->setVar('content_dotitle', in_array('title', $content_option));
        $obj->setVar('content_donotifications', in_array('notifications', $content_option));

        if (preg_match('/^\d+$/', Request::getInt('content_weight', 0)) == false) {
            $error = true;
            $error_message .= PageLocale::E_WEIGHT . '<br />';
            $obj->setVar('content_weight', 0);
        } else {
            $obj->setVar('content_weight', Request::getInt('content_weight', 0));
        }
        if ($error == true) {
            $xoops->tpl()->assign('error_message', $error_message);
        } else {
            if ($newcontent_id = $content_Handler->insert($obj)) {
                // update permissions
                $perm_id = $content_id > 0 ? $content_id : $newcontent_id;
                $groups_view_item = Request::getArray('groups_view_item', array());
                $gperm_Handler->updatePerms($perm_id, $groups_view_item);

                //notifications
                if ($content_id == 0 && $xoops->isActiveModule('notifications')) {
                    $notification_handler = Notifications::getInstance()->getHandlerNotification();
                    $tags = array();
                    $tags['MODULE_NAME'] = 'page';
                    $tags['ITEM_NAME'] = Request::getString('content_title', '');
                    $tags['ITEM_URL'] = \XoopsBaseConfig::get('url') . '/modules/page/viewpage.php?id=' . $newcontent_id;
                    $notification_handler->triggerEvent('global', 0, 'newcontent', $tags);
                    $notification_handler->triggerEvent('item', $newcontent_id, 'newcontent', $tags);
                }
                $xoops->redirect('content.php', 2, XoopsLocale::S_DATABASE_UPDATED);
            }
            echo $xoops->alert('error', $obj->getHtmlErrors());
        }
        $form = $helper->getForm($obj, 'page_content');
        $xoops->tpl()->assign('form', $form->render());
        break;

    case 'delete':
        $admin_page->addItemButton(PageLocale::A_LIST_CONTENT, 'content.php', 'application-view-detail');
        $admin_page->addItemButton(PageLocale::A_ADD_CONTENT, 'content.php?op=new', 'add');
        $admin_page->renderButton();

        $content_id = Request::getInt('content_id', 0);
        $ok = Request::getInt('ok', 0);

        $obj = $content_Handler->get($content_id);
        if ($ok == 1) {
            if (!$xoops->security()->check()) {
                $xoops->redirect('content.php', 3, implode(',', $xoops->security()->getErrors()));
            }
            // Deleting the content
            if ($content_Handler->delete($obj)) {
                // update permissions
                $gperm_Handler->updatePerms($content_id);

                // deleting page_related_link
                $criteria = new CriteriaCompo();
                $criteria->add(new Criteria('link_content_id', $content_id));
                $link_Handler->deleteAll($criteria);

                // deleting comments
                if ($xoops->isActiveModule('comments')) {
                    $comment_handler = Comments::getInstance()->getHandlerComment()->deleteByItemId($helper->getModule()->getVar('mid'), $content_id);
                }

                $xoops->redirect('content.php', 2, XoopsLocale::S_DATABASE_UPDATED);
            } else {
                echo $xoops->alert('error', $obj->getHtmlErrors());
            }
        } else {
            // deleting main and secondary
            echo $xoops->confirm(
                array('ok' => 1, 'content_id' => $content_id, 'op' => 'delete'),
                'content.php',
                XoopsLocale::Q_ARE_YOU_SURE_YOU_WANT_TO_DELETE_THIS_ITEM
                . '<br /><span class="red">' . $obj->getvar('content_title') . '<span>'
            );
        }
        break;

    case 'update_status':
        $xoops->security()->requireAjaxToken();
        $content_id = Request::getInt('content_id', 0);
        if ($content_id > 0) {
            $obj = $content_Handler->get($content_id);
            $old = $obj->getVar('content_status');
            $obj->setVar('content_status', !$old);
            if ($content_Handler->insert($obj)) {
                exit;
            }
            echo $obj->getHtmlErrors();
        }
        break;

    case 'update_display':
        $xoops->security()->requireAjaxToken();
        $content_id = Request::getInt('content_id', 0);
        if ($content_id > 0) {
            $obj = $content_Handler->get($content_id);
            $old = $obj->getVar('content_maindisplay');
            $obj->setVar('content_maindisplay', !$old);
            if ($content_Handler->insert($obj)) {
                exit;
            }
            echo $obj->getHtmlErrors();
        }
        break;

    case 'clone':
        $content_id = Request::getInt('content_id', 0);
        $obj = $content_Handler->getClone($content_id);

        if ($newcontent_id = $content_Handler->insert($obj)) {
            $gperm_arr = $gperm_Handler->getGroupIds('page_view_item', $content_id, $module_id);
            $gperm_Handler->updatePerms($newcontent_id, array_values($gperm_arr));
            $xoops->redirect('content.php', 2, XoopsLocale::S_DATABASE_UPDATED);
        }
        echo $xoops->alert('error', $obj->getHtmlErrors());
        break;
}
$xoops->footer();
