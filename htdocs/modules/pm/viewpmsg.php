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
use Xoops\Html\Menu\ItemList;
use Xoops\Html\Menu\Link;
use Xoops\Html\Menu\Render\BreadCrumb;
use Xoops\Html\Menu\Render\DropDownButton;

/**
 * Private message module
 *
 * @copyright       2000-2016 XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         pm
 * @since           2.3.0
 * @author          Jan Pedersen
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 */

include_once dirname(dirname(__DIR__)) . '/mainfile.php';

$xoops = Xoops::getInstance();

if (!$xoops->isUser()) {
    $xoops->redirect(\XoopsBaseConfig::get('url'), 3, XoopsLocale::E_NO_ACCESS_PERMISSION);
}

$xoops->disableModuleCache(); //disable caching since the URL will be the same, but content different from one user to another
$xoops->header('module:pm/pm_viewpmsg.tpl');

$validOpRequests = array('out', 'save', 'in');
$op = Request::getCmd('op', 'in');
$op = in_array($op, $validOpRequests) ? $op : 'in';

$start = empty($_REQUEST["start"]) ? 0 : (int)($_REQUEST["start"]);
/* @var $pm_handler PmMessageHandler */
$pm_handler = $xoops->getModuleHandler('message');

if (isset($_POST['delete_messages']) && (isset($_POST['msg_id']) || isset($_POST['msg_ids']))) {
    if (!$xoops->security()->check()) {
        $xoops->tpl()->assign('errormsg', implode('<br />', $xoops->security()->getErrors()));
    } else {
        if (empty($_REQUEST['ok'])) {
            echo $xoops->confirm(array(
                                 'ok' => 1, 'delete_messages' => 1, 'op' => $op,
                                 'msg_ids' => json_encode(array_values(array_map('intval', (array) ($_POST['msg_id'] ?? []))))
                            ), $_SERVER['REQUEST_URI'], XoopsLocale::Q_ARE_YOU_SURE_YOU_WANT_TO_DELETE_THIS_MESSAGES);
            $xoops->footer();
        } else {
            $clean_msg_id = json_decode((string) ($_POST['msg_ids'] ?? ''), true, 2);
            $msg = is_array($clean_msg_id) ? array_values(array_map('intval', $clean_msg_id)) : [];
            $size = count($msg);
            for ($i = 0; $i < $size; ++$i) {
                $pm = $pm_handler->get($msg[$i]);
                if (is_object($pm)) {
                    $pm_handler->deleteFromFolder($pm, $xoops->user->getVar('uid'), $op);
                }
                unset($pm);
            }
            $xoops->tpl()->assign('msg', XoopsLocale::S_YOUR_MESSAGES_DELETED);
        }
    }
}
if (isset($_POST['move_messages']) && isset($_POST['msg_id'])) {
    if (!$xoops->security()->check()) {
        $xoops->tpl()->assign('errormsg', implode('<br />', $xoops->security()->getErrors()));
    } else {
        $msg = array_values(array_map('intval', (array) $_POST['msg_id']));
        $size = count($msg);
        $uid = $xoops->user->getVar('uid');
        if ($_POST['op'] === 'save') {
            for ($i = 0; $i < $size; ++$i) {
                $pm = $pm_handler->get($msg[$i]);
                if (!is_object($pm)) {
                    continue;
                }
                if ($pm->getVar('to_userid') == $xoops->user->getVar('uid')) {
                    $pm_handler->setTosave($pm, 0);
                } else {
                    if ($pm->getVar('from_userid') == $xoops->user->getVar('uid')) {
                        $pm_handler->setFromsave($pm, 0);
                    }
                }
                unset($pm);
            }
        } else {
            if (!$xoops->user->isAdmin()) {
                $total_save = $pm_handler->getSavecount();
                $size = min($size, $xoops->getModuleConfig('max_save') - $total_save);
            }
            for ($i = 0; $i < $size; ++$i) {
                $pm = $pm_handler->get($msg[$i]);
                if (!is_object($pm)) {
                    continue;
                }
                if ($_POST['op'] === 'in' && $pm->getVar('to_userid') == $uid) {
                    $pm_handler->setTosave($pm);
                } elseif ($_POST['op'] === 'out' && $pm->getVar('from_userid') == $uid) {
                    $pm_handler->setFromsave($pm);
                }
                unset($pm);
            }
        }
        if ($_POST['op'] === 'save') {
            $xoops->tpl()->assign('msg', _PM_UNSAVED);
        } else {
            if (isset($total_save) && !$xoops->user->isAdmin()) {
                $xoops->tpl()->assign('msg', sprintf(_PM_SAVED_PART, $xoops->getModuleConfig('max_save'), $i));
            } else {
                $xoops->tpl()->assign('msg', _PM_SAVED_ALL);
            }
        }
    }
}
if (isset($_REQUEST['empty_messages'])) {
    if (!$xoops->security()->check()) {
        $xoops->tpl()->assign('errormsg', implode('<br />', $xoops->security()->getErrors()));
    } else {
        if (empty($_REQUEST['ok'])) {
            echo $xoops->confirm(array(
                                 'ok' => 1, 'empty_messages' => 1, 'op' => $op
                            ), $_SERVER['REQUEST_URI'], _PM_RUSUREEMPTY);
            $xoops->footer();
        } else {
            if ($_POST['op'] === 'save') {
                $crit_to = new CriteriaCompo(new Criteria('to_delete', 0));
                $crit_to->add(new Criteria('to_save', 1));
                $crit_to->add(new Criteria('to_userid', $xoops->user->getVar('uid')));
                $crit_from = new CriteriaCompo(new Criteria('from_delete', 0));
                $crit_from->add(new Criteria('from_save', 1));
                $crit_from->add(new Criteria('from_userid', $xoops->user->getVar('uid')));
                $criteria = new CriteriaCompo($crit_to);
                $criteria->add($crit_from, "OR");
            } else {
                if ($_POST['op'] === 'out') {
                    $criteria = new CriteriaCompo(new Criteria('from_delete', 0));
                    $criteria->add(new Criteria('from_userid', $xoops->user->getVar('uid')));
                    $criteria->add(new Criteria('from_save', 0));
                } else {
                    $criteria = new CriteriaCompo(new Criteria('to_delete', 0));
                    $criteria->add(new Criteria('to_userid', $xoops->user->getVar('uid')));
                    $criteria->add(new Criteria('to_save', 0));
                }
            }
            /*
            * The following method has critical scalability problem !
            * deleteAll method should be used instead
            */
            $pms = $pm_handler->getObjects($criteria);
            unset($criteria);
            if (count($pms) > 0) {
                foreach (array_keys($pms) as $i) {
                    if ($pms[$i]->getVar('to_userid') == $xoops->user->getVar('uid')) {
                        if ($_POST['op'] === 'save') {
                            $pm_handler->setTosave($pms[$i], 0);
                        } else {
                            if ($_POST['op'] === 'in') {
                                $pm_handler->setTodelete($pms[$i]);
                            }
                        }
                    }
                    if ($pms[$i]->getVar('from_userid') == $xoops->user->getVar('uid')) {
                        if ($_POST['op'] === 'save') {
                            $pm_handler->setFromsave($pms[$i], 0);
                        } else {
                            if ($_POST['op'] === 'out') {
                                $pm_handler->setFromdelete($pms[$i]);
                            }
                        }
                    }
                }
            }
            $xoops->tpl()->assign('msg', _PM_EMPTIED);
        }
    }
}

