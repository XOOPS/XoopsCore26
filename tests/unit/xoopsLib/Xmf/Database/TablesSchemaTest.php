<?php

declare(strict_types=1);

namespace Xmf\Test\Database;

use PHPUnit\Framework\Attributes\CoversClass;
use Xmf\Database\Tables;

/**
 * Loads table definitions through the 2.6 connection API using a fake connection.
 */
#[CoversClass(Tables::class)]
class TablesSchemaTest extends \PHPUnit\Framework\TestCase
{
    private function tables(SchemaFakeConnection $db): Tables
    {
        $tables = (new \ReflectionClass(Tables::class))->newInstanceWithoutConstructor();
        foreach (['db' => $db, 'databaseName' => 'xoops', 'tables' => [], 'queue' => []] as $name => $value) {
            $property = new \ReflectionProperty(Tables::class, $name);
            $property->setValue($tables, $value);
        }
        return $tables;
    }

    private static function itemsSchema(): SchemaFakeConnection
    {
        $db = new SchemaFakeConnection();
        $db->results['TABLES'] = [
            ['TABLE_NAME' => 'x_items', 'ENGINE' => 'InnoDB', 'CHARACTER_SET_NAME' => 'utf8mb4'],
        ];
        $db->results['COLUMNS'] = [
            ['COLUMN_NAME' => 'id', 'COLUMN_TYPE' => 'int unsigned', 'IS_NULLABLE' => 'NO',
                'COLUMN_DEFAULT' => null, 'EXTRA' => 'auto_increment'],
            ['COLUMN_NAME' => 'title', 'COLUMN_TYPE' => 'varchar(255)', 'IS_NULLABLE' => 'YES',
                'COLUMN_DEFAULT' => "it's", 'EXTRA' => ''],
            ['COLUMN_NAME' => 'created', 'COLUMN_TYPE' => 'timestamp', 'IS_NULLABLE' => 'NO',
                'COLUMN_DEFAULT' => 'current_timestamp()', 'EXTRA' => 'DEFAULT_GENERATED on update CURRENT_TIMESTAMP'],
        ];
        $db->results['STATISTICS'] = [
            ['INDEX_NAME' => 'PRIMARY', 'SEQ_IN_INDEX' => 1, 'NON_UNIQUE' => 0, 'COLUMN_NAME' => 'id', 'SUB_PART' => null],
            ['INDEX_NAME' => 'idx_title', 'SEQ_IN_INDEX' => 1, 'NON_UNIQUE' => 1, 'COLUMN_NAME' => 'title', 'SUB_PART' => 20],
            ['INDEX_NAME' => 'idx_title', 'SEQ_IN_INDEX' => 2, 'NON_UNIQUE' => 1, 'COLUMN_NAME' => 'created', 'SUB_PART' => null],
            ['INDEX_NAME' => 'idx_func', 'SEQ_IN_INDEX' => 1, 'NON_UNIQUE' => 1, 'COLUMN_NAME' => null, 'SUB_PART' => null],
        ];
        return $db;
    }

    public function testUseTableLoadsColumnsAndKeys(): void
    {
        $tables = $this->tables(self::itemsSchema());

        $this->assertTrue($tables->useTable('items'));
        $definition = $tables->dumpTables()['items'];

        $this->assertSame('x_items', $definition['name']);
        $this->assertSame('ENGINE=InnoDB DEFAULT CHARSET=utf8mb4', $definition['options']);
        $this->assertSame(
            [
                ['name' => 'id', 'attributes' => ' int unsigned  NOT NULL auto_increment'],
                ['name' => 'title', 'attributes' => " varchar(255)  DEFAULT 'it''s' "],
                ['name' => 'created', 'attributes' => ' timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP() on update CURRENT_TIMESTAMP'],
            ],
            $definition['columns']
        );
        $this->assertSame(
            [
                'PRIMARY'   => ['columns' => 'id', 'unique' => true],
                'idx_title' => ['columns' => 'title (20), created', 'unique' => false],
            ],
            $tables->getTableIndexes('items')
        );
        $this->assertSame(' varchar(255)  DEFAULT \'it\'\'s\' ', $tables->getColumnAttributes('items', 'TITLE'));
        $this->assertFalse($tables->getColumnAttributes('items', 'missing'));
    }

    public function testUseTableReturnsFalseForUnknownTable(): void
    {
        $db = new SchemaFakeConnection();
        $db->results['TABLES'] = [];
        $tables = $this->tables($db);

        $this->assertFalse($tables->useTable('nothing'));
    }

