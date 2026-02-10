<?php
namespace Xmf\Module\Helper;

require_once(__DIR__.'/../../../../init_new.php');

class GenericHelperTestHelper extends GenericHelper
{
    public static function getHelper($dirname = 'system')
    {
        $instance = new static($dirname);
        return $instance;
    }
}

if (!function_exists('xoops_getHandler')) {
    function xoops_getHandler($name, $optional = false)
    {
        $handler = \Xoops\Core\Handler\Factory::newSpec()->scheme('kernel')->name($name)->optional((bool)$optional)->build();
        return $handler;
    }
}

class GenericHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GenericHelper
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = GenericHelperTestHelper::getHelper();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    public function testGetModule()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testGetConfig()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testGetHandler()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testLoadLanguage()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testSetDebug()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testAddLog()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testIsCurrentModule()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testIsUserAdmin()
    {
        include_once XOOPS_ROOT_PATH . '/kernel/user.php';

        // When the module cannot be loaded from the database, getModule()
        // returns false and getVar() cannot be called on it.
        $module = $this->object->getModule();
        if (false === $module) {
            $this->markTestSkipped('Module object not available (no database connection)');
        }

        $GLOBALS['xoopsUser'] = '';
        $this->assertFalse($this->object->isUserAdmin());

        $GLOBALS['xoopsUser'] = new \XoopsUser();
        $this->assertFalse($this->object->isUserAdmin());
    }

    public function testUrl()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testPath()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testRedirect()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}
