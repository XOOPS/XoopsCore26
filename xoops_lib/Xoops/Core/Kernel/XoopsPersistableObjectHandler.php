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
use Doctrine\DBAL\ParameterType;

/**
 * XOOPS Kernel Persistable Object Handler class.
 *
 * @category  Xoops\Core\Kernel\XoopsPersistableObjectHandler
 * @package   Xoops\Core\Kernel
 * @author    Taiwen Jiang <phppp@users.sourceforge.net>
 * @author    Jan Keller Pedersen <mithrandir@xoops.org>
 * @copyright 2000-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @since     2.0.0
 */
abstract class XoopsPersistableObjectHandler extends XoopsObjectHandler
{
    /**
     * holds reference to custom extended object handler
     *
     * var object
     *
     * @access private
     */
    /**
     * static protected
     */
    protected $handler;

    /**
     * holds reference to predefined extended object handlers: read, stats, joint, write, sync
     *
     * The handlers hold methods for different purposes, which could be all put together inside of current class.
     * However, load codes only if they are necessary, thus they are now split out.
     *
     * var array of objects
     *
     * @access private
     */
    private $handlers = array('read' => null, 'stats' => null, 'joint' => null, 'write' => null, 'sync' => null);

    /**
     * Information about the class, the handler is managing
     *
     * @var string
     * @access public
     */
    public $table;

    /**
     * @var string
     */
    public $keyName;

    /**
     * @var string
     */
    public $className;

    /**
     * @var string
     */
    public $table_link;

    /**
     * @var string
     */
    public $identifierName;

    /**
     * @var string
     */
    public $field_link;

    /**
     * @var string
     */
    public $field_object;

    /**
     * Constructor
     *
     * @param null|Connection $db             database connection
     * @param string          $table          Name of database table
     * @param string          $className      Name of the XoopsObject class this handler manages
     * @param string          $keyName        Name of the property holding the key
     * @param string          $identifierName Name of the property holding an identifier
     *                                         name (title, name ...), used on getList()
     */
    protected function __construct(
        ?Connection $db = null,
        $table = '',
        $className = '',
        $keyName = '',
        $identifierName = ''
    ) {
        parent::__construct($db);
        $this->table = $this->db2->prefix($table);
        $this->keyName = $keyName;
        $this->className = $className;
        if ($identifierName) {
            $this->identifierName = $identifierName;
        }
    }

    /**
     * Set custom handler
     *
     * @param string|object $handler handler
     * @param array|null    $args    arguments
     *
     * @return object|null
     */
    public function setHandler($handler = null, $args = null)
    {
        $this->handler = null;
        if (is_object($handler)) {
            $this->handler = $handler;
        } else {
            if (is_string($handler)) {
                $xmf = XoopsModelFactory::getInstance();
                $this->handler = $xmf->loadHandler($this, $handler, $args);
            }
        }
        return $this->handler;
    }

    /**
     * Load predefined handler
     *
     * @param string $name handler name
     * @param mixed  $args args
     *
     * @return XoopsModelAbstract handler
     */
    public function loadHandler($name, $args = null)
    {
        static $handlers;
        if (!isset($handlers[$name])) {
            $xmf = XoopsModelFactory::getInstance();
            $handlers[$name] = $xmf->loadHandler($this, $name, $args);
        }
        /* @var $handler XoopsModelAbstract */
        $handler = $handlers[$name];
        $handler->setHandler($this);
        $handler->setVars($args);

        return $handler;

        /**
         * // Following code just kept as placeholder for PHP5
         * if (!isset(self::$handlers[$name])) {
         * self::$handlers[$name] = XoopsModelFactory::loadHandler($this, $name, $args);
         * } else {
         * self::$handlers[$name]->setHandler($this);
         * self::$handlers[$name]->setVars($args);
         * }
         *
         * return self::$handlers[$name];
         */
    }

    /**
     * Magic method for overloading of delegation
     *
     * @param string $name method name
     * @param array  $args arguments
     *
     * @return mixed
     */
    public function __call($name, $args)
    {
        if (is_object($this->handler) && is_callable(array($this->handler, $name))) {
            return call_user_func_array(array($this->handler, $name), $args);
        }
        foreach (array_keys($this->handlers) as $_handler) {
            $handler = $this->loadHandler($_handler);
            if (is_callable(array($handler, $name))) {
                return call_user_func_array(array($handler, $name), $args);
            }
        }

        return null;
    }

    /**
     * Methods of native handler
     */

