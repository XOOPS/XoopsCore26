<?php

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Key;

use Xmf\Random;

/**
 * Xmf\Key\Basic
 *
 * a basic general purpose key
 *
 * @category  Xmf\Key\Basic
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class Basic extends KeyAbstract
{
    /**
     * get key for use in signing
     *
     * @return string verifying key, false on error
     */
    public function getSigning()
    {
        return (string) $this->storage->fetch($this->name);
    }

    /**
     * get key for use in verifying
     *
     * @return string verifying key, false on error
     */
    public function getVerifying()
    {
        return (string) $this->storage->fetch($this->name);
    }

    /**
     * create the key and store it for use
     *
     * @return boolean true if key was created and stored, otherwise false
     */
    public function create()
    {
        if (!$this->storage->exists($this->name)) {
            return $this->storage->save($this->name, Random::generateKey());
        }
        return false;
    }

    /**
     * delete the key
     *
     * @return boolean true if key was deleted, otherwise false
     */
    public function kill()
    {
        return $this->storage->delete($this->name);
    }
}
