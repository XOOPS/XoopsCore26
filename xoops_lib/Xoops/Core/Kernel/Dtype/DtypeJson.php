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

use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Kernel\Dtype;

/**
 * DtypeJson
 *
 * @category  Xoops\Core\Kernel\Dtype\DtypeJson
 * @package   Xoops\Core\Kernel
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class DtypeJson extends DtypeAbstract
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
            case Dtype::FORMAT_NONE:
            case 'n':
                break;
            default:
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value = (false === $decoded) ? null : $decoded;
                }
                break;
        }
        return $value;
    }

    /**
     * cleanVar prepare variable for persistence
     *
     * @param XoopsObject $obj object containing variable
     * @param string      $key name of variable
     *
     * @return string|null
     */
    public function cleanVar(XoopsObject $obj, $key)
    {
        $value = $obj->vars[$key]['value'];
        $value = ($value===null || $value==='' || $value===false) ? null : $value;
        if ($value!==null && !is_string($value)) {
            $value = json_encode($value, JSON_FORCE_OBJECT);
            if ($value===false) {
                \Xoops::getInstance()->logger()->warning(
                    sprintf('Failed to encode to JSON - %s', json_last_error_msg())
                );
                $value = null;
            }
        } elseif ($value!==null && null === json_decode($value, true)) {
            $value = json_encode($value, JSON_FORCE_OBJECT);
            if ($value===false) {
                \Xoops::getInstance()->logger()->warning(
                    sprintf('Failed to encode to JSON - %s', json_last_error_msg())
                );
                $value = null;
            }
        }
        return $value;
    }
}
