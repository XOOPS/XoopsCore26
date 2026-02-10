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
use Xoops\Core\FixedGroups;
use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;

/**
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Menus
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id$
 */

class MenusMenu extends XoopsObject
{
    /**
     * constructor
     */
    public function __construct()
    {
        $this->initVar('id', XOBJ_DTYPE_INT);
        $this->initVar('pid', XOBJ_DTYPE_INT);
        $this->initVar('mid', XOBJ_DTYPE_INT);
        $this->initVar('title', XOBJ_DTYPE_TXTBOX, '');
        $this->initVar('alt_title', XOBJ_DTYPE_TXTBOX, '');
        $this->initVar('visible', XOBJ_DTYPE_INT, 1);
        $this->initVar('link', XOBJ_DTYPE_TXTBOX);
        $this->initVar('weight', XOBJ_DTYPE_INT, 255);
        $this->initVar('target', XOBJ_DTYPE_TXTBOX, '_self');
        $this->initVar('groups', XOBJ_DTYPE_ARRAY, serialize(array(FixedGroups::ANONYMOUS, FixedGroups::USERS)));
        $this->initVar('hooks', XOBJ_DTYPE_ARRAY, serialize(array()));
        $this->initVar('image', XOBJ_DTYPE_TXTBOX);
        $this->initVar('css', XOBJ_DTYPE_TXTBOX);
    }
}

class MenusMenuHandler extends XoopsPersistableObjectHandler
{
    /**
     * @param Connection $db
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'menus_menu', 'MenusMenu', 'id', 'title');
    }

    /**
     * @param MenusMenu $obj
     */
    public function updateWeights(MenusMenu $obj)
    {
        $sql = "UPDATE " . $this->table
        . " SET weight = weight+1"
        . " WHERE weight >= " . $obj->getVar('weight')
        . " AND id <> " . $obj->getVar('id')
        /*. " AND pid = " . $obj->getVar('pid')*/
        . " AND mid = " . $obj->getVar('mid')
        ;
        $originalForce = $this->db2->getForce();
        $this->db2->setForce(true);
        $this->db2->query($sql);

        $sql = "SELECT id FROM " . $this->table
        . " WHERE mid = " . $obj->getVar('mid')
        /*. " AND pid = " . $obj->getVar('pid')*/
        . " ORDER BY weight ASC"
        ;
        $result = $this->db2->query($sql);
        $i = 1;  //lets start at 1 please!
        while (false !== (list($id) = $result->fetchNumeric())) {
            $sql = "UPDATE " . $this->table
            . " SET weight = {$i}"
            . " WHERE id = {$id}"
            ;
            $this->db2->query($sql);
            ++$i;
        }
        $this->db2->setForce($originalForce);
    }
}
