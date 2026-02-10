<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

/**
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Comments
 * @author          trabis <lusopoemas@gmail.com>
 * @author          Kazumi Ono (AKA onokazu) http://www.myweb.ne.jp/, http://jp.xoops.org/
 * @version         $Id$
 */

use Xoops\Core\Database\Connection;
use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;

/**
 * A Comment
 *
 * @author        Kazumi Ono    <onokazu@xoops.org>
 * @copyright    copyright (c) 2000-2003 XOOPS.org
 */
class CommentsComment extends XoopsObject
{

    /**
     * Constructor
     **/
    public function __construct()
    {
        $this->initVar('id', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('pid', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('modid', XOBJ_DTYPE_INT, null, false);
        $this->initVar('icon', XOBJ_DTYPE_OTHER, '', false);
        $this->initVar('title', XOBJ_DTYPE_TXTBOX, null, true, 255, true);
        $this->initVar('text', XOBJ_DTYPE_TXTAREA, '', true, null, true);
        $this->initVar('created', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('modified', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('uid', XOBJ_DTYPE_INT, 0, true);
        $this->initVar('ip', XOBJ_DTYPE_OTHER, null, false);
        $this->initVar('sig', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('itemid', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('rootid', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('status', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('exparams', XOBJ_DTYPE_OTHER, null, false, 255);
        $this->initVar('dohtml', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('dosmiley', XOBJ_DTYPE_INT, 1, false);
        $this->initVar('doxcode', XOBJ_DTYPE_INT, 1, false);
        $this->initVar('doimage', XOBJ_DTYPE_INT, 1, false);
        $this->initVar('dobr', XOBJ_DTYPE_INT, 1, false);
    }

    /**
     * @return bool
     */
    public function isRoot()
    {
        return ($this->getVar('id') == $this->getVar('rootid'));
    }
}

/**
 * Comment handler class.
 *
 * @author        Kazumi Ono    <onokazu@xoops.org>
 * @copyright    copyright (c) 2000-2003 XOOPS.org
 */
class CommentsCommentHandler extends XoopsPersistableObjectHandler
{
    /**
     * Constructor
     *
     * @param Connection|null $db {@link Connection}
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'comments', 'CommentsComment', 'id', 'title');
    }

    /**
     * Retrieves comments for an item
     *
     * @param   int     $module_id  Module ID
     * @param   int     $item_id    Item ID
     * @param   string  $order      Sort order
     * @param   int     $status     Status of the comment
     * @param   int     $limit      Max num of comments to retrieve
     * @param   int     $start      Start offset
     *
     * @return  array   Array of {@link CommentsComment} objects
     **/
    public function getByItemId($module_id, $item_id, $order = null, $status = null, $limit = null, $start = 0)
    {
        $criteria = new CriteriaCompo(new Criteria('modid', (int)($module_id)));
        $criteria->add(new Criteria('itemid', (int)($item_id)));
        if (isset($status)) {
            $criteria->add(new Criteria('status', (int)($status)));
        }
        if (isset($order)) {
            $criteria->setOrder($order);
        }
        if (isset($limit)) {
            $criteria->setLimit($limit);
            $criteria->setStart($start);
        }
        return $this->getObjects($criteria);
    }

    /**
     * Gets total number of comments for an item
     *
     * @param   int     $module_id  Module ID
     * @param   int     $item_id    Item ID
     * @param   int     $status     Status of the comment
     *
     * @return  integer   Array of {@link CommentsComment} objects
     **/
    public function getCountByItemId($module_id, $item_id, $status = null)
    {
        $criteria = new CriteriaCompo(new Criteria('modid', (int)($module_id)));
        $criteria->add(new Criteria('itemid', (int)($item_id)));
        if (isset($status)) {
            $criteria->add(new Criteria('status', (int)($status)));
        }
        return $this->getCount($criteria);
    }

    /**
     * @param int $module_id
     * @param int|null $item_id
     * @return int
     */
    public function getCountByModuleId($module_id, $item_id = null)
    {
        $criteria = new CriteriaCompo(new Criteria('modid', (int)($module_id)));
        if (isset($item_id)) {
            $criteria->add(new Criteria('itemid', (int)($item_id)));
        }
        return $this->getCount($criteria);
    }

    /**
     * Get the top {@link CommentsComment}s
     *
     * @param   int     $module_id
     * @param   int     $item_id
     * @param   string  $order
     * @param   int     $status
     *
     * @return  array   Array of {@link CommentsComment} objects
     **/
    public function getTopComments($module_id, $item_id, $order, $status = null)
    {
        $criteria = new CriteriaCompo(new Criteria('modid', (int)($module_id)));
        $criteria->add(new Criteria('itemid', (int)($item_id)));
        $criteria->add(new Criteria('pid', 0));
        if (isset($status)) {
            $criteria->add(new Criteria('status', (int)($status)));
        }
        $criteria->setOrder($order);
        return $this->getObjects($criteria);
    }

    /**
     * Retrieve a whole thread
     *
     * @param   int     $comment_rootid
     * @param   int     $comment_id
     * @param   int     $status
     *
     * @return  array   Array of {@link CommentsComment} objects
     **/
    public function getThread($comment_rootid, $comment_id, $status = null)
    {
        $criteria = new CriteriaCompo(new Criteria('rootid', (int)($comment_rootid)));
        $criteria->add(new Criteria('id', (int)($comment_id), '>='));
        if (isset($status)) {
            $criteria->add(new Criteria('status', (int)($status)));
        }
        return $this->getObjects($criteria);
    }

    /**
     * Update
     *
     * @param   CommentsComment  $comment       {@link CommentsComment} object
     * @param   string  $field_name     Name of the field
     * @param   mixed   $field_value    Value to write
     *
     * @return  bool
     **/
    public function updateByField(CommentsComment $comment, $field_name, $field_value)
    {
        $comment->unsetNew();
        $comment->setVar($field_name, $field_value);
        return $this->insert($comment);
    }

    /**
     * Delete all comments for one whole module
     *
     * @param   int $module_id  ID of the module
     * @return  bool
     **/
    public function deleteByModule($module_id)
    {
        return $this->deleteAll(new Criteria('modid', (int)($module_id)));
    }

    /**
     * @param int $module_id
     * @param int $item_id
     * @return bool
     */
    function deleteByItemId($module_id, $item_id)
    {
        $module_id = (int)($module_id);
        $item_id = (int)($item_id);
        if ($module_id > 0 && $item_id > 0) {
            $comments = $this->getByItemId($module_id, $item_id);
            if (is_array($comments)) {
                $count = count($comments);
                $deleted_num = array();
                for ($i = 0; $i < $count; ++$i) {
                    if (false != $this->delete($comments[$i])) {
                        // store poster ID and deleted post number into array for later use
                        $poster_id = $comments[$i]->getVar('uid');
                        if ($poster_id != 0) {
                            $deleted_num[$poster_id] = !isset($deleted_num[$poster_id]) ? 1
                                : ($deleted_num[$poster_id] + 1);
                        }
                    }
                }

                $member_handler = Xoops::getInstance()->getHandlerMember();
                foreach ($deleted_num as $user_id => $post_num) {
                    // update user posts
                    /* @var $member_handler XoopsMemberHandler */
                    $poster = $member_handler->getUser($user_id);
                    if (is_object($poster)) {
                        $member_handler->updateUserByField($poster, 'posts', $poster->getVar('posts') - $post_num);
                    }
                }
                return true;
            }
        }
        return false;
    }
}
