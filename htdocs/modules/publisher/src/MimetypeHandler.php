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
 * @package   Publisher
 * @since     1.0
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Nazar Aziz <nazar@panthersoftware.com>
 * @copyright 2014 XOOPS Project (http://xoops.org)
 * @license   GNU GPL V2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 */
class MimetypeHandler extends XoopsPersistableObjectHandler
{
    /**
     * PublisherMimetypeHandler
     *
     * @param null|Connection $db database connection
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'publisher_mimetypes', 'PublisherMimetype', 'mime_id', 'mime_ext');
    }
}