if ($op === "out") {
    $criteria = new CriteriaCompo(new Criteria('from_delete', 0));
    $criteria->add(new Criteria('from_userid', $xoops->user->getVar('uid')));
    $criteria->add(new Criteria('from_save', 0));
} else {
    if ($op === "save") {
        $crit_to = new CriteriaCompo(new Criteria('to_delete', 0));
        $crit_to->add(new Criteria('to_save', 1));
        $crit_to->add(new Criteria('to_userid', $xoops->user->getVar('uid')));
        $crit_from = new CriteriaCompo(new Criteria('from_delete', 0));
        $crit_from->add(new Criteria('from_save', 1));
        $crit_from->add(new Criteria('from_userid', $xoops->user->getVar('uid')));
        $criteria = new CriteriaCompo($crit_to);
        $criteria->add($crit_from, "OR");
    } else {
        $criteria = new CriteriaCompo(new Criteria('to_delete', 0));
        $criteria->add(new Criteria('to_userid', $xoops->user->getVar('uid')));
        $criteria->add(new Criteria('to_save', 0));
    }
}
$total_messages = $pm_handler->getCount($criteria);
$criteria->setStart($start);
$criteria->setLimit($xoops->getModuleConfig('perpage'));
$criteria->setSort("msg_time");
$criteria->setOrder("DESC");
$pm_arr = $pm_handler->getAll($criteria, null, false, false);
unset($criteria);

