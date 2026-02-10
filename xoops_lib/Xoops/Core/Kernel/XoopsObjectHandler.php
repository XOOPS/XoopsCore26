<?php
/**
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xoops\Core\Kernel;

use Xoops\Core\Database\Connection;

/**
 * XOOPS Kernel Object Handler
 *
 * This class is an abstract class of handler classes that are responsible for providing
 * data access mechanisms to the data source of its corresponding data objects
 *
 * @category  Xoops\Core\Kernel\XoopsObjectHandler
 * @package   Xoops\Core\Kernel
 * @author    Kazumi Ono <onokazu@xoops.org>
 * @copyright 2000-2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 * @since     2.0.0
 */
abstract class XoopsObjectHandler
{
    /**
     * @var Connection
     */
    public $db2;

    /**
     * called from child classes only
     *
     * @param Connection $db database connection
     */
    protected function __construct(?Connection $db = null)
    {
        if (!($db instanceof Connection)) {
            $db = \Xoops::getInstance()->db();
        }
        $this->db2 = $db;
    }

    /**
     * creates a new object
     *
     * @return XoopsObject
     */
    public function create()
    {
    }

    /**
     * gets a value object
     *
     * @param int $int_id id
     *
     * @return mixed
     */
    public function get($int_id)
    {
    }

    /**
     * insert/update object
     *
     * @param XoopsObject $object object to insert
     * @param bool        $force  use force
     *
     * @return mixed
     */
    public function insert(XoopsObject $object, $force = true)
    {
    }

    /**
     * delete object from database
     *
     * @param XoopsObject $object object to delete
     * @param bool        $force  use force
     *
     * @return boolean|null FALSE if failed.
     */
    public function delete(XoopsObject $object, $force = true)
    {
    }
}
