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
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;
use Doctrine\DBAL\ParameterType;

/**
 * XOOPS private message handler class.
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS private message class objects.
 *
 * @category  Xoops\Core\Kernel\Handlers\XoopsPrivateMessageHandler
 * @package   Xoops\Core\Kernel
 * @author    Kazumi Ono <onokazu@xoops.org>
 * @copyright 2000-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class XoopsPrivateMessageHandler extends XoopsPersistableObjectHandler
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
            'system_privatemessage',
            '\Xoops\Core\Kernel\Handlers\XoopsPrivateMessage',
            'msg_id',
            'subject'
        );
    }

    /**
     * Mark a message as read
     *
     * @param XoopsPrivateMessage $pm XoopsPrivateMessage object
     *
     * @return bool
     **/
    public function setRead(XoopsPrivateMessage $pm)
    {
        $qb = $this->db2->createXoopsQueryBuilder()
            ->update($this->table)
            ->set('read_msg', ':readmsg')
            ->where('msg_id = :msgid')
            ->setParameter('readmsg', 1, ParameterType::INTEGER)
            ->setParameter('msgid', (int)$pm->getVar('msg_id'), ParameterType::INTEGER);
        $result = $qb->execute();

        if (!$result) {
            return false;
        }
        return true;
    }
}
