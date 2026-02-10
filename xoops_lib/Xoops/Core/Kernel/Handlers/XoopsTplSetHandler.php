<?php
/**
 * XOOPS kernel class
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

use Xoops\Core\Database\Connection;
use Xoops\Core\Kernel\CriteriaElement;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;
use Doctrine\DBAL\ParameterType;

/**
 * XOOPS tplset handler class.
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS tplset class objects.
 *
 * @category  Xoops\Core\Kernel\Handlers\XoopsTplSetHandler
 * @package   Xoops\Core\Kernel
 * @author    Kazumi Ono <onokazu@xoops.org>
 * @copyright 2000-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class XoopsTplSetHandler extends XoopsPersistableObjectHandler
{

    /**
     * Constructor
     *
     * @param Connection|null $db database
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct(
            $db,
            'system_tplset',
            '\Xoops\Core\Kernel\Handlers\XoopsTplSet',
            'tplset_id',
            'tplset_name'
        );
    }

    /**
     * getByName
     *
     * @param string $tplset_name of the block to retrieve
     *
     * @return XoopsTplSet|false reference to the tplsets
     */
    public function getByName($tplset_name)
    {
        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();

        $tplset = false;
        $tplset_name = trim($tplset_name);
        if ($tplset_name != '') {
            $qb->select('*')
                ->fromPrefix('system_tplset', null)
                ->where($eb->eq('tplset_name', ':tplsetname'))
                ->setParameter('tplsetname', $tplset_name, ParameterType::STRING);
            $result = $qb->execute();
            if (!$result) {
                return false;
            }
            $allrows = $result->fetchAll();
            if (count($allrows) == 1) {
                $tplset = new XoopsTplSet();
                $tplset->assignVars(reset($allrows));
            }
        }
        return $tplset;
    }

    /**
     * get a list of tplsets matching certain conditions
     *
     * @param CriteriaElement|null $criteria conditions to match
     *
     * @return array array of tplsets matching the conditions
     **/
    public function getNameList(?CriteriaElement $criteria = null)
    {
        $ret = array();
        $tplsets = $this->getObjects($criteria, true);
        foreach (array_keys($tplsets) as $i) {
            $ret[$tplsets[$i]->getVar('tplset_name')] = $tplsets[$i]->getVar('tplset_name');
        }
        return $ret;
    }
}
