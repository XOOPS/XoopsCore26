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
use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;

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

class PagePage_rating extends XoopsObject
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initVar('rating_id', XOBJ_DTYPE_INT, 0, false, 10);
        $this->initVar('rating_content_id', XOBJ_DTYPE_INT, null, false, 10);
        $this->initVar('rating_uid', XOBJ_DTYPE_INT, null, false, 10);
        $this->initVar('rating_rating', XOBJ_DTYPE_OTHER, null, false, 3);
        $this->initVar('rating_ip', XOBJ_DTYPE_TXTBOX, null, false);
        $this->initVar('rating_date', XOBJ_DTYPE_INT, time(), false, 10);
    }
}

class PagePage_ratingHandler extends XoopsPersistableObjectHandler
{
    /**
     * @param null|Connection $db
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'page_rating', 'pagepage_rating', 'rating_id', 'rating_contentid');
    }

    public function getVotebyUser($content_id)
    {
        $helper = Page::getInstance();
        $uid = $helper->getUserId();
        $ip  = $helper->xoops()->getEnv('REMOTE_ADDR');


        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('rating_content_id', $content_id));

        $criteria2 = new CriteriaCompo();
        $criteria2->add(new Criteria('rating_uid', $uid), 'OR');
        $criteria2->add(new Criteria('rating_ip', $ip), 'OR');
        $criteria->add($criteria2, 'AND');

        $res = parent::getAll($criteria, null, true, false);
        if (count($res) > 0) {
            return $res[0]->getVar('rating_rating');
        }
        return -1;
    }

    public function hasVoted($content_id)
    {
        $helper = Page::getInstance();
        $uid = $helper->getUserId();
        $ip  = $helper->xoops()->getEnv('REMOTE_ADDR');

        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('rating_content_id', $content_id));

        $criteria2 = new CriteriaCompo();
        $criteria2->add(new Criteria('rating_uid', $uid), 'OR');
        $criteria2->add(new Criteria('rating_ip', $ip), 'OR');
        $criteria->add($criteria2, 'AND');
        return parent::getCount($criteria);
    }

    public function getStats($content_id)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('rating_content_id', $content_id));

        $i = 0;
        $total = 0;
        $obj = parent::getAll($criteria);
        foreach ($obj as $k => $v) {
            ++$i;
            $total += $v->getVar('rating_rating');
        }
        return array('voters' => $i, 'average' => $total/$i);
    }
}
