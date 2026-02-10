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
 * @author          DuGris (aka Laurent JEN)
 * @version         $Id$
 */

class PagePage_related_link extends XoopsObject
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initVar('link_id', XOBJ_DTYPE_INT, null, false, 8);
        $this->initVar('link_related_id', XOBJ_DTYPE_TXTBOX, null, false);
        $this->initVar('link_content_id', XOBJ_DTYPE_INT, null, false, 1);
        $this->initVar('link_weight', XOBJ_DTYPE_INT, null, false, 1);

        // joint
        $this->initVar('content_id', XOBJ_DTYPE_INT, 0, false, 11);
        $this->initVar('content_title', XOBJ_DTYPE_TXTBOX, '', false);
    }

    public function getValues($keys = null, $format = null, $maxDepth = null)
    {
        $ret = parent::getValues($keys, $format, $maxDepth);
        return $ret;
    }

    public function get_new_id()
    {
        $xoops = Xoops::getInstance();
        $new_id = $xoopsDB->getInsertId();
        return $new_id;
    }
}

class PagePage_related_linkHandler extends XoopsPersistableObjectHandler
{
    /**
     * @param null|Connection $db
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'page_related_link', 'pagepage_related_link', 'link_id', 'link_related_id');
    }

    public function getLinks($related_id, $sort = 'link_weight', $order = 'desc')
    {
        $this->table_link = $this->db->prefix('page_content');
        $this->field_link = 'content_id';
        $this->field_object = 'link_content_id';


        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('link_related_id', $related_id));
        $criteria->setSort($sort);
        $criteria->setOrder($order);
        return parent::getByLink($criteria, null, false);
    }

    public function getContentByRelated($related_id, $sort = 'link_weight', $order = 'asc')
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('link_related_id', $related_id));
        $criteria->setSort($sort);
        $criteria->setOrder($order);

        $ret = array();
        $result = parent::getAll($criteria, array('link_content_id'), false);
        foreach ($result as $k) {
            $ret[] = $k['link_content_id'];
        }
        return $ret;
    }

    public function getContentUsed()
    {
        $result = parent::getAll(null, array('link_content_id'), false);
        foreach ($result as $k) {
            $ret[] = $k['link_content_id'];
        }
        return $ret;
    }

    public function DeleteByIds($links_ids)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('link_id', '(' . implode(', ', $links_ids) . ')', 'IN'));
        return parent::deleteAll($criteria);
    }

    public function menu_related($content_id)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('link_content_id', $content_id));
        $related = parent::getAll($criteria, null, true, false);
        if (count($related) > 0) {
            $related_Handler = Page::getInstance()->getRelatedHandler();
            $related_arr = $related_Handler->get($related[0]->getVar('link_related_id'));
            $ret = $related_arr->getValues();

            // create button prev / next
            $keys = array_keys($ret['related_links']);
            foreach ($keys as $k => $i) {
                if ($content_id == $ret['related_links'][$i]['content_id']) {
                    if (($k-1) >= 0) {
                        $ret['prev_id'] = $ret['related_links'][$keys[($k-1)]]['content_id'];
                        $ret['prev_title'] = $ret['related_links'][$keys[($k-1)]]['content_title'];
                    }
                    if (($k+1) < count($keys)) {
                        $ret['next_id'] = $ret['related_links'][$keys[($k+1)]]['content_id'];
                        $ret['next_title'] = $ret['related_links'][$keys[($k+1)]]['content_title'];
                    }
                    break;
                }
            }
            return $ret;
        }
        return array();
    }
}
