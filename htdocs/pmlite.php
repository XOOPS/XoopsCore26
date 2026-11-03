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
use Xoops\Core\XoopsTpl;

/**
 * XOOPS message processing
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         core
 * @since           2.0.0
 * @version         $Id$
 */

include __DIR__ . '/mainfile.php';
$xoops = Xoops::getInstance();
$xoops_url = \XoopsBaseConfig::get('url');
$xoops->events()->triggerEvent('core.pmlite.start');

$reply = !empty($_GET['reply']) ? 1 : 0;
$send = !empty($_GET['send']) ? 1 : 0;
$send2 = !empty($_GET['send2']) ? 1 : 0;
$sendmod = !empty($_POST['sendmod']) ? 1 : 0; // send from other modules with post data
$to_userid = isset($_GET['to_userid']) ? (int)($_GET['to_userid']) : 0;
$msg_id = isset($_GET['msg_id']) ? (int)($_GET['msg_id']) : 0;

if (empty($_GET['refresh']) && isset($_POST['op']) && $_POST['op'] !== "submit") {
    $jump = "pmlite.php?refresh=" . time();
    if ($send == 1) {
        $jump .= "&amp;send={$send}";
    } else {
        if ($send2 == 1) {
            $jump .= "&amp;send2={$send2}&amp;to_userid={$to_userid}";
        } else {
            if ($reply == 1) {
                $jump .= "&amp;reply={$reply}&amp;msg_id={$msg_id}";
            } else {
            }
        }
    }
    header('location: ' . $jump);
    exit();
}

if (!$xoops->isUser()) {
    $xoops->redirect($xoops_url, 3, XoopsLocale::E_NO_ACCESS_PERMISSION);
}
$xoops->simpleHeader();

