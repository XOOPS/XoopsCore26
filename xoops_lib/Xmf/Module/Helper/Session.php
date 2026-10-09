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
 * Manage session variables for a module. Session variable will be
 * prefixed with the module name to separate it from variables set
 * by other modules or system functions. All data is serialized, so
 * any arbitrary data (i.e. array) can be stored.
 *
 * @category  Xmf\Module\Helper\Session
 * @package   Xmf
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class Session extends AbstractHelper
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * Initialize parent::__construct calls this after verifying module object.
     *
     * @return void
     */
    public function init()
    {
        $this->prefix = $this->module->getVar('dirname') . '_';
    }

    /**
     * Add our module prefix to a name
     *
     * @param string $name name to prefix
     *
     * @return string module prefixed name
     */
    protected function prefix($name)
    {
        $prefixedName = $this->prefix . $name;

        return $prefixedName;
    }

    /**
     * Sets a named session variable respecting our module prefix
     *
     * @param string $name  name of variable
     * @param mixed  $value value of variable
     *
     * @return void
     */
    public function set($name, $value)
    {
        $prefixedName = $this->prefix($name);
        $_SESSION[$prefixedName] = serialize($value);
    }

    /**
     * Fetch a named session variable respecting our module prefix
     *
     * @param string $name    name of variable
     * @param mixed  $default default value to return if config $name is not set
     *
     * @return mixed  $value value of session variable or false if not set
     */
    public function get($name, $default = false)
    {
        $prefixedName = $this->prefix($name);
        if (isset($_SESSION[$prefixedName])) {
            return unserialize($_SESSION[$prefixedName], ['allowed_classes' => false]);
        } else {
            return $default;
        }
    }

    /**
     * Deletes a named session variable respecting our module prefix
     *
     * @param string $name name of variable
     *
     * @return void
     */
    public function del($name)
    {
        $prefixedName = $this->prefix($name);
        $_SESSION[$prefixedName] = null;
        unset($_SESSION[$prefixedName]);
    }

    /**
     * Delete all session variable starting with our module prefix
     *
     * @return void
     */
    public function destroy()
    {
        foreach ($_SESSION as $key => $value) {
            if (0 == substr_compare($key, $this->prefix, 0, strlen($this->prefix))) {
                $_SESSION[$key] = null;
                unset($_SESSION[$key]);
            }
        }
    }
}
