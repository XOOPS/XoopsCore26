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
 * @since           2.0.0
 * @author          Kazumi Ono (AKA onokazu) http://www.myweb.ne.jp/, http://jp.xoops.org/
 * @version         $Id$
 */

namespace Xoops\Core\Kernel\Handlers;

use Xoops\Core\Database\Connection;
use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;

/**
 * A handler for "Who is Online?" information
 *
 * @category  Xoops\Core\Kernel\Handlers\XoopsOnlineHandler
 * @package   Xoops\Core\Kernel
 * @author    Kazumi Ono <onokazu@xoops.org>
 * @copyright 2000-2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class XoopsOnlineHandler extends XoopsPersistableObjectHandler
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
            'system_online',
            '\Xoops\Core\Kernel\Handlers\XoopsOnline',
            'online_uid',
            'online_uname'
        );
    }

    /**
     * Write online information to the database
     *
     * @param int    $uid    UID of the active user
     * @param string $uname  Username
     * @param string $time   time
     * @param string $module Current module
     * @param string $ip     User's IP address
     *
     * @return bool TRUE on success
     */
    public function write($uid, $uname, $time, $module, $ip)
    {
        $criteria = array();
        $criteria['online_uid'] = $uid;
        if ($uid == 0) {
            $criteria['online_ip'] = $ip;
        }
        $rows = $this->db2->updatePrefix(
            'system_online',
            array(
               'online_uname'   => $uname,
               'online_updated' => $time,
               'online_module'  => $module,
            ),
            $criteria
        );
        if ($rows === false) {
            return false;
        }
        if ($rows == 0) {
            $rows = $this->db2->insertPrefix(
                'system_online',
                array(
                    'online_uid'     => $uid,
                    'online_uname'   => $uname,
                    'online_updated' => $time,
                    'online_ip'      => $ip,
                    'online_module'  => $module,
                )
            );
        }
        if ($rows === false) {
            return false;
        }
        return ($rows>0);
    }

    /**
     * Delete online information for a user
     *
     * @param int $uid UID
     *
     * @return bool TRUE on success
     */
    public function destroy($uid)
    {
        $criteria = new Criteria('online_uid', (int)($uid));
        if (false === $this->deleteAll($criteria)) {
            return false;
        }
        return true;
    }

    /**
     * Garbage Collection
     *
     * Delete all online information that has not been updated for a certain time
     *
     * @param int $expire Expiration time in seconds
     *
     * @return bool
     */
    public function gc($expire)
    {
        $criteria = new Criteria('online_updated', time() - (int)($expire), '<');
        if (false === $this->deleteAll($criteria)) {
            return false;
        }
        return true;
    }
}
