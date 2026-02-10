<?php
namespace Xmf\Test;

use Xmf\Random;

class RandomTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Random
     */
    protected $object;
    protected $myClass = '\Xmf\Random';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        // $this->object = new Random();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGenerateOneTimeToken()
    {
        $result = Random::generateOneTimeToken();

        $this->assertTrue(is_string($result));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{128}$/', $result);
    }

    public function testGenerateKey()
    {
        $result = Random::generateKey();

        $this->assertTrue(is_string($result));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{128}$/', $result);
    }
}
