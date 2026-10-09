<?php

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Database;

use Xmf\Module\Helper;
use Xmf\Yaml;

/**
 * Xmf\Database\Migrate
 *
 * For a given module, compare the existing tables with a defined target schema
 * and build a work queue of DDL/SQL to transform the existing tables to the
 * target definitions.
 *
 * Typically, Migrate will be extended by a module specific class that will supply custom
 * logic (see preSyncActions() method.)
 *
 * @category  Xmf\Database\Migrate
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class Migrate
{
    /** @var false|\Xmf\Module\Helper|\Xoops\Module\Helper|\Xoops\Module\Helper\HelperAbstract */
    protected $helper;

    /** @var string[] table names used by module */
    protected $moduleTables;

    /** @var Tables */
    protected $tableHandler;

    /** @var string yaml definition file */
    protected $tableDefinitionFile;

    /** @var array|null target table definitions in Xmf\Database\Tables::dumpTables() format */
    protected $targetDefinitions = null;

    /**
     * Migrate constructor
     *
     * @param string $dirname module directory name that defines the tables to be migrated
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __construct($dirname)
    {
        $this->helper = Helper::getHelper($dirname);
        if (false === $this->helper) {
            throw new \InvalidArgumentException("Invalid module $dirname specified");
        }
        $module = $this->helper->getModule();
        $this->moduleTables = $module->getInfo('tables');
        if (empty($this->moduleTables)) {
            throw new \RuntimeException("No tables established in module");
        }

        $version = preg_replace_callback(
            '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/',
            function ($match) {
                $semver = $match[1] . '_' . $match[2] . '_' . $match[3];
                if (!empty($match[4])) {
                    $semver .= '_' . substr($match[4], 0, 8);
                }
                return $semver;
            },
            $module->getInfo('version')
        );

        $this->tableDefinitionFile = $this->helper->path("sql/{$dirname}_{$version}_migrate.yml");
        $this->tableHandler = new Tables();
    }

    /**
     * Save current table definitions to a file
     *
     * This is intended for developer use when setting up the migration by using the current database state
     *
     * @internal intended for module developers only
     *
     * @return int|false count of bytes written or false on error
     */
    public function saveCurrentSchema()
    {
        $this->tableHandler = new Tables(); // start fresh

        $schema = $this->getCurrentSchema();

        foreach ($schema as $tableName => $tableData) {
            unset($schema[$tableName]['name']);
        }

        return Yaml::save($schema, $this->tableDefinitionFile);
    }

    /**
     * get the current definitions
     *
     * @return array
     */
    public function getCurrentSchema()
    {
        foreach ($this->moduleTables as $tableName) {
            if (false === $this->tableHandler->useTable($tableName)) {
                $this->tableHandler->addTable($tableName);
            }
        }

        return $this->tableHandler->dumpTables();
    }

    /**
     * Return the target database condition
     *
     * @return array table structure
     *
     * @throws SchemaDefinitionException
     */
    public function getTargetDefinitions()
    {
        if ($this->targetDefinitions === null) {
            $definitions = Yaml::read($this->tableDefinitionFile);
            if (!is_array($definitions) || $definitions === []) {
                throw SchemaDefinitionException::forFile($this->tableDefinitionFile);
            }
            $this->targetDefinitions = $definitions;
        }

        return $this->targetDefinitions;
    }

    /**
     * Execute synchronization to transform current schema to target
     *
     * @param bool $force true to force updates even if this is a 'GET' request
     *
     * @return bool true if no errors, false if errors encountered
     */
    public function synchronizeSchema($force = true)
    {
        $this->tableHandler = new Tables(); // start fresh
        $this->getSynchronizeDDL();
        return $this->tableHandler->executeQueue($force);
    }

    /**
     * Compare target and current schema, building work queue in $this->migrate to synchronized
     *
     * @return string[] array of DDL/SQL statements to transform current to target schema
     */
    public function getSynchronizeDDL()
    {
        $this->getTargetDefinitions();
        $this->preSyncActions();
        foreach ($this->moduleTables as $tableName) {
            if ($this->tableHandler->useTable($tableName)) {
                $this->synchronizeTable($tableName);
            } else {
                $this->addMissingTable($tableName);
            }
        }
        return $this->tableHandler->dumpQueue();
    }

    /**
     * Perform any upfront actions before synchronizing the schema.
     *
     * The schema comparison cannot recognize changes such as renamed columns or renamed tables. By overriding
     * this method, an implementation can provide the logic to accomplish these types of changes, and leave
     * the other details to be handled by synchronizeSchema().
     *
     * An suitable implementation should be provided by a module by extending Migrate to define any required
     * actions.
     *
     * Some typical uses include:
     *  - table and column renames
     *  - data conversions
     *  - move column data
     *
     * @return void
     */
    protected function preSyncActions()
    {
    }

    /**
     * Add table create DDL to the work queue
     *
     * @param string $tableName table to add
     *
     * @return void
     *
     * @throws SchemaDefinitionException
     */
    protected function addMissingTable($tableName)
    {
        $targetTable = $this->targetDefinitions[$tableName] ?? null;
        if (
            !is_array($targetTable)
            || !isset($targetTable['options'], $targetTable['columns'])
            || !is_string($targetTable['options'])
            || !is_array($targetTable['columns'])
        ) {
            throw SchemaDefinitionException::forTable($tableName);
        }

        $this->tableHandler->addTable($tableName);
        $this->tableHandler->setTableOptions($tableName, $targetTable['options']);
        foreach ($targetTable['columns'] as $column) {
            $validatedColumn = $this->validatedTargetColumn($tableName, $column);
            $this->tableHandler->addColumn($tableName, $validatedColumn['name'], $validatedColumn['attributes']);
        }
        if (isset($targetTable['keys']) && is_array($targetTable['keys'])) {
            foreach ($targetTable['keys'] as $key => $keyData) {
                if (!is_string($key)) {
                    continue;
                }
                $validatedKey = $this->validatedTargetKey($tableName, $key, $keyData);
                if ($key === 'PRIMARY') {
                    $this->tableHandler->addPrimaryKey($tableName, $validatedKey['columns']);
                } else {
                    $this->tableHandler->addIndex(
                        $key,
                        $tableName,
                        $validatedKey['columns'],
                        $validatedKey['unique']
                    );
                }
            }
        }
    }

    /**
     * Build any DDL required to synchronize an existing table to match the target schema
     *
     * @param string $tableName table to synchronize
     *
     * @return void
     *
     * @throws SchemaDefinitionException
     */
    protected function synchronizeTable($tableName)
    {
        $targetTable = $this->targetDefinitions[$tableName] ?? null;
        if (!is_array($targetTable) || !isset($targetTable['columns']) || !is_array($targetTable['columns'])) {
            throw SchemaDefinitionException::forTable($tableName);
        }

        foreach ($targetTable['columns'] as $column) {
            $validatedColumn = $this->validatedTargetColumn($tableName, $column);
            $attributes = $this->tableHandler->getColumnAttributes($tableName, $validatedColumn['name']);
            if ($attributes === false) {
                $this->tableHandler->addColumn($tableName, $validatedColumn['name'], $validatedColumn['attributes']);
            } elseif ($validatedColumn['attributes'] !== $attributes) {
                $this->tableHandler->alterColumn($tableName, $validatedColumn['name'], $validatedColumn['attributes']);
            }
        }

        $tableDef = $this->tableHandler->dumpTables();
        if (isset($tableDef[$tableName])) {
            foreach ($tableDef[$tableName]['columns'] as $columnData) {
                if (!$this->targetHasColumn($tableName, $columnData['name'])) {
                    $this->tableHandler->dropColumn($tableName, $columnData['name']);
                }
            }
        }

        $existingIndexes = $this->tableHandler->getTableIndexes($tableName);
        if (isset($targetTable['keys']) && is_array($targetTable['keys'])) {
            foreach ($targetTable['keys'] as $key => $keyData) {
                if (!is_string($key)) {
                    continue;
                }
                $validatedKey = $this->validatedTargetKey($tableName, $key, $keyData);
                if ($key === 'PRIMARY') {
                    if (!isset($existingIndexes[$key])) {
                        $this->tableHandler->addPrimaryKey($tableName, $validatedKey['columns']);
                    } elseif ($existingIndexes[$key]['columns'] !== $validatedKey['columns']) {
                        $this->tableHandler->dropPrimaryKey($tableName);
                        $this->tableHandler->addPrimaryKey($tableName, $validatedKey['columns']);
                    }
                } else {
                    if (!isset($existingIndexes[$key])) {
                        $this->tableHandler->addIndex(
                            $key,
                            $tableName,
                            $validatedKey['columns'],
                            $validatedKey['unique']
                        );
                    } elseif (
                        $existingIndexes[$key]['unique'] !== $validatedKey['unique']
                        || $existingIndexes[$key]['columns'] !== $validatedKey['columns']
                    ) {
                        $this->tableHandler->dropIndex($key, $tableName);
                        $this->tableHandler->addIndex(
                            $key,
                            $tableName,
                            $validatedKey['columns'],
                            $validatedKey['unique']
                        );
                    }
                }
            }
        }
        if (false !== $existingIndexes) {
            foreach ($existingIndexes as $key => $keyData) {
                if (!isset($targetTable['keys'][$key])) {
                    if ($key === 'PRIMARY') {
                        $this->tableHandler->dropPrimaryKey($tableName);
                    } else {
                        $this->tableHandler->dropIndex($key, $tableName);
                    }
                }
            }
        }
    }

    /**
     * @param mixed $column
     *
     * @return array{name: string, attributes: string}
     *
     * @throws SchemaDefinitionException
     */
    private function validatedTargetColumn(string $tableName, mixed $column): array
    {
        if (
            !is_array($column)
            || !isset($column['name'], $column['attributes'])
            || !is_string($column['name'])
            || $column['name'] === ''
            || !is_string($column['attributes'])
            || $column['attributes'] === ''
        ) {
            throw SchemaDefinitionException::forTable($tableName);
        }

        return [
            'name' => $column['name'],
            'attributes' => $column['attributes'],
        ];
    }

    /**
     * @param mixed $keyData
     *
     * @return array{columns: string, unique: bool}
     *
     * @throws SchemaDefinitionException
     */
    private function validatedTargetKey(string $tableName, string $keyName, mixed $keyData): array
    {
        if (
            !is_array($keyData)
            || !isset($keyData['columns'])
            || !is_string($keyData['columns'])
            || $keyData['columns'] === ''
        ) {
            throw SchemaDefinitionException::forTable($tableName);
        }

        if ($keyName === 'PRIMARY') {
            return [
                'columns' => $keyData['columns'],
                'unique' => true,
            ];
        }

        if (!array_key_exists('unique', $keyData) || !is_bool($keyData['unique'])) {
            throw SchemaDefinitionException::forTable($tableName);
        }

        return [
            'columns' => $keyData['columns'],
            'unique' => $keyData['unique'],
        ];
    }

    /**
     * determine if a column on a table exists in the target definitions
     *
     * @param string $tableName  table containing the column
     * @param string $columnName column to check
     *
     * @return bool true if table and column combination is defined, otherwise false
     */
    protected function targetHasColumn($tableName, $columnName)
    {
        if (isset($this->targetDefinitions[$tableName])) {
            foreach ($this->targetDefinitions[$tableName]['columns'] as $col) {
                if (strcasecmp($col['name'], $columnName) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * determine if a table exists in the target definitions
     *
     * @param string $tableName table containing the column
     *
     * @return bool true if table is defined, otherwise false
     */
    protected function targetHasTable($tableName)
    {
        if (isset($this->targetDefinitions[$tableName])) {
            return true;
        }
        return false;
    }

    /**
     * Return message from last error encountered
     *
     * @return string last error message
     */
    public function getLastError()
    {
        return $this->tableHandler->getLastError();
    }

    /**
     * Return code from last error encountered
     *
     * @return int last error number
     */
    public function getLastErrNo()
    {
        return $this->tableHandler->getLastErrNo();
    }
}
