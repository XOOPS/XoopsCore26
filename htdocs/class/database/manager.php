<?php
/**
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * Database manager for XOOPS
 *
 * PHP version 5.3
 *
 * @category  Xoops\Class\Database\Manager
 * @package   Manager
 * @author    Haruki Setoyama  <haruki@planewave.org>
 * @copyright 2013 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @version   Release: 2.6
 * @link      http://xoops.org
 * @since     2.6.0
 */

class XoopsDatabaseManager
{
    /**
     * @var array
     */
    private $s_tables = array();

    /**
     * @var array
     */
    private $f_tables = array();

    /**
     * @var XoopsDatabase
     */
    public $db;

    /**
     * @var array
     */
    public $successStrings = array();

    /**
     * @var array
     */
    public $failureStrings = array();

    /**
     *Construct declaration
     */
    public function __construct()
    {
        $xoops = Xoops::getInstance();
        $xoops->db();
        global $xoopsDB;
        $this->db = $xoopsDB;
        $this->db->setPrefix(\XoopsBaseConfig::get('db-prefix'));
        $this->successStrings = array(
            'create' => XoopsLocale::SF_TABLE_CREATED,
            'insert' => XoopsLocale::SF_ENTRIES_INSERTED_TO_TABLE,
            'alter'  => XoopsLocale::SF_TABLE_UPDATED,
            'drop'   => XoopsLocale::SF_TABLE_DROPPED,
        );
        $this->failureStrings = array(
            'create' => XoopsLocale::EF_TABLE_NOT_CREATED,
            'insert' => XoopsLocale::EF_ENTRIES_NOT_INSERTED_TO_TABLE,
            'alter'  => XoopsLocale::EF_TABLE_NOT_UPDATED,
            'drop'   => XoopsLocale::EF_TABLE_NOT_DROPPED,
        );
    }

