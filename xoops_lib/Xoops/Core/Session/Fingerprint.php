<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core\Session;

use Xmf\Security\Format;
use Xmf\Security\Serializer;
use Xoops\Core\AttributeInterface;
use Xoops\Core\HttpRequest;

/**
 * Session management
 *
 * @category  Xoops\Core\Session
 * @package   Fingerprint
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class Fingerprint implements FingerprintInterface
{
    /**
     * The current request's client IP
     *
     * @var string
     */
    protected $clientFingerprint = array();

    /**
     * grab things from the http request we need to use.
     *
     * @return string[] array of fingerprint values
     */
    protected function takePrint()
    {
        $clientFingerprint = array();
        $httpRequest = HttpRequest::getInstance();
        $clientFingerprint['clientIp'] = $httpRequest->getClientIp();
        $clientFingerprint['userAgent'] = $this->makeInert($httpRequest->getHeader('USER_AGENT'));
        $clientFingerprint['acceptLanguage'] = $this->makeInert($httpRequest->getHeader('ACCEPT_LANGUAGE'));

        return $clientFingerprint;
    }

    /**
     * Neutralize some sequences that might be used to slip nefarious bits into our fingerprint.
     * This does not impair the similarity check, but does interfere with serialized object injection.
     *
     * @param string $value fingerprint string to be escaped
     *
     * @return string
     */
    protected function makeInert($value)
    {
        return str_replace(['\\', '{', '}', ':'], '-', $value);
    }

    /**
     * This method manages the session fingerprint
     *
     * Check current client Fingerprint against the values saved in the session.
     * Save the current Fingerprint to the session
     * Rate the fingerprint match pass/fail based on any changes
     * On fail, clear the session, leaving only the new client fingerprint
     *
     * @param AttributeInterface $session session manager object or another
     *                                    AttributeInterface implementing object
     *
     * @return bool true if matched, false if not
     */
    public function checkSessionPrint(AttributeInterface $session)
    {
        $score = 0;   // combined levenshtein distance of changes
        $changes = 0; // number of changed fields
        $currentFingerprint = $this->takePrint();
        $savedFingerprint = Serializer::tryFrom((string) $session->get('SESSION_FINGERPRINT'), false, Format::PHP);
        if ($savedFingerprint === false) {
            $savedFingerprint = $currentFingerprint;
            $changes = empty($_SESSION) ? 0 : 3; // in a populated session - force fail;
        }

        foreach ($currentFingerprint as $key => $current) {
            $distance = levenshtein($current, $savedFingerprint[$key]);
            $score += $distance;
            $changes += ($distance>0) ? 1 : 0;
        }

        $return = true;

        // if more than one field changed, or if that change is a distance greater than 30, fail it.
        if (($changes > 1) || ($score > 30)) {
            $session->clear(); // session data should not be preserved
            $return = false;
        }
        $session->set('SESSION_FINGERPRINT', serialize($currentFingerprint));
        return $return;
    }
}
