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
use Doctrine\DBAL\Types\Type;

/**
 * ExportVisitor is a Schema Visitor that builds an exportable array
 * (not object) version of a schema.
 *
 * @category  Xoops\Core\Database\Schema\ExportVisitor
 * @package   Xoops\Core
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013-2024 XOOPS Project (https//xoops.org)
 * @license   GNU GPL 2 or later (https//www.gnu.org/licenses/gpl-2.0.html)
 */
class ExportVisitor implements SchemaVisitorInterface
{

    protected $schemaArray;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->schemaArray = [];
    }

    /**
     * return the generated Schema
     *
     * @return array Array representation of the generated schema object
     */
    public function getSchemaArray()
    {
        return $this->schemaArray;
    }

    /**
     * Accept an entire schema. Do nothing in this visitor
     *
     * @param Schema $schema schema object
     *
     * @return void
     */
    public function acceptSchema(Schema $schema)
    {
    }

    /**
     * Accept a table
     *
     * @param Table $table a table object
     *
     * @return void
     */
    public function acceptTable(Table $table)
    {
        $this->schemaArray['tables'][$table->getName()]['options'] = $table->getOptions();
    }

    /**
     * Accept a column in a table
     *
     * @param Table  $table  a table object
     * @param Column $column a column object
     *
     * @return void
     */
    public function acceptColumn(Table $table, Column $column)
    {
        // DBAL 4: filter out null values to prevent errors on re-import (e.g. setComment(null))
        $colArray = array_filter($column->toArray(), static fn($v) => $v !== null);
        $colArray['type'] = Type::lookupName($column->getType());
        $this->schemaArray['tables'][$table->getName()]['columns'][$column->getName()] = $colArray;
    }

    /**
     * Accept a foreign key for a table
     *
     * @param Table                $localTable   a table object
     * @param ForeignKeyConstraint $fkConstraint a constraint object
     *
     * @return void
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        if (!isset($this->schemaArray['tables'][$localTable->getName()]['constraint'])) {
            $this->schemaArray['tables'][$localTable->getName()]['constraint']=array();
        }
        $this->schemaArray['tables'][$localTable->getName()]['constraint'][] = array(
                'name' => $fkConstraint->getName(),
                'localcolumns' => $fkConstraint->getLocalColumns(),
                'foreigntable' => $fkConstraint->getForeignTableName(),
                'foreigncolumns' => $fkConstraint->getForeignColumns(),
                'options' => $fkConstraint->getOptions()
            );
    }

    /**
     * Accept an index on in a table
     *
     * @param Table $table a table object
     * @param Index $index a column object
     *
     * @return void
     */
    public function acceptIndex(Table $table, Index $index)
    {
        $this->schemaArray['tables'][$table->getName()]['indexes'][$index->getName()] = array(
                'name' => $index->getName(),
                'columns' => $index->getColumns(),
                'unique' => $index->isUnique(),
                'primary' => $index->isPrimary()
            );
    }

    /**
     * Accept an sequence
     *
     * @param Sequence $sequence a sequence object
     *
     * @return void
     */
    public function acceptSequence(Sequence $sequence)
    {
        $this->schemaArray['sequence'][$sequence->getName()] = array(
                'name' => $sequence->getName(),
                'allocationsize' => $sequence->getAllocationSize(),
                'initialvalue' => $sequence->getInitialValue()
            );
    }
}
