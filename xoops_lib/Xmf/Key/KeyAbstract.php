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

/**
 * Xmf\Key\KeyAbstract
 *
 * manage key and related storage
 *
 * @category  Xmf\Key\KeyAbstract
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
abstract class KeyAbstract
{
    /** @var StorageInterface  */
    protected $storage;

    /** @var string */
    protected $name;

    /**
     * KeyAbstract constructor.
     * @param StorageInterface $storage key store
     * @param string           $name    case-insensitive key name, allow only A-Z, 0-9, _ and -
     */
    public function __construct(StorageInterface $storage, $name)
    {
        $this->storage = $storage;
        $this->name = strtolower(preg_replace('/[^A-Z0-9_-]/i', '', $name));
    }

    /**
     * get key for use in signing
     *
     * @return string signing key
     */
    abstract public function getSigning();

    /**
     * get key for use in verifying
     *
     * @return string verifying key
     */
    abstract public function getVerifying();

    /**
     * create the key and store it for use
     *
     * @return boolean true if key was created and stored, otherwise false
     */
    abstract public function create();

    /**
     * delete the key
     *
     * @return boolean true if key was deleted, otherwise false
     */
    abstract public function kill();
}
