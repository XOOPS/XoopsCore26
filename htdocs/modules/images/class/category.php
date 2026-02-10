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
use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;
use Xoops\Core\Kernel\CriteriaElement;
use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;

/**
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Images
 * @author
 * @version         $Id$
 */

class ImagesCategory extends XoopsObject
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initVar('imgcat_id', XOBJ_DTYPE_INT, 0, false, 5);
        $this->initVar('imgcat_name', XOBJ_DTYPE_TXTBOX, '', true, 100);
        $this->initVar('imgcat_maxsize', XOBJ_DTYPE_INT, 100000, false, 8);
        $this->initVar('imgcat_maxwidth', XOBJ_DTYPE_INT, 128, false, 3);
        $this->initVar('imgcat_maxheight', XOBJ_DTYPE_INT, 128, false, 3);
        $this->initVar('imgcat_display', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('imgcat_weight', XOBJ_DTYPE_INT, 0, false, 3);
        $this->initVar('imgcat_type', XOBJ_DTYPE_TXTBOX, '', true, 1);
        $this->initVar('imgcat_storetype', XOBJ_DTYPE_TXTBOX, 'file', true, 5);
    }
}

class ImagesCategoryHandler extends XoopsPersistableObjectHandler
{
    /**
     * Constructor
     *
     * @param Connection|null $db {@link Xoops\Core\Database\Connection}
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'imagecategory', 'ImagesCategory', 'imgcat_id', 'imgcat_name');
    }

    /**
     * @param CriteriaElement|null $criteria
     * @param bool $id_as_key
     *
     * @return array
     */
    public function getPermittedObjects($criteria = null, $start = 0, $limit = 0, $id_as_key = false, $asobject = true)
    {
        $this->table_link = $this->db2->prefix('system_permission');

        if (isset($criteria)) {
            $criteria = new CriteriaCompo($criteria);
        } else {
            $criteria = new CriteriaCompo();
        }
        $criteria->setSort('o.imgcat_weight, o.imgcat_id');
        $criteria->setOrder('ASC');
        $criteria->setStart($start);
        $criteria->setLimit($limit);

        return parent::getByLink($criteria, null, $asobject, 'gperm_itemid', 'imgcat_id');
    }

    /**
     * Get a list of imagesCategories
     *
     * @param array $groups
     * @param string $perm
     * @param null $display
     * @param null $storetype
     *
     * @return array Array of {@link ImagesImage} objects
     */
    public function getListByPermission($groups = array(), $perm = 'imgcat_read', $display = null, $storetype = null)
    {
        $xoops = Xoops::getInstance();
        $criteria = new CriteriaCompo();
        if (is_array($groups) && !empty($groups)) {
            $criteriaTray = new CriteriaCompo();
            foreach ($groups as $gid) {
                $criteriaTray->add(new Criteria('gperm_groupid', $gid), 'OR');
            }
            $criteria->add($criteriaTray);
            if ($perm === 'imgcat_read' || $perm === 'imgcat_write') {
                $criteria->add(new Criteria('gperm_name', $perm));
                $mid = $xoops->getModuleByDirname('images')->getVar('mid');
                $criteria->add(new Criteria('gperm_modid', $mid));
            }
        }
        if (isset($display)) {
            $criteria->add(new Criteria('imgcat_display', (int)($display)));
        }
        if (isset($storetype)) {
            $criteria->add(new Criteria('imgcat_storetype', $storetype));
        }
        $categories = $this->getPermittedObjects($criteria, 0, 0, true);
        $ret = array();
        foreach (array_keys($categories) as $i) {
            $ret[$i] = $categories[$i]->getVar('imgcat_name');
        }
        return $ret;
    }
}
