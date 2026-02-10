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
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Index;

/**
 * RemovePrefixes is a Schema Visitor that builds an new Schema object
 * without the configured XOOPS db-prefix on table names. A table list
 * can be optionally applied to filter the Schema.
 *
 * This depends on PrefixStripper to do a lot of the grunt work.
 *
 * @category  Xoops\Core\Database\Schema\RemovePrefixes
 * @package   Xoops\Core
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013-2024 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class RemovePrefixes implements SchemaVisitorInterface
{

    protected $newSchema;

    /**
     * Constructor
     *
     * @param string   $prefix    Prefix to remove from table names
     * @param string[] $tableList list of tables to include in new schema. If no
     *                            list is specified, all tables will be included.
     */
    public function __construct(string $prefix, $tableList = [])
    {
        $this->newSchema = new PrefixStripper($prefix, $tableList);
    }

    /**
     * return the generated Schema
     *
     * @return Schema the generated schema object
     */
    public function getNewSchema()
    {
        return $this->newSchema;
    }

    /**
     * Accept schema - not used in this context
     *
     * @param Schema $schema a schema object
     *
     * @return void
     */
    public function acceptSchema(Schema $schema)
    {
    }

    /**
     * Accept a table with all its dependencies.
     *
     * @param Table $table a table object
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function acceptTable(Table $table)
    {
        $this->newSchema->addTable($table);
    }

    /**
     * accept a column in the schema - not used in this context
     *
     * @param Table  $table  a table object to accept a column into
     * @param Column $column a column object to be accepted
     *
     * @return void
     */
    public function acceptColumn(Table $table, Column $column)
    {
    }

    /**
     * Accept a foreign key in the schema - not used in this context
     *
     * @param Table                $localTable   local table to have foreign key
     * @param ForeignKeyConstraint $fkConstraint foreign key constraint
     *
     * @return void
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
    }

    /**
     * Accept an Index - not used in this context
     *
     * @param Table $table indexed table
     * @param Index $index index to accept
     *
     * @return void
     */
    public function acceptIndex(Table $table, Index $index)
    {
    }

    /**
     * Accept a sequence
     *
     * @param Sequence $sequence sequence to accept
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function acceptSequence(Sequence $sequence)
    {
        $this->newSchema->addSequence($sequence);
    }
}
