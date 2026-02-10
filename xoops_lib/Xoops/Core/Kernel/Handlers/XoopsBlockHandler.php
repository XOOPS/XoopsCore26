<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core\Kernel\Handlers;

use Xoops\Core\Database\Connection;
use Xoops\Core\Kernel\CriteriaElement;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;
use Doctrine\DBAL\ParameterType;

/**
 * XoopsBlockHandler
 *
 * @category  Xoops\Core\Kernel\Handlers\XoopsBlockHandler
 * @package   Xoops\Core\Kernel
 * @author    Kazumi Ono (AKA onokazu) http://www.myweb.ne.jp/, http://jp.xoops.org/
 * @author    Gregory Mage (AKA Mage)
 * @author    trabis <lusopoemas@gmail.com>
 * @copyright 2000-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class XoopsBlockHandler extends XoopsPersistableObjectHandler
{
    /**
     * Constructor
     *
     * @param Connection|null $db database
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'system_block', '\Xoops\Core\Kernel\Handlers\XoopsBlock', 'bid', 'name');
    }

    /**
     * Insert a block
     *
     * @param XoopsBlock $obj   block object to persist
     * @param bool       $force force insert even in 'safe' requests
     *
     * @return int|false id of insert, or false on error
     */
    public function insertBlock(XoopsBlock $obj, $force = false)
    {
        $obj->setVar('last_modified', time());
        return parent::insert($obj, $force);
    }

    /**
     * Delete a ID from the database
     *
     * @param XoopsBlock $obj object to delete
     *
     * @return bool
     */
    public function deleteBlock(XoopsBlock $obj)
    {
        if (!parent::delete($obj)) {
            return false;
        }
        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();
        $qb ->deletePrefix('system_permission', null)
            ->where($eb->eq('gperm_name', $eb->literal('block_read')))
            ->andWhere($eb->eq('gperm_itemid', $qb->createNamedParameter($obj->getVar('bid'), ParameterType::INTEGER)))
            ->andWhere($eb->eq('gperm_modid', $qb->createNamedParameter(1, ParameterType::INTEGER)))
            ->execute();

        $qb ->deletePrefix('system_blockmodule', null)
            ->where($eb->eq('block_id', $qb->createNamedParameter($obj->getVar('bid'), ParameterType::INTEGER)))
            ->execute();

        return true;
    }

    /**
     * retrieve array of XoopsBlock objects meeting certain conditions
     *
     * @param CriteriaElement|null $criteria  criteria to match
     * @param bool                 $id_as_key should the blocks' bid be the key for the returned array?
     *
     * @return XoopsBlock[]
     **/
    public function getDistinctObjects(?CriteriaElement $criteria = null, $id_as_key = false)
    {
        $ret = array();

        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();
        $qb ->select('DISTINCT(b.bid)')
            ->addSelect('b.*')
            ->fromPrefix('system_block', 'b')
            ->leftJoinPrefix('b', 'system_blockmodule', 'l', $eb->eq('b.bid', 'l.block_id'));

        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            $criteria->renderQb($qb);
        }

        $result = $qb->execute();
        if (!$result) {
            return $ret;
        }
        while ($myrow = $result->fetchAssociative()) {
            $block = new XoopsBlock();
            $block->assignVars($myrow);
            if (!$id_as_key) {
                $ret[] = $block;
            } else {
                $ret[$myrow['bid']] = $block;
            }
            unset($block);
        }
        return $ret;

    }

    /**
     * get a list of blocks matching certain conditions
     *
     * @param CriteriaElement|null $criteria conditions to match
     *
     * @return array array of blocks matching the conditions
     **/
    public function getNameList(?CriteriaElement $criteria = null)
    {
        $blocks = $this->getObjects($criteria, true);
        $ret = array();
        foreach (array_keys($blocks) as $i) {
            $name = (!$blocks[$i]->isCustom()) ? $blocks[$i]->getVar('name') : $blocks[$i]->getVar('title');
            $ret[$i] = $name;
        }
        return $ret;
    }

    /**
     * get all the blocks that match the supplied parameters
     *
     * @param int|int[] $groupid  groupid (can be an array)
     * @param bool      $asobject retrieve as objects
     * @param int       $side     values:
     *                               0: sideblock - left
     *                               1: sideblock - right
     *                               2: sideblock - left and right
     *                               3: centerblock - left
     *                               4: centerblock - right
     *                               5: centerblock - center
     *                               6: centerblock - left, right, center
     * @param int|null  $visible  0: not visible 1: visible
     * @param string    $orderby  order of the blocks
     * @param int       $isactive 1: active or 0:inactive blocks
     *
     * @return array of block objects
     */
    public function getAllBlocksByGroup(
        $groupid,
        $asobject = true,
        $side = null,
        $visible = null,
        $orderby = "b.weight,b.bid",
        $isactive = 1
    ) {
        $ret = array();
        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();
        if ($asobject) {
            $qb ->select('b.*');
        } else {
            $qb ->select('b.bid');
        }
        $qb ->fromPrefix('system_block', 'b')
            ->leftJoinPrefix('b', 'system_permission', 'l', $eb->eq('b.bid', 'l.gperm_itemid'))
            ->where($eb->eq('gperm_name', $eb->literal('block_read')))
            ->andWhere($eb->eq('gperm_modid', 1));

        if (is_array($groupid)) {
            if (count($groupid) > 1) {
                $in=array();
                foreach ($groupid as $gid) {
                    $in[] = $qb->createNamedParameter($gid, ParameterType::INTEGER);
                }
                $qb->andWhere($eb->in('l.gperm_groupid', $in));
            }
        } else {
            $qb->andWhere($eb->eq('l.gperm_groupid', $qb->createNamedParameter($groupid, ParameterType::INTEGER)));
        }
        $qb->andWhere($eb->eq('b.isactive', $qb->createNamedParameter($isactive, ParameterType::INTEGER)));
        if (isset($side)) {
            // get both sides in sidebox? (some themes need this)
            if ($side == XOOPS_SIDEBLOCK_BOTH) {
                $qb->andWhere($eb->in('b.side', array(0,1)));
            } elseif ($side == XOOPS_CENTERBLOCK_ALL) {
                $qb->andWhere($eb->in('b.side', array(3,4,5,7,8,9)));
            } else {
                $qb->andWhere($eb->eq('b.side', $qb->createNamedParameter($side, ParameterType::INTEGER)));
            }
        }
        if (isset($visible)) {
            $qb->andWhere($eb->eq('b.visible', $qb->createNamedParameter($visible, ParameterType::INTEGER)));
        }
        $qb->orderBy($orderby);
        $result = $qb->execute();
        $added = array();
        while ($myrow = $result->fetchAssociative()) {
            if (!in_array($myrow['bid'], $added)) {
                if (!$asobject) {
                    $ret[] = $myrow['bid'];
                } else {
                    $ret[] = new XoopsBlock($myrow);
                }
                array_push($added, $myrow['bid']);
            }
        }
        return $ret;
    }

    /**
     * getAllBlocks matching selection criteria
     *
     * @param string   $rettype  what to return, values can be object, list or id
     * @param int      $side     block location (side)
     * @param int|null $visible  null for all, 0 not visible, 1 for visible only
     * @param string   $orderby  comma separated columns to order by
     * @param int      $isactive 1: active or 0:inactive blocks
     *
     * @return array
     */
    public function getAllBlocks(
        $rettype = "object",
        $side = null,
        $visible = null,
        $orderby = "side,weight,bid",
        $isactive = 1
    ) {
        $ret = array();
        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();

        $qb ->fromPrefix('system_block', null)
            ->where($eb->eq('isactive', $qb->createNamedParameter($isactive, ParameterType::INTEGER)));
        if (isset($side)) {
            // get both sides in sidebox? (some themes need this)
            if ($side == XOOPS_SIDEBLOCK_BOTH) {
                $qb->andWhere($eb->in('side', array(0,1)));
            } elseif ($side == XOOPS_CENTERBLOCK_ALL) {
                $qb->andWhere($eb->in('side', array(3,4,5,7,8,9)));
            } else {
                $qb->andWhere($eb->eq('side', $qb->createNamedParameter($side, ParameterType::INTEGER)));
            }
        }
        if (isset($visible)) {
            $qb->andWhere($eb->eq('visible', $qb->createNamedParameter($visible, ParameterType::INTEGER)));
        }
        $qb->orderBy($orderby);
        switch ($rettype) {
            case "object":
                $qb->select('*');
                $result = $qb->execute();
                while ($myrow = $result->fetchAssociative()) {
                    $ret[] = new XoopsBlock($myrow);
                }
                break;
            case "list":
                $qb->select('*');
                $result = $qb->execute();
                while ($myrow = $result->fetchAssociative()) {
                    $block = new XoopsBlock($myrow);
                    $title = $block->getVar("title");
                    $title = empty($title) ? $block->getVar("name") : $title;
                    $ret[$block->getVar("bid")] = $title;
                }
                break;
            case "id":
                $qb->select('bid');
                $result = $qb->execute();
                while ($myrow = $result->fetchAssociative()) {
                    $ret[] = $myrow['bid'];
                }
                break;
        }

        return $ret;
    }

    /**
     * get blocks by module id
     *
     * @param int  $moduleid module id
     * @param bool $asobject true to fetch as objects, otherwise associative array
     *
     * @return array of block information
     */
    public function getByModule($moduleid, $asobject = true)
    {
        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();

        $qb ->fromPrefix('system_block', null)
            ->where($eb->eq('mid', $qb->createNamedParameter($moduleid, ParameterType::INTEGER)));
        if ($asobject == true) {
            $qb->select('*');
        } else {
            $qb->select('bid');
        }

        $ret = array();
        $result = $qb->execute();
        while ($myrow = $result->fetchAssociative()) {
            if ($asobject) {
                $ret[] = new XoopsBlock($myrow);
            } else {
                $ret[] = $myrow['bid'];
            }
        }
        return $ret;
    }

    /**
     * XoopsBlock::getAllByGroupModule()
     *
     * @param mixed   $groupid      int group id, int[] of group ids,
     * @param integer $module_id    module id
     * @param boolean $toponlyblock only for top block
     * @param mixed   $visible      restrict by visible values
     * @param string  $orderby      comma separated list of columns to order by
     * @param integer $isactive     restrict by isactive values
     *
     * @return array
     */
    public function getAllByGroupModule(
        $groupid,
        $module_id = 0,
        $toponlyblock = false,
        $visible = null,
        $orderby = 'b.weight, m.block_id',
        $isactive = 1
    ) {
        $ret = array();

        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();

        $blockids=null;
        if (isset($groupid)) {
            $qb ->select('DISTINCT gperm_itemid')
                ->fromPrefix('system_permission', null)
                ->where($eb->eq('gperm_name', $eb->literal('block_read')))
                ->andWhere('gperm_modid=1');

            if (is_array($groupid) && !empty($groupid)) {
                $qb->andWhere($eb->in('gperm_groupid', $groupid));
            } else {
                if ((int)($groupid) > 0) {
                    $qb->andWhere($eb->eq('gperm_groupid', $groupid));
                }
            }
            $result = $qb->execute();
            $blockids = $result->fetchFirstColumn();
        }

        $qb->resetQueryParts();

        $qb ->select('b.*')
            ->fromPrefix('system_block', 'b')
            ->where($eb->eq('b.isactive', $qb->createNamedParameter($isactive, ParameterType::INTEGER)));
        if (isset($visible)) {
            $qb->andWhere($eb->eq('b.visible', $qb->createNamedParameter($visible, ParameterType::INTEGER)));
        }
        if (isset($module_id)) {
            $qb ->fromPrefix('system_blockmodule', 'm')
                ->andWhere($eb->eq('m.block_id', 'b.bid'));
            if (!empty($module_id)) {
                $in=array();
                $in[]=0;
                $in[]=(int)($module_id);
                if ($toponlyblock) {
                    $in[]=(int)(-1);
                }
            } else {
                if ($toponlyblock) {
                    $in=array(0, -1);
                } else {
                    $in=0;
                }
            }
            if (is_array($in)) {
                $qb->andWhere($eb->in('m.module_id', $in));
            } else {
                $qb->andWhere($eb->eq('m.module_id', $in));
            }
        }
        if (!empty($blockids)) {
            $qb->andWhere($eb->in('b.bid', $blockids));
        }
        $qb->orderBy($orderby);
        $result = $qb->execute();
        while ($myrow = $result->fetchAssociative()) {
            $block = new XoopsBlock($myrow);
            $ret[$myrow['bid']] = $block;
            unset($block);
        }
        return $ret;
    }

    /**
     * XoopsBlock::getNonGroupedBlocks()
     *
     * @param integer $module_id    module id
     * @param boolean $toponlyblock only for top block
     * @param mixed   $visible      restrict by visible values
     * @param string  $orderby      comma separated list of columns to order by
     * @param integer $isactive     restrict by isactive values
     *
     * @return array
     */
    public function getNonGroupedBlocks(
        $module_id = 0,
        $toponlyblock = false,
        $visible = null,
        $orderby = 'b.weight, m.block_id',
        $isactive = 1
    ) {
        $ret = array();

        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();

        $qb ->select('DISTINCT(bid)')
            ->fromPrefix('system_block', null);
        $result = $qb->execute();
        $bids = $result->fetchFirstColumn();

        $qb->resetQueryParts();

        $qb ->select('DISTINCT(p.gperm_itemid)')
            ->fromPrefix('system_permission', 'p')
            ->fromPrefix('system_group', 'g')
            ->where($eb->eq('g.groupid', 'p.gperm_groupid'))
            ->andWhere($eb->eq('p.gperm_name', $eb->literal('block_read')));
        $result = $qb->execute();
        $grouped = $result->fetchFirstColumn();

        $non_grouped = array_diff($bids, $grouped);

        if (!empty($non_grouped)) {
            $qb->resetQueryParts();

            $qb ->select('b.*')
                ->fromPrefix('system_block', 'b')
                ->where($eb->eq('b.isactive', $qb->createNamedParameter($isactive, ParameterType::INTEGER)));
            if (isset($visible)) {
                $qb->andWhere($eb->eq('b.visible', $qb->createNamedParameter($visible, ParameterType::INTEGER)));
            }

            if (isset($module_id)) {
                $qb ->fromPrefix('system_blockmodule', 'm')
                    ->andWhere($eb->eq('m.block_id', 'b.bid'));
                if (!empty($module_id)) {
                    $in=array();
                    $in[]=0;
                    $in[]=(int)($module_id);
                    if ($toponlyblock) {
                        $in[]=(int)(-1);
                    }
                } else {
                    if ($toponlyblock) {
                        $in=array(0, -1);
                    } else {
                        $in=0;
                    }
                }
                if (is_array($in)) {
                    $qb->andWhere($eb->in('m.module_id', $in));
                } else {
                    $qb->andWhere($eb->eq('m.module_id', $in));
                }
            }
            $qb->andWhere($eb->in('b.bid', $non_grouped));
            $qb->orderBy($orderby);
            $result = $qb->execute();
            while ($myrow = $result->fetchAssociative()) {
                $block = new XoopsBlock($myrow);
                $ret[$myrow['bid']] = $block;
                unset($block);
            }
        }
        return $ret;
    }

    /**
     * XoopsBlock::countSimilarBlocks()
     *
     * @param int    $moduleId module id
     * @param string $funcNum  func number
     * @param string $showFunc show function
     *
     * @return int count
     */
    public function countSimilarBlocks($moduleId, $funcNum, $showFunc = null)
    {
        $funcNum = (int)($funcNum);
        $moduleId = (int)($moduleId);
        if ($funcNum < 1 || $moduleId < 1) {
            // invalid query
            return 0;
        }

        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();

        $qb ->select('COUNT(*)')
            ->fromPrefix('system_block', null)
            ->where($eb->eq('mid', $qb->createNamedParameter($moduleId, ParameterType::INTEGER)))
            ->andWhere($eb->eq('func_num', $qb->createNamedParameter($funcNum, ParameterType::INTEGER)));

        if (isset($showFunc)) {
            // showFunc is set for more strict comparison
            $qb->andWhere($eb->eq('show_func', $qb->createNamedParameter($showFunc, ParameterType::STRING)));
        }
        if (!$result = $qb->execute()) {
            return 0;
        }
        list ($count) = $result->fetchNumeric();
        return $count;
    }

    /**
     * Aligns the content of a block
     *
     * @param integer $position  order of content
     *                              0 -> content in DB is positioned before the original content
     *                              1 -> content in DB is positioned after the original content
     * @param string  $content   content
     * @param string  $contentdb content from database
     *
     * @return string
     */
    public function buildContent($position, $content = "", $contentdb = "")
    {
        $ret = '';
        if ($position == 0) {
            $ret = $contentdb . $content;
        } else {
            if ($position == 1) {
                $ret = $content . $contentdb;
            }
        }
        return $ret;
    }

    /**
     * Enter description here... appears to be unused?
     *
     * @param string $originaltitle original title
     * @param string $newtitle      new title
     *
     * @return string title winner of the title war?
     */
    public function buildTitle($originaltitle, $newtitle = '')
    {
        if ($newtitle != '') {
            $ret = $newtitle;
        } else {
            $ret = $originaltitle;
        }
        return $ret;
    }

    /************ system ***************/

    /**
     * get list of ids of block that a group has permission to views
     *
     * @param null|integer $groupid group
     *
     * @return int[]
     */
    public function getBlockByPerm($groupid)
    {
        $ret = array();
        if (isset($groupid)) {
            $qb = $this->db2->createXoopsQueryBuilder();
            $eb = $qb->expr();

            $qb ->select('DISTINCT(gperm_itemid)')
                ->fromPrefix('system_permission', 'p')
                ->fromPrefix('system_group', 'g')
                ->where($eb->eq('p.gperm_name', $eb->literal('block_read')))
                ->andWhere('gperm_modid=1');

            if (is_array($groupid)) {
                $qb->andWhere($eb->in('gperm_groupid', $groupid));
            } else {
                if ((int)($groupid) > 0) {
                    $qb->andWhere($eb->eq('gperm_groupid', $groupid));
                }
            }

            $result = $qb->execute();
            $blockids = $result->fetchFirstColumn();
            return $blockids;
        }
        return $ret;
    }
}
