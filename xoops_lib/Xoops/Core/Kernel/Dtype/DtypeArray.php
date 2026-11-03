<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xoops\Core\Kernel\Dtype;

use Xoops\Core\Kernel\Dtype;
use Xoops\Core\Kernel\XoopsObject;

/**
 * DtypeArray
 *
 * @category  Xoops\Core\Kernel\Dtype\DtypeArray
 * @package   Xoops\Core\Kernel
 * @author    trabis <lusopoemas@gmail.com>
 * @copyright 2011-2016 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class DtypeArray extends DtypeAbstract
{
    /**
     * getVar get variable prepared according to format
     *
     * @param XoopsObject $obj    object containing variable
     * @param string      $key    name of variable
     * @param string      $format Dtype::FORMAT_* constant indicating desired formatting
     *
     * @return mixed
     */
    public function getVar(XoopsObject $obj, $key, $format)
    {
        $value = $obj->vars[$key]['value'];
        switch (strtolower($format)) {
            case 'n':
            case Dtype::FORMAT_NONE:
                return $value;
            default:
                if (!is_array($value)) {
                    if ($value != '') {
                        $value = unserialize($value, ['allowed_classes' => false]);
                    }
                    $value = is_array($value) ? $value : array();
                }
                return $value;
        }
    }

    /**
     * cleanVar prepare variable for persistence
     *
     * @param XoopsObject $obj object containing variable
     * @param string      $key name of variable
     *
     * @return string
     */
    public function cleanVar(XoopsObject $obj, $key)
    {
        $value = $obj->vars[$key]['value'];
        $value = (array)$value;
        // TODO: Not encoding safe, should try base64_encode -- phppp
        $value = serialize($value);
        return $value;
    }
}
