<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Database;

use Doctrine\DBAL\Driver\Statement;
use Xmf\Language;
use Xoops\Core\Database\Connection;
use Xoops\Core\Database\Factory;

/**
 * Xmf\Database\Tables
 *
 * inspired by Yii CDbMigration
 *
 * Build a work queue of database changes needed to implement new and
 * changed tables. Define table(s) you are dealing with and any desired
 * change(s). If the changes are already in place (i.e. the new column
 * already exists) no work is added. Then executeQueue() to process the
 * whole set.
 *
 * @category  Xmf\Database\Tables
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2011-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class Tables
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var string
     */
    protected $databaseName;

    /**
     * @var array Tables
     */
    protected $tables;

    /**
     * @var array Work queue
     */
    protected $queue;

    /**
     * @var string last error message
     */
    protected $lastError;

    /**
     * @var int last error number
     */
    protected $lastErrNo;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        Language::load('xmf');

        $this->db = Factory::getConnection();
        $this->databaseName = \XoopsBaseConfig::get('db-name');
        $this->resetQueue();
    }

    /**
     * Return a table name, prefixed with site table prefix
     *
     * @param string $table table name to contain prefix
     *
     * @return string table name with prefix
     */
    protected function name($table)
    {
        return $this->db->prefix($table);
    }

    /**
     * Add new column for table to the work queue
     *
     * @param string $table      table to contain the column
     * @param string $column     name of column to add
     * @param string $attributes column_definition
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function addColumn($table, $column, $attributes)
    {
        $columnDef = array(
            'name' => $column,
            'attributes' => $attributes
        );

        // Find table def.
        if (isset($this->tables[$table])) {
            $tableDef = &$this->tables[$table];
            // Is this on a table we are adding?
            if (isset($tableDef['create']) && $tableDef['create']) {
                array_push($tableDef['columns'], $columnDef);
            } else {
                foreach ($tableDef['columns'] as $col) {
                    if (strcasecmp($col['name'], $column) == 0) {
                        return true;
                    }
                }
                $this->queue[] = "ALTER TABLE `{$tableDef['name']}`"
                    . " ADD COLUMN `{$column}` {$columnDef['attributes']}";
                array_push($tableDef['columns'], $columnDef);
            }
        } else {
            return $this->tableNotEstablished();
        }

        return true; // exists or is added to queue
    }

    /**
     * Add new primary key definition for table to work queue
     *
     * @param string $table  table
     * @param string $column column or comma separated list of columns
     *                       to use as primary key
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function addPrimaryKey($table, $column)
    {
        $columns = str_getcsv(str_replace(' ', '', $column));
        $columnList = '';
        $firstComma = '';
        foreach ($columns as $col) {
            $columnList .= "{$firstComma}`{$col}`";
            $firstComma = ', ';
        }
        if (isset($this->tables[$table])) {
            if (isset($this->tables[$table]['create']) && $this->tables[$table]['create']) {
                $this->tables[$table]['keys']['PRIMARY']['columns'] = $columnList;
            } else {
                $this->queue[] = "ALTER TABLE `{$this->tables[$table]['name']}` ADD PRIMARY KEY({$columnList})";
            }
        } else {
            return $this->tableNotEstablished();
        }

        return true;
    }

    /**
     * Add new index definition for index to work queue
     *
     * @param string $name   name of index to add
     * @param string $table  table indexed
     * @param string $column column or a comma separated list of columns
     *                        to use as the key
     * @param bool   $unique true if index is to be unique
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function addIndex($name, $table, $column, $unique = false)
    {
        $columns = str_getcsv($column);
        $columnList = '';
        $firstComma = '';
        foreach ($columns as $col) {
            $columnList .= $firstComma . $this->quoteIndexColumnName($col);
            $firstComma = ', ';
        }
        if (isset($this->tables[$table])) {
            if (isset($this->tables[$table]['create']) && $this->tables[$table]['create']) {
                $this->tables[$table]['keys'][$name]['columns'] = $columnList;
                $this->tables[$table]['keys'][$name]['unique'] = (bool) $unique;
            } else {
                $add = ($unique ? 'ADD UNIQUE INDEX' : 'ADD INDEX');
                $this->queue[] = "ALTER TABLE `{$this->tables[$table]['name']}` {$add} `{$name}` ({$columnList})";
            }
        } else {
            return $this->tableNotEstablished();
        }

        return true;
    }

    /**
     * Backtick quote the column names used in index creation.
     *
     * Handles prefix indexed columns specified as name(length) - i.e. name(20).
     *
     * @param string $columnName column name to quote with optional prefix length
     *
     * @return string
     */
    protected function quoteIndexColumnName($columnName)
    {
        $column = str_replace(' ', '', $columnName);
        $length = '';

        $lengthPosition = strpos($column, '(');
        if ($lengthPosition) {
            $length = ' ' . substr($column, $lengthPosition);
            $column = substr($column, 0, $lengthPosition);
        }
        $quotedName = "`{$column}`{$length}";

        return $quotedName;
    }

    /**
     * Load table schema from database, or starts new empty schema if
     * table does not exist
     *
     * @param string $table table
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function addTable($table)
    {
        if (isset($this->tables[$table])) {
            return true;
        }
        $tableDef = $this->getTable($table);
        if (is_array($tableDef)) {
            $this->tables[$table] = $tableDef;

            return true;
        } else {
            if ($tableDef === true) {
                $tableDef = array(
                    'name' => $this->name($table),
                    'options' => 'ENGINE=InnoDB',
                    'columns' => array(),
                    'keys' => array(),
                    'create' => true,
                );
                $this->tables[$table] = $tableDef;

                $this->queue[] = array('createtable' => $table);

                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * AddTable only if it exists
     *
     * @param string $table table
     *
     * @return bool true if table exists, false otherwise
     */
    public function useTable($table)
    {
        if (isset($this->tables[$table])) {
            return true;
        }
        $tableDef = $this->getTable($table);
        if (is_array($tableDef)) {
            $this->tables[$table] = $tableDef;
            return true;
        }
        return false;
    }

    /**
     * Get column attributes
     *
     * @param string $table  table containing the column
     * @param string $column column to alter
     *
     * @return string|bool attribute string, or false if error encountered
     */
    public function getColumnAttributes($table, $column)
    {
        // Find table def.
        if (isset($this->tables[$table])) {
            $tableDef = $this->tables[$table];
            // loop thru and find the column
            foreach ($tableDef['columns'] as $col) {
                if (strcasecmp($col['name'], $column) === 0) {
                    return $col['attributes'];
                }
            }
        }

        return false;
    }

    /**
     * Get indexes for a table
     *
     * @param string $table get indexes for this named table
     *
     * @return array|bool array of indexes, or false if error encountered
     */
    public function getTableIndexes($table)
    {
        // Find table def.
        if (isset($this->tables[$table]) && isset($this->tables[$table]['keys'])) {
            return $this->tables[$table]['keys'];
        }

        return false;
    }

    /**
     * Add alter column operation to the work queue
     *
     * @param string $table      table containing the column
     * @param string $column     column to alter
     * @param string $attributes new column_definition
     * @param string $newName    new name for column, blank to keep same
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function alterColumn($table, $column, $attributes, $newName = '')
    {
        if (empty($newName)) {
            $newName = $column;
        }
        // Find table def.
        if (isset($this->tables[$table])) {
            $tableDef = &$this->tables[$table];
            // Is this on a table we are adding?
            if (isset($tableDef['create']) && $tableDef['create']) {
                // loop thru and find the column
                foreach ($tableDef['columns'] as &$col) {
                    if (strcasecmp($col['name'], $column) == 0) {
                        $col['name'] = $newName;
                        $col['attributes'] = $attributes;
                        break;
                    }
                }

                return true;
            } else {
                $this->queue[] = "ALTER TABLE `{$tableDef['name']}` " .
                    "CHANGE COLUMN `{$column}` `{$newName}` {$attributes} ";
                // loop thru and find the column
                foreach ($tableDef['columns'] as &$col) {
                    if (strcasecmp($col['name'], $column) == 0) {
                        $col['name'] = $newName;
                        $col['attributes'] = $attributes;
                        break;
                    }
                }
            }
        } else {
            return $this->tableNotEstablished();
        }

        return true;
    }

    /**
     * Loads table schema from database, and adds newTable with that
     * schema to the queue
     *
     * @param string $table    existing table
     * @param string $newTable new table
     * @param bool   $withData true to copy data, false for schema only
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function copyTable($table, $newTable, $withData = false)
    {
        if (isset($this->tables[$newTable])) {
            return true;
        }
        $tableDef = $this->getTable($table);
        $copy = $this->name($newTable);
        $original = $this->name($table);

        if (is_array($tableDef)) {
            $tableDef['name'] = $copy;
            if ($withData) {
                $this->queue[] = "CREATE TABLE `{$copy}` LIKE `{$original}` ;";
                $this->queue[] = "INSERT INTO `{$copy}` SELECT * FROM `{$original}` ;";
            } else {
                $tableDef['create'] = true;
                $this->queue[] = array('createtable' => $newTable);
            }
            $this->tables[$newTable] = $tableDef;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Add drop column operation to the work queue
     *
     * @param string $table  table containing the column
     * @param string $column column to drop
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function dropColumn($table, $column)
    {
        // Find table def.
        if (isset($this->tables[$table])) {
            $tableDef = $this->tables[$table];
            $this->queue[] = "ALTER TABLE `{$tableDef['name']}` DROP COLUMN `{$column}`";
        } else {
            return $this->tableNotEstablished();
        }

        return true;
    }

    /**
     * Add drop index operation to the work queue
     *
     * @param string $name  name of index to drop
     * @param string $table table indexed
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function dropIndex($name, $table)
    {
        if (isset($this->tables[$table])) {
            $tableDef = $this->tables[$table];
            $this->queue[] = "ALTER TABLE `{$tableDef['name']}` DROP INDEX `{$name}`";
        } else {
            return $this->tableNotEstablished();
        }

        return true;
    }

    /**
     * Add drop for all (non-PRIMARY) keys for a table to the work
     * queue. This can be used to clean up indexes with automatic names.
     *
     * @param string $table table indexed
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function dropIndexes($table)
    {
        // Find table def.
        if (isset($this->tables[$table])) {
            $tableDef = &$this->tables[$table];
            // Is this on a table we are adding?
            if (isset($tableDef['create']) && $tableDef['create']) {
                // strip everything but the PRIMARY from definition
                foreach ($tableDef['keys'] as $keyName => $key) {
                    if ($keyName !== 'PRIMARY') {
                        unset($tableDef['keys'][$keyName]);
                    }
                }
            } else {
                // build drops to strip everything but the PRIMARY
                foreach ($tableDef['keys'] as $keyName => $key) {
                    if ($keyName !== 'PRIMARY') {
                        $this->queue[] = "ALTER TABLE `{$tableDef['name']}` DROP INDEX {$keyName}";
                    }
                }
            }
        } else {
            return $this->tableNotEstablished();
        }

        return true;
    }

    /**
     * Add drop of PRIMARY key for a table to the work queue
     *
     * @param string $table table
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function dropPrimaryKey($table)
    {
        if (isset($this->tables[$table])) {
            $tableDef = $this->tables[$table];
            $this->queue[] = "ALTER TABLE `{$tableDef['name']}` DROP PRIMARY KEY ";
        } else {
            return $this->tableNotEstablished();
        }

        return true;
    }

    /**
     * Add drop of table to the work queue
     *
     * @param string $table table
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function dropTable($table)
    {
        if (isset($this->tables[$table])) {
            $tableDef = $this->tables[$table];
            $this->queue[] = "DROP TABLE `{$tableDef['name']}` ";
            unset($this->tables[$table]);
        }
        // no table is not an error since we are dropping it anyway
        return true;
    }


    /**
     * Add rename table operation to the work queue
     *
     * @param string $table   table
     * @param string $newName new table name
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function renameTable($table, $newName)
    {
        if (isset($this->tables[$table])) {
            $tableDef = $this->tables[$table];
            $newTable = $this->name($newName);
            $this->queue[] = "ALTER TABLE `{$tableDef['name']}` RENAME TO `{$newTable}`";
            $tableDef['name'] = $newTable;
            $this->tables[$newName] = $tableDef;
            unset($this->tables[$table]);
        } else {
            return $this->tableNotEstablished();
        }

        return true;
    }

    /**
     * Add alter table table_options (ENGINE, DEFAULT CHARSET, etc.)
     * to work queue
     *
     * @param string $table   table
     * @param string $options table_options
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function setTableOptions($table, $options)
    {
        if (isset($this->tables[$table])) {
            $tableDef = &$this->tables[$table];
            // Is this on a table we are adding?
            if (isset($tableDef['create']) && $tableDef['create']) {
                $tableDef['options'] = $options;
                return true;
            } else {
                $this->queue[] = "ALTER TABLE `{$tableDef['name']}` {$options} ";
                $tableDef['options'] = $options;
                return true;
            }
        } else {
            return $this->tableNotEstablished();
        }
    }


    /**
     * Clear the work queue
     *
     * @return void
     */
    public function resetQueue()
    {
        $this->tables = array();
        $this->queue  = array();
    }

    /**
     * Executes the work queue
     *
     * @param bool $force true to force updates even if this is a 'GET' request
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function executeQueue($force = false)
    {
        $this->expandQueue();
        foreach ($this->queue as &$ddl) {
            if (is_array($ddl)) {
                if (isset($ddl['createtable'])) {
                    $ddl = $this->renderTableCreate($ddl['createtable']);
                }
            }
            $result = $this->execSql($ddl, $force);
            if (!$result) {
                $this->lastError = $this->db->errorInfo();
                $this->lastErrNo = $this->db->errorCode();

                return false;
            }
        }

        return true;
    }


    /**
     * Create a DELETE statement and add it to the work queue
     *
     * @param string                 $table    table
     * @param string|CriteriaElement $criteria string where clause or object criteria
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function delete($table, $criteria)
    {
        if (isset($this->tables[$table])) {
            $tableDef = $this->tables[$table];
            $where = '';
            if (is_scalar($criteria)) {
                $where = $criteria;
            } elseif (is_object($criteria)) {
                $where = $criteria->renderWhere();
            }
            $this->queue[] = "DELETE FROM `{$tableDef['name']}` {$where}";
        } else {
            return $this->tableNotEstablished();
        }

        return true;
    }

    /**
     * Create an INSERT SQL statement and add it to the work queue.
     *
     * @param string  $table      table
     * @param array   $columns    array of 'column'=>'value' entries
     * @param boolean $quoteValue true to quote values, false if caller handles quoting
     *
     * @return boolean true if no errors, false if errors encountered
     */
    public function insert($table, $columns, $quoteValue = true)
    {
        if (isset($this->tables[$table])) {
            $tableDef = $this->tables[$table];
            $colSql = '';
            $valSql = '';
            foreach ($tableDef['columns'] as $col) {
                $comma = empty($colSql) ? '' : ', ';
                if (isset($columns[$col['name']])) {
                    $colSql .= "{$comma}`{$col['name']}`";
                    $valSql .= $comma
                        . ($quoteValue ? $this->db->quote($columns[$col['name']]) : $columns[$col['name']]);
                }
            }
            $sql = "INSERT INTO `{$tableDef['name']}` ({$colSql}) VALUES({$valSql})";
            $this->queue[] = $sql;

            return true;
        } else {
            return $this->tableNotEstablished();
        }
    }

    /**
     * Create an UPDATE SQL statement and add it to the work queue
     *
     * @param string                 $table      table
     * @param array                  $columns    array of 'column'=>'value' entries
     * @param string|CriteriaElement $criteria   string where clause or object criteria
     * @param boolean                $quoteValue true to quote values, false if caller handles quoting
     *
     * @return boolean true if no errors, false if errors encountered
     */
    public function update($table, $columns, $criteria, $quoteValue = true)
    {
        if (isset($this->tables[$table])) {
            $tableDef = $this->tables[$table];
            $where = '';
            if (is_scalar($criteria)) {
                $where = $criteria;
            } elseif (is_object($criteria)) {
                $where = $criteria->renderWhere();
            }
            $colSql = '';
            foreach ($tableDef['columns'] as $col) {
                $comma = empty($colSql) ? '' : ', ';
                if (isset($columns[$col['name']])) {
                    $colSql .= "{$comma}`{$col['name']}` = "
                        . ($quoteValue ? $this->db->quote($columns[$col['name']]) : $columns[$col['name']]);
                }
            }
            $sql = "UPDATE `{$tableDef['name']}` SET {$colSql} {$where}";
            $this->queue[] = $sql;

            return true;
        } else {
            return $this->tableNotEstablished();
        }
    }

    /**
     * Add statement to remove all rows from a table to the work queue
     *
     * @param string $table table
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function truncate($table)
    {
        if (isset($this->tables[$table])) {
            $tableDef = $this->tables[$table];
            $this->queue[] = "TRUNCATE TABLE `{$tableDef['name']}`";
        } else {
            return $this->tableNotEstablished();
        }

        return true;
    }



    /**
     * return SQL to create the table
     *
     * This method does NOT modify the work queue
     *
     * @param string $table    table
     * @param bool   $prefixed true to return with table name prefixed
     *
     * @return string|false string SQL to create table, or false if errors encountered
     */
    protected function renderTableCreate($table, $prefixed = false)
    {
        if (isset($this->tables[$table])) {
            $tableDef = $this->tables[$table];
            $tableName = ($prefixed ? $tableDef['name'] : $table);
            $sql = "CREATE TABLE `{$tableName}` (";
            $firstComma = '';
            foreach ($tableDef['columns'] as $col) {
                $sql .= "{$firstComma}\n    `{$col['name']}`  {$col['attributes']}";
                $firstComma = ',';
            }
            $keySql = '';
            foreach ($tableDef['keys'] as $keyName => $key) {
                if ($keyName === 'PRIMARY') {
                    $keySql .= ",\n  PRIMARY KEY ({$key['columns']})";
                } else {
                    $unique = $key['unique'] ? 'UNIQUE ' : '';
                    $keySql .= ",\n  {$unique}KEY {$keyName} ({$key['columns']})";
                }
            }
            $sql .= $keySql;
            $sql .= "\n) {$tableDef['options']}";

            return $sql;
        } else {
            return $this->tableNotEstablished();
        }
    }

    /**
     * execute an SQL statement
     *
     * @param string $sql   SQL statement to execute
     * @param bool   $force true to use force updates even in safe requests
     *
     * @return Statement|false result Statement, or false on error
     *                         Any error message is in $this->lastError;
     */
    protected function execSql($sql, $force = false)
    {
        if ($force) {
            $this->db->setForce(true);
        }
        $result = $this->db->query($sql);

        if (!$result) {
            $this->lastError = $this->db->errorInfo();
            $this->lastErrNo = $this->db->errorCode();
        }

        return $result;
    }

    /**
     * fetch the next row of a result set
     *
     * @param Statement $result as returned by query
     *
     * @return mixed false on error
     */
    protected function fetch($result)
    {
        return $result->fetchAssociative();
    }

    /**
     * get table definition from INFORMATION_SCHEMA
     *
     * @param string $table table
     *
     * @return array|bool table definition array if table exists, true if table not defined, or
     *                    false on error. Error message in $this->lastError;
     */
    protected function getTable($table)
    {
        $tableDef = array();

        $sql  = 'SELECT TABLE_NAME, ENGINE, CHARACTER_SET_NAME ';
        $sql .= ' FROM `INFORMATION_SCHEMA`.`TABLES` t, ';
        $sql .= ' `INFORMATION_SCHEMA`.`COLLATIONS` c ';
        $sql .= ' WHERE t.TABLE_SCHEMA = \'' . $this->databaseName . '\' ';
        $sql .= ' AND t.TABLE_NAME = \'' . $this->name($table) . '\' ';
        $sql .= ' AND t.TABLE_COLLATION  = c.COLLATION_NAME ';

        $result = $this->execSql($sql);
        if (!$result) {
            return false;
        }
        $tableSchema = $this->fetch($result);
        if (empty($tableSchema)) {
            return true;
        }
        $tableDef['name'] = $tableSchema['TABLE_NAME'];
        $tableDef['options'] = 'ENGINE=' . $tableSchema['ENGINE'] . ' '
            . 'DEFAULT CHARSET=' . $tableSchema['CHARACTER_SET_NAME'];

        $sql  = 'SELECT * ';
        $sql .= ' FROM `INFORMATION_SCHEMA`.`COLUMNS` ';
        $sql .= ' WHERE TABLE_SCHEMA = \'' . $this->databaseName . '\' ';
        $sql .= ' AND TABLE_NAME = \'' . $this->name($table) . '\' ';
        $sql .= ' ORDER BY `ORDINAL_POSITION` ';

        $result = $this->execSql($sql);

        while ($column = $this->fetch($result)) {
            $attributes = ' ' . $column['COLUMN_TYPE'] . ' '
                . (($column['IS_NULLABLE'] === 'NO') ? ' NOT NULL ' : '')
                . (($column['COLUMN_DEFAULT'] === null) ? '' : " DEFAULT '" . $column['COLUMN_DEFAULT'] . "' ")
                . $column['EXTRA'];

            $columnDef = array(
                'name' => $column['COLUMN_NAME'],
                'attributes' => $attributes
            );

            $tableDef['columns'][] = $columnDef;
        };

        $sql  = 'SELECT `INDEX_NAME`, `SEQ_IN_INDEX`, `NON_UNIQUE`, ';
        $sql .= ' `COLUMN_NAME`, `SUB_PART` ';
        $sql .= ' FROM `INFORMATION_SCHEMA`.`STATISTICS` ';
        $sql .= ' WHERE TABLE_SCHEMA = \'' . $this->databaseName . '\' ';
        $sql .= ' AND TABLE_NAME = \'' . $this->name($table) . '\' ';
        $sql .= ' ORDER BY `INDEX_NAME`, `SEQ_IN_INDEX` ';

        $result = $this->execSql($sql);

        $lastKey = '';
        $keyCols = '';
        $keyUnique = false;
        while ($key = $this->fetch($result)) {
            if ($lastKey != $key['INDEX_NAME']) {
                if (!empty($lastKey)) {
                    $tableDef['keys'][$lastKey]['columns'] = $keyCols;
                    $tableDef['keys'][$lastKey]['unique'] = $keyUnique;
                }
                $lastKey = $key['INDEX_NAME'];
                $keyCols = $key['COLUMN_NAME'];
                if (!empty($key['SUB_PART'])) {
                    $keyCols .= ' (' . $key['SUB_PART'] . ')';
                }
                $keyUnique = !$key['NON_UNIQUE'];
            } else {
                $keyCols .= ', ' . $key['COLUMN_NAME'];
                if (!empty($key['SUB_PART'])) {
                    $keyCols .= ' (' . $key['SUB_PART'] . ')';
                }
            }
        };
        if (!empty($lastKey)) {
            $tableDef['keys'][$lastKey]['columns'] = $keyCols;
            $tableDef['keys'][$lastKey]['unique'] = $keyUnique;
        }

        return $tableDef;
    }

    /**
     * During processing, tables to be created are put in the queue as
     * an array('createtable' => tablename) since the definition is not
     * complete. This method will expand those references to the full
     * ddl to create the table.
     *
     * @return void
     */
    protected function expandQueue()
    {
        foreach ($this->queue as &$ddl) {
            if (is_array($ddl)) {
                if (isset($ddl['createtable'])) {
                    $ddl = $this->renderTableCreate($ddl['createtable'], true);
                }
            }
        }
    }

    /**
     * Return message from last error encountered
     *
     * @return string last error message
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Return code from last error encountered
     *
     * @return int last error number
     */
    public function getLastErrNo()
    {
        return $this->lastErrNo;
    }

    /**
     * dumpTables - utility function to dump raw tables array
     *
     * @return array tables
     */
    public function dumpTables()
    {
        return $this->tables;
    }

    /**
     * dumpQueue - utility function to dump the work queue
     *
     * @return array work queue
     */
    public function dumpQueue()
    {
        $this->expandQueue();

        return $this->queue;
    }

    /**
     * addToQueue - utility function to add a statement to the work queue
     *
     * @param string $sql an SQL/DDL statement to add
     *
     * @return void
     */
    public function addToQueue($sql)
    {
        $this->queue[] = $sql;
    }

    /**
     * Set lastError as table not established
     *
     * @return false
     */
    protected function tableNotEstablished()
    {
        $this->lastError = _DB_XMF_TABLE_IS_NOT_DEFINED;
        $this->lastErrNo = -1;
        return false;
    }
}
