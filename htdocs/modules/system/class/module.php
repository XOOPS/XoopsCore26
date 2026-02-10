<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\Database\Schema\ImportSchema;
use Xoops\Core\FixedGroups;
use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;
use Xoops\Core\Kernel\Handlers\XoopsBlock;
use Xoops\Core\Kernel\Handlers\XoopsModule;
use Xoops\Core\Psr4ClassLoader;
use Xoops\Module\Plugin\ConfigCollector;
use Xmf\Yaml;
use Doctrine\DBAL\Exception as DBALException;
use Xoops\Core\Database\Schema\SchemaSynchronizer;

/**
 * System Module
 *
 * @category  SystemModule
 * @package   SystemModule
 * @author    Andricq Nicolas (AKA MusS)
 * @copyright 2000-2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class SystemModule
{
    public $error = array();
    public $trace = array();
    protected $modulesList = array();
    protected $modulesDirnames = array();
    protected $config_delng = array();
    protected $template_delng = array();
    protected $config_old = array();
    protected $reservedTables = array(
        'system_blockmodule',
        'system_config',
        'system_configoption',
        'system_group',
        'system_usergroup',
        'system_permission',
        'system_module',
        'system_block',
        'system_online',
        'system_privatemessage',
        'system_session',
        'system_tplfile',
        'system_tplset',
        'system_tplsource',
        'system_user',
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        // Get main instance
        $xoops = Xoops::getInstance();
        $module_handler = $xoops->getHandlerModule();

        $this->modulesList = XoopsLists::getModulesList();

        $modules = $module_handler->getObjectsArray();
        /* @var $module XoopsModule */
        foreach ($modules as $module) {
            $this->modulesDirnames[] = $module->getInfo('dirname');
        }
    }

    /**
     * getModuleList
     *
     * @return array of modules
     */
    public function getModuleList()
    {
        // Get main instance
        $xoops = Xoops::getInstance();
        $module_handler = $xoops->getHandlerModule();
        $moduleperm_handler = $xoops->getHandlerGroupPermission();

        $criteria = new CriteriaCompo();
        $criteria->setSort('weight');
        // Get all installed modules
        $modules = $module_handler->getObjects($criteria, true);
        $list = array();
        /* @var $module XoopsModule */
        foreach ($modules as $module) {
            if (!$module->getInfo('extension')) {
                if ($module->getInfo('dirname') === 'system') {
                    $module->setInfo('can_delete', false);
                    $module->setInfo('can_disable', false);
                } else {
                    $module->setInfo('can_delete', true);
                    $module->setInfo('can_disable', true);
                }
                if (round((float)$module->getInfo('version'), 2) != $module->getVar('version')) {
                    $module->setInfo('warning_update', true);
                }
                if (XoopsLoad::fileExists(
                    \XoopsBaseConfig::get('root-path') . '/modules/' . $module->getVar('dirname') . '/icons/logo_small.png'
                )) {
                    $module->setInfo(
                        'logo_small',
                        \XoopsBaseConfig::get('url') . '/modules/' . $module->getVar('dirname') . '/icons/logo_small.png'
                    );
                } else {
                    $module->setInfo('logo_small', \XoopsBaseConfig::get('url') . '/media/xoops/images/icons/16/default.png');
                }
                $module->setInfo('version', round($module->getVar('version') / 100, 2));
                $module->setInfo('update', XoopsLocale::formatTimestamp($module->getVar('last_update'), 's'));
                $module->setInfo(
                    'link_admin',
                    \XoopsBaseConfig::get('url') . '/modules/' . $module->getVar('dirname') . '/' . $module->getInfo('adminindex')
                );

                if ($module->getVar('isactive')) {
                    $module->setInfo('options', $module->getAdminMenu());
                }

                $groups = array();
                if (is_object($xoops->user)) {
                    $groups = $xoops->user->getGroups();
                }

                $sadmin = $moduleperm_handler->checkRight(
                    'module_admin',
                    $module->getVar('mid'),
                    $groups
                );
                if ($sadmin && ($module->getVar('hasnotification')
                    || is_array($module->getInfo('config')) || is_array($module->getInfo('comments')))
                ) {
                    $module->setInfo(
                        'link_pref',
                        \XoopsBaseConfig::get('url') . '/modules/system/admin.php?fct=preferences&amp;op=showmod&amp;mod='
                        . $module->getVar('mid')
                    );
                }

                $list[] = $module;
            }
        }
        return $list;
    }

    /**
     * getInstalledModules
     *
     * @return array of installed modules
     */
    public function getInstalledModules()
    {
        // Get main instance
        $xoops = Xoops::getInstance();
        $module_handler = $xoops->getHandlerModule();

        $ret = array();
        $i = 0;
        foreach ($this->modulesList as $file) {
            if (XoopsLoad::fileExists(\XoopsBaseConfig::get('root-path') . '/modules/' . $file . '/xoops_version.php')) {
                clearstatcache();
                $file = trim($file);
                if (!in_array($file, $this->modulesDirnames)) {
                    /* @var $module XoopsModule */
                    $module = $module_handler->create();
                    $module->loadInfo($file);
                    if (!$module->getInfo('extension')) {
                        $module->setInfo('mid', $i);
                        $module->setInfo('version', round((float)$module->getInfo('version'), 2));
                        $ret[] = $module;
                        unset($module);
                        ++$i;
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * install a module
     *
     * @param string  $mod   module dirname
     * @param boolean $force force query
     *
     * @return bool|XoopsModule|XoopsObject
     */
    public function install($mod = '', $force = false)
    {
        $xoops = Xoops::getInstance();
        $module_handler = $xoops->getHandlerModule();
        $mod = trim($mod);
        try {
            $cnt = $module_handler->getCount(new Criteria('dirname', $mod));
        } catch (DBALException $e) {
            $cnt = 0;
        }
        if ($cnt == 0) {
            /* @var $module XoopsModule */
            $module = $module_handler->create();
            $module->loadInfoAsVar($mod);
            $module->setVar('weight', 1);
            $module->setVar('isactive', 1);
            $module->setVar('last_update', time());
            $this->addTemporaryAutoloader($module);
            $install_script = $module->getInfo('onInstall');
            if ($install_script && trim($install_script) != '') {
                XoopsLoad::loadFile($xoops->path('modules/' . $mod . '/' . trim($install_script)));
            }
            $func = "xoops_module_pre_install_{$mod}";
            // If pre install function is defined, execute
            if (function_exists($func)) {
                $result = $func($module);
                if (!$result) {
                    $this->error[] = sprintf(XoopsLocale::EF_NOT_EXECUTED, $func);
                    $this->error = array_merge($this->error, $module->getErrors());
                    return false;
                } else {
                    $this->trace[] = sprintf(XoopsLocale::SF_EXECUTED, "<strong>{$func}</strong>");
                    $this->trace = array_merge($this->trace, $module->getMessages());
                }
            }
            // Create tables
            $created_tables = array();
            if (count($this->error) == 0) {
                $schema_file = $module->getInfo('schema');
                $sql_file = $module->getInfo('sqlfile');
                if (!empty($schema_file)) {
                    $schema_file_path = \XoopsBaseConfig::get('root-path') . '/modules/' . $mod . '/' . $schema_file;
                    if (!XoopsLoad::fileExists($schema_file_path)) {
                        $this->error[] =
                            sprintf(SystemLocale::EF_SQL_FILE_NOT_FOUND, "<strong>{$schema_file}</strong>");
                        return false;
                    }
                    $importer = new ImportSchema(\XoopsBaseConfig::get('db-prefix') . '_');
                    $importSchema = $importer->importSchemaArray(Yaml::read($schema_file_path));
                    $synchronizer = new SchemaSynchronizer($xoops->db());
                    $synchronizer->updateSchema($importSchema, true);
                } elseif (is_array($sql_file) && !empty($sql_file[\XoopsBaseConfig::get('db-type')])) {
                    $xoops->deprecated('Install SQL files are deprecated since 2.6.0. Convert to portable Schemas');

                    $sql_file_path = \XoopsBaseConfig::get('root-path') . '/modules/' . $mod . '/' . $sql_file[\XoopsBaseConfig::get('db-type')];
                    if (!XoopsLoad::fileExists($sql_file_path)) {
                        $this->error[] =
                            sprintf(SystemLocale::EF_SQL_FILE_NOT_FOUND, "<strong>{$sql_file_path}</strong>");
                        return false;
                    } else {
                        $this->trace[] = sprintf(SystemLocale::SF_SQL_FILE_FOUND, "<strong>{$sql_file_path}</strong>");
                        $this->trace[] = SystemLocale::MANAGING_TABLES;

                        $sql_query = fread(fopen($sql_file_path, 'r'), filesize($sql_file_path));
                        $sql_query = trim($sql_query);
                        SqlUtility::splitMySqlFile($pieces, $sql_query);
                        foreach ($pieces as $piece) {
                            // [0] contains the prefixed query
                            // [4] contains unprefixed table name
                            $prefixed_query = SqlUtility::prefixQuery($piece, $xoops->db()->prefix());
                            if (!$prefixed_query) {
                                $this->error[]['sub'] = '<span class="red">' . sprintf(
                                    XoopsLocale::EF_INVALID_SQL,
                                    '<strong>' . $piece . '</strong>'
                                ) . '</span>';
                                break;
                            }
                            // check if the table name is reserved
                            if (!in_array($prefixed_query[4], $this->reservedTables) || $mod === 'system') {
                                // not reserved, so try to create one
                                try {
                                    $result = $xoops->db()->query($prefixed_query[0]);
                                } catch (Exception $e) {
                                    $xoops->events()->triggerEvent('core.exception', $e);
                                    $result=false;
                                }

                                if (!$result) {
                                    $this->error[] = $xoops->db()->errorInfo();
                                    break;
                                } else {
                                    if (!in_array($prefixed_query[4], $created_tables)) {
                                        $this->trace[]['sub'] = sprintf(
                                            XoopsLocale::SF_TABLE_CREATED,
                                            '<strong>' . $xoops->db()->prefix($prefixed_query[4]) . '</strong>'
                                        );
                                        $created_tables[] = $prefixed_query[4];
                                    } else {
                                        $this->trace[]['sub'] = sprintf(
                                            XoopsLocale::SF_DATA_INSERTED_TO_TABLE,
                                            '<strong>' . $xoops->db()->prefix($prefixed_query[4]) . '</strong>'
                                        );
                                    }
                                }
                            } else {
                                // the table name is reserved, so halt the installation
                                $this->error[]['sub'] = sprintf(
                                    SystemLocale::EF_TABLE_IS_RESERVED,
                                    '<strong>' . $prefixed_query[4] . '</strong>'
                                );
                                break;
                            }
                        }
                        // if there was an error, delete the tables created so far,
                        // so the next installation will not fail
                        if (count($this->error) > 0) {
                            foreach ($created_tables as $table) {
                                try {
                                    $xoops->db()->query('DROP TABLE ' . $xoops->db()->prefix($table));
                                } catch (Exception $e) {
                                    $xoops->events()->triggerEvent('core.exception', $e);
                                }
                            }
                            return false;
                        }
                    }
                }
            }
            // Save module info, blocks, templates and perms
            if (count($this->error) == 0) {
                if (!$module_handler->insertModule($module)) {
                    $this->error[] = sprintf(
                        XoopsLocale::EF_NOT_INSERTED_TO_DATABASE,
                        '<strong>' . $module->getVar('name') . '</strong>'
                    );
                    foreach ($created_tables as $ct) {
                        try {
                            $xoops->db()->query('DROP TABLE ' . $xoops->db()->prefix($ct));
                        } catch (Exception $e) {
                            $xoops->events()->triggerEvent('core.exception', $e);
                        }
                    }
                    $this->error[] = sprintf(XoopsLocale::EF_NOT_INSTALLED, "<strong>" . $module->name() . "</strong>");
                    $this->error[] = XoopsLocale::C_ERRORS;
                    unset($module);
                    unset($created_tables);
                    return false;
                }
                unset($created_tables);
                $this->trace[] = XoopsLocale::S_DATA_INSERTED . sprintf(
                    SystemLocale::F_MODULE_ID,
                    '<strong>' . $module->getVar('mid') . '</strong>'
                );
                $xoops->db()->beginTransaction();
                // install Templates
                $this->installTemplates($module);

                $xoops->templateClearModuleCache($module->getVar('mid'));

                // install blocks
                $this->installBlocks($module);

                // Install Configs
                $this->installConfigs($module);

                if ($module->getInfo('hasMain')) {
                    $groups = array(FixedGroups::ADMIN, FixedGroups::USERS, FixedGroups::ANONYMOUS);
                } else {
                    $groups = array(FixedGroups::ADMIN);
                }
                // retrieve all block ids for this module
                $block_handler = $xoops->getHandlerBlock();
                $blocks = $block_handler->getByModule($module->getVar('mid'), false);
                $this->trace[] = SystemLocale::MANAGING_PERMISSIONS;
                $gperm_handler = $xoops->getHandlerGroupPermission();
                foreach ($groups as $mygroup) {
                    if ($gperm_handler->checkRight('module_admin', 0, $mygroup)) {
                        $mperm = $gperm_handler->create();
                        $mperm->setVar('gperm_groupid', $mygroup);
                        $mperm->setVar('gperm_itemid', $module->getVar('mid'));
                        $mperm->setVar('gperm_name', 'module_admin');
                        $mperm->setVar('gperm_modid', 1);
                        if (!$gperm_handler->insert($mperm)) {
                            $this->trace[]['sub'] = '<span class="red">' . sprintf(
                                SystemLocale::EF_GROUP_ID_ADMIN_ACCESS_RIGHT_NOT_ADDED,
                                '<strong>' . $mygroup . '</strong>'
                            ) . '</span>';
                        } else {
                            $this->trace[]['sub'] = sprintf(
                                SystemLocale::SF_GROUP_ID_ADMIN_ACCESS_RIGHT_ADDED,
                                '<strong>' . $mygroup . '</strong>'
                            );
                        }
                        unset($mperm);
                    }
                    $mperm = $gperm_handler->create();
                    $mperm->setVar('gperm_groupid', $mygroup);
                    $mperm->setVar('gperm_itemid', $module->getVar('mid'));
                    $mperm->setVar('gperm_name', 'module_read');
                    $mperm->setVar('gperm_modid', 1);
                    if (!$gperm_handler->insert($mperm)) {
                        $this->trace[]['sub'] = '<span class="red">' . sprintf(
                            SystemLocale::EF_GROUP_ID_USER_ACCESS_RIGHT_NOT_ADDED,
                            '<strong>' . $mygroup . '</strong>'
                        ) . '</span>';
                    } else {
                        $this->trace[]['sub'] = sprintf(
                            SystemLocale::SF_GROUP_ID_USER_ACCESS_RIGHT_ADDED,
                            '<strong>' . $mygroup . '</strong>'
                        );
                    }
                    unset($mperm);
                    foreach ($blocks as $blc) {
                        $bperm = $gperm_handler->create();
                        $bperm->setVar('gperm_groupid', $mygroup);
                        $bperm->setVar('gperm_itemid', $blc);
                        $bperm->setVar('gperm_name', 'block_read');
                        $bperm->setVar('gperm_modid', 1);
                        if (!$gperm_handler->insert($bperm)) {
                            $this->trace[]['sub'] = '<span class="red">'
                            . SystemLocale::E_BLOCK_ACCESS_NOT_ADDED . ' Block ID: <strong>'
                            . $blc . '</strong> Group ID: <strong>' . $mygroup . '</strong></span>';
                        } else {
                            $this->trace[]['sub'] = SystemLocale::S_BLOCK_ACCESS_ADDED
                                . sprintf(SystemLocale::F_BLOCK_ID, "<strong>" . $blc . "</strong>")
                                . sprintf(SystemLocale::F_GROUP_ID, "<strong>" . $mygroup . "</strong>");
                        }
                        unset($bperm);
                    }
                }
                unset($blocks);
                unset($groups);

                // execute module specific install script if any
                // If pre install function is defined, execute
                $func = "xoops_module_install_{$mod}";
                if (function_exists($func)) {
                    $result = $func($module);
                    if (!$result) {
                        $this->trace[] = sprintf(XoopsLocale::EF_NOT_EXECUTED, $func);
                        $this->trace = array_merge($this->trace, $module->getErrors());
                    } else {
                        $this->trace[] = sprintf(XoopsLocale::SF_EXECUTED, "<strong>{$func}</strong>");
                        $this->trace = array_merge($this->trace, $module->getMessages());
                    }
                }

                $this->trace[] = sprintf(
                    XoopsLocale::SF_INSTALLED,
                    '<strong>' . $module->getVar('name', 's') . '</strong>'
                );
                unset($blocks);

                $xoops->db()->commit();

                $xoops->events()->triggerEvent('system.module.install', $module);
                return $module;
            }
        } else {
            $this->error[] = sprintf(
                XoopsLocale::EF_NOT_INSTALLED,
                '<strong>' . $mod . '</strong>'
            ) . "&nbsp;" . XoopsLocale::C_ERRORS;
            return false;
        }
        return false;
    }

    /**
     * uninstall
     *
     * @param string $mod module dirname
     *
     * @return false|XoopsModule false on failure, module context on success
     */
    public function uninstall($mod = '')
    {
        $xoops = Xoops::getInstance();
        $module_handler = $xoops->getHandlerModule();
        $module = $module_handler->getByDirname($mod);
        $xoops->templateClearModuleCache($module->getVar('mid'));

        if ($module->getVar('dirname') === 'system') {
            $this->error[] = sprintf(
                XoopsLocale::EF_NOT_UNINSTALLED,
                '<strong>' . $module->getVar('name') . '</strong>'
            ) . "&nbsp;" . XoopsLocale::C_ERRORS;
            $this->error[] = " - " . SystemLocale::E_SYSTEM_MODULE_CANNOT_BE_DEACTIVATED;
            return false;
        } elseif ($module->getVar('dirname') == $xoops->getConfig('startpage')) {
            $this->error[] = sprintf(
                XoopsLocale::EF_NOT_UNINSTALLED,
                '<strong>' . $module->getVar('name') . '</strong>'
            ) . "&nbsp;" . XoopsLocale::C_ERRORS;
            $this->error[] = " - " . SystemLocale::E_THIS_MODULE_IS_SET_AS_DEFAULT_START_PAGE;
            return false;
        } else {
            // Load module specific install script if any
            $uninstall_script = $module->getInfo('onUninstall');
            if ($uninstall_script && trim($uninstall_script) != '') {
                XoopsLoad::loadFile($xoops->path('modules/' . $mod . '/' . trim($uninstall_script)));
            }
            $func = "xoops_module_pre_uninstall_{$mod}";
            // If pre uninstall function is defined, execute
            if (function_exists($func)) {
                $result = $func($module);
                if (!$result) {
                    $this->error[] = sprintf(XoopsLocale::EF_NOT_EXECUTED, $func);
                    $this->error[] = sprintf(
                        XoopsLocale::EF_NOT_UNINSTALLED,
                        '<strong>' . $module->getVar('name') . '</strong>'
                    ) . "&nbsp;" . XoopsLocale::C_ERRORS;
                    $this->error = array_merge($this->error, $module->getErrors());
                    return false;
                } else {
                    $this->trace[] = sprintf(XoopsLocale::SF_EXECUTED, "<strong>{$func}</strong>");
                    $this->trace = array_merge($this->trace, $module->getMessages());
                }
            }

            if (false === $module_handler->deleteModule($module)) {
                $this->error[] = sprintf(XoopsLocale::EF_NOT_DELETED, $module->getVar('name'));
                return false;
            } else {
                // delete templates
                $this->deleteTemplates($module);

                // Delete blocks and block template files
                $this->deleteBlocks($module);

                // Delete tables used by this module
                $modtables = $module->getInfo('tables');
                if ($modtables != false && is_array($modtables)) {
                    // get a schema manager
                    $schemaManager = $xoops->db()->getSchemaManager();
                    // create schema from the current database
                    $toSchema = $schemaManager->createSchema();

                    $this->trace[] = SystemLocale::MANAGING_TABLES;
                    foreach ($modtables as $table) {
                        // prevent deletion of reserved core tables!
                        if (!in_array($table, $this->reservedTables)) {
                            $toSchema->dropTable($xoops->db()->prefix($table));
                            $this->trace[]['sub'] = sprintf(
                                XoopsLocale::SF_TABLE_DROPPED,
                                '<strong>' . $xoops->db()->prefix($table) . '</strong>'
                            );
                        } else {
                            $this->trace[]['sub'] = '<span class="red">' . sprintf(
                                XoopsLocale::EF_TABLE_DROP_NOT_ALLOWED,
                                '<strong>' . $xoops->db()->prefix($table) . '</strong>'
                            ) . '</span>';
                        }
                    }
                    $synchronizer = new SchemaSynchronizer($xoops->db());
                    $synchronizer->updateSchema($toSchema, false);
                }

                // delete permissions if any
                $gperm_handler = $xoops->getHandlerGroupPermission();
                if (false === $gperm_handler->deleteByModule($module->getVar('mid'))) {
                    $this->trace[] = '<span class="red">' . SystemLocale::E_GROUP_PERMISSIONS_NOT_DELETED . '</span>';
                } else {
                    $this->trace[] = SystemLocale::S_GROUP_PERMISSIONS_DELETED;
                }

                // delete module config options if any
                $this->deleteConfigs($module);

                // execute module specific install script if any
                $func = 'xoops_module_uninstall_' . $mod;
                if (function_exists($func)) {
                    $result = $func($module);
                    if (!$result) {
                        $this->trace[] = sprintf(XoopsLocale::EF_NOT_EXECUTED, $func);
                        $this->trace = array_merge($this->error, $module->getErrors());
                    } else {
                        $this->trace[] = sprintf(XoopsLocale::SF_EXECUTED, "<strong>{$func}</strong>");
                        $this->trace = array_merge($this->trace, $module->getMessages());
                    }
                }
                $this->trace[] = sprintf(
                    XoopsLocale::SF_UNINSTALLED,
                    '<strong>' . $module->getVar('name') . '</strong>'
                );
                $xoops->events()->triggerEvent('system.module.uninstall', $module);
                return $module;
            }
        }
    }

    /**
     * update
     *
     * @param string $mod module dirname
     *
     * @return mixed boolean false if failed, XoopsModule if success
     */
    public function update($mod = '')
    {
        $xoops = Xoops::getInstance();
        $module_handler = $xoops->getHandlerModule();
        $module = $module_handler->getByDirname($mod);
        $xoops->templateClearModuleCache($module->getVar('mid'));
        // Save current version for use in the update function
        $prev_version = $module->getVar('version');
        // we don't want to change the module name set by admin
        $temp_name = $module->getVar('name');
        $module->loadInfoAsVar($module->getVar('dirname'));
        $module->setVar('name', $temp_name);
        $module->setVar('last_update', time());
        $this->addTemporaryAutoloader($module);

        if (!$module_handler->insertModule($module)) {
            $this->error[] = sprintf(XoopsLocale::EF_NOT_UPDATED, "<strong>" . $module->getVar('name') . "</strong>");
            return false;
        } else {
            // execute module specific preupdate script if any
            $update_script = $module->getInfo('onUpdate');
            if (false != $update_script && trim($update_script) != '') {
                XoopsLoad::loadFile($xoops->path('modules/' . $mod . '/' . trim($update_script)));
                $func = 'xoops_module_pre_update_' . $mod;
                if (function_exists($func)) {
                    $result = $func($module, $prev_version);
                    if (!$result) {
                        $this->trace[] = sprintf(XoopsLocale::EF_NOT_EXECUTED, $func);
                        $this->trace = array_merge($this->error, $module->getErrors());
                    } else {
                        $this->trace[] = sprintf(XoopsLocale::SF_EXECUTED, "<strong>{$func}</strong>");
                        $this->trace = array_merge($this->trace, $module->getMessages());
                    }
                }
            }

            // update schema
            $schema_file = $module->getInfo('schema');
            if (!empty($schema_file)) {
                $schema_file_path = \XoopsBaseConfig::get('root-path') . '/modules/' . $mod . '/' . $schema_file;
                if (!XoopsLoad::fileExists($schema_file_path)) {
                    $this->error[] =
                        sprintf(SystemLocale::EF_SQL_FILE_NOT_FOUND, "<strong>{$schema_file}</strong>");
                    return false;
                }
                $importer = new ImportSchema(\XoopsBaseConfig::get('db-prefix') . '_');
                $importSchema = $importer->importSchemaArray(Yaml::read($schema_file_path));
                $synchronizer = new SchemaSynchronizer($xoops->db());
                $synchronizer->updateSchema($importSchema, true);
            }

            // delete templates
            $this->deleteTemplates($module);

            // install templates
            $this->installTemplates($module);

            // install blocks
            $this->installBlocks($module);

            // reset compile_id
            $xoops->tpl()->setCompileId();

            // first delete all config entries
            $this->deleteConfigs($module);

            // Install Configs
            $this->installConfigs($module);

            // execute module specific update script if any
            $update_script = $module->getInfo('onUpdate');
            if (false != $update_script && trim($update_script) != '') {
                XoopsLoad::loadFile($xoops->path('modules/' . $mod . '/' . trim($update_script)));
                $func = 'xoops_module_update_' . $mod;
                if (function_exists($func)) {
                    $result = $func($module, $prev_version);
                    if (!$result) {
                        $this->trace[] = sprintf(XoopsLocale::EF_NOT_EXECUTED, $func);
                        $this->trace = array_merge($this->error, $module->getErrors());
                    } else {
                        $this->trace[] = sprintf(XoopsLocale::SF_EXECUTED, "<strong>{$func}</strong>");
                        $this->trace = array_merge($this->trace, $module->getMessages());
                    }
                }
            }
            $this->trace[] = sprintf(XoopsLocale::SF_UPDATED, '<strong>' . $module->getVar('name', 's') . '</strong>');
            return $module;
        }
    }

    /**
     * getTemplate
     *
     * @param string $dirname  module directory
     * @param string $template template name
     * @param string $type     template type - blocks, admin
     *
     * @return string
     */
    public function getTemplate($dirname, $template, $type = '')
    {
        $xoops = Xoops::getInstance();
        $ret = '';
        switch ($type) {
            case 'blocks':
            case 'admin':
                $path = $xoops->path('modules/' . $dirname . '/templates/' . $type . '/' . $template);
                break;
            default:
                $path = $xoops->path('modules/' . $dirname . '/templates/' . $template);
                break;
        }
        if (!XoopsLoad::fileExists($path)) {
            return $ret;
        } else {
            $lines = file($path);
        }
        if (!$lines) {
            return $ret;
        }
        $count = count($lines);
        for ($i = 0; $i < $count; ++$i) {
            $ret .= str_replace("\n", "\r\n", str_replace("\r\n", "\n", $lines[$i]));
        }
        return $ret;
    }

    /**
     * installTemplates
     *
     * @param XoopsModule $module module context
     *
     * @return void
     */
    public function installTemplates(XoopsModule $module)
    {
        $xoops = Xoops::getInstance();
        $templates = $module->getInfo('templates');
        if (is_array($templates) && count($templates) > 0) {
            $this->trace[] = SystemLocale::MANAGING_TEMPLATES;
            $tplfile_handler = $xoops->getHandlerTplFile();
            foreach ($templates as $tpl) {
                $tpl['file'] = trim($tpl['file']);
                if (!in_array($tpl['file'], $this->template_delng)) {
                    $type = (isset($tpl['type']) ? $tpl['type'] : 'module');
                    $tpldata = $this->getTemplate($module->getVar('dirname'), $tpl['file'], $type);
                    $tplfile = $tplfile_handler->create();
                    $tplfile->setVar('tpl_refid', $module->getVar('mid'));
                    $tplfile->setVar('tpl_lastimported', 0);
                    $tplfile->setVar('tpl_lastmodified', time());

                    if (preg_match("/\.css$/i", $tpl['file'])) {
                        $tplfile->setVar('tpl_type', 'css');
                    } else {
                        $tplfile->setVar('tpl_type', $type);
                    }
                    $tplfile->setVar('tpl_source', $tpldata);
                    $tplfile->setVar('tpl_module', $module->getVar('dirname'));
                    $tplfile->setVar('tpl_tplset', 'default');
                    $tplfile->setVar('tpl_file', $tpl['file']);
                    $tplfile->setVar('tpl_desc', $tpl['description']);
                    if (!$tplfile_handler->insertTpl($tplfile)) {
                        $this->trace[]['sub'] = '<span class="red">' . sprintf(
                            SystemLocale::EF_TEMPLATE_NOT_ADDED_TO_DATABASE,
                            '<strong>' . $tpl['file'] . '</strong>'
                        ) . '</span>';
                    } else {
                        $newid = $tplfile->getVar('tpl_id');
                        $this->trace[]['sub'] = sprintf(
                            SystemLocale::SF_TEMPLATE_ADDED,
                            '<strong>' . $tpl['file'] . '</strong>'
                        );
                        if ($module->getVar('dirname') === 'system') {
                            if (!$xoops->templateTouch($newid)) {
                                $this->trace[]['sub'] = '<span class="red">' . sprintf(
                                    SystemLocale::EF_TEMPLATE_NOT_RECOMPILED,
                                    '<strong>' . $tpl['file'] . '</strong>'
                                ) . '</span>';
                            } else {
                                $this->trace[]['sub'] = sprintf(
                                    SystemLocale::SF_TEMPLATE_RECOMPILED,
                                    '<strong>' . $tpl['file'] . '</strong>'
                                );
                            }
                        } else {
                            if ($xoops->config['template_set'] === 'default') {
                                if (!$xoops->templateTouch($newid)) {
                                    $this->trace[]['sub'] = '<span class="red">' . sprintf(
                                        SystemLocale::EF_TEMPLATE_NOT_RECOMPILED,
                                        '<strong>' . $tpl['file'] . '</strong>'
                                    ) . '</span>';
                                } else {
                                    $this->trace[]['sub'] = sprintf(
                                        SystemLocale::SF_TEMPLATE_RECOMPILED,
                                        '<strong>' . $tpl['file'] . '</strong>'
                                    );
                                }
                            }
                        }
                    }
                    unset($tpldata);
                } else {
                    $this->trace[]['sub'] = '<span class="red">' . sprintf(
                        SystemLocale::EF_TEMPLATE_NOT_DELETED,
                        '<strong>' . $tpl['file'] . '</strong>'
                    ) . '</span>';
                }
            }
        }
    }

    /**
     * deleteTemplates
     *
     * @param XoopsModule $module module context
     *
     * @return void
     */
    public function deleteTemplates(XoopsModule $module)
    {
        $xoops = Xoops::getInstance();
        $tplfile_handler = $xoops->getHandlerTplFile();
        $templates = $tplfile_handler->find('default', 'module', $module->getVar('mid'));
        if (is_array($templates) && count($templates) > 0) {
            $this->trace[] = SystemLocale::MANAGING_TEMPLATES;
            // delete template file entry in db
            /* @var $template XoopsTplFile */
            foreach ($templates as $template) {
                if (!$tplfile_handler->deleteTpl($template)) {
                    $this->template_delng[] = $template->getVar('tpl_file');
                }
            }
        }
    }

    /**
     * installBlocks
     *
     * @param XoopsModule $module module context
     *
     * @return void
     */
    public function installBlocks(XoopsModule $module)
    {
        $xoops = Xoops::getInstance();
        $blocks = $module->getInfo('blocks');
        $this->trace[] = SystemLocale::MANAGING_BLOCKS;
        $block_handler = $xoops->getHandlerBlock();
        $blockmodulelink_handler = $xoops->getHandlerBlockModuleLink();
        $tplfile_handler = $xoops->getHandlerTplFile();
        $showfuncs = array();
        $funcfiles = array();
        if (is_array($blocks) && count($blocks) > 0) {
            foreach ($blocks as $i => $block) {
                if (isset($block['show_func']) && $block['show_func'] != ''
                    && isset($block['file']) && $block['file'] != ''
                ) {
                    $showfuncs[] = $block['show_func'];
                    $funcfiles[] = $block['file'];

                    $criteria = new CriteriaCompo();
                    $criteria->add(new Criteria('mid', $module->getVar('mid')));
                    $criteria->add(new Criteria('func_num', $i));

                    $block_obj = $block_handler->getObjects($criteria);
                    if (count($block_obj) == 0) {
                        $block_obj[0] = $block_handler->create();
                        $block_obj[0]->setVar('func_num', $i);
                        $block_obj[0]->setVar('mid', $module->getVar('mid'));
                        $block_obj[0]->setVar('name', addslashes($block['name']));
                        $block_obj[0]->setVar('title', addslashes($block['name']));
                        $block_obj[0]->setVar('side', 0);
                        $block_obj[0]->setVar('weight', 0);
                        $block_obj[0]->setVar('visible', 0);
                        $block_obj[0]->setVar(
                            'block_type',
                            ($module->getVar('dirname') === 'system')
                                ? XoopsBlock::BLOCK_TYPE_SYSTEM
                                : XoopsBlock::BLOCK_TYPE_MODULE
                        );
                        $block_obj[0]->setVar('isactive', 1);
                        $block_obj[0]->setVar('content', '');
                        $block_obj[0]->setVar('c_type', XoopsBlock::CUSTOM_HTML);
                        $block_obj[0]->setVar('dirname', $module->getVar('dirname'));
                        $block_obj[0]->setVar('options', isset($block['options']) ? $block['options'] : '');
                    }
                    $block_obj[0]->setVar('func_file', $block['file']);
                    $block_obj[0]->setVar('show_func', isset($block['show_func']) ? $block['show_func'] : '');
                    $block_obj[0]->setVar('edit_func', isset($block['edit_func']) ? $block['edit_func'] : '');
                    $template = $this->getTemplate($module->getVar('dirname'), $block['template'], 'blocks');
                    $block_obj[0]->setVar('template', !empty($template) ? $block['template'] : '');
                    $block_obj[0]->setVar('last_modified', time());

                    if (!$block_handler->insert($block_obj[0])) {
                        $this->trace[]['sub'] = '<span class="red">' . sprintf(
                            XoopsLocale::EF_NOT_UPDATED,
                            $block_obj[0]->getVar('name')
                        ) . '</span>';
                    } else {
                        $this->trace[]['sub'] = sprintf(
                            SystemLocale::SF_BLOCK_UPDATED,
                            '<strong>' . $block_obj[0]->getVar('name')
                        ) . '</strong>' . sprintf(
                            SystemLocale::F_BLOCK_ID,
                            '<strong>' . $block_obj[0]->getVar('bid') . '</strong>'
                        );

                        if (0 == $blockmodulelink_handler->getCount(new Criteria('block_id', $block_obj[0]->getVar('bid')))) {
                            $blockmodulelink = $blockmodulelink_handler->create();
                            $blockmodulelink->setVar('block_id', $block_obj[0]->getVar('bid'));
                            $blockmodulelink->setVar('module_id', 0); //show on all pages
                            $blockmodulelink_handler->insert($blockmodulelink);
                        }

                        if ($template != '') {
                            $tplfile = $tplfile_handler->find('default', 'block', $block_obj[0]->getVar('bid'));
                            if (count($tplfile) == 0) {
                                $tplfile_new = $tplfile_handler->create();
                                $tplfile_new->setVar('tpl_module', $module->getVar('dirname'));
                                $tplfile_new->setVar('tpl_refid', $block_obj[0]->getVar('bid'));
                                $tplfile_new->setVar('tpl_tplset', 'default');
                                $tplfile_new->setVar('tpl_file', $block_obj[0]->getVar('template'));
                                $tplfile_new->setVar('tpl_type', 'block');
                            } else {
                                /* @var $tplfile_new XoopsTplFile */
                                $tplfile_new = $tplfile[0];
                                $tplfile_new->setVars($tplfile_new->getValues());
                            }
                            $tplfile_new->setVar('tpl_source', $template);
                            $tplfile_new->setVar('tpl_desc', $block['description']);
                            $tplfile_new->setVar('tpl_lastmodified', time());
                            $tplfile_new->setVar('tpl_lastimported', 0);
                            if (!$tplfile_handler->insertTpl($tplfile_new)) {
                                $this->trace[]['sub'] = '<span class="red">' . sprintf(
                                    SystemLocale::EF_TEMPLATE_NOT_UPDATED,
                                    '<strong>' . $block['template'] . '</strong>'
                                ) . '</span>';
                            } else {
                                $this->trace[]['sub'] = sprintf(
                                    SystemLocale::SF_TEMPLATE_UPDATED,
                                    '<strong>' . $block['template'] . '</strong>'
                                );
                                if ($module->getVar('dirname') === 'system') {
                                    if (!$xoops->templateTouch($tplfile_new->getVar('tpl_id'))) {
                                        $this->trace[]['sub'] = '<span class="red">' . sprintf(
                                            SystemLocale::EF_TEMPLATE_NOT_RECOMPILED,
                                            '<strong>' . $block['template'] . '</strong>'
                                        ) . '</span>';
                                    } else {
                                        $this->trace[]['sub'] = sprintf(
                                            SystemLocale::SF_TEMPLATE_RECOMPILED,
                                            '<strong>' . $block['template'] . '</strong>'
                                        );
                                    }
                                } else {
                                    if ($xoops->config['template_set'] === 'default') {
                                        if (!$xoops->templateTouch($tplfile_new->getVar('tpl_id'))) {
                                            $this->trace[]['sub'] = '<span class="red">' . sprintf(
                                                SystemLocale::EF_TEMPLATE_NOT_RECOMPILED,
                                                '<strong>' . $block['template'] . '</strong>'
                                            ) . '</span>';
                                        } else {
                                            $this->trace[]['sub'] = sprintf(
                                                SystemLocale::SF_TEMPLATE_RECOMPILED,
                                                '<strong>' . $block['template'] . '</strong>'
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $blocks = $block_handler->getByModule($module->getVar('mid'));
        foreach ($blocks as $block) {
            /* @var $block XoopsBlock */
            if (!in_array($block->getVar('show_func'), $showfuncs)
                || !in_array($block->getVar('func_file'), $funcfiles)
            ) {
                if (!$block_handler->delete($block)) {
                    $this->trace[]['sub'] = '<span class="red">' . sprintf(
                        SystemLocale::EF_BLOCK_NOT_DELETED,
                        "<strong>" . $block->getVar('name') . "</strong>"
                    ) . sprintf(
                        SystemLocale::F_BLOCK_ID,
                        "<strong>" . $block->getVar('bid') . "</strong>"
                    ) . '</span>';
                } else {
                    $this->trace[]['sub'] = sprintf(
                        SystemLocale::SF_BLOCK_DELETED,
                        '<strong>' . $block->getVar('name') . '</strong>'
                    ) . '&nbsp;' . sprintf(
                        SystemLocale::F_BLOCK_ID,
                        '<strong>' . $block->getVar('bid') . '</strong>'
                    );
                    if ($block->getVar('template') != '') {
                        $tplfiles = $tplfile_handler->find(null, 'block', $block->getVar('bid'));
                        if (is_array($tplfiles)) {
                            /* @var $tplfile XoopsTplFile */
                            foreach ($tplfiles as $tplfile) {
                                if (!$tplfile_handler->deleteTpl($tplfile)) {
                                    $this->trace[]['sub'] = '<span class="red">'
                                        . SystemLocale::E_BLOCK_TEMPLATE_DEPRECATED_NOT_REMOVED
                                        . '(ID: <strong>' . $tplfile->getVar('tpl_id') . '</strong>)</span>';
                                } else {
                                    $this->trace[]['sub'] = sprintf(
                                        SystemLocale::SF_BLOCK_TEMPLATE_DEPRECATED,
                                        "<strong>" . $tplfile->getVar('tpl_file') . "</strong>"
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * deleteBlocks
     *
     * @param XoopsModule $module module
     *
     * @return void
     */
    public function deleteBlocks(XoopsModule $module)
    {
        $xoops = Xoops::getInstance();
        $block_handler = $xoops->getHandlerBlock();
        $blocks = $block_handler->getByModule($module->getVar('mid'));
        if (is_array($blocks) && count($blocks) > 0) {
            $tplfile_handler = $xoops->getHandlerTplFile();
            $this->trace[] = SystemLocale::MANAGING_BLOCKS;
            /* @var $block XoopsBlock */
            foreach ($blocks as $block) {
                if (false === $block_handler->deleteBlock($block)) {
                    $this->trace[]['sub'] = '<span class="red">' . sprintf(
                        SystemLocale::EF_BLOCK_NOT_DELETED,
                        "<strong>" . $block->getVar('name') . "</strong>"
                    ) . sprintf(
                        SystemLocale::F_BLOCK_ID,
                        "<strong>" . $block->getVar('bid') . "</strong>"
                    ) . '</span>';
                } else {
                    $this->trace[]['sub'] = sprintf(
                        SystemLocale::SF_BLOCK_DELETED,
                        "<strong>" . $block->getVar('name') . "</strong>"
                    ) . sprintf(
                        SystemLocale::F_BLOCK_ID,
                        "<strong>" . $block->getVar('bid') . "</strong>"
                    );
                }
                if ($block->getVar('template') != '') {
                    $templates = $tplfile_handler->find(null, 'block', $block->getVar('bid'));
                    /* @var $template XoopsTplFile */
                    foreach ($templates as $template) {
                        if (!$tplfile_handler->delete($template)) {
                            $this->trace[]['sub'] = '<span class="red">' . sprintf(
                                SystemLocale::EF_BLOCK_TEMPLATE_NOT_DELETED,
                                $template->getVar('tpl_file')
                            ) . sprintf(
                                SystemLocale::F_TEMPLATE_ID,
                                "<strong>" . $template->getVar('tpl_id') . "</strong>"
                            ) . '</span>';
                        } else {
                            $this->trace[]['sub'] = sprintf(
                                SystemLocale::SF_BLOCK_TEMPLATE_DELETED,
                                "<strong>" . $template->getVar('tpl_file') . "</strong>"
                            ) . sprintf(
                                SystemLocale::F_TEMPLATE_ID,
                                "<strong>" . $template->getVar('tpl_id') . "</strong>"
                            );
                        }
                    }
                    unset($templates);
                }
            }
        }
    }

    /**
     * deleteConfigs
     *
     * @param XoopsModule $module module
     *
     * @return void
     */
    public function deleteConfigs(XoopsModule $module)
    {
        $xoops = Xoops::getInstance();

        $config_handler = $xoops->getHandlerConfig();
        $configs = $config_handler->getConfigs(new Criteria('conf_modid', $module->getVar('mid')));
        if (is_array($configs) && count($configs) > 0) {
            $this->trace[] = SystemLocale::MANAGING_PREFERENCES;
            /* @var $config XoopsConfigItem */
            foreach ($configs as $config) {
                if (!$config_handler->deleteConfig($config)) {
                    $this->trace[]['sub'] = '<span class="red">'
                        . SystemLocale::E_CONFIG_DATA_NOT_DELETED
                        . sprintf(SystemLocale::F_CONFIG_ID, "<strong>" . $config->getVar('conf_id') . "</strong>")
                        . '</span>';
                    // save the name of config failed to delete for later use
                    $this->config_delng[] = $config->getVar('conf_name');
                } else {
                    $this->config_old[$config->getVar('conf_name')]['value'] = $config->getVar('conf_value', 'N');
                    $this->config_old[$config->getVar('conf_name')]['formtype'] = $config->getVar('conf_formtype');
                    $this->config_old[$config->getVar('conf_name')]['valuetype'] = $config->getVar('conf_valuetype');
                    $this->trace[]['sub'] = SystemLocale::S_CONFIG_DATA_DELETED
                        . sprintf(SystemLocale::F_CONFIG_ID, "<strong>" . $config->getVar('conf_id') . "</strong>");
                }
            }
        }
    }

    /**
     * installconfigs
     *
     * @param XoopsModule $module module being installed
     *
     * @return void
     */
    public function installConfigs(XoopsModule $module)
    {
        $xoops = Xoops::getInstance();
        // now reinsert them with the new settings
        $configs = $module->getInfo('config');
        if (!is_array($configs)) {
            $configs = array();
        }

        $collector = new ConfigCollector($module, $configs);
        $xoops->events()->triggerEvent('system.module.update.configs', $collector);

        if (is_array($configs) && count($configs) > 0) {
            $this->trace[] = SystemLocale::MANAGING_PREFERENCES;
            $config_handler = $xoops->getHandlerConfig();
            $order = 0;
            foreach ($configs as $config) {
                // only insert ones that have been deleted previously with success
                if (!in_array($config['name'], $this->config_delng)) {
                    $confobj = $config_handler->createConfig();
                    $confobj->setVar('conf_modid', $module->getVar('mid'));
                    $confobj->setVar('conf_catid', 0);
                    $confobj->setVar('conf_name', $config['name']);
                    $confobj->setVar('conf_title', $config['title']);
                    $confobj->setVar('conf_desc', $config['description']);
                    $confobj->setVar('conf_formtype', $config['formtype']);
                    $confobj->setVar('conf_valuetype', $config['valuetype']);
                    if (isset($this->config_old[$config['name']]['value'])
                        && $this->config_old[$config['name']]['formtype'] == $config['formtype']
                        && $this->config_old[$config['name']]['valuetype'] == $config['valuetype']
                    ) {
                        // preserver the old value if any
                        // form type and value type must be the same
                        $confobj->setVar('conf_value', $this->config_old[$config['name']]['value']);
                    } else {
                        $confobj->setConfValueForInput($config['default']);
                        //$confobj->setVar('conf_value', $config['default']);
                    }
                    $confobj->setVar('conf_order', $order);
                    $confop_msgs = '';
                    if (isset($config['options']) && is_array($config['options'])) {
                        foreach ($config['options'] as $key => $value) {
                            $confop = $config_handler->createConfigOption();
                            $confop->setVar('confop_name', $key);
                            $confop->setVar('confop_value', $value);
                            $confobj->setConfOptions($confop);
                            $confop_msgs .= '<br />&nbsp;&nbsp;&nbsp;&nbsp;';
                            $confop_msgs .= SystemLocale::S_CONFIG_OPTION_ADDED;
                            $confop_msgs .= '&nbsp;';
                            $confop_msgs .= XoopsLocale::C_NAME;
                            $confop_msgs .= ' <strong>'
                                . \Xoops\Locale::translate($key, $module->getVar('dirname'))
                                . '</strong> ';
                            $confop_msgs .= XoopsLocale::C_VALUE . ' <strong>' . $value . '</strong> ';
                            unset($confop);
                        }
                    }
                    ++$order;
                    if (false != $config_handler->insertConfig($confobj)) {
                        $this->trace[]['sub'] = sprintf(
                            SystemLocale::SF_CONFIG_ADDED,
                            "<strong>" . $config['name'] . "</strong>"
                        ) . $confop_msgs;
                    } else {
                        $this->trace[]['sub'] = '<span class="red">'
                            . sprintf(SystemLocale::EF_CONFIG_NOT_ADDED, "<strong>" . $config['name'] . "</strong>")
                            . '</span>';
                    }
                    unset($confobj);
                }
            }
            unset($configs);
        }
    }

    /**
     * addTemporaryAutoloader
     *
     * Add an autoloader for the module being installed or updated.
     * This makes the module's autoloaded classes available for use
     * in onInstall/onUpdate scripts.
     *
     * @param XoopsModule $module module object here namespace is defined
     *
     * @return void
     */
    protected function addTemporaryAutoloader(XoopsModule $module) : void
    {
        $prefix = $module->getVar('namespace');
        if (empty($prefix)) {
            return;
        }
        $xoops = Xoops::getInstance();
        $base_dir = $xoops->path('modules/' . $module->getVar('dirname') . '/src');
        $loader = new Psr4ClassLoader;
        $loader->register();
        $loader->addNamespace($prefix, $base_dir);
    }
}
