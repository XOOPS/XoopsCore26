<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;
use Xmf\Request;

/**
 * Private message
 *
 * @copyright       2000-2016 XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         pm
 * @since           2.3.0
 * @author          Jan Pedersen
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 */
include __DIR__ . '/header.php';

$xoops = Xoops::getInstance();
$xoops->header();

$indexAdmin = new \Xoops\Module\Admin();
$indexAdmin->displayNavigation('prune.php');

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : "form";
/* @var $pmHandler PmMessageHandler */
$pmHandler = $xoops->getModuleHandler('message');

switch ($op) {
    default:
    case "form":
        $form = $pmHandler->getPruneForm();
        $form->display();
        break;

    case "prune":
        if ('POST' !== Request::getMethod() || !$xoops->security()->check()) {
            $xoops->redirect('prune.php', 3, implode('<br />', $xoops->security()->getErrors()));
        }
        $criteria = new CriteriaCompo();
        $afterDateTime = Request::getDateTime('after', null);
        if (null !== $afterDateTime) {
            $criteria->add(new Criteria('msg_time', $afterDateTime->getTimestamp(), ">"));
        }
        $beforeDateTime = Request::getDateTime('before', null);
        if (null !== $beforeDateTime) {
            $criteria->add(new Criteria('msg_time', $beforeDateTime->getTimestamp(), "<"));
        }
        if (isset($_REQUEST['onlyread']) && $_REQUEST['onlyread'] == 1) {
            $criteria->add(new Criteria('read_msg', 1));
        }
        if ((!isset($_REQUEST['includesave']) || $_REQUEST['includesave'] == 0)) {
            $savecriteria = new CriteriaCompo(new Criteria('to_save', 0));
            $savecriteria->add(new Criteria('from_save', 0));
            $criteria->add($savecriteria);
        }
        if (isset($_REQUEST['notifyusers']) && $_REQUEST['notifyusers'] == 1) {
            $notifycriteria = clone $criteria;
            $notifycriteria->add(new Criteria('to_delete', 0));
            $notifycriteria->setGroupBy('to_userid');
            // Get array of uid => number of deleted messages
            $uids = $pmHandler->getCount($notifycriteria);
        }
        $deletedrows = 0; // $pmHandler->deleteAll($criteria);
        if ($deletedrows === false) {
            $xoops->redirect('prune.php', 2, _PM_AM_ERRORWHILEPRUNING);
        }
        if (isset($_REQUEST['notifyusers']) && $_REQUEST['notifyusers'] == 1) {
            $errors = false;
            foreach ($uids as $uid => $messagecount) {
                $pm = $pmHandler->create();
                $pm->setVar("subject", $xoops->getModuleConfig('prunesubject'));
                $pm->setVar("msg_text", str_replace('{PM_COUNT}', $messagecount, $xoops->getModuleConfig('prunemessage')));
                $pm->setVar("to_userid", $uid);
                $pm->setVar("from_userid", $xoops->user->getVar("uid"));
                $pm->setVar("msg_time", time());
                if (!$pmHandler->insert($pm)) {
                    $errors = true;
                    $errormsg[] = $pm->getHtmlErrors();
                }
                unset($pm);
            }
            if ($errors == true) {
                echo implode('<br />', $errormsg);
                $xoops->footer();
            }
        }
        $xoops->redirect('index.php', 2, sprintf(_PM_AM_MESSAGESPRUNED, $deletedrows));
        break;
}
$xoops->footer();
