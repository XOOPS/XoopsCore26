<?php

if (empty($_SERVER["argc"])) {
	echo "Error: This script must be run from the command line";
	die(1);
}
 
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 80400) {
	echo "Error: This script must be run from PHP >= 8.4.0";
	die(1);
}
 
define('CLI', true);

ini_set('memory_limit', -1);
 
while (ob_get_level()) {
	ob_end_flush();
}


/*
<!--
 1. Put your tests in a folder called "tests" (with an .htaccess "deny from all")
 2. If there are any slow tests, annotate the test method with a "@group slow" PHP-doc-style comment. These tests will me ignored by default.
 3. To run tests:
     $ cd my-project
     $ phpunit
 4. To run slow tests:
     $ cd my-project
     $ phpunit --no-configuration --group slow tests
 5. To run a specific test case:
     $ cd my-project
     $ phpunit --no-configuration tests/myTests.php
 5. To run a specific tests (e.g. WidgetAddTest, WidgetDeleteTest):
     $ cd my-project
     $ phpunit --no-configuration --filter Widget tests
 6. Lovely test reports can be found in the test-reports directory (with an .htaccess "deny from all")
-->

*/

require_once __DIR__ . '/DatabaseTestTrait.php';