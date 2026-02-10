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
 * ImportSchema processes an array of schema information and creates
 * a XOOPS_DB_PREFIX prefixed Schema object.
 *
 * @category  Xoops\Core\Database\Schema\ImportSchema
 * @package   Xoops\Core
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class ImportSchema
{

    private $xPrefix;
    private $schemaArray = array();

    /**
     * Constructor
     *
     * @param string $prefix Prefix to remove from table names
     */
    public function __construct(string $prefix)
    {
        $this->xPrefix = $prefix;
    }

    /**
     * Import an array into a schema
     *
     * @param array $schemaArray array version of a schema object
     *
     * @return Schema object built from input array
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function importSchemaArray(array $schemaArray)
    {
        $tables = array();
        $sequences = array();
        $this->schemaArray = $schemaArray;
        foreach ($schemaArray as $type => $entity) {
            switch ($type) {
                case 'tables':
                    $tables = $this->importTables($entity);
                    break;
                case 'sequence':
                    $sequences = $this->importSequences($entity);
                    break;
            }
        }
        return new Schema($tables, $sequences);
    }

    /**
     * Build array of Table objects to add to the schema
     *
     * @param array $tableArray array of table definitions
     *
     * @return array of Table objects
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function importTables(array $tableArray)
    {
        $tables=array();
        foreach ($tableArray as $name => $tabledef) {
            $tableName = $this->xPrefix . $name;
            $columns = array();
            $indexes = array();
            $fkConstraints = array();
            $options = array();
            // DBAL 4: Column::setOptions() only supports keys that have a matching
            // setXxx() method. Platform options (collation, charset, etc.) from
            // toArray() must be extracted and set separately via setPlatformOption().
            $knownColumnOptions = [
                'type', 'name', 'length', 'precision', 'scale', 'unsigned',
                'fixed', 'notnull', 'default', 'columnDefinition',
                'autoincrement', 'comment', 'values', 'platformOptions',
            ];
            foreach ($tabledef['columns'] as $colName => $colOptions) {
                $colType = \Doctrine\DBAL\Types\Type::getType($colOptions['type']);
                unset($colOptions['type']);
                // Remove 'name' key from options - DBAL 4 setName() expects a Name object
                unset($colOptions['name']);
                // DBAL 4: setComment() etc. no longer accept null - filter out null values
                $colOptions = array_filter($colOptions, static fn($v) => $v !== null);
                // Separate platform options from standard column options
                $platformOptions = [];
                foreach ($colOptions as $key => $value) {
                    if (!in_array($key, $knownColumnOptions, true)) {
                        $platformOptions[$key] = $value;
                        unset($colOptions[$key]);
                    }
                }
                $column = new Column($colName, $colType, $colOptions);
                if (!empty($platformOptions)) {
                    $column->setPlatformOptions($platformOptions);
                }
                $columns[] = $column;
            }

            if (isset($tabledef['indexes'])) {
                foreach ($tabledef['indexes'] as $indexName => $indexDef) {
                    $indexes[] = new Index(
                        $indexName,
                        $indexDef['columns'],
                        $indexDef['unique'],
                        $indexDef['primary']
                    );
                }
            }

            if (isset($tabledef['constraint'])) {
                foreach ($tabledef['constraint'] as $constraintDef) {
                    $fkConstraints[] = new ForeignKeyConstraint(
                        $constraintDef['localcolumns'],
                        $constraintDef['foreigntable'],
                        $constraintDef['foreigncolumns'],
                        $constraintDef['name'] ?? '',
                        $constraintDef['options'] ?? []
                    );
                }
            }

            if (isset($tabledef['options'])) {
                $options = $tabledef['options'];
            }

            $tables[] = new Table(
                $tableName,
                $columns,
                $indexes,
                [],  // uniqueConstraints
                $fkConstraints,
                $options
            );
        }
        return $tables;
    }

    /**
     * Build array of Sequence objects to add to the schema
     *
     * @param array $sequenceArray array of table definitions
     *
     * @return array of Sequence objects
     */
    public function importSequences(array $sequenceArray)
    {
        $sequences = array();

        foreach ($sequenceArray as $name => $sequenceDef) {
            $sequences[] = new Sequence(
                $sequenceDef['name'],
                $sequenceDef['allocationsize'],
                $sequenceDef['initialvalue']
            );
        }
        return $sequences;
    }
}
