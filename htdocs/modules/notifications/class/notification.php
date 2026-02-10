<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\Database\Connection;
use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;
use Xoops\Core\Kernel\CriteriaElement;
use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;
use Xoops\Core\Kernel\Handlers\XoopsUser;

/**
 * A Notification
 *
 * @category  Module
 * @package   Notifications
 * @author    Michael van Dam <mvandam@caltech.edu>
 * @author    Kazumi Ono (AKA onokazu) http://www.myweb.ne.jp/, http://jp.xoops.org/
 * @author    trabis <lusopoemas@gmail.com>
 * @copyright 2000-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class NotificationsNotification extends XoopsObject
{
    /**
     * Constructor
     **/
    public function __construct()
    {
        $this->initVar('id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('modid', XOBJ_DTYPE_INT, null, false);
        $this->initVar('category', XOBJ_DTYPE_TXTBOX, null, false, 30);
        $this->initVar('itemid', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('event', XOBJ_DTYPE_TXTBOX, null, false, 30);
        $this->initVar('uid', XOBJ_DTYPE_INT, 0, true);
        $this->initVar('mode', XOBJ_DTYPE_INT, 0, false);
    }

    // FIXME:???
    // To send email to multiple users simultaneously, we would need to move
    // the notify functionality to the handler class.  BUT, some of the tags
    // are user-dependent, so every email msg will be unique.  (Unless maybe use
    // smarty for email templates in the future.)  Also we would have to keep
    // track if each user wanted email or PM.

    /**
     * Send a notification message to the user
     *
     * @param string $template_dir Template directory
     * @param string $template     Template name
     * @param string $subject      Subject line for notification message
     * @param array  $tags         Array of substitutions for template variables
     *
     * @return bool true if success, false if error
     **/
    public function notifyUser($template_dir, $template, $subject, $tags)
    {
        //todo fix this for mail module
        $xoops = xoops::getInstance();
        $helper = Notifications::getInstance();

        // Check the user's notification preference.
        $member_handler = $xoops->getHandlerMember();
        $user = $member_handler->getUser($this->getVar('uid'));
        if (!is_object($user)) {
            return true;
        }
        //todo, remove this from user profile
        $method = $user->getVar('notify_method');

        $xoopsMailer = $xoops->getMailer();
        switch ($method) {
            case NOTIFICATIONS_METHOD_PM:
                $xoopsMailer->usePM();
                //todo, is this config in core or in mail module?
                $xoopsMailer->setFromUser($member_handler->getUser($xoops->getConfig('fromuid')));
                foreach ($tags as $k => $v) {
                    $xoopsMailer->assign($k, $v);
                }
                break;
            case NOTIFICATIONS_METHOD_EMAIL:
                $xoopsMailer->useMail();
                foreach ($tags as $k => $v) {
                    $xoopsMailer->assign($k, preg_replace("/&amp;/i", '&', $v));
                }
                break;
            default:
                return true; // report error in user's profile??
                break;
        }

        // Set up the mailer
        $xoopsMailer->setTemplateDir($template_dir);
        $xoopsMailer->setTemplate($template);
        $xoopsMailer->setToUsers($user);
        //global $xoopsConfig;
        //$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
        //$xoopsMailer->setFromName($xoopsConfig['sitename']);
        $xoopsMailer->setSubject($subject);
        $success = $xoopsMailer->send();

        // If send-once-then-delete, delete notification
        // If send-once-then-wait, disable notification
        $notification_handler = $helper->getHandlerNotification();

        if ($this->getVar('mode') == NOTIFICATIONS_MODE_SENDONCETHENDELETE) {
            $notification_handler->delete($this);
            return $success;
        }

        if ($this->getVar('mode') == NOTIFICATIONS_MODE_SENDONCETHENWAIT) {
            $this->setVar('mode', NOTIFICATIONS_MODE_WAITFORLOGIN);
            $notification_handler->insert($this);
        }
        return $success;
    }
}

/**
 * XOOPS notification handler class.
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS notification class objects.
 *
 * @package       Notifications
 * @author        Michael van Dam <mvandam@caltech.edu>
 * @copyright     copyright (c) 2000-2003 XOOPS.org
 */
class NotificationsNotificationHandler extends XoopsPersistableObjectHandler
{
    /**
     * Constructor
     *
     * @param Connection|null $db {@link Connection}
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'notifications', 'NotificationsNotification', 'id', 'itemid');
    }

    /**
     * Get some {@link NotificationsNotification}s
     *
     * @param CriteriaElement|null $criteria  criteria object
     * @param bool                 $id_as_key Use IDs as keys into the array?
     *
     * @return  array   Array of {@link NotificationsNotification} objects
     */
    public function getObjectsArray(?CriteriaElement $criteria = null, $id_as_key = false)
    {
        $qb = $this->db2->createXoopsQueryBuilder()
            ->select('*')
            ->from($this->table, null);
        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            $criteria->renderQb($qb);
        }
        $result = $qb->execute();
        $ret = array();
        if (!$result) {
            return $ret;
        }
        while ($myrow = $result->fetchAssociative()) {
            $notification = new NotificationsNotification();
            $notification->assignVars($myrow);
            if (!$id_as_key) {
                $ret[] = $notification;
            } else {
                $ret[$myrow['id']] = $notification;
            }
            unset($notification);
        }
        return $ret;
    }

    /**
     * getNotification
     *
     * @param int    $module_id module
     * @param string $category  category
     * @param int    $item_id   item
     * @param string $event     event
     * @param int    $user_id   user
     *
     * @return NotificationsNotification|bool
     *
     * @todo rename this
     * @todo Also, should we have get by module, get by category, etc...??
     */
    public function getNotification($module_id, $category, $item_id, $event, $user_id)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('modid', (int)($module_id)));
        $criteria->add(new Criteria('category', $category));
        $criteria->add(new Criteria('itemid', (int)($item_id)));
        $criteria->add(new Criteria('event', $event));
        $criteria->add(new Criteria('uid', (int)($user_id)));
        $objects = $this->getObjectsArray($criteria);
        if (count($objects) == 1) {
            return $objects[0];
        }
        $inst = false;
        return $inst;
    }

    /**
     * Determine if a user is subscribed to a particular event in
     * a particular module.
     *
     * @param string $category  Category of notification event
     * @param int    $item_id   Item ID of notification event
     * @param string $event     Event
     * @param int    $module_id ID of module (default current module)
     * @param int    $user_id   ID of user (default current user)
     *
     * @return int  0 if not subscribe; non-zero if subscribed
     */
    public function isSubscribed($category, $item_id, $event, $module_id, $user_id)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('modid', (int)($module_id)));
        $criteria->add(new Criteria('category', $category));
        $criteria->add(new Criteria('itemid', (int)($item_id)));
        $criteria->add(new Criteria('event', $event));
        $criteria->add(new Criteria('uid', (int)($user_id)));
        return $this->getCount($criteria);
    }

    /**
     * Subscribe for notification for an event(s)
     *
     * @param string $category  category of notification
     * @param int    $item_id   ID of the item
     * @param mixed  $events    event string or array of events
     * @param int    $mode      force a particular notification mode
     *                          (e.g. once_only) (default to current user preference)
     * @param int    $module_id ID of the module (default to current module)
     * @param int    $user_id   ID of the user (default to current user)
     *
     * @return bool
     *
     * @todo: how about a function to subscribe a whole group of users???
     *      e.g. if we want to add all moderators to be notified of subscription
     *      of new threads...
     */
    public function subscribe($category, $item_id, $events, $mode = null, $module_id = null, $user_id = null)
    {
        $xoops = Xoops::getInstance();
        if (!isset($user_id)) {
            if (!$xoops->isUser()) {
                return false; // anonymous cannot subscribe
            } else {
                $user_id = $xoops->user->getVar('uid');
            }
        }

        if (!isset($module_id)) {
            $module_id = $xoops->module->getVar('mid');
        }

        //todo, remove this from user profile
        if (!isset($mode)) {
            $user = new XoopsUser($user_id);
            $mode = $user->getVar('notify_mode');
        }

        if (!is_array($events)) {
            $events = array($events);
        }
        foreach ($events as $event) {
            if ($notification = $this->getNotification($module_id, $category, $item_id, $event, $user_id)) {
                if ($notification->getVar('mode') != $mode) {
                    $this->updateByField($notification, 'mode', $mode);
                }
            } else {
                $notification = $this->create();
                $notification->setVar('modid', $module_id);
                $notification->setVar('category', $category);
                $notification->setVar('itemid', $item_id);
                $notification->setVar('uid', $user_id);
                $notification->setVar('event', $event);
                $notification->setVar('mode', $mode);
                $this->insert($notification);
            }
        }
        return true;
    }

    /**
     * Get a list of notifications by user ID
     *
     * @param int $user_id ID of the user
     *
     * @return array Array of {@link NotificationsNotification} objects
     *
     * @todo this will be to provide a list of everything a particular
     *       user has subscribed to... e.g. for on the 'Profile' page, similar
     *       to how we see the various posts etc. that the user has made.
     *       We may also want to have a function where we can specify module id
     **/
    public function getByUser($user_id)
    {
        $criteria = new Criteria('uid', $user_id);
        return $this->getObjectsArray($criteria, true);
    }

    /**
     * Get a list of notification events for the current item/mod/user
     *
     * @param string $category  caategory
     * @param int    $item_id   id
     * @param int    $module_id module
     * @param int    $user_id   user
     *
     * @return array
     *
     * @todo rename this?
     */
    public function getSubscribedEvents($category, $item_id, $module_id, $user_id)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('modid', (int)($module_id)));
        $criteria->add(new Criteria('category', $category));
        if ($item_id) {
            $criteria->add(new Criteria('itemid', (int)($item_id)));
        }
        $criteria->add(new Criteria('uid', (int)($user_id)));
        $results = $this->getObjectsArray($criteria, true);
        $ret = array();
        /* @var NotificationsNotification $result*/
        foreach ($results as $result) {
            $ret[] = $result->getVar('event');
        }
        return $ret;
    }

    /**
     * Retrieve items by their ID
     *
     * @param int    $module_id Module ID
     * @param int    $item_id   Item ID
     * @param string $order     Sort order
     * @param int    $status    status
     *
     * @return  array   Array of {@link NotificationsNotification} objects
     *
     * @todo is this a useful function?? (Copied from comment_handler)
     */
    public function getByItemId($module_id, $item_id, $order = null, $status = null)
    {
        $criteria = new CriteriaCompo(new Criteria('modid', (int)($module_id)));
        $criteria->add(new Criteria('itemid', (int)($item_id)));
        if (isset($status)) {
            $criteria->add(new Criteria('status', (int)($status)));
        }
        if (isset($order)) {
            $criteria->setOrder($order);
        }
        return $this->getObjectsArray($criteria);
    }

    /**
     * Send notifications to users
     *
     * @param string $category     notification category
     * @param int    $item_id      ID of the item
     * @param string $events       notification event
     * @param array  $extra_tags   array of substitutions for template to be
     *                             merged with the one from function..
     * @param array  $user_list    only notify the selected users
     * @param int    $module_id    ID of the module
     * @param int    $omit_user_id ID of the user to omit from notifications. (default to
     *                              current user).  set to 0 for all users to receive notification.
     *
     * @return void
     *
     * @todo(?) - pass in an event LIST.  This will help to avoid
     *      problem of sending people multiple emails for similar events.
     *      BUT, then we need an array of mail templates, etc...  Unless
     *      mail templates can include logic in the future, then we can
     *      tailor the mail so it makes sense for any of the possible
     *      (or combination of) events.
     */
    public function triggerEvents(
        $category,
        $item_id,
        $events,
        $extra_tags = array(),
        $user_list = array(),
        $module_id = null,
        $omit_user_id = null
    ) {
        if (!is_array($events)) {
            $events = array($events);
        }
        foreach ($events as $event) {
            $this->triggerEvent($category, $item_id, $event, $extra_tags, $user_list, $module_id, $omit_user_id);
        }
    }

    /**
     * triggerEvent
     *
     * @param int   $category     notification category
     * @param int   $item_id      ID of the item
     * @param int   $event        notification event
     * @param array $extra_tags   array of substitutions for template
     * @param array $user_list    users to notify
     * @param int   $module_id    module
     * @param int   $omit_user_id users to not notify
     *
     * @return bool
     */
    public function triggerEvent(
        $category,
        $item_id,
        $event,
        $extra_tags = array(),
        $user_list = array(),
        $module_id = null,
        $omit_user_id = null
    ) {
        $xoops = xoops::getInstance();
        $helper = Notifications::getInstance();

        if (!isset($module_id)) {
            $module = $xoops->module;
            $module_id = $xoops->isModule() ? $xoops->module->getVar('mid') : 0;
        } else {
            $module = $xoops->getHandlerModule()->get($module_id);
        }

        // Check if event is enabled
        $mod_config = $xoops->getHandlerConfig()->getConfigsByModule($module->getVar('mid'));
        if (empty($mod_config['notifications_enabled'])) {
            return false;
        }
        $category_info = $helper->getCategory($category, $module->getVar('dirname'));
        $event_info = $helper->getEvent($category, $event, $module->getVar('dirname'));
        if (!in_array(
            $helper->generateConfig($category_info, $event_info, 'option_name'),
            $mod_config['notification_events']
        ) && empty($event_info['invisible'])) {
            return false;
        }

        if (!isset($omit_user_id)) {
            if ($xoops->isUser()) {
                $omit_user_id = $xoops->user->getVar('uid');
            } else {
                $omit_user_id = 0;
            }
        }
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('modid', (int)($module_id)));
        $criteria->add(new Criteria('category', $category));
        $criteria->add(new Criteria('itemid', (int)($item_id)));
        $criteria->add(new Criteria('event', $event));
        $mode_criteria = new CriteriaCompo();
        $mode_criteria->add(new Criteria('mode', NOTIFICATIONS_MODE_SENDALWAYS), 'OR');
        $mode_criteria->add(new Criteria('mode', NOTIFICATIONS_MODE_SENDONCETHENDELETE), 'OR');
        $mode_criteria->add(new Criteria('mode', NOTIFICATIONS_MODE_SENDONCETHENWAIT), 'OR');
        $criteria->add($mode_criteria);
        if (!empty($user_list)) {
            $user_criteria = new CriteriaCompo();
            foreach ($user_list as $user) {
                $user_criteria->add(new Criteria('uid', (int)($user)), 'OR');
            }
            $criteria->add($user_criteria);
        }
        $notifications = $this->getObjectsArray($criteria);
        if (empty($notifications)) {
            return false;
        }

        $item_info = $helper->getEvent($category, $item_id, $module->getVar('dirname'));

        // Add some tag substitutions here
        $tags = $helper->getTags($category, $item_id, $event, $module->getVar('dirname'));

        $tags['X_ITEM_NAME']
            = !empty($item_info['name']) ? $item_info['name'] : '[' . _MD_NOTIFICATIONS_ITEMNAMENOTAVAILABLE . ']';
        $tags['X_ITEM_URL']
            = !empty($item_info['url']) ? $item_info['url'] : '[' . _MD_NOTIFICATIONS_ITEMURLNOTAVAILABLE . ']';
        $tags['X_ITEM_TYPE']
            = !empty($category_info['item_name']) ? $category_info['title'] : '['
            . _MD_NOTIFICATIONS_ITEMTYPENOTAVAILABLE . ']';
        $tags['X_MODULE'] = $module->getVar('name');
        $tags['X_MODULE_URL'] = \XoopsBaseConfig::get('url') . '/modules/' . $module->getVar('dirname') . '/';
        $tags['X_NOTIFY_CATEGORY'] = $category;
        $tags['X_NOTIFY_EVENT'] = $event;

        $template_dir = $event_info['mail_template_dir'];
        $template = $event_info['mail_template'] . '.tpl';
        $subject = $event_info['mail_subject'];

        foreach ($notifications as $notification) {
            /* @var $notification NotificationsNotification */
            if (empty($omit_user_id) || $notification->getVar('uid') != $omit_user_id) {
                // user-specific tags
                //$tags['X_UNSUBSCRIBE_URL'] = 'TODO';
                // TODO: don't show unsubscribe link if it is 'one-time' ??
                $tags['X_UNSUBSCRIBE_URL'] = $helper->url('index.php');
                $tags = array_merge($tags, $extra_tags);
                $notification->notifyUser($template_dir, $template, $subject, $tags);
            }
        }
        return true;
    }

    /**
     * Delete all notifications for one user
     *
     * @param int $user_id ID of the user
     *
     * @return  bool
     **/
    public function unsubscribeByUser($user_id)
    {
        $criteria = new Criteria('uid', (int)($user_id));
        return $this->deleteAll($criteria);
    }

    /**
     * Unsubscribe notifications for an event(s).
     *
     * @param string $category  category of the events
     * @param int    $item_id   ID of the item
     * @param mixed  $events    event string or array of events
     * @param int    $module_id ID of the module (default current module)
     * @param int    $user_id   UID of the user (default current user)
     *
     * @return bool
     *
     * @todo allow these to use current module, etc...
     */
    public function unsubscribe($category, $item_id, $events, $module_id = null, $user_id = null)
    {
        $xoops = Xoops::getInstance();
        if (!isset($user_id)) {
            if (!$xoops->isUser()) {
                return false; // anonymous cannot subscribe
            } else {
                $user_id = $xoops->user->getVar('uid');
            }
        }
        if (!isset($module_id)) {
            $module_id = $xoops->module->getVar('mid');
        }
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('modid', (int)($module_id)));
        $criteria->add(new Criteria('category', $category));
        $criteria->add(new Criteria('itemid', (int)($item_id)));
        $criteria->add(new Criteria('uid', (int)($user_id)));
        if (!is_array($events)) {
            $events = array($events);
        }
        $event_criteria = new CriteriaCompo();
        foreach ($events as $event) {
            $event_criteria->add(new Criteria('event', $event), 'OR');
        }
        $criteria->add($event_criteria);
        return $this->deleteAll($criteria);
    }

    /**
     * Delete all notifications for a particular module
     *
     * @param int $module_id ID of the module
     *
     * @return  bool
     *
     * @todo When 'update' a module, may need to switch around some
     **/
    public function unsubscribeByModule($module_id)
    {
        $criteria = new Criteria('modid', (int)($module_id));
        return $this->deleteAll($criteria);
    }

    /**
     * Delete all subscriptions for a particular item.
     *
     * @param int    $module_id ID of the module to which item belongs
     * @param string $category  Notification category of the item
     * @param int    $item_id   ID of the item
     *
     * @return bool
     **/
    public function unsubscribeByItem($module_id, $category, $item_id)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('modid', (int)($module_id)));
        $criteria->add(new Criteria('category', $category));
        $criteria->add(new Criteria('itemid', (int)($item_id)));
        return $this->deleteAll($criteria);
    }

    /**
     * Perform notification maintenance activites at login time.
     * In particular, any notifications for the newly logged-in
     * user with mode NOTIFICATIONS_MODE_WAITFORLOGIN are
     * switched to mode NOTIFICATIONS_MODE_SENDONCETHENWAIT.
     *
     * @param int $user_id ID of the user being logged in
     *
     * @return void
     **/
    public function doLoginMaintenance($user_id)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('uid', (int)($user_id)));
        $criteria->add(new Criteria('mode', NOTIFICATIONS_MODE_WAITFORLOGIN));

        $notifications = $this->getObjectsArray($criteria, true);
        foreach ($notifications as $n) {
            /* @var $n NotificationsNotification */
            $n->setVar('mode', NOTIFICATIONS_MODE_SENDONCETHENWAIT);
            $this->insert($n);
        }
    }

    /**
     * Update
     *
     * @param NotificationsNotification $notification {@link NotificationsNotification} object
     * @param string                    $field_name   Name of the field
     * @param mixed                     $field_value  Value to write
     *
     * @return bool
     **/
    public function updateByField(NotificationsNotification $notification, $field_name, $field_value)
    {
        $notification->unsetNew();
        $notification->setVar($field_name, $field_value);
        return $this->insert($notification);
    }
}
