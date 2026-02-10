<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;
use Xoops\Core\Kernel\CriteriaElement;
use Xoops\Core\Kernel\XoopsObjectHandler;
use Xoops\Core\Kernel\Handlers\XoopsModule;


/**
 * Userconfigs
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id$
 */

class UserconfigsConfigHandler extends XoopsObjectHandler
{
    /**
     * holds reference to config item handler(DAO) class
     *
     * @var       UserconfigsItemHandler
     * @access    private
     */
    private $_iHandler;

    /**
     * holds reference to config option handler(DAO) class
     *
     * @var       UserconfigsOptionHandler
     * @access    private
     */
    private $_oHandler;

    /**
     * holds an array of cached references to config value arrays,
     *  indexed on module id and user id
     *
     * @var     array
     * @access  private
     */
    private $_cachedConfigs = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $helper = Userconfigs::getInstance();
        $this->_iHandler = $helper->getHandlerItem();
        $this->_oHandler = $helper->getHandlerOption();
    }

    /**
     * Create a config
     *
     * @see     UserconfigsItem
     * @return  UserconfigsItem {@link UserconfigsItem}
     */
    public function createConfig()
    {
        $instance = $this->_iHandler->create();
        return $instance;
    }

    /**
     * Get a config
     *
     * @param int  $id          ID of the config
     * @param bool $withoptions load the config's options now?
     *
     * @return   UserconfigsItem {@link UserconfigsItem}
     */
    public function getConfig($id, $withoptions = false)
    {
        /* @var $config UserconfigsItem */
        $config = $this->_iHandler->get($id);
        if ($withoptions == true) {
            $config->setConfOptions($this->getConfigOptions(new Criteria('conf_id', $id)));
        }
        return $config;
    }

    /**
     * insert a new config in the database
     *
     * @param UserconfigsItem $config {@link UserconfigsItem}
     *
     * @return bool
     */
    public function insertConfig(UserconfigsItem $config)
    {
        if (!$this->_iHandler->insert($config)) {
            return false;
        }
        $options = $config->getConfOptions();
        $count = count($options);
        $conf_id = $config->getVar('conf_id');
        for ($i = 0; $i < $count; ++$i) {
            $options[$i]->setVar('conf_id', $conf_id);
            if (!$this->_oHandler->insert($options[$i])) {
                foreach ($options[$i]->getErrors() as $msg) {
                    $config->setErrors($msg);
                }
            }
        }
        if (!empty($this->_cachedConfigs[$config->getVar('conf_modid')][$config->getVar('conf_uid')])) {
            unset($this->_cachedConfigs[$config->getVar('conf_modid')][$config->getVar('conf_uid')]);
        }
        return true;
    }

    /**
     * Delete a config from the database
     *
     * @param UserconfigsItem $config {@link UserconfigsItem}
     *
     * @return bool
     */
    public function deleteConfig(UserconfigsItem $config)
    {
        if (!$this->_iHandler->delete($config, true)) {
            return false;
        }
        $options = $config->getConfOptions();
        $count = count($options);
        if ($count == 0) {
            $options = $this->getConfigOptions(new Criteria('conf_id', $config->getVar('conf_id')));
            $count = count($options);
        }
        if (is_array($options) && $count > 0) {
            for ($i = 0; $i < $count; ++$i) {
                $this->_oHandler->delete($options[$i], true);
            }
        }
        if (!empty($this->_cachedConfigs[$config->getVar('conf_modid')][$config->getVar('conf_uid')])) {
            unset($this->_cachedConfigs[$config->getVar('conf_modid')][$config->getVar('conf_uid')]);
        }
        return true;
    }

    /**
     * get one or more Configs
     *
     * @param CriteriaElement|null $criteria  {@link CriteriaElement}
     * @param bool                 $id_as_key Use the configs' ID as keys?
     *
     * @return    array   Array of {@link UserconfigsItem} objects
     */
    public function getConfigs(?CriteriaElement $criteria = null, $id_as_key = false)
    {
        $criteria2 = new CriteriaCompo();
        if ($criteria) {
            $criteria2->add($criteria);
            if (!$criteria->getSort()) {
                $criteria2->setSort('conf_order');
                $criteria2->setOrder('ASC');
            }
        } else {
            $criteria2->setSort('conf_order');
            $criteria2->setOrder('ASC');
        }
        return $this->_iHandler->getObjects($criteria2, $id_as_key);
    }

    /**
     * Count some configs
     *
     * @param CriteriaElement|null $criteria {@link CriteriaElement}
     *
     * @return int
     */
    public function getConfigCount(?CriteriaElement $criteria = null)
    {
        return $this->_iHandler->getCount($criteria);
    }

    /**
     * Get configs from a certain module
     *
     * @param int $module ID of a module
     *
     * @return    array   array of {@link UserconfigsConfig}s
     */
    public function getConfigsByModule($module = 0)
    {
        $ret = array();
        $criteria = new Criteria('conf_modid', (int)($module));
        $configs = $this->getConfigs($criteria, true);
        if (is_array($configs)) {
            foreach (array_keys($configs) as $i) {
                $ret[$configs[$i]->getVar('conf_name')] = $configs[$i]->getConfValueForOutput();
            }
        }
        $_cachedConfigs[$module] = $ret;
        return $_cachedConfigs[$module];
    }

    /**
     * Deletes configs from a certain module
     *
     * @param int $module
     *
     * @return bool
     */
    public function deleteConfigsByModule($module = 0)
    {
        $criteria = new Criteria('conf_modid', (int)($module));
        $configs = $this->getConfigs($criteria, true);
        if (is_array($configs)) {
            foreach (array_keys($configs) as $i) {
                $this->deleteConfig($configs[$i]);
            }
            return true;
        }
        return false;
    }

    /**
     * Get configs from a certain user
     *
     * @param int $uid      ID of a user
     * @param int $moduleId ID of a module
     *
     * @return    array   array of {@link UserconfigsConfig}s
     */
    public function getConfigsByUser($uid, $moduleId)
    {
        static $_cachedConfigs;
        if (!empty($_cachedConfigs[$moduleId][$uid])) {
            return $_cachedConfigs[$moduleId][$uid];
        } else {
            $ret = array();
            $criteria = new CriteriaCompo(new Criteria('conf_modid', (int)($moduleId)));
            $criteria->add(new Criteria('conf_uid', (int)($uid)));
            $configs = $this->getConfigs($criteria, true);
            if (is_array($configs)) {
                foreach (array_keys($configs) as $i) {
                    $ret[$configs[$i]->getVar('conf_name')] = $configs[$i]->getConfValueForOutput();
                }
            }
            $_cachedConfigs[$moduleId][$uid] = $ret;
            return $_cachedConfigs[$moduleId][$uid];
        }
    }

    /**
     * Make a new {@link UserconfigsOption}
     *
     * @return UserconfigsOption {@link UserconfigsOption}
     */
    public function createConfigOption()
    {
        $inst = $this->_oHandler->create();
        return $inst;
    }

    /**
     * Get a {@link UserconfigsOption}
     *
     * @param int $id ID of the config option
     *
     * @return   UserconfigsOption  {@link UserconfigsOption}
     */
    public function getConfigOption($id)
    {
        $inst = $this->_oHandler->get($id);
        return $inst;
    }

    /**
     * Get one or more {@link UserconfigsOption}s
     *
     * @param CriteriaElement|null $criteria  {@link CriteriaElement}
     * @param bool                 $id_as_key Use IDs as keys in the array?
     *
     * @return    array   Array of {@link UserconfigsOption}s
     */
    public function getConfigOptions(?CriteriaElement $criteria = null, $id_as_key = false)
    {
        return $this->_oHandler->getObjects($criteria, $id_as_key);
    }

    /**
     * Count some {@link UserconfigsOption}s
     *
     * @param CriteriaElement|null $criteria {@link CriteriaElement}
     *
     * @return    int     Count of {@link UserconfigsOption}s matching $criteria
     */
    public function getConfigOptionsCount(?CriteriaElement $criteria = null)
    {
        return $this->_oHandler->getCount($criteria);
    }

    /**
     * Get a list of configs
     *
     * @param int $conf_modid ID of the modules
     * @param int $conf_uid   ID of the user
     *
     * @return    array   Associative array of name=>value pairs.
     */
    public function getConfigList($conf_modid, $conf_uid = 0)
    {
        if (!empty($this->_cachedConfigs[$conf_modid][$conf_uid])) {
            return $this->_cachedConfigs[$conf_modid][$conf_uid];
        } else {
            $criteria = new CriteriaCompo(new Criteria('conf_modid', $conf_modid));
            if (empty($conf_uid)) {
                $criteria->add(new Criteria('conf_uid', $conf_uid));
            }
            $criteria->setSort('conf_order');
            $criteria->setOrder('ASC');
            $configs = $this->_iHandler->getObjects($criteria);
            $confcount = count($configs);
            $ret = array();
            for ($i = 0; $i < $confcount; ++$i) {
                $ret[$configs[$i]->getVar('conf_name')] = $configs[$i]->getConfValueForOutput();
            }
            $this->_cachedConfigs[$conf_modid][$conf_uid] = $ret;
            return $ret;
        }
    }

    public function createDefaultUserConfigs($uid, XoopsModule $module)
    {
        /* @var $plugin UserconfigsPluginInterface */
        if ($plugin = \Xoops\Module\Plugin::getPlugin($module->getVar('dirname'), 'userconfigs')) {
            // now reinsert them with the new settings
            $configs = $plugin->configs();
            if (!is_array($configs)) {
                $configs = array();
            }

            if (is_array($configs) && count($configs) > 0) {
                $order = 0;
                foreach ($configs as $config) {
                    $confobj = $this->createConfig();
                    $confobj->setVar('conf_modid', $module->getVar('mid'));
                    $confobj->setVar('conf_uid', $uid);
                    $confobj->setVar('conf_name', $config['name']);
                    $confobj->setVar('conf_title', $config['title']);
                    $confobj->setVar('conf_desc', $config['description']);
                    $confobj->setVar('conf_formtype', $config['formtype']);
                    $confobj->setVar('conf_valuetype', $config['valuetype']);
                    $confobj->setConfValueForInput($config['default']);
                    $confobj->setVar('conf_order', $order);
                    if (isset($config['options']) && is_array($config['options'])) {
                        foreach ($config['options'] as $key => $value) {
                            $confop = $this->createConfigOption();
                            $confop->setVar('confop_name', $key);
                            $confop->setVar('confop_value', $value);
                            $confobj->setConfOptions($confop);
                            unset($confop);
                        }
                    }
                    ++$order;
                    $this->insertConfig($confobj);
                    unset($confobj);
                }
                unset($configs);
            }
        }
    }
}
