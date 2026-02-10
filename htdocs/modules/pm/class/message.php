<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\Kernel\Dtype;
use Xoops\Core\Kernel\Handlers\XoopsUser;
use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;
use Xoops\Core\Database\Connection;

/**
 * Private message module
 *
 * @package   pm
 * @author    Jan Pedersen
 * @author    Taiwen Jiang <phppp@users.sourceforge.net>
 * @author    Kazumi Ono    <onokazu@xoops.org>
 * @copyright 2000-2016 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class PmMessage extends XoopsObject
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initVar('msg_id', Dtype::TYPE_INTEGER, null, false);
        $this->initVar('msg_image', Dtype::TYPE_OTHER, 'icon1.gif', false, 100);
        $this->initVar('subject', Dtype::TYPE_TEXT_BOX, null, true, 255);
        $this->initVar('from_userid', Dtype::TYPE_INTEGER, null, true);
        $this->initVar('to_userid', Dtype::TYPE_INTEGER, null, true);
        $this->initVar('msg_time', Dtype::TYPE_INTEGER, time(), false);
        $this->initVar('msg_text', Dtype::TYPE_TEXT_AREA, null, true);
        $this->initVar('read_msg', Dtype::TYPE_INTEGER, 0, false);
        $this->initVar('from_delete', Dtype::TYPE_INTEGER, 1, false);
        $this->initVar('to_delete', Dtype::TYPE_INTEGER, 0, false);
        $this->initVar('from_save', Dtype::TYPE_INTEGER, 0, false);
        $this->initVar('to_save', Dtype::TYPE_INTEGER, 0, false);
    }
}

/**
 * Class PmMessageHandler persistence of PmMessages
 */
class PmMessageHandler extends XoopsPersistableObjectHandler
{
    /**
     * @param null|Connection $db
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'system_privatemessage', 'PmMessage', 'msg_id', 'subject');
    }

    /**
     * Mark a message as read
     *
     * @param PmMessage $pm
     * @param int       $val
     * @return bool
     */
    public function setRead(PmMessage $pm, $val = 1)
    {
        return $this->updateAll('read_msg', (int)($val), new Criteria('msg_id', $pm->getVar('msg_id')), true);
    }

    /**
     * Mark a message as from_delete = 1 or removes it if the recipient has also deleted it
     *
     * @param PmMessage $pm
     * @param int       $val
     * @return bool
     */
    public function setFromDelete(PmMessage $pm, $val = 1)
    {
        if ($pm->getVar('to_delete') == 0) {
            return $this->updateAll('from_delete', (int)($val), new Criteria('msg_id', $pm->getVar('msg_id')));
        } else {
            return parent::delete($pm);
        }
    }

    /**
     * Mark a message as to_delete = 1 or removes it if the sender has also deleted it or sent by anonymous
     *
     * @param PmMessage $pm
     * @param int       $val
     * @return bool
     */
    public function setTodelete(PmMessage $pm, $val = 1)
    {
        if ($pm->getVar('from_delete') == 0 && $pm->getVar('from_userid') == 0) {
            return $this->updateAll('to_delete', (int)($val), new Criteria('msg_id', $pm->getVar('msg_id')));
        } else {
            return parent::delete($pm);
        }
    }

    /**
     * Mark a message as from_save = 1
     *
     * @param PmMessage $pm
     * @param int       $val
     * @return bool
     */
    public function setFromsave(PmMessage $pm, $val = 1)
    {
        return $this->updateAll('from_save', (int)($val), new Criteria('msg_id', $pm->getVar('msg_id')));
    }

    /**
     * Mark a message as to_save = 1
     *
     * @param PmMessage $pm
     * @param int       $val
     * @return bool
     */
    public function setTosave(PmMessage $pm, $val = 1)
    {
        return $this->updateAll('to_save', (int)($val), new Criteria('msg_id', $pm->getVar('msg_id')));
    }

