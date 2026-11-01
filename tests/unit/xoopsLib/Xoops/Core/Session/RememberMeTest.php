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
     * @var int random id so the cache entries cannot belong to a real user or another run
     */
    protected $userId;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new RememberMe;
        $this->userId = random_int(1000000000, 2000000000);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        \Xoops::getInstance()->cache()->delete("user/{$this->userId}/usercookie");
        \Xoops::getInstance()->cache()->delete("user/{$this->userId}/usercookie_gen");
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
        $userId = $this->userId;
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
        $userId = $this->userId;
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

    public function testInvalidateAllForUserRejectsConcurrentLogin()
    {
        $userId = $this->userId;
        new RememberMeTestDouble()->createUserCookie($userId);

        // a login reads the tokens while the revocation runs and writes them back after it
        $login = new RememberMeTestDouble();
        $login->deferWrite = true;
        $revoke = new RememberMeTestDouble();
        $revoke->beforeWrite = function () use ($login, $userId) {
            $login->createUserCookie($userId);
        };
        $revoke->invalidateAllForUser($userId);
        ($login->pendingWrite)();

        $next = new RememberMeTestDouble();
        $next->cookie = $login->cookie;
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
    public $deferWrite = false;
    public $pendingWrite = null;

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
        if ($this->deferWrite) {
            $this->deferWrite = false;
            $this->pendingWrite = function () use ($userId) {
                parent::writeUserTokens($userId);
            };
            return;
        }
        parent::writeUserTokens($userId);
    }
}
