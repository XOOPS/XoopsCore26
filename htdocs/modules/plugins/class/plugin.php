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
 * plugins module
 *
 * @copyright       2013-2019 XOOPS Project (https://xoops.org)
 * @license         GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @author          trabis <lusopoemas@gmail.com>
 */

use Xoops\Core\Database\Connection;
use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;
use Xoops\Core\Kernel\Dtype;
use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;

class PluginsPlugin extends XoopsObject
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initVar('plugin_id', Dtype::TYPE_INTEGER, null, false);
        $this->initVar('plugin_caller', Dtype::TYPE_TEXT_BOX, null, false);
        $this->initVar('plugin_listener', Dtype::TYPE_TEXT_BOX, null, false);
        $this->initVar('plugin_status', Dtype::TYPE_INTEGER, null, 1);
        $this->initVar('plugin_order', Dtype::TYPE_INTEGER, null, false, 0);
    }
}

class PluginsPluginHandler extends XoopsPersistableObjectHandler
{
    /**
     * @param null|Connection $db database
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'plugins_plugin', 'PluginsPlugin', 'plugin_id', 'plugin_caller');
    }

    /**
     * @param string $listener
     * @param string $caller
     * @return bool|PluginsPlugin
     */
    public function getLC($listener, $caller)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('plugin_listener', $listener));
        $criteria->add(new Criteria('plugin_caller', $caller));

        //Expecting only one result;
        if ($objects = $this->getObjects($criteria)) {
            return $objects[0];
        } else {
            return false;
        }
    }

    /**
     * @param string $listener
     * @return array Array of PluginsPlugin
     */
    public function getByListener($listener)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('plugin_listener', (string)$listener));
        $criteria->setSort('plugin_status DESC, plugin_order');
        $criteria->setOrder('ASC');
        return $this->getObjects($criteria);
    }

    /**
     * @param string $caller
     * @return array Array of PluginsPlugin
     */
    public function getByCaller($caller)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('plugin_caller', (string)$caller));
        $criteria->setSort('plugin_status DESC, plugin_order');
        $criteria->setOrder('ASC');
        return $this->getObjects($criteria);
    }


    /**
     * @return array Array of PluginsPlugin
     */
    public function getThemAll()
    {
        $criteria = new CriteriaCompo();
        $criteria->setSort('plugin_status DESC, plugin_order');
        $criteria->setOrder('ASC');
        return $this->getObjects($criteria);
    }

    /**
     * @return array Array of Listeners
     */
    public function getListeners()
    {
        $ret = array();
        $qb = $this->db2->createXoopsQueryBuilder();
        $qb->select('plugin_listener')
            ->fromPrefix('plugins_plugin', '')
            ->groupBy('plugin_listener');
        $result = $qb->execute();
        while ($row = $result->fetchAssociative()) {
            $ret[$row['plugin_listener']] = $this->getModuleName($row['plugin_listener']);
        }
        return $ret;
    }

    /**
     * @return array Array of Callers
     */
    public function getCallers()
    {
        $ret = array();
        $qb = $this->db2->createXoopsQueryBuilder();
        $qb->select('plugin_caller')
            ->fromPrefix('plugins_plugin', '')
            ->groupBy('plugin_caller');
        $result = $qb->execute();
        while ($row = $result->fetchAssociative()) {
            $ret[$row['plugin_caller']] = $this->getModuleName($row['plugin_caller']);
        }
        return $ret;
    }

    /**
     * Gets the module name but checks if it is active or not
     * There is a preload that calls this method before deleting deactivated module entries
     *
     * @param string $dirname
     * @return mixed
     */
    public function getModuleName(string $dirname)
    {
         if ($module = \Xoops::getInstance()->getModuleByDirname((string)$dirname)) {
             return $module->getVar('name');
         } else {
             return $dirname;
         }
    }

    /**
     * @param string $listener
     * @param string $caller
     * @param int $status
     * @param int $order
     * @return bool
     */
    public function addNew($listener, $caller, $status = 1, $order = 0)
    {
        $object = new PluginsPlugin();
        $object->setNew();
        $object->setVar('plugin_listener', $listener);
        $object->setVar('plugin_caller', $caller);
        $object->setVar('plugin_status', $status);
        $object->setVar('plugin_order', $order);
        return $this->insert($object, true);
    }

    /**
     * Updates the order value after a post request
     *
     * @param int $id
     * @param int $order
     */
    public function updateOrder(int $id, int $order)
    {
        $this->updateAll('plugin_order', $order, new Criteria('plugin_id', $id));
    }

    /**
     * Updates the status value after a post request
     *
     * @param int $id
     * @param int $status
     */
    public function updateStatus(int $id, int $status)
    {
        $this->updateAll('plugin_status', $status, new Criteria('plugin_id', $id));
    }

    /**
     * Get Listeners By Caller
     * Check if the module is active in case it was deactivated
     *
     * @param string $caller
     * @return array
     */
    public function getActiveListenersByCaller(string $caller)
    {
        $xoops = \Xoops::getInstance();
        $ret = array();
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('plugin_caller', (string)$caller));
        $criteria->add(new Criteria('plugin_status', 1));
        $criteria->setSort('plugin_order');
        $criteria->setOrder('ASC');
        $plugins = $this->getAll($criteria, 'plugin_listener', false, false);
        foreach ($plugins as $plugin) {
            if ($xoops->isActiveModule($plugin['plugin_listener'])) {
                $ret[$plugin['plugin_listener']] = $plugin['plugin_listener'];
            }
        }
        return $ret;

    }

    /**
     * Deletes all entries by name
     * Useful when a module is deleted
     *
     * @param string $name
     * @return bool
     */
    public function deleteLC(string $name)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('plugin_caller', (string)$name));
        $criteria->add(new Criteria('plugin_listener', (string)$name), 'OR');
        return $this->deleteAll($criteria, true);
    }
}
