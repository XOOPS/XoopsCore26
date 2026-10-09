<?php
namespace Xmf\Test\Jwt;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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

    // ported from XMF 1.3.2 tests/unit
    /**
     * Test fromHeader by running in a separate process to avoid static cache issues.
     */
    #[RunInSeparateProcess]
    public function testFromHeaderWithBearerScheme()
    {
        $claims = array('rat' => 'cute');
        $jwt = new JsonWebToken($this->testKey);
        $token = $jwt->create($claims, 60);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        $actual = TokenReader::fromHeader($this->testKey, $claims);
        $this->assertIsObject($actual);
        $this->assertSame('cute', $actual->rat);
    }

    #[RunInSeparateProcess]
    public function testFromHeaderRejectsNonBearerScheme()
    {
        // a valid token under another scheme, so only the scheme check can reject it
        $jwt = new JsonWebToken($this->testKey);
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic ' . $jwt->create(array('rat' => 'cute'), 60);
        $actual = TokenReader::fromHeader($this->testKey);
        $this->assertFalse($actual);
    }

    #[RunInSeparateProcess]
    public function testFromHeaderAcceptsBareTokenOnCustomHeader()
    {
        $claims = array('rat' => 'cute');
        $jwt = new JsonWebToken($this->testKey);
        $token = $jwt->create($claims, 60);

        $_SERVER['HTTP_X_AUTH_TOKEN'] = $token;
        $actual = TokenReader::fromHeader($this->testKey, $claims, 'X-Auth-Token');
        $this->assertIsObject($actual);
        $this->assertSame('cute', $actual->rat);
    }

    #[RunInSeparateProcess]
    public function testFromHeaderRejectsEmptyHeader()
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $actual = TokenReader::fromHeader($this->testKey);
        $this->assertFalse($actual);
    }
}
