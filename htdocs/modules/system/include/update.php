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
use Xoops\Core\Kernel\Handlers\XoopsModule;

/**
 * System update functions
 *
 * @author    Kazumi Ono (AKA onokazu)
 * @copyright 2000-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */

/**
 * xoops_module_update_system
 *
 * @param XoopsModule $module
 *
 * @return bool
 */
function xoops_module_update_system(XoopsModule $module)
{
    $xoops = Xoops::getInstance();
    if ($module->getVar('version') == 100) {
        $qb = $xoops->db()->createXoopsQueryBuilder();
        $eb = $qb->expr();
        $sql = $qb->select('t1.tpl_id')
            ->fromPrefix('system_tplfile', 't1')
            ->fromPrefix('system_tplfile', 't2')
            ->where($eb->eq('t1.tpl_module', 't2.tpl_module '))
            ->andWhere($eb->eq('t1.tpl_tplset', 't2.tpl_tplset'))
            ->andWhere($eb->eq('t1.tpl_file', 't2.tpl_file'))
            ->andWhere($eb->eq('t1.tpl_id', 't2.tpl_id'));
        $result = $sql->execute();
        $tplids = array();
        while (list($tplid) = $result->fetchNumeric()) {
            $tplids[] = $tplid;
        }
        if (count($tplids) > 0) {
            $tplfile_handler = $xoops->getHandlerTplFile();
            $duplicate_files = $tplfile_handler->getTplObjects(
                new Criteria('tpl_id', "(".implode(',', $tplids).")", "IN")
            );

            if (count($duplicate_files) > 0) {
                foreach (array_keys($duplicate_files) as $i) {
                    $tplfile_handler->deleteTpl($duplicate_files[$i]);
                }
            }
        }
    }
    // Copy old configs in new configs and delete old configs
    // Not for conf_catid =5 (Update in search extension)
    // Not for conf_catid =6 (Update in mail user extension)
    $config_handler = $xoops->getHandlerConfig();
    $criteria = new CriteriaCompo();
    $criteria->add(new Criteria('conf_modid', 0));
    $criteria->add(new Criteria('conf_catid', 5, '!='));
    $criteria->add(new Criteria('conf_catid', 6, '!='));
    $configs = $config_handler->getConfigs($criteria);
    $confcount = count($configs);
    if ($confcount > 0) {
        for ($i = 0; $i < $confcount; ++$i) {
            $criteria = new CriteriaCompo();
            $criteria->add(new Criteria('conf_modid', 1));
            $criteria->add(new Criteria('conf_name', $configs[$i]->getVar('conf_name')));
            $new_configs = $config_handler->getConfigs($criteria);
            $new_confcount = count($new_configs);
            if ($new_confcount > 0) {
                for ($j = 0; $j < $new_confcount; ++$j) {
                    $obj = $config_handler->getConfig($new_configs[$j]->getVar('conf_id'));
                }
                $obj->setVar("conf_value", $configs[$i]->getVar('conf_value'));
                $config_handler->insertConfig($obj);
                $config_handler->deleteConfig($configs[$i]);
            }

        }

    }
    return true;
}
