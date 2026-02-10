<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core\Database\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

/**
 * SchemaSynchronizer - DBAL 4 replacement for the removed
 * Doctrine\DBAL\Schema\Synchronizer\SingleDatabaseSynchronizer.
 *
 * In DBAL 3, SingleDatabaseSynchronizer provided updateSchema() and
 * getUpdateSchema() methods. DBAL 4 removed this class entirely.
 * This class provides equivalent functionality using the DBAL 4 API.
 *
 * @category  Xoops\Core\Database\Schema\SchemaSynchronizer
 * @package   Xoops\Core
 * @author    XOOPS Project
 * @copyright 2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class SchemaSynchronizer
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the SQL statements needed to update the database to match the target schema.
     *
     * @param Schema $toSchema   The target schema
     * @param bool   $noDropping If true, DROP statements are filtered out
     *
     * @return string[] Array of SQL statements
     */
    public function getUpdateSchema(Schema $toSchema, bool $noDropping = false): array
    {
        $sm = $this->connection->createSchemaManager();
        $comparator = $sm->createComparator();
        $fromSchema = $sm->introspectSchema();
        $schemaDiff = $comparator->compareSchemas($fromSchema, $toSchema);
        $platform = $this->connection->getDatabasePlatform();
        $sql = $platform->getAlterSchemaSQL($schemaDiff);

        if ($noDropping) {
            $sql = array_filter($sql, static function (string $query): bool {
                return !preg_match('/^\s*DROP\s/i', $query);
            });
        }

        return array_values($sql);
    }

    /**
     * Execute the SQL statements needed to update the database to match the target schema.
     *
     * @param Schema $toSchema   The target schema
     * @param bool   $noDropping If true, DROP statements are skipped
     *
     * @return void
     */
    public function updateSchema(Schema $toSchema, bool $noDropping = false): void
    {
        $sql = $this->getUpdateSchema($toSchema, $noDropping);
        foreach ($sql as $query) {
            $this->connection->executeStatement($query);
        }
    }
}
