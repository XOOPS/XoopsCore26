<?php
require_once(__DIR__ . '/../../../../init_new.php');

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\PDO\SQLite\Driver;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Xoops\Core\Database\Connection;

/**
 * safeQuery() return values, against an in-memory SQLite database
 */
#[CoversClass(Connection::class)]
#[RequiresPhpExtension('pdo_sqlite')]
class ConnectionSafeQueryTest extends \PHPUnit\Framework\TestCase
{
    /** @var Connection */
    private $db;

    protected function setUp(): void
    {
        $this->db = new Connection(['driver' => 'pdo_sqlite', 'memory' => true], new Driver(), new Configuration());
        $this->db->setSafe(true);
    }

    public function testDdlThatChangesNoRowsReportsSuccess(): void
    {
        $this->assertTrue($this->db->query('CREATE TABLE items (id INTEGER, title TEXT)'));
    }

    public function testWriteReturnsAffectedRowCount(): void
    {
        $this->db->query('CREATE TABLE items (id INTEGER, title TEXT)');

        $this->assertSame(2, $this->db->query("INSERT INTO items VALUES (1, 'a'), (2, 'b')"));
        $this->assertSame(1, $this->db->query("UPDATE items SET title = 'c' WHERE id = 2"));
    }

    public function testWriteMatchingNoRowsReportsSuccess(): void
    {
        $this->db->query('CREATE TABLE items (id INTEGER, title TEXT)');

        $this->assertTrue($this->db->query("UPDATE items SET title = 'x' WHERE id = 99"));
        $this->assertTrue($this->db->safeQuery('DELETE FROM items'));
    }

    public function testSelectReturnsResult(): void
    {
        $this->db->query('CREATE TABLE items (id INTEGER)');

        $result = $this->db->query('SELECT * FROM items');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->fetchAssociative());
    }

    public function testFailedStatementReturnsNull(): void
    {
        $this->assertNull($this->db->query('CREATE TABLE broken ('));
        $this->assertNull($this->db->query('SELECT * FROM missing_table'));
    }

    public function testWriteIsRefusedWhenNotSafe(): void
    {
        $this->db->query('CREATE TABLE items (id INTEGER)');
        $this->db->setSafe(false);

        $this->assertNull($this->db->query('INSERT INTO items VALUES (1)'));
        $this->assertInstanceOf(Result::class, $this->db->query('SELECT * FROM items'));

        $this->db->setForce(true);
        $this->assertSame(1, $this->db->query('INSERT INTO items VALUES (1)'));
        $this->assertFalse($this->db->getForce(), 'force applies to one statement');
    }
}
