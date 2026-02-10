<?php
/**
 * XOOPS Kernel Class
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
 * @since           2.6.0
 * @author          Kazumi Ono (AKA onokazu) http://www.myweb.ne.jp/, http://jp.xoops.org/
 * @version         $Id$
 */

namespace Xoops\Core\Kernel\Handlers;

use Xoops\Core\Database\Connection;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;
use Doctrine\DBAL\ParameterType;

/**
 * XOOPS membership handler class. (Singleton)
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS group membership class objects.
 *
 * @category  Xoops\Core\Kernel\Handlers\XoopsMembershipHandler
 * @package   Xoops\Core\Kernel
 * @author    Kazumi Ono <onokazu@xoops.org>
 * @copyright 2000-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class XoopsMembershipHandler extends XoopsPersistableObjectHandler
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
            'system_usergroup',
            '\Xoops\Core\Kernel\Handlers\XoopsMembership',
            'linkid',
            'groupid'
        );
    }

    /**
     * retrieve groups for a user
     *
     * @param int $uid ID of the user objects
     *
     * @return array array of groups the user belongs to
     */
    public function getGroupsByUser($uid)
    {
        $ret = array();
        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();
        $qb ->select('groupid')
            ->fromPrefix('system_usergroup', 'g')
            ->where($eb->eq('g.uid', ':uid'))
            ->setParameter('uid', $uid, ParameterType::INTEGER);
        $result = $qb->execute();
        while ($myrow = $result->fetchAssociative()) {
            $ret[] = $myrow['groupid'];
        }

        return $ret;
    }

    /**
     * retrieve users belonging to a group
     *
     * @param int $groupid ID of the group
     * @param int $limit   number of entries to return
     * @param int $start   offset of first entry to return
     *
     * @return array array of users belonging to the group
     */
    public function getUsersByGroup($groupid, $limit = 0, $start = 0)
    {
        $ret = array();
        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();
        $qb ->select('uid')
            ->fromPrefix('system_usergroup', 'g')
            ->where($eb->eq('g.groupid', ':gid'))
            ->setParameter('gid', $groupid, ParameterType::INTEGER);
        if ($limit!=0 || $start!=0) {
            $qb->setFirstResult($start)
                ->setMaxResults($limit);
        }
        $result = $qb->execute();
        while ($myrow = $result->fetchAssociative()) {
            $ret[] = $myrow['uid'];
        }

        return $ret;
    }
}
