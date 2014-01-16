<?php

/**
 * Test: Nette\Database test bootstrap.
 *
 * @author     Jakub Vrana
 * @author     Jan Skrasek
 */

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/helpers.inc.php';


if (!class_exists('PDO')) {
	Tester\Environment::skip('Requires PHP extension PDO.');
}

if (!is_file(__DIR__ . '/databases.ini')) {
	Tester\Environment::skip('Missing file databases.ini');
}

$options = Tester\DataProvider::load(__DIR__ . '/databases.ini', isset($query) ? $query : NULL);
$options = isset($_SERVER['argv'][1]) ? $options[$_SERVER['argv'][1]] : reset($options);
$options += array('user' => NULL, 'password' => NULL);

try {
	$connection = new Nette\Database\Connection($options['dsn'], $options['user'], $options['password']);
} catch (PDOException $e) {
	Tester\Environment::skip("Connection to '$options[dsn]' failed. Reason: " . $e->getMessage());
}

if (strpos($options['dsn'], 'sqlite::memory:') === FALSE) {
	Tester\Environment::lock($options['dsn'], dirname(TEMP_DIR));
}

$driverName = $connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
$cacheMemoryStorage = new Nette\Caching\Storages\MemoryStorage;
$reflection = new Nette\Database\Reflection\DiscoveredReflection($connection, $cacheMemoryStorage);
$context = new Nette\Database\Context($connection, $reflection, $cacheMemoryStorage);


/** Replaces [] with driver-specific quotes */
function reformat($s)
{
	global $driverName;
	if (is_array($s)) {
		if (isset($s[$driverName])) {
			return $s[$driverName];
		}
		$s = $s[0];
	}
	if ($driverName === 'mysql') {
		return strtr($s, '[]', '``');
	} elseif ($driverName === 'pgsql') {
		return strtr($s, '[]', '""');
	} elseif ($driverName === 'sqlsrv' || $driverName === 'sqlite' || $driverName === 'sqlite2') {
		return $s;
	} else {
		trigger_error("Unsupported driver $driverName", E_USER_WARNING);
	}
}


/* Listen for all queries */
$monitor = new QueryMonitor($driverName);
$connection->onQuery[] = function($foo, $result) use ($monitor) {
	if (!$result instanceof Exception) {
		$monitor->query($result->queryString);
	}
};


/* Wrap supplemental driver to catch reflection queries */
$driverWrapper = new DriverWrapper($connection->getSupplementalDriver(), $monitor);
$ref = new ReflectionClass($connection);
$ref = $ref->getProperty('driver');
$ref->setAccessible(TRUE);
$ref->setValue($connection, $driverWrapper);
$ref->setAccessible(FALSE);
unset($ref, $driverWrapper);


/** Assert queries catched by QueryMonitor */
function assertQueries(array $queries) {
	global $monitor;

	$queries = flattenQueries($queries);
	$queries = array_map('reformat', $queries);

	Tester\Assert::same($queries, $monitor->getQueries());
}


/** Make expected queries flatten for specific driver */
function flattenQueries(array $queries) {
	global $driverName;

	$flatten = array();
	foreach ($queries as $query) {
		if (is_array($query)) {
			if (array_key_exists($driverName, $query)) {
				$flatten[] = $query[$driverName];
			} elseif (array_key_exists(0, $query)) {
				$flatten[] = $query[0];
			}

		} else {
			$flatten[] = $query;
		}
	}

	return $flatten;
}