$myts = \Xoops\Core\Text\Sanitizer::getInstance();
if (isset($_POST['op']) && $_POST['op'] === "submit") {
    $member_handler = $xoops->getHandlerMember();
    $count = $member_handler->getUserCount(new Criteria('uid', (int)($_POST['to_userid'])));
    $tpl = new XoopsTpl();
    if ($count != 1) {
        $error_message = XoopsLocale::E_SELECTED_USER_DOES_NOT_EXIST;
        $error_message .= "<br />" . XoopsLocale::E_GO_BACK_AND_TRY_AGAIN;
        $error_message .= "<br />[ <a href='javascript:history.go(-1)'>" . XoopsLocale::GO_BACK . "</a> ]";
        $tpl->assign('error_message', $error_message);
    } else {
        if ($xoops->security()->check()) {
            $pm_handler = $xoops->getHandlerPrivateMessage();
            $pm = $pm_handler->create();
            $pm->setVar("msg_time", time());
            $icon = (string) ($_POST['msg_image'] ?? '');
            if (1 === preg_match('/^[\w-]{1,60}\.(?:gif|png|jpe?g)\z/i', $icon)) {
                $pm->setVar("msg_image", $icon);
            }
            $pm->setVar("subject", $_POST['subject']);
            $pm->setVar("msg_text", $_POST['message']);
            $pm->setVar("to_userid", $_POST['to_userid']);
            $pm->setVar("from_userid", $xoops->user->getVar("uid"));
            if (!$pm_handler->insert($pm)) {
                $error_message = $pm->getHtmlErrors();
                $error_message .= "<br /><a href='javascript:history.go(-1)'>" . XoopsLocale::GO_BACK . "</a>";
                $tpl->assign('error_message', $error_message);
            } else {
                // @todo: Send notification email if user has selected this in the profile
                $info_message = XoopsLocale::S_MESSAGED_HAS_BEEN_POSTED;
                $info_message .= "<br />";
                $info_message .= "<br /><a href=\"javascript:window.opener.location='" . $xoops_url . "/viewpmsg.php';window.close();\">" . XoopsLocale::CLICK_HERE_TO_VIEW_YOU_PRIVATE_MESSAGES . "</a>";
                $info_message .= "<br /><br /><a href=\"javascript:window.close();\">" . XoopsLocale::OR_CLICK_HERE_TO_CLOSE_WINDOW . "</a>";
                $tpl->assign('info_message', $info_message);
            }
        } else {
            $error_message = implode('<br />', $xoops->security()->getErrors());
            $error_message .= "<br /><a href=\"javascript:window.close();\">" . XoopsLocale::OR_CLICK_HERE_TO_CLOSE_WINDOW . "</a>";
            $tpl->assign('error_message', $error_message);
        }
    }
    $tpl->display("module:system/system_pmlite.tpl");

} else {
    $message = '';
    $pm_uname = '';
    if ($reply == 1 || $send == 1 || $send2 == 1 || $sendmod == 1) {
        if ($reply == 1) {
            $pm_handler = $xoops->getHandlerPrivateMessage();
            $pm = $pm_handler->get($msg_id);
            if ($pm->getVar("to_userid") == $xoops->user->getVar('uid')) {
                $pm_uname = XoopsUser::getUnameFromId($pm->getVar("from_userid"));
                $message = "[quote]\n";
                $message .= sprintf(XoopsLocale::CF_WROTE, $pm_uname);
                $message .= "\n" . $pm->getVar("msg_text", "E") . "\n[/quote]";
            } else {
                unset($pm);
                $reply = 0;
                $send2 = 0;
            }
        }

        $tpl = new XoopsTpl();
        $form = new Xoops\Form\ThemeForm('', 'pmform', 'pmlite.php', 'post', true);

        if ($reply == 1) {
            $subject = $pm->getVar('subject', 'E');
            if (!preg_match("/^" . XoopsLocale::C_RE . "/i", $subject)) {
                $subject = XoopsLocale::C_RE . ' ' . $subject;
            }
            $form->addElement(new Xoops\Form\Label(XoopsLocale::C_TO, $pm_uname));
            $form->addElement(new Xoops\Form\Hidden('to_userid', $pm->getVar("from_userid")));
        } else {
            if ($sendmod == 1) {
                $form->addElement(new Xoops\Form\Label(XoopsLocale::C_TO, XoopsUser::getUnameFromId($_POST["to_userid"])));
                $form->addElement(new Xoops\Form\Hidden('to_userid', $_POST["to_userid"]));
                $subject = $myts->htmlSpecialChars($_POST['subject']);
                $message = $myts->htmlSpecialChars($_POST['message']);
            } else {
                if ($send2 == 1) {
                    $form->addElement(new Xoops\Form\Label(XoopsLocale::C_TO, XoopsUser::getUnameFromId($to_userid, false)));
                    $form->addElement(new Xoops\Form\Hidden('to_userid', $to_userid));
                } else {
                    $form->addElement(new Xoops\Form\SelectUser(XoopsLocale::C_TO, 'to_userid'));
                }
                $subject = "";
                $message = "";
            }
        }
        $form->addElement(new Xoops\Form\Text(XoopsLocale::SUBJECT, 'subject', 4, 100, $subject), true);

        //$icons = new Xoops\Form\Radio(XoopsLocale::MESSAGE_ICON, 'msg_image', '', true);
        //\Xoops\Core\Lists\SubjectIcon::setOptionsArray($icons);
        //$form->addElement($icons, false);
        $form->addElement(new Xoops\Form\DhtmlTextArea(XoopsLocale::MESSAGE, 'message', $message, 8, 37), true);
        $form->addElement(new Xoops\Form\Hidden('op', 'submit'));

        $buttons = new Xoops\Form\ElementTray('');
        $buttons ->addElement(new Xoops\Form\Button('', 'submit', XoopsLocale::A_SUBMIT, 'submit'));
        $buttons ->addElement(new Xoops\Form\Button('', 'reset', XoopsLocale::A_CLEAR, 'reset'));
        $cancel_send = new Xoops\Form\Button('', 'cancel', XoopsLocale::CANCEL_SEND, 'button');
        $cancel_send->set('onclick', 'javascript:window.close();');
        $buttons ->addElement($cancel_send);
        $form->addElement($buttons);
        $tpl->assign('form', $form->render());
        $tpl->display("module:system/system_pmlite.tpl");
    }
}
$xoops->simpleFooter();
