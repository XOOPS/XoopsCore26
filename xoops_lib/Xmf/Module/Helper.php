<?php

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Module;

use Xmf\Module\Helper\GenericHelper;

/**
 * Helper gets an instance of module helper for the specified module.
 * The helper is defined by the Xoops 2.6 Xoops\Module\Helper\HelperAbstract
 * and in pre 2.6 systems we mimic that definition with using an
 * instance of Xmf\Module\GenericHelper.
 *
 * @category  Xmf\Module\Helper
 * @package   Xmf
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class Helper extends GenericHelper
{
    /**
     * Get an instance of a module helper for the module identified by $dirname
     *
     * @param string $dirname module directory
     *
     * @return \Xmf\Module\Helper|\Xoops\Module\Helper|false a Helper object of false on error
     */
    public static function getHelper($dirname = 'system')
    {
        static $instance = array();

        if (!isset($instance[$dirname])) {
            $instance[$dirname] = false;

            // if this is a 2.6 system turn everything over to the core
            if (class_exists('Xoops', false)) {
                $instance[$dirname] = \Xoops\Module\Helper::getHelper($dirname);
            } else {
                // otherwise, get a GenericHelper instance for dirname
                if (xoops_isActiveModule($dirname)) {
                    $instance[$dirname] = new static($dirname);
                }
            }
        }

        return $instance[$dirname];
    }
}