$xoops->tpl()->assign('total_messages', $total_messages);
$xoops->tpl()->assign('op', $op);

if ($total_messages > $xoops->getModuleConfig('perpage')) {
    $nav = new XoopsPageNav($total_messages, $xoops->getModuleConfig('perpage'), $start, "start", 'op=' . $op);
    $xoops->tpl()->assign('pagenav', $nav->renderNav(4));
}

$xoops->tpl()->assign('display', $total_messages > 0);
$xoops->tpl()->assign('anonymous', $xoops->getConfig('anonymous'));
if (count($pm_arr) > 0) {
    foreach (array_keys($pm_arr) as $i) {
        if ($op === 'out') {
            $uids[] = $pm_arr[$i]['to_userid'];
        } else {
            $uids[] = $pm_arr[$i]['from_userid'];
        }
    }
    $member_handler = $xoops->getHandlerMember();
    $senders = $member_handler->getUserList(new Criteria('uid', "(" . implode(", ", array_unique($uids)) . ")", "IN"));
    foreach (array_keys($pm_arr) as $i) {
        $message = $pm_arr[$i];
        //$message['msg_time'] = XoopsLocale::formatTimestamp($message["msg_time"], 'e');
        if ($op === "out") {
            $message['postername'] = $senders[$pm_arr[$i]['to_userid']];
            $message['posteruid'] = $pm_arr[$i]['to_userid'];
        } else {
            $message['postername'] = $senders[$pm_arr[$i]['from_userid']];
            $message['posteruid'] = $pm_arr[$i]['from_userid'];
        }
        $message['msg_no'] = $i;
        $xoops->tpl()->append('messages', $message);
    }
}

$send_button = new Xoops\Form\Button('', 'send', XoopsLocale::A_SEND);
$send_button->set(
    'onclick',
    'openWithSelfMain("' . $xoops->url('modules/pm/pmlite.php?send=1') . '" , "pmlite", 740,640);'
);
$delete_button = new Xoops\Form\Button('', 'delete_messages', XoopsLocale::A_DELETE, 'submit');
$move_button = new Xoops\Form\Button('', 'move_messages', ($op === 'save') ? _PM_UNSAVE
            : _PM_TOSAVE, 'submit');
$empty_button = new Xoops\Form\Button('', 'empty_messages', _PM_EMPTY, 'submit');

$pmform = new Xoops\Form\ThemeForm('', 'pmform', 'viewpmsg.php', 'post', true);
$pmform->addElement($send_button);
$pmform->addElement($move_button);
$pmform->addElement($delete_button);
$pmform->addElement($empty_button);
$pmform->addElement(new Xoops\Form\Hidden('op', $op));
$pmform->assign($xoops->tpl());

$bcMenu = new ItemList();
if ($op === 'out') {
    $bcMenu->addItem(new Link(['caption' => _PM_OUTBOX, 'icon' => 'glyphicon glyphicon-share']));
} elseif ($op === 'save') {
    $bcMenu->addItem(new Link(['caption' => _PM_SAVEBOX, 'icon' => 'glyphicon glyphicon-save']));
} else {
    $bcMenu->addItem(new Link(['caption' => XoopsLocale::INBOX, 'icon' => 'glyphicon glyphicon-inbox']));
}
$breadCrumb = new BreadCrumb();
$xoops->tpl()->assign('breadcrumbs', $breadCrumb->render($bcMenu));

$menu = new ItemList(['caption' => 'Folders', 'icon' => 'glyphicon glyphicon-folder-open']);
$menu->addItem(new Link(['caption' => XoopsLocale::INBOX, 'link' => 'viewpmsg.php?op=in', 'icon' => 'glyphicon glyphicon-inbox']));
$menu->addItem(new Link(['caption' => _PM_OUTBOX, 'link' => 'viewpmsg.php?op=out', 'icon' => 'glyphicon glyphicon-share']));
$menu->addItem(new Link(['caption' => _PM_SAVEBOX, 'link' => 'viewpmsg.php?op=save', 'icon' => 'glyphicon glyphicon-save']));

$folderMenu = new \Xoops\Html\Menu\Render\DropDownButton();
$xoops->tpl()->assign('folders', $folderMenu->render($menu));

$xoops->footer();
