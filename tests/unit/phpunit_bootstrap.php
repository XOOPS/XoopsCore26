<?php
/**
 * PHPUnit bootstrap file for XOOPS Core test suite.
 * Loads vendor autoloader and test helper traits.
 */

// Load Composer autoloader
require_once dirname(__DIR__, 2) . '/xoops_lib/vendor/autoload.php';

// Load test helper traits
require_once __DIR__ . '/DatabaseTestTrait.php';