    /**
     * create a new object
     *
     * @param bool $isNew Flag the new objects as new
     *
     * @return XoopsObject
     */
    public function create($isNew = true)
    {
        if (empty($this->className)) {
            return false;
        }

        /* @var $obj XoopsObject */
        $obj = new $this->className();
        if ($isNew === true) {
            $obj->setNew();
        }
        return $obj;
    }

    /**
     * Load an object from the database
     *
     * @param mixed $id     ID
     * @param array $fields fields to fetch
     *
     * @return XoopsObject|null
     */
    public function get($id = null, $fields = null)
    {
        $object = null;
        if (empty($id)) {
            $object = $this->create();
            return $object;
        }
        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();
        if (is_array($fields) && count($fields) > 0) {
            if (!in_array($this->keyName, $fields)) {
                $fields[] = $this->keyName;
            }
            $first=true;
            foreach ($fields as $field) {
                if ($first) {
                    $first=false;
                    $qb->select($field);
                } else {
                    $qb->addSelect($field);
                }
            }
        } else {
            $qb->select('*');
        }
        $qb->from($this->table, null)
            ->where($eb->eq($this->keyName, ':id'))
            ->setParameter('id', $id, ParameterType::INTEGER);
        if (!$result = $qb->execute()) {
            return $object;
        }
        $row = $result->fetchAssociative();
        if (!$row) {
            return $object;
        }
        $object = $this->create(false);
        $object->assignVars($row);

        return $object;
    }

    /**
     * Methods of write handler
     */

    /**
     * insert an object into the database
     *
     * @param XoopsObject $object object to insert
     * @param bool        $force  flag to force the query execution despite security settings
     *
     * @return mixed
     */
    public function insert(XoopsObject $object, $force = true)
    {
        /* @var $handler Model\Write */
        $handler = $this->loadHandler('write');
        return $handler->insert($object, $force);
    }

    /**
     * delete an object from the database
     *
     * @param XoopsObject $object object to delete
     * @param bool        $force  force delete
     *
     * @return bool FALSE if failed.
     */
    public function delete(XoopsObject $object, $force = false)
    {
        /* @var $handler Model\Write */
        $handler = $this->loadHandler('write');
        return $handler->delete($object, $force);
    }

    /**
     * delete all objects matching the conditions
     *
     * @param CriteriaElement $criteria criteria to match
     * @param boolean         $force    force to delete
     * @param boolean         $asObject delete in object way: instantiate all objects
     *                                       and delete one by one
     *
     * @return bool
     */
    public function deleteAll(CriteriaElement $criteria, $force = true, $asObject = false)
    {
        if (empty($criteria)) {
            return false;
        }

        /* @var $handler Model\Write */
        $handler = $this->loadHandler('write');
        return $handler->deleteAll($criteria, $force, $asObject);
    }

    /**
     * Change a field for objects with a certain criteria
     *
     * @param string          $fieldname  Name of the field
     * @param mixed           $fieldvalue Value to write
     * @param CriteriaElement $criteria   criteria to match
     * @param boolean         $force      force to query
     *
     * @return bool
     */
    public function updateAll($fieldname, $fieldvalue, CriteriaElement $criteria, $force = false)
    {
        if (empty($criteria)) {
            return false;
        }

        /* @var $handler Model\Write */
        $handler = $this->loadHandler('write');
        return $handler->updateAll($fieldname, $fieldvalue, $criteria, $force);
    }

    /**
     * Methods of read handler
     */

    /**
     * Retrieve objects from the database
     *
     * @param CriteriaElement|null $criteria  criteria to match
     * @param bool                 $id_as_key use the ID as key for the array
     * @param bool                 $as_object return an array of objects
     *
     * @return array
     */
    public function getObjects(?CriteriaElement $criteria = null, $id_as_key = false, $as_object = true)
    {
        /* @var $handler Model\Read */
        $handler = $this->loadHandler('read');
        $ret = $handler->getObjects($criteria, $id_as_key, $as_object);
        return $ret;
    }

    /**
     * get all objects matching a condition
     *
     * @param CriteriaElement|null $criteria  criteria to match
     * @param array                $fields    variables to fetch
     * @param bool                 $asObject  flag indicating as object, otherwise as array
     * @param bool                 $id_as_key use the ID as key for the array
     *
     * @return array of objects/array as requested by $asObject
     */
    public function getAll(?CriteriaElement $criteria = null, $fields = null, $asObject = true, $id_as_key = true)
    {
        /* @var $handler Model\Read */
        $handler = $this->loadHandler('read');
        $ret = $handler->getAll($criteria, $fields, $asObject, $id_as_key);
        return $ret;
    }

