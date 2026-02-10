<?php
namespace Xmf\Test;

use Xmf\Uuid;

class UuidTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Random
     */
    protected $object;
    protected $myClass = '\Xmf\Uuid';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGenerate()
    {
        // match spec for version 4 UUID as per rfc4122
        $uuidMatch = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

        $result = Uuid::generate();
        $this->assertMatchesRegularExpression($uuidMatch, $result);

        $anotherResult = Uuid::generate();
        $this->assertMatchesRegularExpression($uuidMatch, $anotherResult);

        $this->assertNotEquals($result, $anotherResult);
    }

    public function testPackUnpack()
    {
        $uuid = Uuid::generate();
        $binUuid = Uuid::packAsBinary($uuid);
        $strUuid = Uuid::unpackBinary($binUuid);
        $this->assertEquals($uuid, $strUuid);
    }

    public function testInvalidPack()
    {
        $this->expectException('\InvalidArgumentException');
        $binUuid = Uuid::packAsBinary('garbage-data');
    }

    public function testInvalidUnpack()
    {
        $this->expectException('\InvalidArgumentException');
        $binUuid = Uuid::unpackBinary('123456789012345');
    }

    public function testInvalidUnpack2()
    {
        $this->expectException('\UnexpectedValueException');
        $binUuid = Uuid::unpackBinary('0000000000000000');
    }

    /* verify natural sort order is the same for readable and binary formats */
    public function testSortOrder()
    {
        $auuid = [];
        $buuid = [];
        for ($i=1; $i<10; ++$i) {
            $uuid = Uuid::generate();
            $auuid[] = $uuid;
            $buuid[] = Uuid::packAsBinary($uuid);
        }
        sort($auuid);
        sort($buuid);
        foreach ($auuid as $key => $uuid) {
            $this->assertEquals($auuid[$key], Uuid::unpackBinary($buuid[$key]));
        }
    }
}

