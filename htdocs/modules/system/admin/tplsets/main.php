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
use Xmf\Request;

/**
 * Template sets Manager
 *
 * @copyright   XOOPS Project (http://xoops.org)
 * @license     GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author      Kazumi Ono (AKA onokazu)
 * @package     system
 */

// Get main instance
$xoops = Xoops::getInstance();
$system = System::getInstance();
$system_breadcrumb = SystemBreadcrumb::getInstance();

// Check users rights
if (!$xoops->isUser() || !$xoops->isModule() || !$xoops->user->isAdmin($xoops->module->mid())) {
    exit(XoopsLocale::E_NO_ACCESS_PERMISSION);
}

// Get Action type
$op = Request::getString('op', 'default');

// Call Header
$xoops->header('admin:system/system_templates.tpl');
// Define scripts
$xoops->theme()->addBaseScriptAssets(array('@jquery', '@jqueryui'));
$xoops->theme()->addScript('media/jquery/plugins/jquery.easing.js');
$xoops->theme()->addScript('media/jquery/plugins/jqueryFileTree.js');
$xoops->theme()->addScript('modules/system/js/admin.js');
$xoops->theme()->addScript('modules/system/js/templates.js');
$xoops->theme()->addScript('modules/system/js/code_mirror/codemirror.js');
// Define Stylesheet
$xoops->theme()->addStylesheet('modules/system/css/admin.css');
$xoops->theme()->addStylesheet('modules/system/css/code_mirror/docs.css');
// Define Breadcrumb and tips
$system_breadcrumb->addLink(SystemLocale::TEMPLATES_MANAGER, system_adminVersion('tplsets', 'adminpath'));