    /**
     * Retrieve a list of objects data
     *
     * @param CriteriaElement|null $criteria criteria to match
     * @param int                  $limit    Max number of objects to fetch
     * @param int                  $start    Which record to start at
     *
     * @return array
     */
    public function getList(?CriteriaElement $criteria = null, $limit = 0, $start = 0)
    {
        /* @var $handler Model\Read */
        $handler = $this->loadHandler('read');
        $ret = $handler->getList($criteria, $limit, $start);
        return $ret;
    }

    /**
     * get IDs of objects matching a condition
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return array of object IDs
     */
    public function getIds(?CriteriaElement $criteria = null)
    {
        /* @var $handler Model\Read */
        $handler = $this->loadHandler('read');
        $ret = $handler->getIds($criteria);
        return $ret;
    }

    /**
     * Methods of stats handler
     */

    /**
     * count objects matching a condition
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return int count of objects
     */
    public function getCount(?CriteriaElement $criteria = null)
    {
        /* @var $handler Model\Stats */
        $handler = $this->loadHandler('stats');
        return $handler->getCount($criteria);
    }

    /**
     * Get counts of objects matching a condition
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return array of counts
     */
    public function getCounts(?CriteriaElement $criteria = null)
    {
        /* @var $handler Model\Stats*/
        $handler = $this->loadHandler('stats');
        return $handler->getCounts($criteria);
    }

    /**
     * Methods of joint handler
     */

    /**
     * get a list of objects matching a condition joint with another related object
     *
     * @param CriteriaElement|null $criteria     criteria to match
     * @param array                $fields       variables to fetch
     * @param bool                 $asObject     flag indicating as object, otherwise as array
     * @param string               $field_link   field of linked object for JOIN
     * @param string               $field_object field of current object for JOIN
     *
     * @return array as specified by $asObject
     */
    public function getByLink(
        ?CriteriaElement $criteria = null,
        $fields = null,
        $asObject = true,
        $field_link = null,
        $field_object = null
    ) {
        /* @var $handler Model\Joint */
        $handler = $this->loadHandler('joint');
        $ret = $handler->getByLink($criteria, $fields, $asObject, $field_link, $field_object);
        return $ret;
    }

    /**
     * Count of objects matching a condition
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return int count of objects
     */
    public function getCountByLink(?CriteriaElement $criteria = null)
    {
        /* @var $handler Model\Joint */
        $handler = $this->loadHandler('joint');
        $ret = $handler->getCountByLink($criteria);
        return $ret;
    }

    /**
     * array of count of objects matching a condition of, groupby linked object keyname
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return int count of objects
     */
    public function getCountsByLink(?CriteriaElement $criteria = null)
    {
        /* @var $handler Model\Joint */
        $handler = $this->loadHandler('joint');
        $ret = $handler->getCountsByLink($criteria);
        return $ret;
    }

    /**
     * update objects matching a condition against linked objects
     *
     * @param array                $data     array of key => value
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return int count of objects
     */
    public function updateByLink($data, ?CriteriaElement $criteria = null)
    {
        /* @var $handler Model\Joint */
        $handler = $this->loadHandler('joint');
        $ret = $handler->updateByLink($data, $criteria);
        return $ret;
    }

    /**
     * Delete objects matching a condition against linked objects
     *
     * @param CriteriaElement|null $criteria criteria to match
     *
     * @return int count of objects
     */
    public function deleteByLink(?CriteriaElement $criteria = null)
    {
        /* @var $handler Model\Joint */
        $handler = $this->loadHandler('joint');
        $ret = $handler->deleteByLink($criteria);
        return $ret;
    }

    /**
     * Methods of sync handler
     */

    /**
     * Clean orphan objects against linked objects
     *
     * @param string $table_link   table of linked object for JOIN
     * @param string $field_link   field of linked object for JOIN
     * @param string $field_object field of current object for JOIN
     *
     * @return bool true on success
     */
    public function cleanOrphan($table_link = '', $field_link = '', $field_object = '')
    {
        /* @var $handler Model\Sync */
        $handler = $this->loadHandler('sync');
        $ret = $handler->cleanOrphan($table_link, $field_link, $field_object);
        return $ret;
    }

    /**
     * Synchronizing objects
     *
     * @param string $table_link   parent table
     * @param string $field_link   primary key (parent table)
     * @param string $field_object foreign key (child table)
     *
     * @return bool true on success
     */
    public function synchronization($table_link = '', $field_link = '', $field_object = '')
    {
        $retval = $this->cleanOrphan($table_link, $field_link, $field_object);
        return $retval;
    }
}
