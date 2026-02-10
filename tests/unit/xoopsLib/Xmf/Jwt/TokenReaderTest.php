<?php
namespace Xmf\Test\Jwt;

use Xmf\Jwt\KeyFactory;
use Xmf\Jwt\JsonWebToken;
use Xmf\Jwt\TokenReader;
use Xmf\Key\ArrayStorage;
use Xmf\Key\KeyAbstract;

class TokenReaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ArrayStorage
     */
    protected $storage;

    /**
     * @var KeyAbstract
     */
    protected $testKey;

    /**
     * @var string
     */
    protected $testKeyName = 'x-unit-test-key';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->storage = new ArrayStorage();
        $this->testKey = KeyFactory::build($this->testKeyName, $this->storage);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        $this->storage->delete($this->testKeyName);
    }

    public function testFromString()
    {
        $claims = array('rat' => 'cute');
        $jwt = new JsonWebToken($this->testKey);
        $token = $jwt->create($claims);

        $actual = TokenReader::fromString($this->testKey, $token);
        foreach ($claims as $name => $value) {
            $this->assertEquals($value, $actual->$name);
        }

        $actual = TokenReader::fromString($this->testKey, $token, array('rat' => 'odd'));
        $this->assertFalse($actual);
    }

    public function testFromCookie()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testFromRequest()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testFromHeader()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
