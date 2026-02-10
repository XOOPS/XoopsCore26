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
 * SystemExtension
 *
 * @category  SystemExtension
 * @package   SystemExtension
 * @author    Andricq Nicolas (AKA MusS)
 * @copyright 2013-2014 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class SystemExtension extends SystemModule
{

    /**
     * getExtension
     *
     * @param string $mod module dirname
     *
     * @return type
     */
    public function getExtension($mod = '')
    {
        $ret = array();
        $extension = self::getExtensionList();
        foreach ($extension as $list) {
            /* @var $list XoopsModule */
            if ($list->getInfo('install')) {
                if (!is_array($list->getInfo('extension_module'))) {
                    $ret[] = $list;
                } else {
                    if (array_search($mod, $list->getInfo('extension_module')) !== false) {
                        $ret[] = $list;
                        //echo $list->getInfo('name') . is_array( $list->getInfo('extension_module') );
                    }
                }
                unset($list);
            }
        }

        return $ret;
    }

    /**
     * Return all extensions
     *
     * @return array
     */
    public function getExtensionList()
    {
        // Get main instance
        $xoops = Xoops::getInstance();
        $module_handler = $xoops->getHandlerModule();
        $moduleperm_handler = $xoops->getHandlerGroupPermission();

        $ret = array();
        $i = 0;
        foreach ($this->modulesList as $file) {
            $file = trim($file);
            if (XoopsLoad::fileExists(\XoopsBaseConfig::get('root-path') . '/modules/' . $file . '/xoops_version.php')) {
                clearstatcache();
                /* @var $module XoopsModule */
                $module = $module_handler->create();
                $module->loadInfoAsVar($file);
                if ($module->getInfo('extension')) {
                    if (in_array($file, $this->modulesDirnames)) {
                        $module->setInfo('install', true);
                        $extension = $module_handler->getByDirname($module->getInfo('dirname'));
                        $module->setInfo('mid', $extension->getVar('mid'));
                        $module->setInfo(
                            'update',
                            XoopsLocale::formatTimestamp($extension->getVar('last_update'), 's')
                        );
                        $module->setInfo('hasconfig', $module->getVar('hasconfig'));
                        if (round((float)$module->getInfo('version'), 2) != $extension->getVar('version')) {
                            $module->setInfo('warning_update', true);
                        }
                        $groups = array();
                        if (is_object($xoops->user)) {
                            $groups = $xoops->user->getGroups();
                        }
                        $sadmin = $moduleperm_handler
                            ->checkRight('module_admin', $module->getInfo('mid'), $groups);
                        if ($sadmin && ($module->getVar('hasnotification')
                            || is_array($module->getInfo('config'))
                            || is_array($module->getInfo('comments')))
                        ) {
                            $module->setInfo(
                                'link_pref',
                                \XoopsBaseConfig::get('url') . '/modules/system/admin.php?fct=preferences&amp;op=showmod&amp;mod='
                                . $module->getInfo('mid')
                            );
                        }
                    } else {
                        $module->setInfo('install', false);
                    }
                    $module->setInfo('version', round((float)$module->getInfo('version'), 2));
                    if (XoopsLoad::fileExists(
                        \XoopsBaseConfig::get('root-path') . '/modules/' . $module->getInfo('dirname') . '/icons/logo_small.png'
                    )) {
                        $module->setInfo(
                            'logo_small',
                            \XoopsBaseConfig::get('url') . '/modules/' . $module->getInfo('dirname') . '/icons/logo_small.png'
                        );
                    } else {
                        $module->setInfo('logo_small', \XoopsBaseConfig::get('url') . '/media/xoops/images/icons/16/default.png');
                    }
                    if (XoopsLoad::fileExists(
                        \XoopsBaseConfig::get('root-path') . '/modules/' . $module->getInfo('dirname') . '/icons/logo_large.png'
                    )) {
                        $module->setInfo(
                            'logo_large',
                            \XoopsBaseConfig::get('url') . '/modules/' . $module->getInfo('dirname') . '/icons/logo_large.png'
                        );
                    } else {
                        $module->setInfo('logo_large', \XoopsBaseConfig::get('url') . '/media/xoops/images/icons/32/default.png');
                    }
                    $module->setInfo(
                        'link_admin',
                        \XoopsBaseConfig::get('url') . '/modules/' . $module->getInfo('dirname') . '/' . $module->getInfo('adminindex')
                    );
                    $module->setInfo('options', $module->getAdminMenu());
                    $ret[] = $module;
                    unset($module);
                    ++$i;
                }
            }
        }
        return $ret;
    }

    /**
     * getInstalledExtensions
     *
     * @return array
     */
    public function getInstalledExtensions()
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
                    if ($module->getInfo('extension')) {
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
}
