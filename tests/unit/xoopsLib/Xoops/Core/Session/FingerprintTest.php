<?php
require_once __DIR__.'/../../../../init_new.php';

use Xoops\Core\Session\Fingerprint;

class FingerprintTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Fingerprint
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Fingerprint;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    /**
     * the fingerprint this request produces, as checkSessionPrint() stores it
     *
     * @return string[]
     */
    private function currentPrint()
    {
        $session = new \Xoops\Core\XoopsArray();
        $this->object->checkSessionPrint($session);

        return unserialize($session->get('SESSION_FINGERPRINT'), array('allowed_classes' => false));
    }

    public function testCheckSessionPrintAcceptsItsOwnPrint()
    {
        $session = new \Xoops\Core\XoopsArray();
        $session->set('SESSION_FINGERPRINT', serialize($this->currentPrint()));
        $session->set('kept', 'yes');

        $this->assertTrue($this->object->checkSessionPrint($session));
        $this->assertSame('yes', $session->get('kept'));
    }

    public static function malformedPrints()
    {
        return array(
            'scalar' => array(serialize('192.0.2.1')),
            'empty array' => array(serialize(array())),
            'missing field' => array(serialize(array('clientIp' => '192.0.2.1', 'userAgent' => 'x'))),
            'non-string field' => array(serialize(
                array('clientIp' => array('192.0.2.1'), 'userAgent' => 'x', 'acceptLanguage' => 'en')
            )),
            'object' => array('O:8:"stdClass":0:{}'),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('malformedPrints')]
    public function testCheckSessionPrintFailsAMalformedPrintInAPopulatedSession($stored)
    {
        $current = $this->currentPrint();
        $session = new \Xoops\Core\XoopsArray();
        $session->set('SESSION_FINGERPRINT', $stored);
        $session->set('dropped', 'yes');

        $hadSession = isset($_SESSION);
        $saved = $hadSession ? $_SESSION : null;
        $_SESSION = array('populated' => true);
        try {
            $this->assertFalse($this->object->checkSessionPrint($session));
        } finally {
            if ($hadSession) {
                $_SESSION = $saved;
            } else {
                unset($_SESSION);
            }
        }

        $this->assertNull($session->get('dropped'));
        $this->assertSame(serialize($current), $session->get('SESSION_FINGERPRINT'));
    }

    public function testContracts()
    {
        $this->assertInstanceOf('\Xoops\Core\Session\Fingerprint', $this->object);
        $this->assertInstanceOf('\Xoops\Core\Session\FingerprintInterface', $this->object);
    }

    public function testCheckSessionPrint()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
