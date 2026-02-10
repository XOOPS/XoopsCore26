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
 * SchemaVisitorInterface replaces Doctrine\DBAL\Schema\Visitor\Visitor
 * which was removed in DBAL 4.x.
 *
 * This interface provides a visitor pattern for traversing schema objects.
 *
 * @category  Xoops\Core\Database\Schema\SchemaVisitorInterface
 * @package   Xoops\Core
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013-2024 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
interface SchemaVisitorInterface
{
    /**
     * Accept a schema
     *
     * @param Schema $schema schema object
     *
     * @return void
     */
    public function acceptSchema(Schema $schema);

    /**
     * Accept a table
     *
     * @param Table $table table object
     *
     * @return void
     */
    public function acceptTable(Table $table);

    /**
     * Accept a column in a table
     *
     * @param Table  $table  table object
     * @param Column $column column object
     *
     * @return void
     */
    public function acceptColumn(Table $table, Column $column);

    /**
     * Accept a foreign key for a table
     *
     * @param Table                $localTable   table object
     * @param ForeignKeyConstraint $fkConstraint constraint object
     *
     * @return void
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint);

    /**
     * Accept an index on a table
     *
     * @param Table $table table object
     * @param Index $index index object
     *
     * @return void
     */
    public function acceptIndex(Table $table, Index $index);

    /**
     * Accept a sequence
     *
     * @param Sequence $sequence sequence object
     *
     * @return void
     */
    public function acceptSequence(Sequence $sequence);
}
