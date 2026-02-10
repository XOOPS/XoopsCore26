<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Module;

/**
 * Xoops ModuleAdmin Classes
 *
 * @category  Xoops\Module\Admin
 * @package   Admin
 * @author    Mage Grégory (AKA Mage)
 * @copyright 2013-2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      xoops.org
 * @since     2.6.0
 */
class Admin
{
    /**
     * Set module directory
     *
     * @var string
     */
    public $tplModule = 'system';

    /**
     * Template call for each render parts
     *
     * @var array
     */
    public $tplFile = array(
        'index' => 'admin_index.tpl',
        'about' => 'admin_about.tpl',
        'infobox' => 'admin_infobox.tpl',
        'bread' => 'admin_breadcrumb.tpl',
        'button' => 'admin_buttons.tpl',
        'tips' => 'admin_tips.tpl',
        'nav'   => 'admin_navigation.tpl',
    );

    /**
     * Tips to display in admin page
     *
     * @var string
     */
    private $tips = '';

    /**
     * List of button
     *
     * @var array
     */
    private $itemButton = array();

    /**
     * List of Info Box
     *
     * @var array
     */
    private $itemInfoBox = array();

    /**
     * List of line of an Info Box
     *
     * @var array
     */
    private $itemConfigBoxLine = array();

    /**
     * Breadcrumb data
     *
     * @var array
     */
    private $bread = array();

