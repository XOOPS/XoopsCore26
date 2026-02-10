<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Jwt;

use Xmf\Key\Basic;
use Xmf\Key\FileStorage;
use Xmf\Key\StorageInterface;

/**
 * Build a key to be used for JSON Web Token processing
 *
 * @category  Xmf\Jwt\KeyFactory
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2016-2018 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class KeyFactory
{
    /**
     * Create a Key object for JWT use based on default choices. If the key has not been
     * established, create it.
     *
     * @param string           $keyName name of the key
     * @param StorageInterface $storage key store to use, defaults to FileStorage
     *
     * @return Basic
     *
     * @throws \InvalidArgumentException on unusable key name
     */
    public static function build($keyName, ?StorageInterface $storage = null)
    {
        if (empty($keyName) || !is_string($keyName)) {
            throw new \InvalidArgumentException('keyName must be a non-empty string');
        }
        $storage = (null === $storage) ? new FileStorage() : $storage;
        $key = new Basic($storage, $keyName);
        $key->create(); // will automatically skip if key has already been generated
        return $key;
    }
}
