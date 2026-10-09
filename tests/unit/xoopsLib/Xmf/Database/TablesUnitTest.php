<?php

// Ported from XMF 1.3.2 tests/unit/Database/TablesTest.php. The execSql() routing
// tests are left out: 2.6 keeps its own Doctrine-based execSql().
namespace Xmf\Test\Database;

use Xmf\Database\Tables;

class TablesUnitTest extends \PHPUnit\Framework\TestCase
{
    public function testRenderTableCreateReturnsFalseForMalformedColumnDefinition()
    {
        $tables = new TestableTables();
        $tables->setTables(
            array(
                'demo' => array(
                    'options' => 'ENGINE=InnoDB',
                    'columns' => array(
                        array('attributes' => 'int NOT NULL'),
                    ),
                ),
            )
        );

        $this->assertFalse($tables->callRenderTableCreate('demo'));
    }

    public function testRenderTableCreateIncludesPrimaryAndUniqueKeys()
    {
        $tables = new TestableTables();
        $tables->setTables(
            array(
                'demo' => array(
                    'name' => 'xoops_demo',
                    'options' => 'ENGINE=InnoDB',
                    'columns' => array(
                        array('name' => 'id', 'attributes' => 'int NOT NULL'),
                        array('name' => 'title', 'attributes' => 'varchar(255) NOT NULL'),
                    ),
                    'keys' => array(
                        'PRIMARY' => array('columns' => '`id`'),
                        'idx_title' => array('columns' => '`title`', 'unique' => true),
                    ),
                ),
            )
        );

        $sql = $tables->callRenderTableCreate('demo', true);

        $this->assertStringContainsString('CREATE TABLE `xoops_demo`', $sql);
        $this->assertStringContainsString('PRIMARY KEY (`id`)', $sql);
        $this->assertStringContainsString('UNIQUE KEY idx_title (`title`)', $sql);
    }

    public function testInsertSkipsMalformedColumnDefinitionWithWarning()
    {
        $tables = new TestableTables();
        $tables->setDb(new FakeTablesDatabase());
        $tables->setTables(
            array(
                'demo' => array(
                    'name' => 'xoops_demo',
                    'columns' => array(
                        array('name' => 'id', 'attributes' => 'int NOT NULL'),
                        array('attributes' => 'varchar(255) NOT NULL'),
                    ),
                ),
            )
        );

        $warning = $this->captureWarning(static function () use ($tables): void {
            $tables->insert('demo', array('id' => 7));
        });

        $this->assertStringContainsString('Skipping malformed column definition in Xmf\Database\Tables::insert', $warning);
        $this->assertSame("INSERT INTO `xoops_demo` (`id`) VALUES('7')", $tables->dumpQueue()[0]);
    }

    public function testUpdateSkipsMalformedColumnDefinitionWithWarning()
    {
        $tables = new TestableTables();
        $tables->setDb(new FakeTablesDatabase());
        $tables->setTables(
            array(
                'demo' => array(
                    'name' => 'xoops_demo',
                    'columns' => array(
                        array('name' => 'title', 'attributes' => 'varchar(255) NOT NULL'),
                        array('attributes' => 'varchar(255) NOT NULL'),
                    ),
                ),
            )
        );

        $warning = $this->captureWarning(static function () use ($tables): void {
            $tables->update('demo', array('title' => 'Updated'), 'WHERE id = 1');
        });

        $this->assertStringContainsString('Skipping malformed column definition in Xmf\Database\Tables::update', $warning);
        $this->assertSame("UPDATE `xoops_demo` SET `title` = 'Updated' WHERE id = 1", $tables->dumpQueue()[0]);
    }

    public function testInsertReturnsFalseWhenNoValidColumnsMatch()
    {
        $tables = new TestableTables();
        $tables->setDb(new FakeTablesDatabase());
        $tables->setTables(
            array(
                'demo' => array(
                    'name' => 'xoops_demo',
                    'columns' => array(
                        array('name' => 'id', 'attributes' => 'int NOT NULL'),
                    ),
                ),
            )
        );

        $this->assertFalse($tables->insert('demo', array('title' => 'Ignored')));
        $this->assertSame('No valid columns supplied for insert', $tables->getLastError());
        $this->assertSame(-1, $tables->getLastErrNo());
        $this->assertSame(array(), $tables->dumpQueue());
    }

    public function testUpdateReturnsFalseWhenNoValidColumnsMatch()
    {
        $tables = new TestableTables();
        $tables->setDb(new FakeTablesDatabase());
        $tables->setTables(
            array(
                'demo' => array(
                    'name' => 'xoops_demo',
                    'columns' => array(
                        array('name' => 'id', 'attributes' => 'int NOT NULL'),
                    ),
                ),
            )
        );

        $this->assertFalse($tables->update('demo', array('title' => 'Ignored'), 'WHERE id = 1'));
        $this->assertSame('No valid columns supplied for update', $tables->getLastError());
        $this->assertSame(-1, $tables->getLastErrNo());
        $this->assertSame(array(), $tables->dumpQueue());
    }

    public function testQuoteDefaultClauseEscapesSingleQuotes()
    {
        $tables = new TestableTables();
        $result = $tables->callQuoteDefaultClause("O'Reilly");
        $this->assertSame(" DEFAULT 'O''Reilly' ", $result);
    }

    public function testQuoteDefaultClauseNullReturnsEmpty()
    {
        $tables = new TestableTables();
        $result = $tables->callQuoteDefaultClause(null);
        $this->assertSame('', $result);
    }

    public function testQuoteDefaultClauseCurrentTimestamp()
    {
        $tables = new TestableTables();
        $result = $tables->callQuoteDefaultClause('CURRENT_TIMESTAMP');
        $this->assertSame(' DEFAULT CURRENT_TIMESTAMP ', $result);
    }





    private function captureWarning(callable $callback): string
    {
        $warning = '';

        set_error_handler(static function (int $errno, string $errstr) use (&$warning): bool {
            $warning = $errstr;
            return true;
        });

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $warning;
    }
}

class TestableTables extends Tables
{
    public function __construct()
    {
    }

    public function setDb(object $db): void
    {
        $this->db = $db;
    }

    public function setTables(array $tables): void
    {
        $this->tables = $tables;
        $this->queue = array();
    }

    public function callRenderTableCreate(string $table, bool $prefixed = false): string|false
    {
        return $this->renderTableCreate($table, $prefixed);
    }

    public function callQuoteDefaultClause(?string $default): string
    {
        return $this->quoteDefaultClause($default);
    }

    /**
     * @return \mysqli_result|bool
     */
}

class FakeTablesDatabase
{
    public function quote(mixed $value): string
    {
        return "'" . addslashes((string) $value) . "'";
    }
}