    /**
     * get user's message count in savebox
     *
     * @param XoopsUser|null $user
     * @return int
     */
    public function getSavecount(?XoopsUser $user = null)
    {
        $xoops = Xoops::getInstance();
        if (!is_object($user)) {
            $user = $xoops->user;
        }
        $criteriaTo = new CriteriaCompo(new Criteria('to_delete', 0));
        $criteriaTo->add(new Criteria('to_save', 1));
        $criteriaTo->add(new Criteria('to_userid', $user->getVar('uid')));
        $criteriaFrom = new CriteriaCompo(new Criteria('from_delete', 0));
        $criteriaFrom->add(new Criteria('from_save', 1));
        $criteriaFrom->add(new Criteria('from_userid', $user->getVar('uid')));
        $criteria = new CriteriaCompo($criteriaTo);
        $criteria->add($criteriaFrom, "OR");
        return $this->getCount($criteria);
    }

    /**
     * Send a message to user's email
     *
     * @param PmMessage      $pm
     * @param null|XoopsUser $user
     * @return bool
     */
    public function sendEmail(PmMessage $pm, ?XoopsUser $user = null)
    {
        $xoops = Xoops::getInstance();
        if (!is_object($user)) {
            $user = $xoops->user;
        }
        $msg = sprintf(_PM_EMAIL_DESC, $user->getVar("uname"));
        $msg .= "\n\n";
        $msg .= XoopsLocale::formatTimestamp($pm->getVar("msg_time"));
        $msg .= "\n";
        $from = new XoopsUser($pm->getVar("from_userid"));
        $to = new XoopsUser($pm->getVar("to_userid"));
        $msg .= sprintf(_PM_EMAIL_FROM, $from->getVar("uname") . " (" . \XoopsBaseConfig::get('url')
            . "/userinfo.php?uid=" . $pm->getVar("from_userid") . ")");
        $msg .= "\n";
        $msg .= sprintf(_PM_EMAIL_TO, $to->getVar("uname") . " (" . \XoopsBaseConfig::get('url')
            . "/userinfo.php?uid=" . $pm->getVar("to_userid") . ")");
        $msg .= "\n";
        $msg .= _PM_EMAIL_MESSAGE . ":\n";
        $msg .= "\n" . $pm->getVar("subject") . "\n";
        $msg .= "\n" . strip_tags(str_replace(array(
                                                   "<p>", "</p>", "<br />", "<br />"
                                              ), "\n", $pm->getVar("msg_text"))) . "\n\n";
        $msg .= "--------------\n";
        $msg .= $xoops->getConfig('sitename') . ": " . \XoopsBaseConfig::get('url') . "\n";

        $xoopsMailer = $xoops->getMailer();
        $xoopsMailer->useMail();
        $xoopsMailer->setToEmails($user->getVar("email"));
        $xoopsMailer->setFromEmail($xoops->getConfig('adminmail'));
        $xoopsMailer->setFromName($xoops->getConfig('sitename'));
        $xoopsMailer->setSubject(sprintf(_PM_EMAIL_SUBJECT, $pm->getVar("subject")));
        $xoopsMailer->setBody($msg);
        return $xoopsMailer->send();
    }

    /**
     * Get form for setting prune criteria
     *
     * @return Xoops\Form\ThemeForm
     **/
    public function getPruneForm()
    {
        $form = new Xoops\Form\ThemeForm(_PM_AM_PRUNE, 'form', 'prune.php', 'post', true);

        $after = new Xoops\Form\DateSelect(_PM_AM_PRUNEAFTER, 'after');
        $after->set('value', '');
        $form->addElement($after);
        $before = new Xoops\Form\DateSelect(_PM_AM_PRUNEBEFORE, 'before');
        $before->set('value', '');
        $form->addElement($before);
        $form->addElement(new Xoops\Form\RadioYesNo(_PM_AM_ONLYREADMESSAGES, 'onlyread', 1));
        $form->addElement(new Xoops\Form\RadioYesNo(_PM_AM_INCLUDESAVE, 'includesave', 0));
        $form->addElement(new Xoops\Form\RadioYesNo(_PM_AM_NOTIFYUSERS, 'notifyusers', 0));
        $form->addElement(new Xoops\Form\Hidden('op', 'prune'));
        $form->addElement(new Xoops\Form\Button('', 'submit', XoopsLocale::A_SUBMIT, 'submit'));

        return $form;
    }
}
