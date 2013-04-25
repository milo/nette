<?php

/**
 * Test: Nette\Database test boostap.
 *
 * @author     Jakub Vrana
 * @author     Jan Skrasek
 * @package    Nette\Database
 */

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/helpers.inc.php';


if (!is_file(__DIR__ . '/databases.ini')) {
	Tester\Helpers::skip();
}

$options = Tester\DataProvider::load(__DIR__ . '/databases.ini', isset($query) ? $query : NULL);
$options = isset($_SERVER['argv'][1]) ? $options[$_SERVER['argv'][1]] : reset($options);
$options += array('user' => NULL, 'password' => NULL);

try {
	$connection = new Nette\Database\Connection($options['dsn'], $options['user'], $options['password']);
} catch (PDOException $e) {
	Tester\Helpers::skip("Connection to '$options[dsn]' failed. Reason: " . $e->getMessage());
}

if (strpos($options['dsn'], 'sqlite::memory:') === FALSE) {
	Tester\Helpers::lock($options['dsn'], dirname(TEMP_DIR));
}
$driverName = $connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
$dao = new Nette\Database\SelectionFactory($connection);



/** Replaces [] with driver-specific quotes */
function reformat($s)
{
	global $driverName;
	if (is_array($s)) {
		$ret = array();
		foreach ($s as $sql) {
			if (is_array($sql)) {
				$found = FALSE;
				foreach ($sql as $k => $v) {
					if (in_array($driverName, explode('|', $k), TRUE)) {
						if ($v !== NULL) {
							$ret[] = reformat($v);
						}
						$found = TRUE;
						break;
					}
				}
				!$found && trigger_error("Unsupported driver $driverName", E_USER_WARNING);

			} else {
				$ret[] = reformat($sql);
			}
		}
		return $ret;
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
$connection->onQuery[] = function($dao, $result) use ($monitor) {
	if (!$result instanceof Exception) {
		$monitor->query($result->queryString);
	}
};

/* Wrap supplemental driver to catch reflection queries purpose */
$driverWrapper = new DriverWrapper($connection->getSupplementalDriver(), $monitor);
$ref = new ReflectionClass($connection);
$ref = $ref->getProperty('driver');
$ref->setAccessible(TRUE);
$ref->setValue($connection, $driverWrapper);
$ref->setAccessible(FALSE);
unset($ref);



/** Load SQL */
function loadSqlFile($file) {
	global $connection;
	global $monitor;

	Nette\Database\Helpers::loadFromFile($connection, $file);
	$monitor->flush();
}
