<?php
/**
 * XOOPS Kernel Class
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         kernel
 * @since           2.0.0
 * @author          Kazumi Ono (AKA onokazu) http://www.myweb.ne.jp/, http://jp.xoops.org/
 * @version         $Id$
 */

namespace Xoops\Core\Kernel\Handlers;

use Xoops\Core\Kernel\Dtype;
use Xoops\Core\Kernel\XoopsObject;

/**
 * deprecated
 * Config type
 */
define('XOOPS_CONF', 1);
define('XOOPS_CONF_USER', 2);
define('XOOPS_CONF_METAFOOTER', 3);
define('XOOPS_CONF_CENSOR', 4);
define('XOOPS_CONF_SEARCH', 5);
define('XOOPS_CONF_MAILER', 6);
define('XOOPS_CONF_AUTH', 7);

/**
 * Configuration Item
 *
 * @category  Xoops\Core\Kernel\Handlers\XoopsConfigItem
 * @package   Xoops\Core\Kernel
 * @author    Kazumi Ono    <onokazu@xoops.org>
 * @copyright 2000-2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class XoopsConfigItem extends XoopsObject
{

    /**
     * Config options
     *
     * @var    array
     */
    private $configurationOptions = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initVar('conf_id', Dtype::TYPE_INTEGER, null, false);
        $this->initVar('conf_modid', Dtype::TYPE_INTEGER, null, false);
        $this->initVar('conf_catid', Dtype::TYPE_INTEGER, null, false);
        $this->initVar('conf_name', Dtype::TYPE_OTHER);
        $this->initVar('conf_title', Dtype::TYPE_TEXT_BOX);
        $this->initVar('conf_value', Dtype::TYPE_TEXT_AREA);
        $this->initVar('conf_desc', Dtype::TYPE_OTHER);
        $this->initVar('conf_formtype', Dtype::TYPE_OTHER);
        $this->initVar('conf_valuetype', Dtype::TYPE_OTHER);
        $this->initVar('conf_order', Dtype::TYPE_INTEGER);
    }

    /**
     * getter
     *
     * @param string $format Dtype::FORMAT_xxxx constant
     *
     * @return mixed
     */
    public function id($format = Dtype::FORMAT_NONE)
    {
        return $this->getVar('conf_id', $format);
    }

    /**
     * getter
     *
     * @param string $format Dtype::FORMAT_xxxx constant
     *
     * @return mixed
     */
    public function conf_id($format = '')
    {
        return $this->getVar('conf_id', $format);
    }

    /**
     * getter
     *
     * @param string $format Dtype::FORMAT_xxxx constant
     *
     * @return mixed
     */
    public function conf_modid($format = '')
    {
        return $this->getVar('conf_modid', $format);
    }

    /**
     * getter
     *
     * @param string $format Dtype::FORMAT_xxxx constant
     *
     * @return mixed
     */
    public function conf_catid($format = '')
    {
        return $this->getVar('conf_catid', $format);
    }

    /**
     * getter
     *
     * @param string $format Dtype::FORMAT_xxxx constant
     *
     * @return mixed
     */
    public function conf_name($format = '')
    {
        return $this->getVar('conf_name', $format);
    }

    /**
     * getter
     *
     * @param string $format Dtype::FORMAT_xxxx constant
     *
     * @return mixed
     */
    public function conf_title($format = '')
    {
        return $this->getVar('conf_title', $format);
    }

    /**
     * getter
     *
     * @param string $format Dtype::FORMAT_xxxx constant
     *
     * @return mixed
     */
    public function conf_value($format = '')
    {
        return $this->getVar('conf_value', $format);
    }

    /**
     * getter
     *
     * @param string $format Dtype::FORMAT_xxxx constant
     *
     * @return mixed
     */
    public function conf_desc($format = '')
    {
        return $this->getVar('conf_desc', $format);
    }

    /**
     * getter
     *
     * @param string $format Dtype::FORMAT_xxxx constant
     *
     * @return mixed
     */
    public function conf_formtype($format = '')
    {
        return $this->getVar('conf_formtype', $format);
    }

    /**
     * getter
     *
     * @param string $format Dtype::FORMAT_xxxx constant
     *
     * @return mixed
     */
    public function conf_valuetype($format = '')
    {
        return $this->getVar('conf_valuetype', $format);
    }

    /**
     * getter
     *
     * @param string $format Dtype::FORMAT_xxxx constant
     *
     * @return mixed
     */
    public function conf_order($format = '')
    {
        return $this->getVar('conf_order', $format);
    }

    /**
     * Get a config value in a format ready for output
     *
     * @return    string
     */
    public function getConfValueForOutput()
    {
        switch ($this->getVar('conf_valuetype')) {
            case 'int':
                return (int)($this->getVar('conf_value', 'n'));
                break;
            case 'array':
                $value = @unserialize($this->getVar('conf_value', 'n'), ['allowed_classes' => false]);
                return $value ? $value : array();
            case 'float':
                $value = $this->getVar('conf_value', 'n');
                return (float)$value;
                break;
            case 'textarea':
                return $this->getVar('conf_value');
            default:
                return $this->getVar('conf_value', 'n');
                break;
        }
    }

    /**
     * Set a config value
     *
     * @param mixed $value       Value by reference
     *
     * @return void
     */
    public function setConfValueForInput(&$value)
    {
        switch ($this->getVar('conf_valuetype')) {
            case 'array':
                if (!is_array($value)) {
                    $value = explode('|', trim($value));
                }
                $this->setVar('conf_value', serialize($value));
                break;
            case 'text':
                $this->setVar('conf_value', trim($value));
                break;
            default:
                $this->setVar('conf_value', $value);
                break;
        }
    }

    /**
     * Assign one or more configuration options
     *
     * @param XoopsConfigOption|XoopsConfigOption[] $option configuration option(s)
     *
     * @return void
     */
    public function setConfOptions($option)
    {
        if (is_array($option)) {
            $count = count($option);
            for ($i = 0; $i < $count; ++$i) {
                $this->setConfOptions($option[$i]);
            }
        } else {
            if (is_object($option)) {
                $this->configurationOptions[] = $option;
            }
        }
    }

    /**
     * Get the configuration options for this item
     *
     * @return XoopsConfigOption[]
     */
    public function getConfOptions()
    {
        return $this->configurationOptions;
    }

    /**
     * Clear options from this item
     *
     * @return void
     **/
    public function clearConfOptions()
    {
        $this->configurationOptions = array();
    }
}
