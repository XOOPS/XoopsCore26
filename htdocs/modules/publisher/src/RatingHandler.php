<?php

namespace XoopsModules\Publisher;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

use Xoops\Core\Database\Connection;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;

/**
 *  Publisher class
 *
 * @copyright       The XUUPS Project http://sourceforge.net/projects/xuups/
 * @license         GNU GPL V2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Publisher
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id$
 */
require_once \dirname(__DIR__) . '/include/common.php';

/**
 * Class RatingHandler
 * @package XoopsModules\Publisher
 */
class RatingHandler extends XoopsPersistableObjectHandler
{
    /**
     * @param \Xoops\Core\Database\Connection $db
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'publisher_rating', Rating::class, 'ratingid', 'itemid');
    }
}
