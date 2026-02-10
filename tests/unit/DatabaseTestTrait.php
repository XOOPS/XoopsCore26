<?php
/**
 * Trait for tests that require database connectivity.
 * Call requireDatabase() in setUp() to skip if DB is unavailable.
 */
trait DatabaseTestTrait
{
    protected function requireDatabase(): void
    {
        try {
            $db = \Xoops::getInstance()->db();
            if ($db === null) {
                $this->markTestSkipped('Database connection not available');
            }
            // Try a simple query to verify actual connectivity
            $db->fetchAssociative('SELECT 1');
        } catch (\Exception $e) {
            $this->markTestSkipped('Database not available: ' . $e->getMessage());
        }
    }
}
