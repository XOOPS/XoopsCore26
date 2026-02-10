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
use Xoops\Core\Kernel\Dtype;
use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;

/**
 * SmiliesSmiley object
 *
 * @category  Modules\Smilies
 * @package   Modules
 * @author    Unknown <nobody@localhost.local>
 * @copyright 2013-2015 The XOOPS Project https://github.com/XOOPS/XoopsCore
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class SmiliesSmiley extends XoopsObject
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initVar('smiley_id', Dtype::TYPE_INTEGER, null, false);
        $this->initVar('smiley_code', Dtype::TYPE_TEXT_BOX, null, true, 100);
        $this->initVar('smiley_url', Dtype::TYPE_OTHER, null, false, 30);
        $this->initVar('smiley_emotion', Dtype::TYPE_TEXT_BOX, null, true, 100);
        $this->initVar('smiley_display', Dtype::TYPE_INTEGER, 1, false);
    }
}

/**
 * SmiliesSmileyHandler
 *
 * @category  Modules\Smilies
 * @package   Modules
 * @author    Unknown <nobody@localhost.local>
 * @copyright 2013-2015 The XOOPS Project https://github.com/XOOPS/XoopsCore
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class SmiliesSmileyHandler extends XoopsPersistableObjectHandler
{
    /**
     * Constructor
     *
     * @param Connection|null $db {@link Connection}
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'smilies', 'SmiliesSmiley', 'smiley_id', 'smiley_emotion');
    }

    /**
     * get array of smilies
     *
     * @param int  $start          offset of first row to return
     * @param int  $limit          max number of row to return
     * @param bool $fetchAsObjects fetch as objects
     *
     * @return array
     */
    public function getSmilies($start = 0, $limit = 0, $fetchAsObjects = true)
    {
        $criteria = new CriteriaCompo();
        $criteria->setSort('smiley_id');
        $criteria->setOrder('ASC');
        $criteria->setStart($start);
        $criteria->setLimit($limit);
        return $this->getAll($criteria, false, $fetchAsObjects);
    }

    /**
     * get array of active smilies
     *
     * @param bool $fetchAsObjects fetch as objects
     *
     * @return array
     */
    public function getActiveSmilies($fetchAsObjects = true)
    {
        $criteria = new CriteriaCompo(new Criteria('smiley_display', 1));
        $criteria->setSort('smiley_id');
        $criteria->setOrder('ASC');
        $results = $this->getAll($criteria, false, $fetchAsObjects);
        $uploadPath = \XoopsBaseConfig::get('uploads-url') . '/';
        foreach ($results as $i => $smile) {
            $results[$i]['smiley_url'] = $uploadPath . $smile['smiley_url'];
        }

        return $results;
    }
}