switch ($op) {
    //index
    default:
        // Define Breadcrumb and tips
        $admin_page = new \Xoops\Module\Admin();
        $admin_page->addBreadcrumbLink(SystemLocale::CONTROL_PANEL, \XoopsBaseConfig::get('url') . '/admin.php', true);
        $admin_page->addBreadcrumbLink(SystemLocale::TEMPLATES_MANAGER, $system->adminVersion('tplsets', 'adminpath'));
        $admin_page->renderBreadcrumb();
        $admin_page->addTips(SystemLocale::TEMPLATES_TIPS);
        $admin_page->renderTips();

        $xoops->tpl()->assign('index', true);

        $form = new Xoops\Form\ThemeForm(
            SystemLocale::TEMPLATE_OVERLOADED,
            "form",
            'admin.php?fct=tplsets',
            "post",
            true
        );

        $ele = new Xoops\Form\Select(SystemLocale::CHOOSE_TEMPLATE, 'tplset', $xoops->getConfig('tplset'));
        $tplset_handler = $xoops->getHandlerTplSet();
        $tplsetlist = $tplset_handler->getNameList();
        asort($tplsetlist);
        foreach ($tplsetlist as $key => $name) {
            $ele->addOption($key, $name);
        }
        $form->addElement($ele);
        $form->addElement(new Xoops\Form\SelectTheme(XoopsLocale::SELECT_THEME, 'select_theme', 1, 5), true);
        $form->addElement(new Xoops\Form\RadioYesNo(SystemLocale::FORCED_FILE_GENERATION, 'force_generated', 0), true);

        $modules = new Xoops\Form\Select(XoopsLocale::SELECT_MODULE, 'select_modules');

        $module_handler = $xoops->getHandlerModule();
        $criteria = new CriteriaCompo(new Criteria('isactive', 1));
        $moduleslist = $module_handler->getNameList($criteria, true);
        $modules->addOption(0, XoopsLocale::ALL_MODULES);
        $modules->addOptionArray($moduleslist);
        $form->addElement($modules, true);

        $form->addElement(new Xoops\Form\Hidden("active_templates", "0"));
        $form->addElement(new Xoops\Form\Hidden("active_modules", "0"));
        $form->addElement(new Xoops\Form\Hidden("op", "tpls_overload"));
        $form->addElement(new Xoops\Form\Button("", "submit", XoopsLocale::A_SUBMIT, "submit"));
        $form->display();
        break;

    //overload template
    case 'tpls_overload':
        if (!$xoops->security()->check()) {
            $xoops->redirect('admin.php?fct=tplsets', 3, implode('<br />', $xoops->security()->getErrors()));
        }
        // Assign Breadcrumb menu
        $system_breadcrumb->addHelp(system_adminVersion('tplsets', 'help') . '#override');
        $system_breadcrumb->addLink(SystemLocale::FILES_GENERATED);
        $system_breadcrumb->render();

        if ($_REQUEST['select_modules'] == '0' || $_REQUEST['active_modules'] == '1') {
            //Generate modules
            if (isset($_REQUEST['select_theme']) && isset($_REQUEST['force_generated'])) {
                //on verifie si le dossier module existe
                $template_overload = \XoopsBaseConfig::get('themes-path')
                    . '/' . $_REQUEST['select_theme'] . '/modules';
                $indexFile = \XoopsBaseConfig::get('root-path') . "/modules/system/include/index.html";
                $verif_write = false;
                $text = '';

                if (!is_dir($template_overload)) {
                    //Creation du dossier modules

                    if (!is_dir($template_overload)) {
                        mkdir($template_overload, 0777);
                    }
                    chmod($template_overload, 0777);
                    copy($indexFile, $template_overload . "/index.html");
                }

                $tplset = Request::getString('tplset', 'default', 'post');

                //on crée uniquement les templates qui n'existent pas
                $module_handler = $xoops->getHandlerModule();
                $tplset_handler = $xoops->getHandlerTplSet();
                $tpltpl_handler = $xoops->getHandlerTplFile();

                $criteria = new CriteriaCompo();
                $criteria->add(new Criteria('tplset_name', $tplset));
                $tplsets_arr = $tplset_handler->getObjects();
                $tcount = $tplset_handler->getCount();
                ;
                $installed_mods = $tpltpl_handler->getModuleTplCount($tplset);

                //all templates or only one template
                if ($_REQUEST['active_templates'] == 0) {
                    foreach (array_keys($tplsets_arr) as $i) {
                        $tplsetname = $tplsets_arr[$i]->getVar('tplset_name');
                        $tplstats = $tpltpl_handler->getModuleTplCount($tplsetname);

                        if (count($tplstats) > 0) {
                            foreach ($tplstats as $moddir => $filecount) {
                                $module = $xoops->getModuleByDirname($moddir);
                                if (is_object($module)) {
                                    // create module folder
                                    if (!is_dir($template_overload . '/' . $module->getVar('dirname'))) {
                                        mkdir($template_overload . '/' . $module->getVar('dirname'), 0777);
                                        chmod($template_overload . '/' . $module->getVar('dirname'), 0777);
                                        copy($indexFile, $template_overload . '/' . $module->getVar('dirname') . '/index.html');
                                    }

                                    // create block folder
                                    if (!is_dir($template_overload . '/' . $module->getVar('dirname') . '/blocks')) {
                                        if (!is_dir($template_overload . '/' . $module->getVar('dirname') . '/blocks')) {
                                            mkdir($template_overload . '/' . $module->getVar('dirname') . '/blocks', 0777);
                                        }
                                        chmod($template_overload . '/' . $module->getVar('dirname') . '/blocks', 0777);
                                        copy($indexFile, $template_overload . '/' . $module->getVar('dirname') . '/blocks' . '/index.html');
                                    }

                                    $class = "odd";
                                    $text .= '<table cellspacing="1" class="outer"><tr><th colspan="3" align="center">' . XoopsLocale::C_MODULES . ucfirst($module->getVar('dirname')) . '</th></tr><tr><th align="center">' . XoopsLocale::TYPES . '</th><th  align="center">' . XoopsLocale::FILES . '</th><th>' . XoopsLocale::STATUS . '</th></tr>';

                                    // create template
                                    $templates = $tpltpl_handler->find($tplsetname, 'module', null, $moddir);
                                    for ($j = 0; $j < count($templates); ++$j) {
                                        $filename = $templates[$j]->getVar('tpl_file');
                                        if ($tplsetname == $tplset) {
                                            $physical_file = \XoopsBaseConfig::get('themes-path') . '/' . $_REQUEST['select_theme'] . '/modules/' . $moddir . '/' . $filename;

                                            $tplfile = $tpltpl_handler->get($templates[$j]->getVar('tpl_id'), true);

                                            if (is_object($tplfile)) {
                                                if (!XoopsLoad::fileExists($physical_file) || $_REQUEST['force_generated'] == 1) {
                                                    $open = fopen($physical_file, 'w+');
                                                    if (fwrite($open, "" . html_entity_decode($tplfile->getVar('tpl_source', 'E'), ENT_QUOTES))) {
                                                        $text .= '<tr class="' . $class . '"><td align="center">' . XoopsLocale::TEMPLATES . '</td><td>' . $physical_file . '</td><td align="center">';
                                                        if (XoopsLoad::fileExists($physical_file)) {
                                                            $text .= '<img width="16" src="' . system_AdminIcons('success.png') . '" /></td></tr>';
                                                        } else {
                                                            $text .= '<img width="16" src="' . system_AdminIcons('cancel.png') . '" /></td></tr>';
                                                        }
                                                        $verif_write = true;
                                                    }
                                                    fclose($open);
                                                    $class = ($class === "even") ? "odd" : "even";
                                                }
                                            }
                                        }
                                    }

                                    // create block template
                                    $btemplates = $tpltpl_handler->find($tplsetname, 'block', null, $moddir);
                                    for ($k = 0; $k < count($btemplates); ++$k) {
                                        $filename = $btemplates[$k]->getVar('tpl_file');
                                        if ($tplsetname == $tplset) {
                                            $physical_file = \XoopsBaseConfig::get('themes-path') . '/' . $_REQUEST['select_theme'] . '/modules/' . $moddir . '/blocks/' . $filename;
                                            $btplfile = $tpltpl_handler->get($btemplates[$k]->getVar('tpl_id'), true);

                                            if (is_object($btplfile)) {
                                                if (!XoopsLoad::fileExists($physical_file) || $_REQUEST['force_generated'] == 1) {
                                                    $open = fopen($physical_file, 'w+');
                                                    if (fwrite($open, "" . mb_convert_encoding(html_entity_decode($btplfile->getVar('tpl_source', 'E')), 'UTF-8', 'ISO-8859-1') . "")) {
                                                        $text .= '<tr class="' . $class . '"><td align="center">' . XoopsLocale::BLOCKS . '</td><td>' . $physical_file . '</td><td align="center">';
                                                        if (XoopsLoad::fileExists($physical_file)) {
                                                            $text .= '<img width="16" src="' . system_AdminIcons('success.png') . '" /></td></tr>';
                                                        } else {
                                                            $text .= '<img width="16" src="' . system_AdminIcons('cancel.png') . '" /></td></tr>';
                                                        }
                                                        $verif_write = true;
                                                    }
                                                    fclose($open);
                                                    $class = ($class === "even") ? "odd" : "even";
                                                }
                                            }
                                        }
                                    }
                                    $text .= '</table>';
                                }
                            }
                            unset($module);
                        }
                    }
                } else {
                    foreach (array_keys($tplsets_arr) as $i) {
                        $tplsetname = $tplsets_arr[$i]->getVar('tplset_name');
                        $tplstats = $tpltpl_handler->getModuleTplCount($tplsetname);

                        if (count($tplstats) > 0) {
                            $moddir = $_REQUEST['select_modules'];
                            $module = $xoops->getModuleByDirname($moddir);
                            if (is_object($module)) {
                                // create module folder
                                if (!is_dir($template_overload . '/' . $module->getVar('dirname'))) {
                                    mkdir($template_overload . '/' . $module->getVar('dirname'), 0777);
                                    chmod($template_overload . '/' . $module->getVar('dirname'), 0777);
                                    copy($indexFile, $template_overload . '/' . $module->getVar('dirname') . '/index.html');
                                }

                                // create block folder
                                if (!is_dir($template_overload . '/' . $module->getVar('dirname') . '/blocks')) {
                                    if (!is_dir($template_overload . '/' . $module->getVar('dirname') . '/blocks')) {
                                        mkdir($template_overload . '/' . $module->getVar('dirname') . '/blocks', 0777);
                                    }
                                    chmod($template_overload . '/' . $module->getVar('dirname') . '/blocks', 0777);
                                    copy($indexFile, $template_overload . '/' . $module->getVar('dirname') . '/blocks' . '/index.html');
                                }

                                $class = "odd";
                                $text .= '<table cellspacing="1" class="outer"><tr><th colspan="3" align="center">' . XoopsLocale::C_MODULES . ucfirst($module->getVar('dirname')) . '</th></tr><tr><th align="center">' . XoopsLocale::TYPES . '</th><th  align="center">' . XoopsLocale::FILES . '</th><th>' . XoopsLocale::STATUS . '</th></tr>';
                                $select_templates_modules = $_REQUEST['select_templates_modules'];
                                for ($l = 0; $l < count($_REQUEST['select_templates_modules']); ++$l) {
                                    // create template
                                    $templates = $tpltpl_handler->find($tplsetname, 'module', null, $moddir);
                                    for ($j = 0; $j < count($templates); ++$j) {
                                        $filename = $templates[$j]->getVar('tpl_file');
                                        if ($tplsetname == $tplset) {
                                            $physical_file = \XoopsBaseConfig::get('themes-path') . '/' . $_REQUEST['select_theme'] . '/modules/' . $moddir . '/' . $filename;

                                            $tplfile = $tpltpl_handler->get($templates[$j]->getVar('tpl_id'), true);

                                            if (is_object($tplfile)) {
                                                if (!XoopsLoad::fileExists($physical_file) || $_REQUEST['force_generated'] == 1) {
                                                    if ($select_templates_modules[$l] == $filename) {
                                                        $open = fopen($physical_file, 'w+');
                                                        if (fwrite($open, html_entity_decode($tplfile->getVar('tpl_source', 'E'), ENT_QUOTES))) {
                                                            $text .= '<tr class="' . $class . '"><td align="center">' . XoopsLocale::TEMPLATES . '</td><td>' . $physical_file . '</td><td align="center">';
                                                            if (XoopsLoad::fileExists($physical_file)) {
                                                                $text .= '<img width="16" src="' . system_AdminIcons('success.png') . '" /></td></tr>';
                                                            } else {
                                                                $text .= '<img width="16" src="' . system_AdminIcons('cancel.png') . '" /></td></tr>';
                                                            }
                                                            $verif_write = true;
                                                        }
                                                        fclose($open);
                                                    }
                                                    $class = ($class === "even") ? "odd" : "even";
                                                }
                                            }
                                        }
                                    }

                                    // create block template
                                    $btemplates = $tpltpl_handler->find($tplsetname, 'block', null, $moddir);
                                    for ($k = 0; $k < count($btemplates); ++$k) {
                                        $filename = $btemplates[$k]->getVar('tpl_file');
                                        if ($tplsetname == $tplset) {
                                            $physical_file = \XoopsBaseConfig::get('themes-path') . '/' . $_REQUEST['select_theme'] . '/modules/' . $moddir . '/blocks/' . $filename;
                                            $btplfile = $tpltpl_handler->get($btemplates[$k]->getVar('tpl_id'), true);

                                            if (is_object($btplfile)) {
                                                if (!XoopsLoad::fileExists($physical_file) || $_REQUEST['force_generated'] == 1) {
                                                    if ($select_templates_modules[$l] == $filename) {
                                                        $open = fopen($physical_file, 'w+');
                                                        if (fwrite($open, mb_convert_encoding(html_entity_decode($btplfile->getVar('tpl_source', 'E')), 'UTF-8', 'ISO-8859-1') . "")) {
                                                            $text .= '<tr class="' . $class . '"><td align="center">' . XoopsLocale::BLOCKS . '</td><td>' . $physical_file . '</td><td align="center">';
                                                            if (XoopsLoad::fileExists($physical_file)) {
                                                                $text .= '<img width="16" src="' . system_AdminIcons('success.png') . '" /></td></tr>';
                                                            } else {
                                                                $text .= '<img width="16" src="' . system_AdminIcons('cancel.png') . '" /></td></tr>';
                                                            }
                                                            $verif_write = true;
                                                        }
                                                        fclose($open);
                                                    }
                                                    $class = ($class === "even") ? "odd" : "even";
                                                }
                                            }
                                        }
                                    }
                                }
                                $text .= '</table>';
                            }
                            unset($module);
                        }
                    }
                }
                $xoops->tpl()->assign('infos', $text);
                $xoops->tpl()->assign('verif', $verif_write);
            } else {
                $xoops->redirect("admin.php?fct=tplsets", 2, XoopsLocale::S_DONE);
            }
        } else {
            // Generate one module
            $xoops->tpl()->assign('index', true);

            $tplset = Request::getString('tplset', 'default', 'post');

            $form = new Xoops\Form\ThemeForm(XoopsLocale::SELECT_TEMPLATES, "form", 'admin.php?fct=tplsets', "post", true);

            $tpltpl_handler = $xoops->getHandlerTplFile();
            $templates_arr = $tpltpl_handler->find($tplset, '', null, $_REQUEST['select_modules']);

            $modules = new Xoops\Form\Select(XoopsLocale::SELECT_TEMPLATES, 'select_templates_modules', null, 10, true);
            foreach (array_keys($templates_arr) as $i) {
                $modules->addOption($templates_arr[$i]->getVar('tpl_file'));
            }
            $form->addElement($modules);

            $form->addElement(new Xoops\Form\Hidden("active_templates", "1"));
            $form->addElement(new Xoops\Form\Hidden("force_generated", $_REQUEST['force_generated']));
            $form->addElement(new Xoops\Form\Hidden("select_modules", $_REQUEST['select_modules']));
            $form->addElement(new Xoops\Form\Hidden("active_modules", "1"));
            $form->addElement(new Xoops\Form\Hidden("select_theme", $_REQUEST['select_theme']));
            $form->addElement(new Xoops\Form\Hidden("op", "tpls_overload"));
            $form->addElement(new Xoops\Form\Button("", "submit", XoopsLocale::A_SUBMIT, "submit"));
            $xoops->tpl()->assign('form', $form->render());
        }
        break;

    // save
    case 'tpls_save':
        if (!$xoops->security()->check()) {
            $xoops->redirect('admin.php?fct=tplsets', 3, implode('<br />', $xoops->security()->getErrors()));
        }
        $clean_path_file = Request::getString('path_file', '');
        if (!empty($clean_path_file)) {
            $path_file = realpath(\XoopsBaseConfig::get('themes-path') . '/' . trim($clean_path_file));
            $path_file = str_replace('\\', '/', $path_file);
            $pathInfo = pathinfo($path_file);
            if (!in_array($pathInfo['extension'], array('css', 'html', 'tpl'))) {
                $xoops->redirect("admin.php?fct=tplsets", 2, XoopsLocale::E_NOT_DONE);
            }
            // copy file
            $copy_file = $path_file . '.back';
            copy($path_file, $copy_file);
            // Save modif
            if (isset($_REQUEST['templates'])) {
                $open = fopen($path_file, "w+");
                if (!fwrite($open, mb_convert_encoding(stripslashes($_REQUEST['templates']), 'UTF-8', 'ISO-8859-1'))) {
                    $xoops->redirect("admin.php?fct=tplsets", 2, XoopsLocale::E_NOT_DONE);
                }
                fclose($open);
            }
        }
        $xoops->redirect("admin.php?fct=tplsets", 2, XoopsLocale::S_DONE);
        break;
}
// Call Footer
$xoops->footer();
