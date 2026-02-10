<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core\Kernel\Model;

use Xoops\Core\Kernel\XoopsModelAbstract;

/**
 * Object synchronization handler class.
 *
 * Usage of methods provided by XoopsModelSync:
 *
 * Step #1: set linked table and joint fields through XoopsPersistableObjectHandler:
 *      $handler->table_link = $handler->db2->prefix("the_linked_table");
 *          full name of the linked table that is used for the query
 *      $handler->field_link = "the_linked_field";
 *          name of field in linked table that will be used to link
 *          the linked table with current table
 *      $handler->field_object = "the_object_field";
 *          name of field in current table that will be used to link
 *          the linked table with current table; linked field name will
 *          be used if the field name is not set
 * Step #2: perform query
 *
 * @category  Xoops\Core\Kernel\Model\Sync
 * @package   Xoops\Core\Kernel
 * @author    Taiwen Jiang <phppp@users.sourceforge.net>
 * @copyright 2000-2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 * @since     2.3.0
 */
class Sync extends XoopsModelAbstract
{
    /**
     * Clean orphan objects in this handler (child table) that are not in parent table
     *
     * The parameters can be defined in the handler. Naming should be updated to reflect
     * standard relational terminology.
     *
     * @param string $table_link   parent table
     * @param string $field_link   primary key (parent table)
     * @param string $field_object foreign key (child table)
     *
     * @return bool true on success
     */
    public function cleanOrphan($table_link = '', $field_link = '', $field_object = '')
    {
        if (!empty($table_link)) {
            $this->handler->table_link = $table_link;
        }
        if (!empty($field_link)) {
            $this->handler->field_link = $field_link;
        }
        if (!empty($field_object)) {
            $this->handler->field_object = $field_object;
        }

        if (empty($this->handler->field_object)
            || empty($this->handler->table_link)
            || empty($this->handler->field_link)
        ) {
            trigger_error(
                "The link information is not set for '" . get_class($this->handler) . "' yet.",
                E_USER_WARNING
            );
            return false;
        }

        // there were two versions of this sql, first for mysql 4.1+ and
        // the second for earlier versions.
        // TODO: Need to analyse and find the most portable query to use here
        /*
        $sql = "DELETE FROM `{$this->handler->table}`"
            . " WHERE (`{$this->handler->field_object}` NOT IN "
            . "( SELECT DISTINCT `{$this->handler->field_link}` FROM `{$this->handler->table_link}`) )";
        */
        $sql = "DELETE `{$this->handler->table}` FROM `{$this->handler->table}`"
            . " LEFT JOIN `{$this->handler->table_link}` AS aa "
            . " ON `{$this->handler->table}`.`{$this->handler->field_object}` = aa.`{$this->handler->field_link}`"
            . " WHERE (aa.`{$this->handler->field_link}` IS NULL)";

        return $this->handler->db2->executeStatement($sql);
    }
}
