<?php

declare(strict_types=1);

namespace Xmf\Test\Database;

use PHPUnit\Framework\Attributes\CoversClass;
use Xmf\Database\Migrate;
use Xmf\Database\SchemaDefinitionException;
use Xmf\Yaml;

/**
 * Exercises the Migrate synchronisation flow against a recording table handler, no database needed.
 */
#[CoversClass(Migrate::class)]
class MigrateFlowTest extends \PHPUnit\Framework\TestCase
{
    /** @var string|null */
    private $yamlFile;

    protected function tearDown(): void
    {
        if (null !== $this->yamlFile && file_exists($this->yamlFile)) {
            unlink($this->yamlFile);
        }
    }

    private function migrate(array $tables, RecordingTableHandler $handler, ?array $target = null): FlowMigrate
    {
        $migrate = new FlowMigrate();
        $migrate->moduleTables = $tables;
        $migrate->tableHandler = $handler;
        $migrate->targetDefinitions = $target;
        return $migrate;
    }

    private static function target(): array
    {
        return [
            'items' => [
                'options' => 'ENGINE=InnoDB',
                'columns' => [
                    ['name' => 'id', 'attributes' => 'int NOT NULL'],
                    ['name' => 'title', 'attributes' => 'varchar(255) NOT NULL'],
                    ['name' => 'added', 'attributes' => 'int NOT NULL DEFAULT 0'],
                ],
                'keys' => [
                    'PRIMARY'   => ['columns' => '`id`', 'unique' => true],
                    'idx_title' => ['columns' => '`title`', 'unique' => true],
                    'idx_new'   => ['columns' => '`added`', 'unique' => false],
                ],
            ],
            'logs' => [
                'options' => 'ENGINE=InnoDB',
                'columns' => [['name' => 'log_id', 'attributes' => 'int NOT NULL']],
            ],
        ];
    }

    public function testGetSynchronizeDdlSynchronizesExistingAndAddsMissingTables(): void
    {
        $handler = new RecordingTableHandler();
        $handler->existingTables = ['items'];
        $handler->existingColumns = ['id' => 'int NOT NULL', 'title' => 'varchar(100) NOT NULL'];
        $handler->dumpTables = ['items' => ['columns' => [
            ['name' => 'id'], ['name' => 'title'], ['name' => 'obsolete'],
        ]]];
        $handler->existingIndexes = [
            'PRIMARY'   => ['columns' => '`id`, `title`', 'unique' => true],
            'idx_title' => ['columns' => '`title`', 'unique' => false],
            'idx_gone'  => ['columns' => '`obsolete`', 'unique' => false],
        ];

        $queue = $this->migrate(['items', 'logs'], $handler, self::target())->getSynchronizeDDL();

        $this->assertSame(['queued'], $queue);
        $this->assertSame([
            ['alterColumn', 'items', 'title', 'varchar(255) NOT NULL'],
            ['addColumn', 'items', 'added', 'int NOT NULL DEFAULT 0'],
            ['dropColumn', 'items', 'obsolete'],
            ['dropPrimaryKey', 'items'],
            ['addPrimaryKey', 'items', '`id`'],
            ['dropIndex', 'idx_title', 'items'],
            ['addIndex', 'idx_title', 'items', '`title`', true],
            ['addIndex', 'idx_new', 'items', '`added`', false],
            ['dropIndex', 'idx_gone', 'items'],
            ['addTable', 'logs'],
            ['setTableOptions', 'logs', 'ENGINE=InnoDB'],
            ['addColumn', 'logs', 'log_id', 'int NOT NULL'],
        ], $handler->calls);
    }

    public function testSynchronizeAddsPrimaryKeyWhenMissing(): void
    {
        $handler = new RecordingTableHandler();
        $handler->existingTables = ['items'];
        $handler->existingColumns = [
            'id' => 'int NOT NULL', 'title' => 'varchar(255) NOT NULL', 'added' => 'int NOT NULL DEFAULT 0',
        ];
        $handler->existingIndexes = [
            'idx_title' => ['columns' => '`title`', 'unique' => true],
            'idx_new'   => ['columns' => '`added`', 'unique' => false],
        ];

        $this->migrate(['items'], $handler, self::target())->getSynchronizeDDL();

        $this->assertSame([['addPrimaryKey', 'items', '`id`']], $handler->calls);
    }

    public function testSynchronizeToleratesMissingIndexList(): void
    {
        $handler = new RecordingTableHandler();
        $handler->existingTables = ['logs'];
        $handler->existingColumns = ['log_id' => 'int NOT NULL'];
        $handler->existingIndexes = false;

        $this->migrate(['logs'], $handler, self::target())->getSynchronizeDDL();

        $this->assertSame([], $handler->calls);
    }

