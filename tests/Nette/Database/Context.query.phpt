<?php

/**
 * Test: Nette\Database\Connection query methods.
 *
 * @author     David Grudl
 * @dataProvider? databases.ini
 */

use Tester\Assert;

require __DIR__ . '/connect.inc.php'; // create $connection

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/files/{$driverName}-nette_test1.sql");
$monitor->start();


test(function() use ($context) {
	$res = $context->query('SELECT id FROM author WHERE id = ?', 11);
	Assert::type( 'Nette\Database\ResultSet', $res );
	Assert::same( 'SELECT id FROM author WHERE id = 11', $res->getQueryString() );
});
assertQueries(array(
	'SELECT id FROM author WHERE id = 11'
));


test(function() use ($context) {
	$res = $context->query('SELECT id FROM author WHERE id = ? OR id = ?', 11, 12);
	Assert::same( 'SELECT id FROM author WHERE id = 11 OR id = 12', $res->getQueryString() );
});
assertQueries(array(
	'SELECT id FROM author WHERE id = 11 OR id = 12'
));


test(function() use ($context) {
	$res = $context->queryArgs('SELECT id FROM author WHERE id = ? OR id = ?', array(11, 12));
	Assert::same( 'SELECT id FROM author WHERE id = 11 OR id = 12', $res->getQueryString() );
});
assertQueries(array(
	'SELECT id FROM author WHERE id = 11 OR id = 12'
));
