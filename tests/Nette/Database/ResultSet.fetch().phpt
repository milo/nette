<?php

/**
 * Test: Nette\Database\ResultSet::fetch()
 *
 * @author     David Grudl
 * @author     Jan Skrasek
 * @dataProvider? databases.ini
 */

use Tester\Assert;

require __DIR__ . '/connect.inc.php'; // create $connection

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/files/{$driverName}-nette_test1.sql");
$monitor->start();


test(function() use ($context) {
	$res = $context->query('SELECT name, name FROM author');

	Assert::error(function () use ($res) {
		$res->fetch();
	}, E_USER_NOTICE, 'Found duplicate columns in database result set.');

	$res->fetch();
});
assertQueries(array(
	'SELECT name, name FROM author'
));


test(function() use ($context, $driverName) { // tests closeCursor()
	if ($driverName === 'mysql') {
		$context->query('CREATE DEFINER = CURRENT_USER PROCEDURE `testProc`(IN param int(10) unsigned) BEGIN SELECT * FROM book WHERE id != param; END;;');
		$context->getConnection()->getPdo()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, FALSE);

		$res = $context->query('CALL testProc(1)');
		foreach ($res as $row) {}

		$res = $context->query('SELECT * FROM book');
		foreach ($res as $row) {}

		assertQueries(array(
			'CREATE DEFINER = CURRENT_USER PROCEDURE [testProc](IN param int(10) unsigned) BEGIN SELECT * FROM book WHERE id != param; END;;',
			'CALL testProc(1)',
			'SELECT * FROM book',
		));
	}
});
assertQueries(array(
));
