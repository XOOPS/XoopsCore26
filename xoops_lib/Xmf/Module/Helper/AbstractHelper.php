<?php

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Module\Helper;

/**
 * Xmf\Module\Helper\AbstractHelper defines the basis for various
 * helpers that simplify routine module tasks.
 *
 * @category  Xmf\Module\Helper\AbstractHelper
 * @package   Xmf
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
abstract class AbstractHelper
{
    /**
     * @var string module directory name
     */
    protected $dirname;

    /**
     * @var XoopsModule
     */
    protected $module;

    /**
     * @var bool true if debug is enabled
     */
    protected $debug;

    /**
     * Instantiate a XoopsModule object for the helper to use.
     * The module is determined as follows:
     * - if null is passed, use the current module
     * - if a string is passed, use as dirname to load
     *
     * @param string|null $dirname dirname
     */
    public function __construct($dirname = null)
    {
        $this->module = null;

        if (class_exists('Xoops', false)) {
            $xoops = \Xoops::getInstance();
        }
        if (empty($dirname)) {
            // nothing specified, use current module
            if (isset($xoops)) {
                $this->module = $xoops->module;
            } else {
                $this->module = $GLOBALS['xoopsModule'];
            }
        } else {
            // assume dirname specified, try to get a module object
            if (isset($xoops)) {
                $moduleHandler = $xoops->getHandlerModule();
            } else {
                /** @var \XoopsModuleHandler $moduleHandler */
                $moduleHandler = xoops_getHandler('module');
            }
            $this->module = $moduleHandler->getByDirname($dirname);
        }
        if (is_object($this->module)) {
            $this->dirname = $this->module->getVar('dirname');
            $this->init();
        }
    }

    /**
     * init() is called once/if __construct has a module object.
     * $this->module will have a module object that any further
     * initialization can use.
     *
     * @return void
     */
    abstract public function init();

    /**
     * Return the dirname for this helper
     *
     * @return string|null a dirname
     */
    public function dirname()
    {
        return $this->dirname;
    }

    /**
     * Set debug option on or off
     *
     * @param bool $bool true to turn on debug logging, false for off
     *
     * @return void
     */
    public function setDebug($bool = true)
    {
        $this->debug = (bool) $bool;
    }

    /**
     * Add a message to the module log
     *
     * @param mixed $log log item, can be message or variable
     *
     * @return void
     */
    public function addLog($log)
    {
        if ($this->debug) {
            $message = $this->serializeForHelperLog($log);
            if (class_exists('Xoops', false)) {
                \Xoops::getInstance()->logger()->debug($message, array('channel' => 'Extra'));
            } elseif (is_object($GLOBALS['xoopsLogger'])) {
                $GLOBALS['xoopsLogger']->addExtra(get_called_class(), $message);
            }
        }
    }

    /**
     * Serialize an arbitrary value to string. Intended for data being addLog()ed
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function serializeForHelperLog($value)
    {
        if (is_resource($value)) {
            $value = '(resource:' . get_resource_type($value) . ')';
        }
        if (!is_string($value)) {
            $value = json_encode($value);
        }
        return (string) $value;
    }
}