    /**
     * Current module object
     *
     * @var \Xoops\Core\Kernel\Handlers\XoopsModule $module
     */
    private $module = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $xoops = \Xoops::getInstance();
        $this->module = $xoops->module;
        $xoops->theme()->addStylesheet('media/xoops/css/moduladmin.css');
    }

    /**
     * Add breadcrumb menu
     *
     * @param string $title title
     * @param string $link  url
     * @param bool   $home  is home
     *
     * @return void
     */
    public function addBreadcrumbLink($title = '', $link = '', $home = false)
    {
        if ($title != '') {
            $this->bread[] = array(
                'link' => $link, 'title' => $title, 'home' => $home
            );
        }
    }

    /**
     * Add config line
     *
     * @param string $value line value - a string or array of values
     * @param string $type  type of line default, folder, chmod, extension, module
     *                       Or, type value for itemConfigBoxLine -- accept, warning, error
     *
     * @return bool
     */
    public function addConfigBoxLine($value = '', $type = 'default')
    {
        switch ($type) {
            default:
            case "default":
                $this->itemConfigBoxLine[] = array('type' => $type, 'text' => $value);
                break;

            case "folder":
                if (!is_dir($value)) {
                    $this->itemConfigBoxLine[] = array(
                        'type' => 'error', 'text' => sprintf(\XoopsLocale::EF_FOLDER_DOES_NOT_EXIST, $value)
                    );
                } else {
                    $this->itemConfigBoxLine[] = array(
                        'type' => 'accept', 'text' => sprintf(\XoopsLocale::SF_FOLDER_EXISTS, $value)
                    );
                }
                break;

            case "chmod":
                if (is_dir($value[0])) {
                    if (substr(decoct(fileperms($value[0])), 2) != $value[1]) {
                        $this->itemConfigBoxLine[] = array(
                            'type' => 'error',
                            'text' => sprintf(
                                \XoopsLocale::EF_FOLDER_MUST_BE_WITH_CHMOD,
                                $value[0],
                                $value[1],
                                substr(decoct(fileperms($value[0])), 2)
                            )
                        );
                    } else {
                        $this->itemConfigBoxLine[] = array(
                            'type' => 'accept',
                            'text' => sprintf(
                                \XoopsLocale::EF_FOLDER_MUST_BE_WITH_CHMOD,
                                $value[0],
                                $value[1],
                                substr(decoct(fileperms($value[0])), 2)
                            )
                        );
                    }
                }
                break;

            case "extension":
                $xoops = \Xoops::getInstance();
                if (is_array($value)) {
                    $text = $value[0];
                    $type = $value[1];
                } else {
                    $text = $value;
                    $type = 'error';
                }
                if ($xoops->isActiveModule($text) == false) {
                    $this->itemConfigBoxLine[] = array(
                        'type' => $type, 'text' => sprintf(\XoopsLocale::EF_EXTENSION_IS_NOT_INSTALLED, $text)
                    );
                } else {
                    $this->itemConfigBoxLine[] = array(
                        'type' => 'accept', 'text' => sprintf(\XoopsLocale::SF_EXTENSION_IS_INSTALLED, $text)
                    );
                }
                break;

            case "module":
                $xoops = \Xoops::getInstance();
                if (is_array($value)) {
                    $text = $value[0];
                    $type = $value[1];
                } else {
                    $text = $value;
                    $type = 'error';
                }
                if ($xoops->isActiveModule($text) == false) {
                    $this->itemConfigBoxLine[] = array(
                        'type' => $type, 'text' => sprintf(\XoopsLocale::F_MODULE_IS_NOT_INSTALLED, $text)
                    );
                } else {
                    $this->itemConfigBoxLine[] = array(
                        'type' => 'accept', 'text' => sprintf(\XoopsLocale::F_MODULE_IS_INSTALLED, $text)
                    );
                }
                break;

            case "service":
                $xoops = \Xoops::getInstance();
                if (is_array($value)) {
                    $text = $value[0];
                    $type = $value[1];
                } else {
                    $text = $value;
                    $type = 'error';
                }
                if ($xoops->service($text)->isAvailable()) {
                    $this->itemConfigBoxLine[] = array(
                        'type' => 'accept', 'text' => sprintf(\XoopsLocale::SF_SERVICE_IS_INSTALLED, $text)
                    );
                } else {
                    $this->itemConfigBoxLine[] = array(
                        'type' => $type, 'text' => sprintf(\XoopsLocale::EF_SERVICE_IS_NOT_INSTALLED, $text)
                    );
                }
                break;

        }
        return true;
    }

    // simplified config aliases

    /**
     * Add error to config box
     *
     * @param string $value the error message
     *
     * @return bool
     */
    public function addConfigError($value = '')
    {
        return $this->addConfigBoxLine($value, 'error');
    }

    /**
     * Add accept (OK) message to config box
     *
     * @param string $value the OK message
     *
     * @return bool
     */
    public function addConfigAccept($value = '')
    {
        return $this->addConfigBoxLine($value, 'accept');
    }

    /**
     * Add warning to config box
     *
     * @param string $value the warning message
     *
     * @return bool
     */
    public function addConfigWarning($value = '')
    {
        return $this->addConfigBoxLine($value, 'warning');
    }

    /**
     * Check for installed module and version and to configuration box
     *
     * @param string  $moddir     module directory name
     * @param integer $minversion minimum acceptable module version (100 = V1.00)
     *
     * @return bool true if requested version of the module is available
     */
    public function addConfigModuleVersion($moddir, $minversion)
    {
        $return=false;
        $helper= \Xoops::getInstance()->getModuleHelper($moddir);
        if (is_object($helper) && is_object($helper->getModule())) {
            $mod_modversion=$helper->getModule()->getVar('version');
            $mod_version_f = $mod_modversion/100;
            $min_version_f = $minversion/100;
            $value = sprintf(
                \XoopsLocale::EF_MODULE_VERSION,
                strtoupper($moddir),
                $min_version_f,
                $mod_version_f
            );
            if ($mod_modversion>=$minversion) {
                $this->addConfigAccept($value);
                $return=true;
            } else {
                $this->addConfigError($value);
            }
        } else {
            $value = sprintf(
                \XoopsLocale::EF_MODULE_NOTFOUND,
                strtoupper($moddir),
                $minversion/100
            );
            $this->addConfigError($value);
        }

        return $return;
    }

    /**
     * Add Info box
     * Template in htdocs/modules/system/templates/admin/admin_infobox.tpl
     *
     * @param string $title title
     * @param string $type  type  will be used as icon name in title in template
     * @param string $extra extra added to the div element that surrounds the box
     *
     * @return bool
     */
    public function addInfoBox($title, $type = 'default', $extra = '')
    {
        $ret['title'] = $title;
        $ret['type'] = $type;
        $ret['extra'] = $extra;
        $this->itemInfoBox[] = $ret;
        return true;
    }

    /**
     * Add line to the info box
     *
     * @param string $text  line text
     * @param string $type  icon
     * @param string $color css class
     *
     * @return bool
     */
    public function addInfoBoxLine($text = '', $type = 'default', $color = 'inherit')
    {
        $ret = array();
        $ret['text'] = $text;
        $ret['color'] = $color;

        foreach (array_keys($this->itemInfoBox) as $i) {
            if ($this->itemInfoBox[$i]['type'] == $type) {
                $this->itemInfoBox[$i]['line'][] = $ret;
            }
        }
        return true;
    }

    /**
     * Add Item button
     *
     * @param string $title title
     * @param string $link  link
     * @param string $icon  icon
     * @param string $extra extra
     *
     * @return bool
     */
    public function addItemButton($title, $link, $icon = 'add', $extra = '')
    {
        $ret['title'] = $title;
        $ret['link'] = $link;
        $ret['icon'] = $icon;
        $ret['extra'] = $extra;
        $this->itemButton[] = $ret;
        return true;
    }

    /**
     * Add a tips
     *
     * @param string $text text
     *
     * @return void
     */
    public function addTips($text = '')
    {
        $this->tips = $text;
    }

    /**
     * Construct template path
     *
     * @param string $type type
     *
     * @return string
     */
    private function getTplPath($type = '')
    {
        return 'admin:' . $this->tplModule . '/' . $this->tplFile[$type];
    }

    /**
     * renderBreadcrumb
     *
     * @return string
     */
    public function renderBreadcrumb()
    {
        $xoops = \Xoops::getInstance();
        $xoops->tpl()->assign('xo_admin_breadcrumb', $this->bread);
        return $xoops->tpl()->fetch($this->getTplPath('bread'));
    }

    /**
     * displayBreadcrumb
     *
     * @return void
     */
    public function displayBreadcrumb()
    {
        echo $this->renderBreadcrumb();
    }

    /**
     * Render all items buttons
     *
     * @param string $position  position
     * @param string $delimiter delimiter
     *
     * @return string
     */
    public function renderButton($position = "floatright", $delimiter = "&nbsp;")
    {
        $xoops = \Xoops::getInstance();

        $xoops->tpl()->assign('xo_admin_buttons_position', $position);
        $xoops->tpl()->assign('xo_admin_buttons_delim', $delimiter);
        $xoops->tpl()->assign('xo_admin_buttons', $this->itemButton);
        return $xoops->tpl()->fetch($this->getTplPath('button'));
    }

    /**
     * displayButton
     *
     * @param string $position  position
     * @param string $delimiter delimiter
     *
     * @return void
     */
    public function displayButton($position = "floatright", $delimiter = "&nbsp;")
    {
        echo $this->renderButton($position, $delimiter);
    }

    /**
     * Render InfoBox
     *
     * @return string
     */
    public function renderInfoBox()
    {
        $xoops = \Xoops::getInstance();
        $xoops->tpl()->assign('xo_admin_box', $this->itemInfoBox);
        return $xoops->tpl()->fetch($this->getTplPath('infobox'));
    }

    /**
     * displayInfoBox
     *
     * @return void
     */
    public function displayInfoBox()
    {
        echo $this->renderInfoBox();
    }

    /**
     * Render index page for admin
     *
     * @return string
     */
    public function renderIndex()
    {
        $xoops = \Xoops::getInstance();
        $this->module->loadAdminMenu();
        foreach (array_keys($this->module->adminmenu) as $i) {
            if (\XoopsLoad::fileExists($xoops->path("media/xoops/images/icons/32/" . $this->module->adminmenu[$i]['icon']))) {
                $this->module->adminmenu[$i]['icon'] = $xoops->url("media/xoops/images/icons/32/" . $this->module->adminmenu[$i]['icon']);
            } elseif (\XoopsLoad::fileExists($xoops->path("modules/" . $xoops->module->dirname() . "/assets/icons/32/" . $this->module->adminmenu[$i]['icon']))) {
                $this->module->adminmenu[$i]['icon'] = $xoops->url("modules/" . $xoops->module->dirname() . "/assets/icons/32/" . $this->module->adminmenu[$i]['icon']);
            } else {
                $this->module->adminmenu[$i]['icon'] = $xoops->url("modules/" . $xoops->module->dirname() . "/icons/32/" . $this->module->adminmenu[$i]['icon']);
            }
            $xoops->tpl()->append('xo_admin_index_menu', $this->module->adminmenu[$i]);
        }
        if ($this->module->getInfo('help')) {
            $help = array();
            $help['link'] = '../system/help.php?mid=' . $this->module->getVar('mid', 's')
                . "&amp;" . $this->module->getInfo('help');
            $help['icon'] = $xoops->url("/media/xoops/images/icons/32/help.png");
            $help['title'] = \XoopsLocale::HELP;
            $xoops->tpl()->append('xo_admin_index_menu', $help);
        }
        $xoops->tpl()->assign('xo_admin_box', $this->itemInfoBox);

        // If you use a config label
        if ($this->module->getInfo('min_php') || $this->module->getInfo('min_xoops')
            || $this->module->getInfo('min_db') || !empty($this->itemConfigBoxLine)
        ) {
            // PHP version
            if ($this->module->getInfo('min_php')) {
                if (0 >= version_compare(phpversion(), $this->module->getInfo('min_php'))) {
                    $this->addConfigBoxLine(
                        sprintf(
                            \XoopsLocale::F_MINIMUM_PHP_VERSION_REQUIRED,
                            $this->module->getInfo('min_php'),
                            phpversion()
                        ),
                        'error'
                    );
                } else {
                    $this->addConfigBoxLine(
                        sprintf(
                            \XoopsLocale::F_MINIMUM_PHP_VERSION_REQUIRED,
                            $this->module->getInfo('min_php'),
                            phpversion()
                        ),
                        'accept'
                    );
                }
            }

            // Database version
            // Validate that the current system is acceptable for the module by checking a set of specific
            // databases and versions if specified in min_db key.
            //
            // An array of minimum versions keyed by platform can be optionally specified in xoops_version.php:
            //   $modversion['min_db'] = array('mysql' => '5.0.7', 'sqlite' => '3.0.8');
            //
            // If min_db is specified, and the current database is included in the array, the version will be
            // compared to the specified minimum. If the reported database version is equal or greater, an OK
            // message will be generated. If the version is lower, an error message will be issued.
            //
            // If min_db is specified, and the current platform is not included in the array, an error message
            // will indicate the module does not support the database current platform.
            //
            // If at all possible, modules should NOT specify this, as with Doctrine DBAL we should be able to
            // support many different databases, and thus portable modules are prefered. The supported databases
            // and versions are set by XoopsCore and Doctrine, and should not be restricted without reason.

            $dbarray = $this->module->getInfo('min_db');
            if ($dbarray !== false) {
                // In DBAL 4.x, Platform::getName() was removed. Derive name from class.
                $platform = $xoops->db()->getDatabasePlatform();
                $platformClass = get_class($platform);
                $dbCurrentPlatform = match (true) {
                    str_contains($platformClass, 'MySQL') || str_contains($platformClass, 'MariaDb') => 'mysql',
                    str_contains($platformClass, 'PostgreSQL') => 'postgresql',
                    str_contains($platformClass, 'SQLite') => 'sqlite',
                    default => strtolower(basename(str_replace('\\', '/', $platformClass))),
                };
                $pdo = $xoops->db()->getNativeConnection();
                $dbCurrentVersion = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
                if (isset($dbarray[$dbCurrentPlatform])) {
                    $dbRequiredVersion = $dbarray[$dbCurrentPlatform];
                    if (0 >= version_compare($dbCurrentVersion, $dbRequiredVersion)) {
                        $this->addConfigBoxLine(
                            sprintf(
                                strtoupper(\XoopsBaseConfig::get('db-type')) . ' '
                                . \XoopsLocale::F_MINIMUM_DATABASE_VERSION_REQUIRED,
                                $dbRequiredVersion,
                                $dbCurrentVersion
                            ),
                            'error'
                        );
                    } else {
                        $this->addConfigBoxLine(
                            sprintf(
                                strtoupper(\XoopsBaseConfig::get('db-type')) . ' ' . \XoopsLocale::F_MINIMUM_DATABASE_VERSION_REQUIRED,
                                $dbRequiredVersion,
                                $dbCurrentVersion
                            ),
                            'accept'
                        );
                    }
                } else {
                        $this->addConfigBoxLine(
                            sprintf(\XoopsLocale::EF_DATABASE_NOT_SUPPORTED, $dbCurrentPlatform),
                            'error'
                        );
                }
            }

            // xoops version
            if ($this->module->getInfo('min_xoops')) {
                $xoopsVersion = substr(\Xoops::VERSION, 6); // skip 'XOOPS ' prefix
                $xoopsCmpVersion = str_ireplace(['Alpha', 'Beta', 'RC'], ['0Alpha', '0Beta', '0RC'], $xoopsVersion);
                if (0 >= version_compare($xoopsCmpVersion, $this->module->getInfo('min_xoops'))) {
                    $this->addConfigBoxLine(
                        sprintf(
                            \XoopsLocale::F_MINIMUM_XOOPS_VERSION_REQUIRED,
                            $this->module->getInfo('min_xoops'),
                            $xoopsVersion
                        ),
                        'error'
                    );
                } else {
                    $this->addConfigBoxLine(
                        sprintf(
                            \XoopsLocale::F_MINIMUM_XOOPS_VERSION_REQUIRED,
                            $this->module->getInfo('min_xoops'),
                            $xoopsVersion
                        ),
                        'accept'
                    );
                }
            }
            $xoops->tpl()->assign('xo_admin_index_config', $this->itemConfigBoxLine);
        }
        return $xoops->tpl()->fetch($this->getTplPath('index'));
    }

    /**
     * displayIndex
     *
     * @return void
     */
    public function displayIndex()
    {
        echo $this->renderIndex();
    }

    /**
     * Render navigation to admin page
     *
     * @param string $menu current menu
     *
     * @return array
     */
    public function renderNavigation($menu = '')
    {
        $xoops = \Xoops::getInstance();
        $ret = array();

        $this->module->loadAdminMenu();
        foreach (array_keys($this->module->adminmenu) as $i) {
            if ($this->module->adminmenu[$i]['link'] == "admin/" . $menu) {
                if (\XoopsLoad::fileExists($xoops->path("media/xoops/images/icons/32/" . $this->module->adminmenu[$i]['icon']))) {
                    $this->module->adminmenu[$i]['icon'] = $xoops->url("media/xoops/images/icons/32/". $this->module->adminmenu[$i]['icon']);
                } elseif (\XoopsLoad::fileExists($xoops->path("modules/" . $xoops->module->dirname() . "/assets/icons/32/" . $this->module->adminmenu[$i]['icon']))) {
                    $this->module->adminmenu[$i]['icon'] = $xoops->url("modules/" . $xoops->module->dirname() . "/assets/icons/32/" . $this->module->adminmenu[$i]['icon']);
                } else {
                    $this->module->adminmenu[$i]['icon'] = $xoops->url("modules/" . $xoops->module->dirname() . "/icons/32/". $this->module->adminmenu[$i]['icon']);
                }
                $xoops->tpl()->assign('xo_sys_navigation', $this->module->adminmenu[$i]);
                $ret[] = $xoops->tpl()->fetch($this->getTplPath('nav'));
            }
        }
        return $ret;
    }

    /**
     * displayNavigation
     *
     * @param string $menu current menu
     *
     * @return void
     */
    public function displayNavigation($menu = '')
    {
        $items = $this->renderNavigation($menu);
        foreach ($items as $item) {
            echo $item;
        }
    }

    /**
     * Render tips to admin page
     *
     * @return string
     */
    public function renderTips()
    {
        $xoops = \Xoops::getInstance();
        $xoops->tpl()->assign('xo_admin_tips', $this->tips);
        return $xoops->tpl()->fetch($this->getTplPath('tips'));
    }

    /**
     * displayTips
     *
     * @return void
     */
    public function displayTips()
    {
        echo $this->renderTips();
    }

    /**
     * Render about page
     *
     * @param bool $logo_xoops show logo
     *
     * @return bool|mixed|string
     */
    public function renderAbout($logo_xoops = true)
    {
        $xoops = \Xoops::getInstance();

        $date = explode('/', $this->module->getInfo('release_date'));
        $author = explode(',', $this->module->getInfo('author'));
        $nickname = explode(',', $this->module->getInfo('nickname'));
        $release_date = \XoopsLocale::formatTimestamp(mktime(0, 0, 0, $date[1], $date[2], $date[0]), 's');

        $author_list = '';
        foreach (array_keys($author) as $i) {
            $author_list .= $author[$i];
            if (isset($nickname[$i]) && $nickname[$i] != '') {
                $author_list .= " (" . $nickname[$i] . "), ";
            } else {
                $author_list .= ", ";
            }
        }
        $changelog = '';
        $language = $xoops->getConfig('locale');
        if (!is_file(
            \XoopsBaseConfig::get('root-path') . "/modules/" . $this->module->getVar("dirname")
            . "/locale/" . $language . "/changelog.txt"
        )) {
            $language = 'en_US';
        }
        $file = \XoopsBaseConfig::get('root-path') . "/modules/" . $this->module->getVar("dirname")
            . "/locale/" . $language . "/changelog.txt";
        if (is_readable($file)) {
            $changelog = mb_convert_encoding(implode("<br />", file($file)), 'UTF-8', 'ISO-8859-1') . "\n";
        } else {
            $file = \XoopsBaseConfig::get('root-path') . "/modules/" . $this->module->getVar("dirname") . "/docs/changelog.txt";
            if (is_readable($file)) {
                $changelog = mb_convert_encoding(implode("<br />", file($file)), 'UTF-8', 'ISO-8859-1') . "\n";
            }
        }
        $author_list = substr($author_list, 0, -2);

        $this->module->setInfo('release_date', $release_date);
        $this->module->setInfo('author_list', $author_list);
        if (is_array($this->module->getInfo('paypal'))) {
            $this->module->setInfo('paypal', $this->module->getInfo('paypal'));
        }
        $this->module->setInfo('changelog', $changelog);
        $xoops->tpl()->assign('module', $this->module);

        $this->addInfoBox(\XoopsLocale::MODULE_INFORMATION, 'info', 'id="xo-about"');
        $this->addInfoBoxLine(
            \XoopsLocale::C_DESCRIPTION . ' ' . $this->module->getInfo("description"),
            'info'
        );
        $this->addInfoBoxLine(
            \XoopsLocale::C_UPDATE_DATE . ' <span class="bold">'
            . \XoopsLocale::formatTimestamp($this->module->getVar("last_update"), "m")
            . '</span>',
            'info'
        );
        $this->addInfoBoxLine(
            \XoopsLocale::C_WEBSITE . ' <a class="xo-tooltip" href="http://'
            . $this->module->getInfo("module_website_url")
            . '" rel="external" title="'
            . $this->module->getInfo("module_website_name") . ' - '
            . $this->module->getInfo("module_website_url") . '">'
            . $this->module->getInfo("module_website_name") . '</a>',
            'info'
        );

        $xoops->tpl()->assign('xoops_logo', $logo_xoops);
        $xoops->tpl()->assign('xo_admin_box', $this->itemInfoBox);
        return $xoops->tpl()->fetch($this->getTplPath('about'));
    }

    /**
     * displayAbout
     *
     * @param bool $logo_xoops display logo
     *
     * @return void
     */
    public function displayAbout($logo_xoops = true)
    {
        echo $this->renderAbout($logo_xoops);
    }
}
