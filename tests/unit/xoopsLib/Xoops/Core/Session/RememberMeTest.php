<?php
require_once __DIR__.'/../../../../init_new.php';

use Xoops\Core\Session\RememberMe;

class RememberMeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RememberMe
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new RememberMe;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        foreach ([900001, 900002] as $userId) {
            \Xoops::getInstance()->cache()->delete("user/{$userId}/usercookie");
            \Xoops::getInstance()->cache()->delete("user/{$userId}/usercookie_gen");
        }
    }

    public function testRecall()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testForget()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testInvalidateAllForUser()
    {
        $userId = 900001;
        $remember = new RememberMeTestDouble();
        $remember->createUserCookie($userId);
        $cookie = $remember->cookie;
        $this->assertSame($userId, $remember->recall());

        new RememberMeTestDouble()->invalidateAllForUser($userId);

        $stale = new RememberMeTestDouble();
        $stale->cookie = $remember->cookie;
        $this->assertFalse($stale->recall());
        $stale->cookie = $cookie;
        $this->assertFalse($stale->recall());
    }

    public function testInvalidateAllForUserSurvivesConcurrentRecall()
    {
        $userId = 900002;
        $remember = new RememberMeTestDouble();
        $remember->createUserCookie($userId);

        // a password reset revokes the tokens after recall() read them but before it writes them back
        $remember->beforeWrite = function () use ($userId) {
            new RememberMeTestDouble()->invalidateAllForUser($userId);
        };
        $this->assertSame($userId, $remember->recall());

        $next = new RememberMeTestDouble();
        $next->cookie = $remember->cookie;
        $this->assertFalse($next->recall());
    }

    public function testCreateUserCookie()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}

/**
 * Keeps the cookie in memory and can run a callback just before tokens are written
 */
class RememberMeTestDouble extends RememberMe
{
    public $cookie = false;
    public $beforeWrite = null;

    protected function readUserCookie()
    {
        return $this->cookie;
    }

    protected function writeUserCookie($cookieData, $expire = 2592000)
    {
        $this->cookie = is_array($cookieData) ? $cookieData : false;
    }

    protected function writeUserTokens($userId)
    {
        if (null !== $this->beforeWrite) {
            $callback = $this->beforeWrite;
            $this->beforeWrite = null;
            $callback();
        }
        parent::writeUserTokens($userId);
    }
}
