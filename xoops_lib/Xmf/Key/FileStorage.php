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
 * Xmf\Key\StorageInterface
 *
 * load a database table
 *
 * @category  Xmf\Key\FileStorage
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class FileStorage implements StorageInterface
{
    /**
     * @var string $storagePath filesystem path to storage
     */
    protected $storagePath;

    /**
     * @var string $systemSecret prefix unique to this system
     */
    protected $systemSecret;

    /**
     * FileStorage constructor.
     *
     * @param string|null $storagePath  filesystem path to storage (without trailing slash)
     * @param string|null $systemSecret prefix unique to this system
     */
    public function __construct($storagePath = null, $systemSecret = null)
    {
        $this->storagePath = (null === $storagePath) ? XOOPS_VAR_PATH . '/data' : $storagePath;
        $this->systemSecret = (null === $systemSecret) ? $this->generateSystemSecret() : $systemSecret;
    }

    /**
     * Fetch key data by name
     *
     * @param string $name key name
     *
     * @return string file name
     */
    protected function fileName($name)
    {
        return $this->storagePath . "/{$this->systemSecret}-key-{$name}.php";
    }

    /**
     * Construct a string related to the system to make name less predictable
     *
     * @return string
     */
    protected function generateSystemSecret()
    {
        $db = \XoopsDatabaseFactory::getDatabaseConnection();
        $prefix = $db->prefix();
        $secret = md5($prefix);
        $secret = substr($secret, 8, 8);
        return $secret;
    }

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
        if (empty($data) || !is_string($data)) {
            throw new \DomainException('Invalid key data');
        }
        $fileContents = "<?php\n//**Warning** modifying this file will break things!\n"
            . "return " . var_export($data, true) . ";\n";
        return (false !== file_put_contents($this->fileName($name), $fileContents));
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
        return include $this->fileName($name);
    }

    /**
     * Check if key data exists
     *
     * @param string $name key name
     *
     * @return boolean true if key exists, otherwise false
     */
    public function exists($name)
    {
        return file_exists($this->fileName($name));
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
        return unlink($this->fileName($name));
    }
}
