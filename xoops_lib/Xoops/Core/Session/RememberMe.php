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

use Xmf\Random;
use Xmf\Request;
use Xoops\Core\HttpRequest;

/**
 * Provide Remember Me functionality to restore a user's login state in a new session
 *
 * This incorporates ideas from Barry Jaspan's article found here:
 * http://jaspan.com/improved_persistent_login_cookie_best_practice
 *
 * There are problems with most of the published articles on the subject of persitent
 * authorization cookies, most specifically when dealing with concurrency issues in the
 * modern web. If two or more requests from the same browser instance arrive at the server
 * in a short time (i.e. impatient reload, restored tabs) all presenting the same one use
 * token in the auth cookie, one will work, and the others will fail.
 *
 * Using this functionality is a security risk. Ideally, this should only be used over ssl,
 * but even then, the possibility of cookie theft still exists. Present that stolen cookie
 * and the thief can become the authorized user. The following details the steps taken to
 * provide a smooth user experience while minimizing the exposure surface of this risk.
 *
 * Each time a new persistent auth cookie is requested, a new "series" is started.
 * Associated with the series is a one time token, that changes whenever it is used.
 * To "debounce" any concurrent requests:
 *      Instead of erasing the old token immediately, a short expire time is set.
 *      If a cookie is used with the expiring token, it is updated to the new session.
 *      After the expire time elapses, the old token is erased.
 * If a cookie with an invalid series is presented, it is erased and ignored.
 * If a cookie has a valid series, but an unknown token, we treat this as evidence of a stolen
 * cookie or hack attempt and clear all stored series/tokens associated with the user.
 *
 * Additionally, the surrounding application logic is aware that the persistent auth logic
 * was used. We only supply a saved id, the application must process that id. That "fact" can
 * be saved to require authentication confirmation as appropriate.
 *
 * @category  Xoops\Core\Session
 * @package   RememberMe
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class RememberMe
{

    /**
     * @var array
     */
    protected $userTokens = array();

    /**
     * @var integer
     */
    protected $userId = 0;

    /**
     * @var \Xoops
     */
    protected $xoops = null;

    /**
     * @var integer
     */
    protected $now = 0;

    /**
     * @var string revocation generation; tokens tagged with any other one are discarded
     */
    protected $generation = '';

    /**
     * constructor
     */
    public function __construct()
    {
        $this->xoops = \Xoops::getInstance();
        $this->now = time();
    }


    /**
     * Recall a user id from the "remember me" cookie.
     *
     * @return integer|false user id, or false if non-exisiting or invalid cookie
     */
    public function recall()
    {
        $this->now = time();
        $cookieData = $this->readUserCookie();
        if (false === $cookieData) {
            return false;   // no or invalid cookie
        }
        list($userId, $series, $token) = $cookieData;
        $this->readUserTokens($userId);
        if ($this->hasSeriesToken($series, $token)) {
            $values = $this->getSeriesToken($series, $token);
            // debounce concurrent requests
            if (isset($values['next_token'])) {
                // this token was already replaced, use replacement to update cookie
                $nextToken = $values['next_token'];
            } else {
                // issue a new token for this series
                $nextToken = $this->getNewToken();
                // expire old token, and forward to the new one
                $values = array('expires_at' => $this->now + 10, 'next_token' => $nextToken);
                $this->setSeriesToken($series, $token, $values);
                // register the new token
                $values = array('expires_at' => $this->now + 2592000);
                $this->setSeriesToken($series, $nextToken, $values);
            }
            $cookieData = array($userId, $series, $nextToken);
            $this->writeUserCookie($cookieData);
            $return = $userId;
        } else {
            // cookie is not valid
            if ($this->hasSeries($series)) {
                // We have a valid series, but an invalid token.
                // Highly possible token was comprimised. Invalidate all saved tokens;
                $this->clearUserTokens();
            }
            $this->clearUserCookie();
            $return = false;
        }
        $this->writeUserTokens($userId);
        return $return;
    }

    /**
     * Forget a "remember me" cookie. This should be invoked if a user explicitly
     * logs out of a session. If a cookie is set for this session, this will clear it
     * and remove the associated series tokens.
     *
     * @return void
     */
    public function forget()
    {
        $this->now = time();
        $cookieData = $this->readUserCookie();
        if (false !== $cookieData) {
            list($userId, $series, $token) = $cookieData;
            $this->readUserTokens($userId);
            $this->unsetSeries($series);
            $this->writeUserTokens($userId);
        }
        $this->clearUserCookie();
    }

    /**
     * Invalidate all existing "remember me" cookie by deleting all the series/tokens
     *
     * This should be called during a password change. A fresh generation also voids
     * tokens that a concurrent recall() writes back after this call. It is random rather
     * than a counter so concurrent revocations cannot collapse into one. Tokens refresh
     * their 30 day lifetime on every recall but the generation is never rewritten (a
     * rewrite could restore a revoked value), so it is kept for ten years; a null TTL
     * would mean the cache default of five days. If it is evicted, tagged tokens fail closed.
     *
     * @param integer $userId id of user associated with the sessions/tokens to be invalidated
     *
     * @return void
     */
    public function invalidateAllForUser($userId)
    {
        $this->readUserTokens($userId);
        $this->xoops->cache()->write("user/{$userId}/usercookie_gen", Random::generateKey(), 315360000);
        $this->clearUserTokens();
        $this->writeUserTokens($userId);
    }

    /**
     * Check if the given series exists
     *
     * @param string $series series identifier
     *
     * @return boolean true if series exists, otherwise false
     */
    protected function hasSeries($series)
    {
        return isset($this->userTokens[$series]);
    }

    /**
     * Unset an entire series
     *
     * @param string $series series identifier
     *
     * @return void
     */
    protected function unsetSeries($series)
    {
        unset($this->userTokens[$series]);
    }

    /**
     * Get the values associated with a given series and token
     *
     * @param string $series series identifier
     * @param string $token  token to check
     *
     * @return boolean true if series and token combination exists, otherwise false
     */
    protected function hasSeriesToken($series, $token)
    {
        return isset($this->userTokens[$series][$token]);
    }

    /**
     * Get the values associated with a given series and token
     *
     * @param string $series series identifier
     * @param string $token  token to check
     *
     * @return array|false
     */
    protected function getSeriesToken($series, $token)
    {
        if (isset($this->userTokens[$series][$token])) {
            return $this->userTokens[$series][$token];
        }
        return false;
    }

    /**
     * Get the values associated with a given series and token
     *
     * @param string $series series identifier
     * @param string $token  token to check
     * @param array  $values valuestoken to check
     *
     * @return void
     */
    protected function setSeriesToken($series, $token, $values)
    {
        $values['gen'] = $this->generation;
        $this->userTokens[$series][$token] = $values;
    }

    /**
     * Get the values associated with a given series and token
     *
     * @param string $series series identifier
     * @param string $token  token to check
     *
     * @return void
     */
    protected function unsetSeriesToken($series, $token)
    {
        unset($this->userTokens[$series][$token]);
    }

    /**
     * read existing user tokens from persistent storage
     *
     * @param integer $userId id of user to read tokens for
     *
     * @return void
     */
    protected function readUserTokens($userId)
    {
        $key = "user/{$userId}/usercookie";
        $this->generation = (string) $this->xoops->cache()->read("user/{$userId}/usercookie_gen");
        $this->userTokens = $this->xoops->cache()->read($key);
        if (false === $this->userTokens) {
            $this->clearUserTokens();
        }
        $this->removeExpiredTokens();
    }

    /**
     * write the existing user tokens to persistent storage
     *
     * @param integer $userId id of user to write tokens for
     *
     * @return void
     */
    protected function writeUserTokens($userId)
    {
        $key = "user/{$userId}/usercookie";
        $this->xoops->cache()->write($key, $this->userTokens, 2592000);
    }

    /**
     * Remove any expired or revoked tokens
     *
     * @return void
     */
    protected function removeExpiredTokens()
    {
        $now = $this->now;
        $userTokens = $this->userTokens;
        foreach ($userTokens as $series => $tokens) {
            foreach ($tokens as $token => $values) {
                if ((isset($values['expires_at']) && $values['expires_at'] < $now)
                    || ($values['gen'] ?? '') !== $this->generation
                ) {
                    $this->unsetSeriesToken($series, $token);
                }
            }
        }
        $userTokens = $this->userTokens;
        foreach ($userTokens as $series => $tokens) {
            if (empty($tokens)) {
                $this->unsetSeries($series);
            }
        }
    }

    /**
     * Clear all tokens for this user
     * @return void
     */
    protected function clearUserTokens()
    {
        $this->userTokens = array();
    }

    /**
     * Generate a new series
     *
     * @return string a new series key
     */
    protected function getNewSeries()
    {
        return Random::generateKey();
    }

    /**
     * Generate a new token
     *
     * @return string a new token
     */
    protected function getNewToken()
    {
        return Random::generateOneTimeToken();
    }

    /**
     * Create a new user cookie, usually in response to login with "remember me" selected
     *
     * @param integer $userId id of user to be remembered
     *
     * @return void
     **/
    public function createUserCookie($userId)
    {
        $this->readUserTokens($userId);
        $this->now = time();
        $series = $this->getNewSeries();
        $token = $this->getNewToken();
        $cookieData = array($userId, $series, $token);
        $this->setSeriesToken($series, $token, array('expires_at' => $this->now + 2592000));
        $this->writeUserCookie($cookieData);
        $this->writeUserTokens($userId);
    }

    /**
     * Update cookie status for current session
     *
     * @return void
     **/
    protected function clearUserCookie()
    {
        $this->writeUserCookie('', -3600);
    }

    /**
     * Read the user cookie
     *
     * @return array|false the cookie data as array(userid, series, token), or
     *                     false if cookie does not exist (or not configured)
     */
    protected function readUserCookie()
    {
        $usercookie = $this->xoops->getConfig('usercookie');
        if (empty($usercookie)) {
            return false; // remember me is not configured
        }

        $usercookie = $this->xoops->getConfig('usercookie');
        $notFound = 'Nosuchcookie';
        $cookieData = Request::getString($usercookie, $notFound, 'COOKIE');
        if ($cookieData !== $notFound) {
            $temp = explode('-', $cookieData);
            if (count($temp) == 3) {
                $temp[0] = (integer) $temp[0];
                return $temp;
            }
            $this->clearUserCookie(); // clean up garbage cookie
        }
        return false;
    }

    /**
     * Update cookie status for current session
     *
     * @param array|string $cookieData usercookie value
     * @param integer      $expire     seconds until usercookie expires
     *
     * @return void
     **/
    protected function writeUserCookie($cookieData, $expire = 2592000)
    {
        $usercookie = $this->xoops->getConfig('usercookie');
        if (empty($usercookie)) {
            return; // remember me is not configured
        }
        if (is_array($cookieData)) {
            $cookieData = implode('-', $cookieData);
        }
        $httpRequest = HttpRequest::getInstance();
        $path = \XoopsBaseConfig::get('cookie-path');
        $domain = \XoopsBaseConfig::get('cookie-domain');
        $secure = $httpRequest->is('ssl');
        setcookie($usercookie, $cookieData, $this->now + $expire, $path, $domain, $secure, true);
    }
}
