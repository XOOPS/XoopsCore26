<?php
/**
 * XOOPS Kernel Class
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         kernel
 * @since           2.0.0
 * @version         $Id$
 */

namespace Xoops\Core\Kernel\Handlers;

use Xoops\Core\Database\Connection;
use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaElement;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;
use Doctrine\DBAL\ParameterType;

/**
 * XOOPS module handler class.
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS module class objects.
 *
 * @category  Xoops\Core\Kernel\XoopsModuleHandler
 * @package   Xoops\Core\Kernel
 * @author    Kazumi Ono <onokazu@xoops.org>
 * @author    Taiwen Jiang <phppp@users.sourceforge.net>
 * @copyright 2000-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class XoopsModuleHandler extends XoopsPersistableObjectHandler
{
    /**
     * holds an array of cached module references, indexed by module id
     *
     * @var array
     * @access private
     */
    private $cachedModulesByMid = array();

    /**
     * holds an array of cached module references, indexed by module dirname
     *
     * @var array
     * @access private
     */
    private $cachedModulesByDirname = array();

    /**
     * Constructor
     *
     * @param Connection|null $db database
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'system_module', '\Xoops\Core\Kernel\Handlers\XoopsModule', 'mid', 'dirname');
    }

    /**
     * Load a module from the database
     *
     * @param int $id ID of the module
     *
     * @return XoopsModule|false on fail
     */
    public function getById($id = null)
    {
        $id = (int)($id);
        if ($id > 0) {
            if (!empty($this->cachedModulesByMid[$id])) {
                return $this->cachedModulesByMid[$id];
            } else {
                $module = parent::get($id);
                if (!is_object($module)) {
                    return false;
                }
                $this->cachedModulesByMid[$id] = $module;
                $this->cachedModulesByDirname[$module->getVar('dirname')] = $module;
                return $module;
            }
        }
        return false;
    }

    /**
     * Load a module by its dirname
     *
     * @param string $dirname module directory name
     *
     * @return XoopsModule|false FALSE on fail
     */
    public function getByDirname($dirname)
    {
        $dirname = basename(trim($dirname));

        if (!empty($this->cachedModulesByDirname[$dirname])) {
            return $this->cachedModulesByDirname[$dirname];
        } else {
            $criteria = new Criteria('dirname', $dirname);
            $modules = $this->getObjectsArray($criteria);
            if (count($modules) == 1 && is_object($modules[0])) {
                $module = $modules[0];
            } else {
                return false;
            }
            /* @var $module XoopsModule */
            $this->cachedModulesByDirname[$dirname] = $module;
            $this->cachedModulesByMid[$module->getVar('mid')] = $module;
            return $module;
        }
    }

    /**
     * Write a module to the database
     *
     * @param XoopsModule $module module to insert
     *
     * @return bool
     */
    public function insertModule(XoopsModule $module)
    {
        if (false === parent::insert($module)) {
            return false;
        }

        $dirname = $module->getVar('dirname');
        $mid = $module->getVar('mid');

        if (!empty($this->cachedModulesByDirname[$dirname])) {
            unset($this->cachedModulesByDirname[$dirname]);
        }
        if (!empty($this->cachedModulesByMid[$mid])) {
            unset($this->cachedModulesByMid[$mid]);
        }
        return true;
    }

    /**
     * Delete a module from the database
     *
     * @param XoopsModule $module module to delete
     *
     * @return bool
     */
    public function deleteModule(XoopsModule $module)
    {
        if (!parent::delete($module)) {
            return false;
        }

        $mid = $module->getVar('mid');
        $dirname = $module->getVar('dirname');

        // delete admin and read permissions assigned for this module
        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();
        $qb ->deletePrefix('system_permission')
            ->where(
                $eb->orX(
                    $eb->eq('gperm_name', $eb->literal('module_admin')),
                    $eb->eq('gperm_name', $eb->literal('module_read'))
                )
            )
            ->andWhere($eb->eq('gperm_itemid', ':itemid'))
            ->setParameter('itemid', $mid, ParameterType::INTEGER)
            ->execute();

        $qb->resetQueryParts(); // reset
        $qb ->select('block_id')
            ->fromPrefix('system_blockmodule', null)
            ->where($eb->eq('module_id', ':mid'))
            ->setParameter('mid', $mid, ParameterType::INTEGER);
        $result = $qb->execute();
        $block_id_arr = array();
        while ($myrow = $result->fetchAssociative()) {
            array_push($block_id_arr, $myrow['block_id']);
        }

        foreach ($block_id_arr as $i) {
            $qb->resetQueryParts(); // reset
            $qb ->select('COUNT(*)')
                ->fromPrefix('system_blockmodule', null)
                ->where($eb->neq('module_id', ':mid'))
                ->setParameter('mid', $mid, ParameterType::INTEGER)
                ->andWhere($eb->eq('block_id', ':bid'))
                ->setParameter('bid', $i, ParameterType::INTEGER);
            $result = $qb->execute();
            $count = $result->fetchOne();

            if ($count > 0) {
                // this block has other entries, so delete the entry for this module
                $qb->resetQueryParts(); // reset
                $qb ->deletePrefix('system_blockmodule')
                    ->where($eb->eq('module_id', ':mid'))
                    ->setParameter('mid', $mid, ParameterType::INTEGER)
                    ->andWhere($eb->eq('block_id', ':bid'))
                    ->setParameter('bid', $i, ParameterType::INTEGER)
                    ->execute();
            } else {
                // this block does not have other entries, so disable the block and let it show
                // on top page only. otherwise, this block will not display anymore on block admin page!
                $qb->resetQueryParts(); // reset
                $qb ->updatePrefix('system_block')
                    ->set('visible', ':notvisible')
                    ->where($eb->eq('bid', ':bid'))
                    ->setParameter('bid', $i, ParameterType::INTEGER)
                    ->setParameter('notvisible', 0, ParameterType::INTEGER)
                    ->execute();

                $qb->resetQueryParts(); // reset
                $qb ->updatePrefix('system_blockmodule')
                    ->set('module_id', ':nomid')
                    ->where($eb->eq('module_id', ':mid'))
                    ->setParameter('mid', $mid, ParameterType::INTEGER)
                    ->setParameter('nomid', -1, ParameterType::INTEGER)
                    ->execute();
            }
        }

        if (!empty($this->cachedModulesByDirname[$dirname])) {
            unset($this->cachedModulesByDirname[$dirname]);
        }
        if (!empty($this->cachedModulesByMid[$mid])) {
            unset($this->cachedModulesByMid[$mid]);
        }
        $cache = \Xoops::getInstance()->cache();
        $cache->delete("system/module/id/{$mid}");
        $cache->delete("system/module/dirname/{$dirname}");
        $cache->delete("module/{$dirname}");
        return true;
    }

    /**
     * Load some modules
     *
     * @param CriteriaElement|null $criteria  criteria to match
     * @param bool                 $id_as_key Use the ID as key into the array
     *
     * @return array
     */
    public function getObjectsArray(?CriteriaElement $criteria = null, $id_as_key = false)
    {
        $ret = array();
        $qb = $this->db2->createXoopsQueryBuilder();
        $qb->select('*')->fromPrefix('system_module', null);
        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            $criteria->setSort('weight');
            $criteria->renderQb($qb);
            $qb->addOrderBy('mid', 'ASC');
        }
        // During install, we start with no tables and no installed modules. We need to
        // handle the resulting exceptions and return an empty array.
        try {
            if (!$result = $qb->execute()) {
                return $ret;
            }
        } catch (\Throwable $e) {
            /** should get specific exceptions */
            \Xoops::getInstance()->events()->triggerEvent('core.exception', $e);
            return $ret;
        }
        while ($myrow = $result->fetchAssociative()) {
            $module = new XoopsModule();
            $module->assignVars($myrow);
            if (!$id_as_key) {
                $ret[] = $module;
            } else {
                $ret[$myrow['mid']] = $module;
            }
            unset($module);
        }
        return $ret;
    }

    /**
     * returns an array of module names
     *
     * @param CriteriaElement|null $criteria       criteria
     * @param bool                 $dirname_as_key true  = array key is module directory
     *                                             false = array key is module id
     *
     * @return array
     */
    public function getNameList(?CriteriaElement $criteria = null, $dirname_as_key = false)
    {
        $ret = array();
        $modules = $this->getObjectsArray($criteria, true);
        foreach (array_keys($modules) as $i) {
            if (!$dirname_as_key) {
                $ret[$i] = $modules[$i]->getVar('name');
            } else {
                $ret[$modules[$i]->getVar('dirname')] = $modules[$i]->getVar('name');
            }
        }
        return $ret;
    }
}