    public function testSynchronizeRejectsTableWithoutTargetDefinition(): void
    {
        $handler = new RecordingTableHandler();
        $handler->existingTables = ['unknown'];

        $this->expectException(SchemaDefinitionException::class);
        $this->migrate(['unknown'], $handler, self::target())->getSynchronizeDDL();
    }

    public function testNonPrimaryKeyMustDeclareUniqueness(): void
    {
        $target = self::target();
        unset($target['items']['keys']['idx_new']['unique']);

        $this->expectException(SchemaDefinitionException::class);
        $this->migrate(['items'], new RecordingTableHandler(), $target)->getSynchronizeDDL();
    }

    public function testNonStringKeyNamesAreIgnored(): void
    {
        $target = ['logs' => [
            'options' => 'ENGINE=InnoDB',
            'columns' => [['name' => 'log_id', 'attributes' => 'int NOT NULL']],
            'keys'    => [0 => ['columns' => '`log_id`', 'unique' => false]],
        ]];
        $handler = new RecordingTableHandler();

        $this->migrate(['logs'], $handler, $target)->getSynchronizeDDL();

        $this->assertSame([
            ['addTable', 'logs'],
            ['setTableOptions', 'logs', 'ENGINE=InnoDB'],
            ['addColumn', 'logs', 'log_id', 'int NOT NULL'],
        ], $handler->calls);
    }

    public function testGetCurrentSchemaAddsTablesTheHandlerDoesNotKnow(): void
    {
        $handler = new RecordingTableHandler();
        $handler->existingTables = ['items'];
        $handler->dumpTables = ['items' => ['name' => 'x_items']];

        $schema = $this->migrate(['items', 'logs'], $handler)->getCurrentSchema();

        $this->assertSame(['items' => ['name' => 'x_items'], 'logs' => ['name' => 'x_logs']], $schema);
        $this->assertSame([['addTable', 'logs']], $handler->calls);
    }

    public function testGetTargetDefinitionsReadsYamlFile(): void
    {
        $this->yamlFile = tempnam(sys_get_temp_dir(), 'xmfmig');
        Yaml::save(self::target(), $this->yamlFile);
        $migrate = $this->migrate([], new RecordingTableHandler());
        $migrate->tableDefinitionFile = $this->yamlFile;

        $this->assertSame(self::target(), $migrate->getTargetDefinitions());
    }

    public function testGetTargetDefinitionsRejectsMissingFile(): void
    {
        $migrate = $this->migrate([], new RecordingTableHandler());
        $migrate->tableDefinitionFile = sys_get_temp_dir() . '/xmf-no-such-schema.yml';

        $this->expectException(SchemaDefinitionException::class);
        $migrate->getTargetDefinitions();
    }

    public function testLastErrorComesFromTheTableHandler(): void
    {
        $handler = new RecordingTableHandler();
        $migrate = $this->migrate([], $handler);

        $this->assertSame('handler error', $migrate->getLastError());
        $this->assertSame(1146, $migrate->getLastErrNo());
    }
}

class FlowMigrate extends Migrate
{
    public $moduleTables;
    public $tableHandler;
    public $targetDefinitions;
    public $tableDefinitionFile;

    public function __construct()
    {
        // skip the parent constructor: it needs a database connection
    }
}

class RecordingTableHandler
{
    public $calls = [];
    public $existingTables = [];
    public $existingColumns = [];
    public $dumpTables = [];
    /** @var array|false */
    public $existingIndexes = [];

    public function __call($name, $args)
    {
        $this->calls[] = array_merge([$name], $args);
        return true;
    }

    // like Tables::addTable(), a new table shows up in dumpTables()
    public function addTable($table)
    {
        $this->calls[] = ['addTable', $table];
        $this->dumpTables[$table] = ['name' => 'x_' . $table];
        return true;
    }

    public function useTable($table)
    {
        return in_array($table, $this->existingTables, true);
    }

    public function getColumnAttributes($table, $column) // NOSONAR $table mirrors the Tables API
    {
        return $this->existingColumns[$column] ?? false;
    }

    public function dumpTables()
    {
        return $this->dumpTables;
    }

    public function getTableIndexes($table) // NOSONAR $table mirrors the Tables API
    {
        return $this->existingIndexes;
    }

    public function dumpQueue()
    {
        return ['queued'];
    }

    public function getLastError()
    {
        return 'handler error';
    }

    public function getLastErrNo()
    {
        return 1146;
    }
}
