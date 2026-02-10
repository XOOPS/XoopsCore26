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

use Xmf\Yaml;
use Xoops\Core\Kernel\CriteriaElement;

/**
 * Xmf\Database\TableLoad
 *
 * load a database table
 *
 * @category  Xmf\Database\TableLoad
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013-2018 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class TableLoad
{

    /**
     * loadTableFromArray
     *
     * @param string $table name of table to load without prefix
     * @param array  $data  array of rows to insert
     *                      Each element of the outer array represents a single table row.
     *                      Each row is an associative array in 'column' => 'value' format.
     *
     * @return int number of rows inserted
     */
    public static function loadTableFromArray($table, $data)
    {
        $db = \Xoops::getInstance()->db();
        $count = 0;
        $db->beginTransaction();
        foreach ($data as $row) {
            $count += $db->insertPrefix($table, $row);
        }
        $db->commit();
        return $count;
    }

    /**
     * loadTableFromYamlFile
     *
     * @param string $table    name of table to load without prefix
     * @param string $yamlFile name of file containing data dump in YAML format
     *
     * @return int number of rows inserted
     */
    public static function loadTableFromYamlFile($table, $yamlFile)
    {
        $count = 0;

        $data = Yaml::readWrapped($yamlFile); // work with phpmyadmin YAML dumps
        if (false !== $data) {
            $count = static::loadTableFromArray($table, $data);
        }

        return $count;
    }

    /**
     * truncateTable - empty a database table
     *
     * @param string $table name of table to truncate
     *
     * @return int number of affected rows
     */
    public static function truncateTable($table)
    {
        $db = \Xoops::getInstance()->db();
        $platform = $db->getDatabasePlatform();
        $sql = $platform->getTruncateTableSQL($db->prefix($table));

        return $db->exec($sql);
    }

    /**
     * countRows - get count of rows in a table
     *
     * @param string          $table    name of table to count
     * @param CriteriaElement $criteria optional criteria
     *
     * @return int number of rows
     */
    public static function countRows($table, ?CriteriaElement $criteria = null)
    {
        $db = \Xoops::getInstance()->db();
        $qb = $db->createXoopsQueryBuilder();
        $qb->select('COUNT(*)')
            ->fromPrefix($table, '');
        if (isset($criteria)) {
            $qb = $criteria->renderQb($qb, '');
        }
        $result = $qb->execute();
        $count = $result->fetchOne();
        return (int)$count;
    }

    /**
     * extractRows - get rows, all or a subset, from a table as an array
     *
     * @param string          $table       name of table to read
     * @param CriteriaElement $criteria    optional criteria
     * @param string[]        $skipColumns do not include these columns in the extract
     *
     * @return array of table rows
     */
    public static function extractRows($table, ?CriteriaElement $criteria = null, $skipColumns = array())
    {
        $db = \Xoops::getInstance()->db();
        $qb = $db->createXoopsQueryBuilder();
        $qb->select('*')->fromPrefix($table, '');
        if (isset($criteria)) {
            $qb = $criteria->renderQb($qb, '');
        }
        $result = $qb->execute();
        $rows = $result->fetchAll();

        if (!empty($skipColumns)) {
            foreach ($rows as $index => $row) {
                foreach ($skipColumns as $column) {
                    unset($rows[$index][$column]);
                }
            }
        }

        return $rows;
    }

    /**
     * Save table data to a YAML file
     *
     * @param string          $table name of table to load without prefix
     * @param string          $yamlFile name of file containing data dump in YAML format
     * @param CriteriaElement $criteria optional criteria
     * @param string[]        $skipColumns do not include columns in this list
     *
     * @return bool true on success, false on error
     */
    public static function saveTableToYamlFile($table, $yamlFile, $criteria = null, $skipColumns = array())
    {
        $rows = static::extractRows($table, $criteria, $skipColumns);

        $count = Yaml::save($rows, $yamlFile);

        return (false !== $count);
    }
}
