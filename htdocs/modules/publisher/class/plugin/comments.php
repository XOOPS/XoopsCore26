<?php

//namespace XoopsModules\Publisher\Plugin;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 *  Publisher class
 *
 * @copyright       The XUUPS Project http://sourceforge.net/projects/xuups/
 * @license         GNU GPL V2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Class
 * @subpackage      Utils
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id$
 */
use CommentsComment;
use CommentsPluginInterface;
use Xoops;
use Xoops\Module\Plugin\PluginAbstract;
use XoopsModules\Publisher;
use XoopsModules\Publisher\Helper;

/**
 * Class CommentsPlugin
 * @package XoopsModules\Publisher\Plugin
 */
class PublisherCommentsPlugin extends PluginAbstract implements CommentsPluginInterface
{
    /**
     * @return string
     */
    public function itemName()
    {
        return 'itemid';
    }

    /**
     * @return string
     */
    public function pageName()
    {
        return 'item.php';
    }

    /**
     * @return array
     */
    public function extraParams()
    {
        return [];
    }

    /**
     * This method will be executed upon successful post of an approved comment.
     * This includes comment posts by administrators, and change of comment status from 'pending' to 'active' state.
     * An CommentsComment object that has been approved will be passed as the first and only parameter.
     * This should be useful for example notifying the item submitter of a comment post.
     */
    public function approve(CommentsComment $comment)
    {
        //Where are you looking at?
    }

    /**
     * This method will be executed whenever the total number of 'active' comments for an item is changed.
     *
     * @param int $item_id   The unique ID of an item
     * @param int $total_num The total number of active comments
     */
    public function update($item_id, $total_num)
    {
        $db = Xoops::getInstance()->db();
        $sql = 'UPDATE ' . $db->prefix('publisher_items') . ' SET comments = ' . (int)$total_num . ' WHERE itemid = ' . (int)$item_id;
        $db->query($sql);
    }

    /**
     * This method will be executed whenever a new comment form is displayed.
     * You can set a default title for the comment and a header to be displayed on top of the form
     * ex: return array(
     *      'title' => 'My Article Title',
     *      'text' => 'Content of the article');
     *      'timestamp' => time(); //Date of the article in unix format
     *      'uid' => Id of the article author
     *
     * @param int $item_id The unique ID of an item
     *
     * @return array
     */
    public function itemInfo($item_id)
    {
        $ret = [];
        require_once \dirname(\dirname(__DIR__)) . '/include/common.php';

        /* @var Publisher\Item $itemObj */
        $itemObj = Helper::getInstance()->getItemHandler()->get((int)$item_id);
        // same rule as item.php: published, in a category this visitor may read
        if (!\is_object($itemObj) || $itemObj->isNew() || !$itemObj->accessGranted()) {
            return $ret;
        }
        $ret['text'] = '';
        $summary = $itemObj->summary();
        if ('' != $summary) {
            $ret['text'] .= $summary . '<br><br>';
        }
        $ret['text'] .= $itemObj->body();
        $ret['title'] = $itemObj->title();
        $ret['uid'] = $itemObj->getVar('uid');
        $ret['timestamp'] = $itemObj->getVar('datesub', 'n');

        return $ret;
    }
}
