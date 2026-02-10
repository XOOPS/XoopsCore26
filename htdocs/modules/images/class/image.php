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

class ImagesImage extends XoopsObject
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initVar('image_id', XOBJ_DTYPE_INT, null, false, 8);
        $this->initVar('image_name', XOBJ_DTYPE_TXTBOX, '', true, 30);
        $this->initVar('image_nicename', XOBJ_DTYPE_TXTBOX, '', true, 255);
        $this->initVar('image_mimetype', XOBJ_DTYPE_TXTBOX, '', true, 30);
        $this->initVar('image_created', XOBJ_DTYPE_INT, time(), false, 10);
        $this->initVar('image_display', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('image_weight', XOBJ_DTYPE_INT, 0, false, 5);
        $this->initVar('imgcat_id', XOBJ_DTYPE_INT, 0, false, 5);
    }
}

class ImagesImage_Body extends XoopsObject
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initVar('image_id', XOBJ_DTYPE_INT, null, false, 8);
        $this->initVar('image_name', XOBJ_DTYPE_TXTBOX, '', true, 30);
        $this->initVar('image_nicename', XOBJ_DTYPE_TXTBOX, '', true, 255);
        $this->initVar('image_mimetype', XOBJ_DTYPE_TXTBOX, '', true, 30);
        $this->initVar('image_created', XOBJ_DTYPE_INT, time(), false, 10);
        $this->initVar('image_display', XOBJ_DTYPE_INT, 1, false, 1);
        $this->initVar('image_weight', XOBJ_DTYPE_INT, 0, false, 5);
        $this->initVar('imgcat_id', XOBJ_DTYPE_INT, 0, false, 5);

        $this->initVar('image_body', XOBJ_DTYPE_SOURCE, null, true);
    }
}

class ImagesImageHandler extends XoopsPersistableObjectHandler
{
    /**
     * Constructor
     *
     * @param Connection|null $db {@link Connection}
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'image', 'ImagesImage', 'image_id', 'image_name');
    }

    public function getById($image_id, $asobject = true)
    {
        $this->table_link = $this->db2->prefix('imagebody');
        $this->className = 'ImagesImage_Body';


        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('o.image_id', $image_id));

        return parent::getByLink($criteria, null, $asobject, 'image_id', 'image_id');
    }

    public function getByCategory($imgcat_id, $start = 0, $limit = 0, $asobject = true)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('imgcat_id', $imgcat_id));
        $criteria->setSort('image_weight');
        $criteria->setOrder('ASC');
        $criteria->setStart($start);
        $criteria->setLimit($limit);

        return parent::getObjects($criteria, null, $asobject);
    }

    public function countByCategory($imgcat_id)
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('imgcat_id', $imgcat_id));

        return parent::getCount($criteria);
    }
}
