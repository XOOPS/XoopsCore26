<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\Kernel\Handlers\XoopsUser;
use Xmf\Request;
use Xoops\Html\Menu\ItemList;
use Xoops\Html\Menu\Link;
use Xoops\Html\Menu\Render\BreadCrumb;

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
$validOpRequests = array('out', 'save', 'in');
$op = Request::getCmd('op', 'in');
$op = in_array($op, $validOpRequests) ? $op : 'in';
$msg_id = Request::getInt('msg_id', 0);

/* @var $pm_handler PmMessageHandler */
$pm_handler = $xoops->getModuleHandler('message');
if ($msg_id > 0) {
    $pm = $pm_handler->get($msg_id);
} else {
    $pm = null;
}

if (is_object($pm) && !$xoops->user->isAdmin() && ($pm->getVar('from_userid') != $xoops->user->getVar('uid'))
    && ($pm->getVar('to_userid') != $xoops->user->getVar('uid'))
) {
    $xoops->redirect(XOOPS_URL . '/modules/' . $xoops->module->getVar("dirname", "n") . '/index.php', 2, XoopsLocale::E_NO_ACCESS_PERMISSION);
}

if (is_object($pm) && !empty($_POST['action'])) {
    if (!$xoops->security()->check()) {
        echo implode('<br />', $xoops->security()->getErrors());
        exit();
    }
    $res = false;
    if (!empty($_REQUEST['email_message'])) {
        $res = $pm_handler->sendEmail($pm, $xoops->user);
    } elseif (!empty($_REQUEST['move_message'])
               && $op !== 'save'
               && !$xoops->user->isAdmin()
               && $pm_handler->getSavecount() >= $xoops->getModuleConfig('max_save')
    ) {
        $res_message = sprintf(_PM_SAVED_PART, $xoops->getModuleConfig('max_save'), 0);
    } elseif (!empty($_REQUEST['delete_message'])) {
        $res = (bool) $pm_handler->deleteFromFolder($pm, $xoops->user->getVar('uid'), $op);
    } elseif (!empty($_REQUEST['move_message'])) {
        $uid = $xoops->user->getVar('uid');
        switch ($op) {
            case 'out':
                if ($pm->getVar('from_userid') == $uid) {
                    $res = $pm_handler->setFromsave($pm);
                }
                break;
            case 'save':
                // A message is in the save box of its recipient, its sender, or both.
                $res1 = $res2 = null;
                if ($pm->getVar('to_userid') == $uid) {
                    $res1 = $pm_handler->setTosave($pm, 0);
                }
                if ($pm->getVar('from_userid') == $uid) {
                    $res2 = $pm_handler->setFromsave($pm, 0);
                }
                $res = (null !== $res1 || null !== $res2) && false !== $res1 && false !== $res2;
                break;
            case 'in':
            default:
                if ($pm->getVar('to_userid') == $uid) {
                    $res = $pm_handler->setTosave($pm);
                }
                break;
        }
    }
    $res_message = isset($res_message) ? $res_message : (($res) ? _PM_ACTION_DONE : _PM_ACTION_ERROR);
    $xoops->redirect('viewpmsg.php?op=' . $op, 2, $res_message);
}
$start = !empty($_GET['start']) ? (int)($_GET['start']) : 0;
$total_messages = !empty($_GET['total_messages']) ? (int)($_GET['total_messages']) : 0;
$xoops->header('module:pm/pm_readpmsg.tpl');

if (!is_object($pm)) {
    if ($op === "out") {
        $criteria = new CriteriaCompo(new Criteria('from_delete', 0));
        $criteria->add(new Criteria('from_userid', $xoops->user->getVar('uid')));
        $criteria->add(new Criteria('from_save', 0));
    } elseif ($op === "save") {
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

    $criteria->setLimit(1);
    $criteria->setStart($start);
    $criteria->setSort('msg_time');
    $criteria->setOrder("DESC");
    list($pm) = $pm_handler->getObjects($criteria);
}

$pmform = new Xoops\Form\ThemeForm('', 'pmform', 'readpmsg.php', 'post', true);
if (is_object($pm) && !empty($pm)) {
    if ($pm->getVar('from_userid') != $xoops->user->getVar('uid')) {
        $reply_button = new Xoops\Form\Button('', 'send', XoopsLocale::A_REPLY);
        $reply_button->set('onclick',
            'javascript:openWithSelfMain("'
            . $xoops->url("modules/pm/pmlite.php?reply=1&amp;msg_id={$msg_id}")
            . '", "pmlite", 740,640);'
        );
        $pmform->addElement($reply_button);
    }
    $pmform->addElement(new Xoops\Form\Button('', 'delete_message', XoopsLocale::A_DELETE, 'submit'));
    $pmform->addElement(new Xoops\Form\Button('', 'move_message', ($op === 'save') ? _PM_UNSAVE : _PM_TOSAVE, 'submit'));
    $pmform->addElement(new Xoops\Form\Button('', 'email_message', _PM_EMAIL, 'submit'));
    $pmform->addElement(new Xoops\Form\Hidden('msg_id', $pm->getVar("msg_id")));
    $pmform->addElement(new Xoops\Form\Hidden('op', $op));
    $pmform->addElement(new Xoops\Form\Hidden('action', 1));
    $pmform->assign($xoops->tpl());

    if ($pm->getVar("from_userid") == $xoops->user->getVar("uid")) {
        $poster = new XoopsUser($pm->getVar("to_userid"));
    } else {
        $poster = new XoopsUser($pm->getVar("from_userid"));
    }
    if (!is_object($poster)) {
        $xoops->tpl()->assign('poster', false);
        $xoops->tpl()->assign('anonymous', $xoopsConfig['anonymous']);
    } else {
        $xoops->tpl()->assign('poster', $poster);
        $avatar = $xoops->service('avatar')->getAvatarUrl($poster)->getValue();
        $xoops->tpl()->assign('poster_avatar', $avatar);
    }

    if ($pm->getVar("to_userid") == $xoops->user->getVar("uid") && $pm->getVar('read_msg') == 0) {
        $pm_handler->setRead($pm);
    }

    $message = $pm->getValues();
    //$message['msg_time'] = XoopsLocale::formatTimestamp($pm->getVar("msg_time"));
}


$xoops->tpl()->assign('message', $message);
$xoops->tpl()->assign('op', $op);
$xoops->tpl()->assign('previous', $start - 1);
$xoops->tpl()->assign('next', $start + 1);
$xoops->tpl()->assign('total_messages', $total_messages);

$menu = new ItemList();
if ($op === 'out') {
    $menu->addItem(new Link(['caption' => _PM_OUTBOX, 'link' => 'viewpmsg.php?op=out', 'icon' => 'glyphicon glyphicon-share']));
} elseif ($op === 'save') {
    $menu->addItem(new Link(['caption' => _PM_SAVEBOX, 'link' => 'viewpmsg.php?op=save', 'icon' => 'glyphicon glyphicon-save']));
} else {
    $menu->addItem(new Link(['caption' => XoopsLocale::INBOX, 'link' => 'viewpmsg.php?op=in', 'icon' => 'glyphicon glyphicon-inbox']));
}
$menu->addItem(new Link(['caption' => $message['subject']]));
$breadCrumb = new BreadCrumb();
$xoops->tpl()->assign('breadcrumbs', $breadCrumb->render($menu));

$xoops->footer();
