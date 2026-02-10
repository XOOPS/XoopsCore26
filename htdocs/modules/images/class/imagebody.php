<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\Database\Connection;
use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;

/**
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Images
 * @author
 * @version         $Id$
 */

class ImagesImagebody extends XoopsObject
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initVar('image_id', XOBJ_DTYPE_INT, null, false, 8);
        $this->initVar('image_body', XOBJ_DTYPE_SOURCE, null, true);
    }
}

class ImagesImagebodyHandler extends XoopsPersistableObjectHandler
{
    /**
     * Constructor
     *
     * @param Connection|null $db {@link Connection}
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'imagebody', 'ImagesImagebody', 'image_id', 'image_body');
    }
}
