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
 * page module
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @since           2.6.0
 * @author          Mage Grégory (AKA Mage)
 * @version         $Id$
 */

class PageCommentsPlugin extends Xoops\Module\Plugin\PluginAbstract implements CommentsPluginInterface
{
    /**
     * @return string
     */
    public function itemName()
    {
        return 'id';
    }

    /**
     * @return string
     */
    public function pageName()
    {
        return 'viewpage.php';
    }

    /**
     * @return array
     */
    public function extraParams()
    {
        return array();
    }

    /**
     * This method will be executed upon successful post of an approved comment.
     * This includes comment posts by administrators, and change of comment status from 'pending' to 'active' state.
     * An CommentsComment object that has been approved will be passed as the first and only parameter.
     * This should be useful for example notifying the item submitter of a comment post.
     *
     * @param CommentsComment $comment
     *
     * @return void
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
     *
     * @return void
     */
    public function update($item_id, $total_num)
    {
        $db = Xoops::getInstance()->db();
        $sql = 'UPDATE ' . $db->prefix('page_content') . ' SET content_comments = ' . (int)($total_num) . ' WHERE content_id = ' . (int)($item_id);
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
        $ret = array();
        // Get handler
        $content_Handler = \Xoops::getModuleHelper('page')->getContentHandler();
        $view_content = $content_Handler->getViewable($item_id);
        if (null === $view_content) {
            return $ret;
        } else {
            $ret['title'] = $view_content->getVar('content_title');
            $ret['text'] = $view_content->getVar('content_shorttext') . $view_content->getVar('content_text');
            $ret['uid'] = $view_content->getVar('content_author');
            $ret['timestamp'] = $view_content->getVar('content_create');
        }
        return $ret;
    }
}
