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
use Xoops\Core\Kernel\Handlers\XoopsUser;

/**
 * page module
 *
 * @copyright XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package   page
 * @since     2.6.0
 * @author    Mage Grégory (AKA Mage)
 */

class PagePage_content extends XoopsObject
{

    public $options = array(
        'title',
        'author',
        'date',
        'hits',
        'rating',
        'print',
        'mail',
        'coms',
        'ncoms',
        'notifications',
        'pdf',
        'social'
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initVar('content_id', XOBJ_DTYPE_INT, 0, false, 11);
        $this->initVar('content_title', XOBJ_DTYPE_TXTBOX, '', false);
        $this->initVar('content_shorttext', XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('content_text', XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('content_create', XOBJ_DTYPE_INT, time(), false, 10);
        $this->initVar('content_author', XOBJ_DTYPE_INT, 0, false, 11);
        $this->initVar('content_status', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('content_hits', XOBJ_DTYPE_INT, 0, false, 10);
        $this->initVar('content_rating', XOBJ_DTYPE_OTHER, 0, false, 10);
        $this->initVar('content_votes', XOBJ_DTYPE_INT, 0, false, 11);
        $this->initVar('content_comments', XOBJ_DTYPE_INT, 0, false, 11);
        $this->initVar('content_mkeyword', XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('content_mdescription', XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('content_maindisplay', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('content_weight', XOBJ_DTYPE_INT, 0, false, 5);
        $this->initVar('content_dopdf', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('content_doprint', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('content_dosocial', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('content_doauthor', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('content_dodate', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('content_domail', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('content_dohits', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('content_dorating', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('content_docoms', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('content_doncoms', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('content_dotitle', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('content_donotifications', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('dohtml', XOBJ_DTYPE_INT, 1, false);
    }

    public function getValues($keys = null, $format = null, $maxDepth = null)
    {
        $page = Page::getInstance();
        $ret = parent::getValues($keys, $format, $maxDepth);
        $ret['rating'] = number_format($this->getVar('content_rating'), 1);
        // these next two lines are rather silly
        $ret['content_authorid'] = $this->getVar('content_author');
        $ret['content_author'] = XoopsUser::getUnameFromId($this->getVar('content_author'), true);
        $ret['content_date'] = XoopsLocale::formatTimestamp($this->getVar('content_create'), $page->getConfig('page_dateformat'));
        $ret['content_time'] = XoopsLocale::formatTimestamp($this->getVar('content_create'), $page->getConfig('page_timeformat'));
        $ret['content_rating'] = number_format($this->getVar('content_rating'), 2);
        return $ret;
    }

    public function toArray()
    {
        $ret = parent::getValues();
        unset($ret['dohtml']);
        return $ret;
    }

    public function getOptions()
    {
        $xoops = Xoops::getInstance();
        $ret = array();
        if ($this->getVar('content_dotitle') == 1) {
            array_push($ret, 'title');
        }
        if ($this->getVar('content_doauthor') == 1) {
            array_push($ret, 'author');
        }
        if ($this->getVar('content_dodate') == 1) {
            array_push($ret, 'date');
        }
        if ($this->getVar('content_dohits') == 1) {
            array_push($ret, 'hits');
        }
        if ($this->getVar('content_dorating') == 1) {
            array_push($ret, 'rating');
        }
        if ($this->getVar('content_doprint') == 1) {
            array_push($ret, 'print');
        }
        if ($this->getVar('content_domail') == 1) {
            array_push($ret, 'mail');
        }
        if ($xoops->isActiveModule('comments')) {
            if ($this->getVar('content_docoms') == 1) {
                array_push($ret, 'coms');
            }
            if ($this->getVar('content_doncoms') == 1) {
                array_push($ret, 'ncoms');
            }
        }
        if ($xoops->isActiveModule('notifications')) {
            if ($this->getVar('content_donotifications') == 1) {
                array_push($ret, 'notifications');
            }
        }
        if ($xoops->isActiveModule('pdf')) {
            if ($this->getVar('content_dopdf') == 1) {
                array_push($ret, 'pdf');
            }
        }
        if ($xoops->isActiveModule('xoosocialnetwork')) {
            if ($this->getVar('content_dosocial') == 1) {
                array_push($ret, 'social');
            }
        }
        return $ret;
    }

    public function get_new_id()
    {
        return Xoops::getInstance()->db()->getInsertId();
    }
}

class PagePage_contentHandler extends XoopsPersistableObjectHandler
{
    /**
     * @param null|Connection $db
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'page_content', 'pagepage_content', 'content_id', 'content_title');
    }

    public function getPagePublished($start = 0, $limit = 0, $sort = 'content_weight ASC, content_title', $order = 'ASC')
    {
        $helper = Page::getInstance();
        $xoops = $helper->xoops();
        $module_id = $helper->getModule()->getVar('mid');


        // get permitted id
        $groups = $xoops->getUserGroups();
        $pages_ids = $helper->getGrouppermHandler()->getItemIds('page_view_item', $groups, $module_id);

        // criteria
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('content_id', '(' . implode(', ', $pages_ids) . ')', 'IN'));
        $criteria->add(new Criteria('content_status', 0, '!='));
        $criteria->add(new Criteria('content_maindisplay', 0, '!='));
        $criteria->setSort($sort);
        $criteria->setOrder($order);
        $criteria->setStart($start);
        $criteria->setLimit($limit);
        return parent::getAll($criteria);
    }

    public function getCountPublished($start = 0, $limit = 0, $sort = 'content_weight ASC, content_title', $order = 'ASC')
    {
        $helper = Page::getInstance();
        $xoops = $helper->xoops();
        $module_id = $helper->getModule()->getVar('mid');

        // get permitted id
        $groups = $xoops->getUserGroups();
        $pages_ids = $helper->getGrouppermHandler()->getItemIds('page_view_item', $groups, $module_id);

        // criteria
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('content_id', '(' . implode(', ', $pages_ids) . ')', 'IN'));
        $criteria->add(new Criteria('content_status', 0, '!='));
        $criteria->add(new Criteria('content_maindisplay', 0, '!='));
        $criteria->setSort($sort);
        $criteria->setOrder($order);
        $criteria->setStart($start);
        $criteria->setLimit($limit);
        return parent::getCount($criteria);
    }

    public function getPage($start = 0, $limit = 0, $sort = 'content_weight ASC, content_title', $order = 'ASC')
    {
        $criteria = new CriteriaCompo();
        $criteria->setSort($sort);
        $criteria->setOrder($order);
        $criteria->setStart($start);
        $criteria->setLimit($limit);
        return parent::getAll($criteria);
    }

    public function countPage($start = 0, $limit = 0, $sort = 'content_weight ASC, content_title', $order = 'ASC')
    {
        $criteria = new CriteriaCompo();
        $criteria->setSort($sort);
        $criteria->setOrder($order);
        $criteria->setStart($start);
        $criteria->setLimit($limit);
        return parent::getCount($criteria);
    }

    public function getPageTitle($status = null, $sort = 'content_weight ASC, content_title', $order = 'ASC')
    {
        $criteria = new CriteriaCompo();
        if (isset($status)) {
            $criteria->add(new Criteria('content_status', $status));
        }
        $criteria->setSort($sort);
        $criteria->setOrder($order);
        return parent::getAll($criteria, array('content_id', 'content_title'), false);
    }

    public function getClone($content_id)
    {
        $values = parent::get($content_id)->toArray();
        $values['content_id'] = 0;
        $values['content_title'] = PageLocale::CONTENT_COPY . $values['content_title'];
        $values['content_weight'] = 0;
        $values['content_hits'] = 0;
        $values['content_votes'] = 0;
        $values['content_rating'] = 0;
        $values['content_create'] = time();

        $obj = parent::create();
        $obj->setVars($values);
        return $obj;
    }
}
