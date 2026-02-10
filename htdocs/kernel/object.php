<?php
/**
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

use Xoops\Core\Database\Connection;

/**
 * This class is for compatibility with pre 2.6.0 code
 */
abstract class XoopsObject extends Xoops\Core\Kernel\XoopsObject
{
}

/**
 * This class is for compatibility with pre 2.6.0 code
 */
abstract class XoopsObjectHandler extends Xoops\Core\Kernel\XoopsObjectHandler
{
    /**
     * This is a reference to the legacy database connection
     */
    public $db;

    /**
     * this is a legacy compatibility shim to make the legacy database available
     *
     * @param Connection $db reference to the {@link Connection} object
     */
    public function __construct(Connection $db)
    {
        $this->db = XoopsDatabaseFactory::getDatabaseConnection(); // get legacy connection
        parent::__construct($db);
    }
}

/**
 * This class is for compatibility with pre 2.6.0 code
 */
abstract class XoopsPersistableObjectHandler extends Xoops\Core\Kernel\XoopsPersistableObjectHandler
{
    /**
     * This is a reference to the legacy database connection
     */
    public $db;

    /**
     * this is a legacy compatibility shim to make the legacy database available
     *
     * @param Connection $db reference to the {@link Connection} object
     */
    protected function __construct(
        ?Connection $db = null,
        $table = '',
        $className = '',
        $keyName = '',
        $identifierName = ''
    ) {
        if ($db===null) {
            $this->db2 = \Xoops\Core\Database\Factory::getConnection();
            $db = $this->db2;
        }
        $this->db = XoopsDatabaseFactory::getDatabaseConnection(); // get legacy connection
        parent::__construct($db, $table, $className, $keyName, $identifierName);
    }
}
