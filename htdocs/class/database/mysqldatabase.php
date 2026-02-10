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

// FetchMode removed in Doctrine DBAL 4.x - using Result methods directly

/**
 * connection to a mysql database - legacy support only
 *
 * PHP version 5.3
 *
 * @category   Xoops\Class\Database\MySQLDatabase
 * @package    MySQLDatabase
 * @author     Kazumi Ono <onokazu@xoops.org>
 * @author     readheadedrod <redheadedrod@hotmail.com>
 * @author     Richard Griffith <richard@geekwright.com>
 * @copyright  2013-2019 XOOPS Project (https://xoops.org)
 * @license    GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
 *
 */
class XoopsMySQLDatabase extends XoopsDatabase
{

    /**
     * @var object keep track of last result since we need it for getAffectedRows
     */
    private $lastResult = null;

    /**
     * Database connection
     *
     * @var Xoops\Core\Database\Connection;
     */
    public $conn;

    /**
     * Database connection is established
     *
     * @var boolean
     */
    private $connect = false;

    /**
     * Database connection
     *
     * @var resource
     */
    private $selectdb;

    /**
     * Issue a deprecated warning once per session
     *
     * @return void
     */
    protected function deprecated()
    {
        static $warning_issued = false;
        if (!$warning_issued) {
            $warning_issued = true;
            $stack = debug_backtrace();
            $frame = $stack[1];
            Xoops::getInstance()->deprecated(
                'Legacy XoopsDB is deprecated since 2.6.0; all calls should be using Doctrine through $xoops->db(). '
                . 'Called from ' . $frame['function'] . '() in ' . $frame['file'] . ' line '. $frame['line']
            );
        }
    }


