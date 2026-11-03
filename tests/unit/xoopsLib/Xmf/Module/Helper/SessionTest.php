<?php
namespace Xmf\Module\Helper;

require_once(__DIR__.'/../../../../init_new.php');

class SessionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Session
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    /** @var array<string, mixed> */
    private $savedSession = [];

    protected function setUp(): void
    {
        $this->savedSession = $_SESSION ?? [];
        $this->object = new Session;
    }

    /**
     * Restores $_SESSION, also when an assertion failed.
     */
    protected function tearDown(): void
    {
        $_SESSION = $this->savedSession;
    }

    public function testInit()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testSet()
    {
        // a value get() could not read back is refused instead of stored
        $this->expectException(\RuntimeException::class);
        $this->object->set('big', str_repeat('a', 5000000));
    }

    public function testGet()
    {
        $value = ['token' => "bin\0ary", 'list' => [1, 'two']];
        $this->object->set('roundtrip', $value);
        $this->assertSame($value, $this->object->get('roundtrip'));

        $this->assertSame('dflt', $this->object->get('never-set', 'dflt'));

        // a stored enum or object is refused, not restored
        $this->object->set('enum', [SessionTestEnum::On]);
        $this->assertFalse($this->object->get('enum', 'dflt'));
    }

    public function testDel()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testDestroy()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}

enum SessionTestEnum
{
    case On;
}
