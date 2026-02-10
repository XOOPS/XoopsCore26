<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core\Database\Schema;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Sequence;

/**
 * PrefixStripper extends Schema so we can easily add tables and
 * sequences selectively while visiting another schema.
 *
 * New schema will be stripped of database and prefix and optionally
 * filtered by a table list
 *
 * @category  Xoops\Core\Database\Schema\PrefixStripper
 * @package   Xoops\Core
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class PrefixStripper extends Schema
{

    private $xPrefix;
    private $tableList = [];

    /**
     * constructor
     *
     * @param string       $prefix       Required table name prefix to remove
     * @param string[]     $tableList    list of table names (without prefix) to include
     * @param SchemaConfig $schemaConfig SchemaConfig object to include in schema
     */
    public function __construct(string $prefix, array $tableList = [], ?SchemaConfig $schemaConfig = null)
    {
        $this->xPrefix = $prefix;
        $this->tableList = $tableList;
        parent::__construct([], [], $schemaConfig);
    }

    /**
     * Add a table object to the schema
     *
     * @param Table $table table object to add
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function addTable(Table $table)
    {
        try {
            $name = $table->getName();
            $len = strlen($this->xPrefix);
            if (substr_compare($name, $this->xPrefix, 0, $len)===0) {
                $name = substr($name, $len);
                if (empty($this->tableList) || in_array($name, $this->tableList)) {
                    $newtable = new Table(
                        $name,
                        $table->getColumns(),
                        $table->getIndexes(),
                        $table->getUniqueConstraints(),
                        $table->getForeignKeys(),
                        $table->getOptions()
                    );
                    $this->_addTable($newtable);
                }
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            \Xoops::getInstance()->events()->triggerEvent('core.exception', $e);
            throw $e;
        }
    }

    /**
     * Add a sequence to the schema
     *
     * @param Sequence $sequence a sequence
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function addSequence(Sequence $sequence)
    {
        try {
            $this->_addSequence($sequence);
        } catch (\Doctrine\DBAL\Schema\SchemaException $e) {
            \Xoops::getInstance()->events()->triggerEvent('core.exception', $e);
            throw $e;
        }
    }
}