    public function testUseTableReportsQueryFailure(): void
    {
        $db = new SchemaFakeConnection();
        $db->fail = true;
        $tables = $this->tables($db);

        $this->assertFalse($tables->useTable('items'));
        $this->assertSame(['42S02', 1146, 'missing'], $tables->getLastError());
        $this->assertSame('42S02', $tables->getLastErrNo());
    }

    public function testAddIndexOnNewTableAndAlterOnExistingTable(): void
    {
        $tables = $this->tables(self::itemsSchema());
        $tables->addTable('fresh');
        $tables->addColumn('fresh', 'id', 'int NOT NULL');
        $tables->addIndex('idx_id', 'fresh', 'id', true);

        $this->assertSame(['columns' => '`id`', 'unique' => true], $tables->dumpTables()['fresh']['keys']['idx_id']);

        $tables->useTable('items');
        $tables->addIndex('idx_new', 'items', 'created');
        $this->assertContains('ALTER TABLE `x_items` ADD INDEX `idx_new` (`created`)', $tables->dumpQueue());
    }

    private function queueCreateTable(SchemaFakeConnection $db): Tables
    {
        $tables = $this->tables($db);
        $tables->addTable('fresh');
        $tables->setTableOptions('fresh', 'ENGINE=InnoDB');
        $tables->addColumn('fresh', 'id', 'int NOT NULL');
        $tables->addPrimaryKey('fresh', 'id');
        return $tables;
    }

    public function testExecuteQueueRendersCreateTableAndRunsItForced(): void
    {
        $db = self::itemsSchema();
        $db->affectedRows = 1;
        $tables = $this->queueCreateTable($db);

        $this->assertTrue($tables->executeQueue(true));
        $this->assertTrue($db->forced);
        $this->assertStringStartsWith('CREATE TABLE `x_fresh` (', end($db->queries));
        $this->assertStringContainsString('PRIMARY KEY (`id`)', end($db->queries));
    }

    public function testExecuteQueueAcceptsDdlThatAffectsNoRows(): void
    {
        $this->markTestIncomplete(
            'Existing 2.6 bug: Connection::safeQuery() returns null when a statement affects 0 rows, '
            . 'which every CREATE TABLE does, so Tables::executeQueue() reports a successful CREATE as failed.'
        );
        $tables = $this->queueCreateTable(self::itemsSchema());

        $this->assertTrue($tables->executeQueue(true));
    }

    public function testExecuteQueueStopsOnFailedStatement(): void
    {
        $db = self::itemsSchema();
        $tables = $this->tables($db);
        $tables->useTable('items');
        $tables->dropColumn('items', 'title');
        $db->fail = true;

        $this->assertFalse($tables->executeQueue());
        $this->assertSame('42S02', $tables->getLastErrNo());
    }
}

/**
 * Minimal stand-in for Xoops\Core\Database\Connection as used by Tables
 */
class SchemaFakeConnection
{
    /** @var array<string, array<int, array<string, mixed>>> rows keyed by INFORMATION_SCHEMA table */
    public $results = [];
    public $queries = [];
    public $fail = false;
    public $force = false;
    /** @var bool whether setForce(true) was called before the last statement */
    public $forced = false;
    public $affectedRows = 0;

    public function prefix($table = '')
    {
        return 'x_' . $table;
    }

    public function quote($value)
    {
        return "'" . str_replace("'", "''", (string) $value) . "'";
    }

    public function setForce($force)
    {
        $this->force = (bool) $force;
        $this->forced = $this->forced || $this->force;
    }

    public function query($sql)
    {
        $this->queries[] = $sql;
        // like Connection::safeQuery(): force lasts for one statement, and a
        // non-SELECT returns its affected-row count, or null when that is 0
        $this->force = false;
        if ($this->fail) {
            return false;
        }
        if (stripos(ltrim($sql), 'select') !== 0) {
            return $this->affectedRows ?: null;
        }
        // only x_items exists; every other table comes back empty
        if (strpos($sql, "TABLE_NAME = 'x_items'") === false) {
            return new SchemaFakeResult([]);
        }
        foreach ($this->results as $schemaTable => $rows) {
            if (strpos($sql, '`INFORMATION_SCHEMA`.`' . $schemaTable . '`') !== false) {
                return new SchemaFakeResult($rows);
            }
        }
        return new SchemaFakeResult([]);
    }

    public function errorInfo()
    {
        return ['42S02', 1146, 'missing'];
    }

    public function errorCode()
    {
        return '42S02';
    }
}

class SchemaFakeResult
{
    private $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function fetchAssociative()
    {
        return array_shift($this->rows) ?? false;
    }
}
