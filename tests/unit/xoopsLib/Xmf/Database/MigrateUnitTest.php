<?php

// Ported from XMF 1.3.2 tests/unit/Database/MigrateTest.php.
namespace Xmf\Test\Database;

use Xmf\Database\Migrate;
use Xmf\Database\SchemaDefinitionException;

class MigrateUnitTest extends \PHPUnit\Framework\TestCase
{
    public function testAddMissingTableThrowsForMalformedColumnDefinition()
    {
        $migrate = new TestableMigrate();
        $migrate->setTargetDefinitions(array(
            'demo' => array(
                'options' => 'ENGINE=InnoDB',
                'columns' => array(
                    array('attributes' => 'int NOT NULL'),
                ),
            ),
        ));
        $migrate->setTableHandler(new FakeMigrateTableHandler());

        $this->expectException(SchemaDefinitionException::class);
        $this->expectExceptionMessage('No schema definition for table demo');

        $migrate->callAddMissingTable('demo');
    }

    public function testAddMissingTableAddsValidatedColumnsAndKeys()
    {
        $migrate = new TestableMigrate();
        $migrate->setTargetDefinitions(array(
            'demo' => array(
                'options' => 'ENGINE=InnoDB',
                'columns' => array(
                    array('name' => 'id', 'attributes' => 'int NOT NULL'),
                    array('name' => 'title', 'attributes' => 'varchar(255) NOT NULL'),
                ),
                'keys' => array(
                    'PRIMARY' => array('columns' => '`id`'),
                    'idx_title' => array('columns' => '`title`', 'unique' => false),
                ),
            ),
        ));
        $handler = new FakeMigrateTableHandler();
        $migrate->setTableHandler($handler);

        $migrate->callAddMissingTable('demo');

        $this->assertSame(
            array(
                array('addTable', 'demo'),
                array('setTableOptions', 'demo', 'ENGINE=InnoDB'),
                array('addColumn', 'demo', 'id', 'int NOT NULL'),
                array('addColumn', 'demo', 'title', 'varchar(255) NOT NULL'),
                array('addPrimaryKey', 'demo', '`id`'),
                array('addIndex', 'idx_title', 'demo', '`title`', false),
            ),
            $handler->calls
        );
    }

    public function testSynchronizeTableThrowsForMalformedKeyDefinition()
    {
        $migrate = new TestableMigrate();
        $migrate->setTargetDefinitions(array(
            'demo' => array(
                'columns' => array(
                    array('name' => 'id', 'attributes' => 'int NOT NULL'),
                ),
                'keys' => array(
                    'idx_title' => array('columns' => '`title`', 'unique' => 'yes'),
                ),
            ),
        ));
        $handler = new FakeMigrateTableHandler();
        $handler->existingColumns = array('id' => 'int NOT NULL');
        $handler->dumpTables = array(
            'demo' => array(
                'columns' => array(
                    array('name' => 'id'),
                ),
            ),
        );
        $handler->existingIndexes = array();
        $migrate->setTableHandler($handler);

        $this->expectException(SchemaDefinitionException::class);
        $this->expectExceptionMessage('No schema definition for table demo');

        $migrate->callSynchronizeTable('demo');
    }

    public function testSynchronizeTableDropsPrimaryKeyWhenTargetOmitsIt()
    {
        $migrate = new TestableMigrate();
        $migrate->setTargetDefinitions(array(
            'demo' => array(
                'columns' => array(
                    array('name' => 'id', 'attributes' => 'int unsigned NOT NULL'),
                    array('name' => 'title', 'attributes' => 'varchar(255) NOT NULL'),
                ),
            ),
        ));
        $handler = new FakeMigrateTableHandler();
        $handler->existingColumns = array('id' => 'int NOT NULL');
        $handler->dumpTables = array(
            'demo' => array(
                'columns' => array(
                    array('name' => 'id'),
                    array('name' => 'obsolete'),
                ),
            ),
        );
        $handler->existingIndexes = array(
            'PRIMARY' => array('columns' => '`id`', 'unique' => true),
            'idx_old' => array('columns' => '`obsolete`', 'unique' => false),
        );
        $migrate->setTableHandler($handler);

        $migrate->callSynchronizeTable('demo');

        $this->assertContains(array('alterColumn', 'demo', 'id', 'int unsigned NOT NULL'), $handler->calls);
        $this->assertContains(array('addColumn', 'demo', 'title', 'varchar(255) NOT NULL'), $handler->calls);
        $this->assertContains(array('dropColumn', 'demo', 'obsolete'), $handler->calls);
        $this->assertContains(array('dropPrimaryKey', 'demo'), $handler->calls);
        $this->assertContains(array('dropIndex', 'idx_old', 'demo'), $handler->calls);
    }
}

class TestableMigrate extends Migrate
{
    public function __construct()
    {
    }

    public function setTargetDefinitions(array $targetDefinitions): void
    {
        $this->targetDefinitions = $targetDefinitions;
    }

    public function setTableHandler(object $tableHandler): void
    {
        $this->tableHandler = $tableHandler;
    }

    public function callAddMissingTable(string $tableName): void
    {
        $this->addMissingTable($tableName);
    }

    public function callSynchronizeTable(string $tableName): void
    {
        $this->synchronizeTable($tableName);
    }
}

class FakeMigrateTableHandler
{
    public array $calls = array();
    public array $existingColumns = array();
    public array $dumpTables = array();
    public array|false $existingIndexes = array();

    public function addTable(string $tableName): bool
    {
        $this->calls[] = array('addTable', $tableName);
        return true;
    }

    public function setTableOptions(string $tableName, string $options): bool
    {
        $this->calls[] = array('setTableOptions', $tableName, $options);
        return true;
    }

    public function addColumn(string $tableName, string $name, string $attributes): bool
    {
        $this->calls[] = array('addColumn', $tableName, $name, $attributes);
        return true;
    }

    public function addPrimaryKey(string $tableName, string $columns): bool
    {
        $this->calls[] = array('addPrimaryKey', $tableName, $columns);
        return true;
    }

    public function addIndex(string $name, string $tableName, string $columns, bool $unique): bool
    {
        $this->calls[] = array('addIndex', $name, $tableName, $columns, $unique);
        return true;
    }

    public function getColumnAttributes(string $tableName, string $name): string|false
    {
        return $this->existingColumns[$name] ?? false;
    }

    public function alterColumn(string $tableName, string $name, string $attributes): bool
    {
        $this->calls[] = array('alterColumn', $tableName, $name, $attributes);
        return true;
    }

    public function dumpTables(): array
    {
        return $this->dumpTables;
    }

    public function getTableIndexes(string $tableName): array|false
    {
        return $this->existingIndexes;
    }

    public function dropPrimaryKey(string $tableName): bool
    {
        $this->calls[] = array('dropPrimaryKey', $tableName);
        return true;
    }

    public function dropIndex(string $name, string $tableName): bool
    {
        $this->calls[] = array('dropIndex', $name, $tableName);
        return true;
    }

    public function dropColumn(string $tableName, string $name): bool
    {
        $this->calls[] = array('dropColumn', $tableName, $name);
        return true;
    }
}
