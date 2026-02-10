<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core\Kernel\Handlers;

use Xoops\Core\Database\Connection;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;

/**
 * XOOPS Kernel Class
 *
 * @category  Xoops\Core\Kernel\Handlers\XoopsBlockModuleLinkHandler
 * @package   Xoops\Core\Kernel
 * @author    Gregory Mage (AKA Mage)
 * @author    trabis <lusopoemas@gmail.com>
 * @copyright 2000-2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class XoopsBlockModuleLinkHandler extends XoopsPersistableObjectHandler
{
    /**
     * __construct
     *
     * @param null|Connection $db database
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct(
            $db,
            'system_blockmodule',
            '\Xoops\Core\Kernel\Handlers\XoopsBlockModuleLink',
            'block_id',
            'module_id'
        );
    }
}