    /**
     * Is the database connectable?
     *
     * @return bool is it connectable?
     */
    public function isConnectable()
    {
        // DBAL 4: connect() is protected; test connectivity with a lightweight query.
        // $this->db is XoopsMySQLDatabaseSafe (legacy wrapper), use queryF() not executeQuery().
        try {
            return (false !== $this->db->queryF('SELECT 1'));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Checks if a database exists
     *
     * @return bool returns if exists
     */
    public function dbExists()
    {
        // DBAL 4: connect() is protected; test connectivity with a lightweight query.
        // $this->db is XoopsMySQLDatabaseSafe (legacy wrapper), use queryF() not executeQuery().
        try {
            return (false !== $this->db->queryF('SELECT 1'));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * creates a database table
     *
     * @return bool return if successful
     */
    public function createDB()
    {
        // DBAL 4: connect() is protected; connection is established lazily on first query
        $result = $this->db->query("CREATE DATABASE " . \XoopsBaseConfig::get('db-name'));

        return ($result != false) ? true : false;
    }

    /**
     * Loads a query from a file
     *
     * @param string $sql_file_path name of file to read
     * @param bool   $force         weither to force the query or not
     *
     * @return bool
     */
    public function queryFromFile($sql_file_path, $force = false)
    {
        if (!XoopsLoad::fileExists($sql_file_path)) {
            return false;
        }
        $queryFunc = (bool)$force ? "queryF" : "query";
        $sql_query = trim(fread(fopen($sql_file_path, 'r'), filesize($sql_file_path)));
        SqlUtility::splitMySqlFile($pieces, $sql_query);
        // DBAL 4: connection is established lazily on first query
        foreach ($pieces as $piece) {
            $piece = trim($piece);
            // [0] contains the prefixed query
            // [4] contains unprefixed table name
            $prefixed_query = SqlUtility::prefixQuery($piece, $this->db->prefix());
            if ($prefixed_query != false) {
                $table = $this->db->prefix($prefixed_query[4]);
                if ($prefixed_query[1] === 'CREATE TABLE') {
                    if ($this->db->$queryFunc($prefixed_query[0]) != false) {
                        if (!isset($this->s_tables['create'][$table])) {
                            $this->s_tables['create'][$table] = 1;
                        }
                    } else {
                        if (!isset($this->f_tables['create'][$table])) {
                            $this->f_tables['create'][$table] = 1;
                        }
                    }
                } else {
                    if ($prefixed_query[1] === 'INSERT INTO') {
                        if ($this->db->$queryFunc($prefixed_query[0]) != false) {
                            if (!isset($this->s_tables['insert'][$table])) {
                                $this->s_tables['insert'][$table] = 1;
                            } else {
                                $this->s_tables['insert'][$table]++;
                            }
                        } else {
                            if (!isset($this->f_tables['insert'][$table])) {
                                $this->f_tables['insert'][$table] = 1;
                            } else {
                                $this->f_tables['insert'][$table]++;
                            }
                        }
                    } else {
                        if ($prefixed_query[1] === 'ALTER TABLE') {
                            if ($this->db->$queryFunc($prefixed_query[0]) != false) {
                                if (!isset($this->s_tables['alter'][$table])) {
                                    $this->s_tables['alter'][$table] = 1;
                                }
                            } else {
                                if (!isset($this->s_tables['alter'][$table])) {
                                    $this->f_tables['alter'][$table] = 1;
                                }
                            }
                        } else {
                            if ($prefixed_query[1] === 'DROP TABLE') {
                                if ($this->db->$queryFunc('DROP TABLE ' . $table) != false) {
                                    if (!isset($this->s_tables['drop'][$table])) {
                                        $this->s_tables['drop'][$table] = 1;
                                    }
                                } else {
                                    if (!isset($this->s_tables['drop'][$table])) {
                                        $this->f_tables['drop'][$table] = 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * returns a report
     *
     * @return string
     */
    public function report()
    {
        $commands = array('create', 'insert', 'alter', 'drop');
        $content = '<ul class="log">';
        foreach ($commands as $cmd) {
            if (!@empty($this->s_tables[$cmd])) {
                foreach ($this->s_tables[$cmd] as $key => $val) {
                    $content .= '<li class="success">';
                    $content .= ($cmd !== 'insert')
                        ? sprintf($this->successStrings[$cmd], $key)
                        : sprintf($this->successStrings[$cmd], $val, $key);
                    $content .= "</li>\n";
                }
            }
        }
        foreach ($commands as $cmd) {
            if (!@empty($this->f_tables[$cmd])) {
                foreach ($this->f_tables[$cmd] as $key => $val) {
                    $content .= '<li class="failure">';
                    $content .= ($cmd !== 'insert')
                        ? sprintf($this->failureStrings[$cmd], $key)
                        : sprintf($this->failureStrings[$cmd], $val, $key);
                    $content .= "</li>\n";
                }
            }
        }
        $content .= '</ul>';
        return $content;
    }

    /**
     * runs a query
     *
     * @param string $sql sql statement to perform
     *
     * @return resource
     */
    public function query($sql)
    {
        // DBAL 4: connection is established lazily on first query
        return $this->db->query($sql);
    }

    /**
     * Setup prefix table
     *
     * @param string $table table to prefix
     *
     * @return string prefixed table
     */
    public function prefix($table)
    {
        // DBAL 4: connection is established lazily on first query
        return $this->db->prefix($table);
    }

    /**
     * fetches an array
     *
     * @param string $ret resource that was returned from query
     *
     * @return array returns the array
     */
    public function fetchArray($ret)
    {
        // DBAL 4: connection is established lazily on first query
        return $this->db->fetchArray($ret);
    }

    /**
     * Inserts into a table
     *
     * @param string $table table to insert into
     * @param string $query query to use to insert
     *
     * @return bool|void
     */
    public function insert($table, $query)
    {
        // DBAL 4: connection is established lazily on first query
        $table = $this->db->prefix($table);
        $query = 'INSERT INTO ' . $table . ' ' . $query;
        if (!$this->db->queryF($query)) {
            if (!isset($this->f_tables['insert'][$table])) {
                $this->f_tables['insert'][$table] = 1;
            } else {
                $this->f_tables['insert'][$table]++;
            }
            return false;
        } else {
            if (!isset($this->s_tables['insert'][$table])) {
                $this->s_tables['insert'][$table] = 1;
            } else {
                $this->s_tables['insert'][$table]++;
            }
            return $this->db->getInsertId();
        }
    }

    /**
     * return the error
     *
     * @return bool
     */
    public function isError()
    {
        return (isset($this->f_tables)) ? true : false;
    }

    /**
     * Deletes tables
     *
     * No callers found in core. foreach loop is suspect, using key and value for
     * a list of tables? No docs or examples about what it is supposed to do.
     *
     * @param array $tables table to delete
     *
     * @return array list of dropped tables
     */
    public function deleteTables($tables)
    {
        $deleted = array();
        // DBAL 4: connection is established lazily on first query
        foreach ($tables as $key => $val) {
            //was: if (!$this->db->query("DROP TABLE " . $this->db->prefix($key))) {
            if (!$this->db->query("DROP TABLE " . $this->db->prefix($val))) {
                $deleted[] = $val;
            }
        }
        return $deleted;
    }

    /**
     * Checks to see if table exists
     *
     * @param string $table name of database table looking for
      *
     * @return bool true if exists or false if doesnt
     */
    public function tableExists($table)
    {
        $table = trim($table);
        $ret = false;
        if ($table != '') {
            // DBAL 4: connection is established lazily on first query
            $sql = 'SELECT COUNT(*) FROM ' . $this->db->prefix($table);
            $ret = (false != $this->db->query($sql)) ? true : false;
        }
        return $ret;
    }

    /**
     * This method allows to copy fields from one table to another
     *
     * @param array  $fieldsMap  Map of the fields
     *                           ex: array('oldfieldname' => 'newfieldname');
     * @param string $oTableName Old Table
     * @param string $nTableName New Table
     * @param bool   $dropTable  Drop old Table
     *
     * @return this does not return anything
     */
    public function copyFields(
        $fieldsMap,
        $oTableName,
        $nTableName,
        $dropTable = false
    ) {
        $sql = "SHOW COLUMNS FROM " . $this->db->prefix($oTableName);
        $result = $this->db->queryF($sql);
        if (($rows = $this->db->getRowsNum($result)) == count($fieldsMap)) {
            $sql = "SELECT * FROM " . $this->db->prefix($oTableName);
            $result = $this->db->queryF($sql);
            while (false !== ($myrow = $this->db->fetchArray($result))) {
                ksort($fieldsMap);
                ksort($myrow);
                $sql = "INSERT INTO `" . $this->db->prefix($nTableName)
                    . "` " . "(`" . implode("`,`", $fieldsMap) . "`)" .
                    " VALUES ('" . implode("','", $myrow) . "')";

                $this->db->queryF($sql);
            }
            if ($dropTable) {
                $sql = "DROP TABLE " . $this->db->prefix($oTableName);
                $this->db->queryF($sql);
            }
        }
    }
}
