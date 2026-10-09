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
 * Xmf\Key\ArrayStorage
 *
 * A non-persisting key storage, mainly for testing
 *
 * @category  Xmf\Key\StorageInterface
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class ArrayStorage extends \ArrayObject implements StorageInterface
{
    /**
     * Save key data by name
     *
     * @param string $name key name
     * @param string $data key data, serialized to string if required
     *
     * @return boolean true if key saved, otherwise false
     */
    public function save($name, $data)
    {
        $this->offsetSet($name, $data);
        return true;
    }

    /**
     * Fetch key data by name
     *
     * @param string $name key name
     *
     * @return string|false key data (possibly serialized) or false on error
     */
    public function fetch($name)
    {
        if ($this->offsetExists($name)) {
            return $this->offsetGet($name);
        }
        return false;
    }

    /**
     * Fetch key data by name
     *
     * @param string $name key name
     *
     * @return boolean true if key exists, otherwise false
     */
    public function exists($name)
    {
        return $this->offsetExists($name);
    }

    /**
     * Delete a key
     *
     * @param string $name key name
     *
     * @return boolean true if key deleted, otherwise false
     */
    public function delete($name)
    {
        if ($this->offsetExists($name)) {
            $this->offsetUnset($name);
            return true;
        }
        return false;
    }
}