    /**
     * connect to the database
     *
     * @param bool $selectdb select the database now?
     *
     * @return bool successful?
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function connect($selectdb = true)
    {
        $this->connect = (is_object($this->conn));
        $this->selectdb = $selectdb;
        $this->allowWebChanges = ($_SERVER['REQUEST_METHOD'] !== 'GET');
        return $this->connect;
    }


    /**
     * generate an ID for a new row
     *
     * This is for compatibility only. Will always return 0, because MySQL supports
     * autoincrement for primary keys.
     *
     * @param string $sequence name of the sequence from which to get the next ID
     *
     * @return int always 0, because mysql has support for autoincrement
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function genId($sequence)
    {
        $this->deprecated();
        return 0; // will use auto_increment
    }

    /**
     * Get a result row as an enumerated array
     *
     * @param resource $result resource to get result from
     *
     * @return array
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function fetchRow($result)
    {
        $this->deprecated();
        if (!is_object($result)) {
            return null;
        }
        return $result->fetchNumeric();
    }

    /**
     * Fetch a result row as an associative array
     *
     * @param resource $result resource to get result from
     *
     * @return array
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function fetchArray($result)
    {
        $this->deprecated();

        if (!is_object($result)) {
            return null;
        }
        return $result->fetchAssociative();
    }

    /**
     * Fetch a result row as an associative array
     *
     * @param resource $result resource to get result from
     *
     * @return array
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function fetchBoth($result)
    {
        $this->deprecated();

        if (!is_object($result)) {
            return null;
        }
        // FetchMode::MIXED removed in DBAL 4. Simulate with both numeric and associative keys.
        $assoc = $result->fetchAssociative();
        if ($assoc === false) {
            return false;
        }
        return array_merge(array_values($assoc), $assoc);
    }

    /**
     * Fetch a result row as an object
     *
     * @param resource $result resource to get result from
     *
     * @return object|stdClass
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function fetchObject($result)
    {
        $this->deprecated();

        if (!is_object($result)) {
            return null;
        }
        // FetchMode::STANDARD_OBJECT removed in DBAL 4. Simulate with fetchAssociative + cast.
        $assoc = $result->fetchAssociative();
        return ($assoc !== false) ? (object) $assoc : false;
    }

    /**
     * Get the ID generated from the previous INSERT operation
     *
     * @return int
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function getInsertId()
    {
        $this->deprecated();
        return $this->conn->lastInsertId();
    }

    /**
     * Get number of rows in result
     *
     * @param resource $result the resource containing the number of rows
     *
     * @return int the number of rows to return
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function getRowsNum($result)
    {
        Xoops::getInstance()->deprecated('getRowsNum is deprecated and not dependable.');
        //$this->deprecated();
        return $result->rowCount();
    }

    /**
     * Get number of affected rows
     *
     * @return int
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function getAffectedRows()
    {
        $this->deprecated();
        return $this->lastResult->rowCount();
    }

    /**
     * Close MySQL connection
     *
     * @return void
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function close()
    {
        $this->deprecated();
        return $this->conn->close();
    }

    /**
     * will free all memory associated with the result identifier result.
     *
     * @param resource $result query result
     *
     * @return bool TRUE on success or FALSE on failure.
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function freeRecordSet($result)
    {
        $this->deprecated();

        return $result->closeCursor();
    }

    /**
     * Returns the text of the error message from previous MySQL operation
     *
     * @return bool Returns the error text from the last MySQL function,
     * or '' (the empty string) if no error occurred.
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function error()
    {
        $this->deprecated();

        return $this->conn->errorInfo();
    }

    /**
     * Returns the numerical value of the error message from previous
     * MySQL operation
     *
     * @return int Returns the error number from the last MySQL function
     * , or 0 (zero) if no error occurred.
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function errno()
    {
        $this->deprecated();

        return $this->conn->errorCode();
    }

    /**
     * Returns escaped string text with single
     * quotes around it to be safely stored in database
     *
     * @param string $str unescaped string text
     *
     * @return string escaped string text with single quotes around
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function quoteString($str)
    {
        $this->deprecated();

        return $this->quote($str);
    }

    /**
     * Quotes a string for use in a query.
     *
     * @param string $string string to quote
     *
     * @return string
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function quote($string)
    {
        $this->deprecated();

        return  $this->conn->quote($string);
    }

    /**
     * Escapes a string for use in a query. Does not add quotes.
     *
     * @param string $string string to escape
     *
     * @return string
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function escape($string)
    {
        $this->deprecated();

        $string = $this->quote($string);
        return substr($string, 1, -1);
    }

    /**
     * perform a query on the database
     *
     * @param string $sql   a valid MySQL query
     * @param int    $limit number of records to return
     * @param int    $start offset of first record to return
     *
     * @return bool|resource query result or FALSE if successful
     * or TRUE if successful and no result
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function queryF($sql, $limit = 0, $start = 0)
    {
        $this->deprecated();

        if (!empty($limit)) {
            if (empty($start)) {
                $start = 0;
            }
            $sql = $sql . ' LIMIT ' . (int) $start . ', ' . (int) $limit;
        }
        $events = \Xoops::getInstance()->events();
        $events->triggerEvent('core.database.query.start');
        try {
            $result = $this->conn->query($sql);
        } catch (Exception $e) {
            $result=false;
        }
        $this->lastResult = $result;
        $events->triggerEvent('core.database.query.end');

        if ($result) {
            $events->triggerEvent('core.database.query.success', (array($sql)));
            return $result;
        } else {
            $events->triggerEvent('core.database.query.failure', (array($sql, $this)));
            return false;
        }
    }

    /**
     * perform a query
     *
     * This method is empty and does nothing! It should therefore only be
     * used if nothing is exactly what you want done! ;-)
     *
     * @param string $sql   a valid MySQL query
     * @param int    $limit number of records to return
     * @param int    $start offset of first record to return
     *
     * @return void
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function query($sql, $limit = 0, $start = 0)
    {
        $this->deprecated();
    }

    /**
     * perform queries from SQL dump file in a batch
     *
     * @param string $file file path to an SQL dump file
     *
     * @return bool FALSE if failed reading SQL file or TRUE
     * if the file has been read and queries executed
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function queryFromFile($file)
    {
        $this->deprecated();

        if (false !== ($fp = fopen($file, 'r'))) {
            $sql_queries = trim(fread($fp, filesize($file)));
            SqlUtility::splitMySqlFile($pieces, $sql_queries);
            foreach ($pieces as $query) {
                // [0] contains the prefixed query
                // [4] contains unprefixed table name
                $prefixed_query
                    = SqlUtility::prefixQuery(trim($query), $this->prefix());
                if ($prefixed_query != false) {
                    $this->query($prefixed_query[0]);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Get field name
     *
     * @param resource $result query result
     * @param int      $offset numerical field index
     *
     * @return string
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function getFieldName($result, $offset)
    {
        $this->deprecated();

        try {
            $temp = $result->getColumnMeta($offset);
            return $temp['name'];
        } catch (PDOException $e) {
            return null;
        }

    }

    /**
     * Get field type
     *
     * @param resource $result query result
     * @param int      $offset numerical field index
     *
     * @return string
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function getFieldType($result, $offset)
    {
        $this->deprecated();

        try {
            $temp = ($result->getColumnMeta($offset));
            $t = $temp['native_type'];

            $temp = (string)(
                ((($t === 'STRING') || ($t === 'VAR_STRING') ) ? 'string' : '' ) .
                ( (in_array($t, array('TINY', 'SHORT', 'LONG', 'LONGLONG', 'INT24'))) ? 'int' : '' ) .
                ( (in_array($t, array('FLOAT', 'DOUBLE', 'DECIMAL', 'NEWDECIMAL'))) ? 'real' : '' ) .
                ( ($t === 'TIMESTAMP') ? 'timestamp' : '' ) .
                ( ($t === 'YEAR') ? 'year' : '') .
                ( (($t === 'DATE') || ($t === 'NEWDATE') ) ? 'date' : '' ) .
                ( ($t === 'TIME') ? 'time' : '' ) .
                ( ($t === 'SET') ? 'set' : '' ) .
                ( ($t === 'ENUM') ? 'enum' : '' ) .
                ( ($t === 'GEOMETRY') ? 'geometry' : '' ) .
                ( ($t === 'DATETIME') ? 'datetime' : '' ) .
                ( (in_array($t, array('TINY_BLOB', 'BLOB', 'MEDIUM_BLOB', 'LONG_BLOB' ))) ? 'blob' : '' ) .
                ( ($t === 'NULL') ? 'null' : '' )
            );
            return $temp;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get number of fields in result
     *
     * @param resource $result query result
     *
     * @return int
     * @deprecated since version 2.6.0 - alpha 3. Switch to doctrine connector.
     */
    public function getFieldsNum($result)
    {
        $this->deprecated();

        return $result->columnCount();
    }

    /**
     * getServerVersion get version of the mysql server
     *
     * @return string
     */
    public function getServerVersion()
    {
        try {
            $pdo = \Xoops::getInstance()->db()->getNativeConnection();
            $version = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } catch (\Exception $e) {
            $version = '(not available)';
        }
        return $version;
    }
}
