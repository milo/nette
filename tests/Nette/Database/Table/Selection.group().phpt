<?php

/**
 * Test: Nette\Database\Table: grouping.
 *
 * @author     Jan Skrasek
 * @dataProvider? ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/../connect.inc.php'; // create $connection

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/../files/{$driverName}-nette_test1.sql");
$monitor->start();


test(function() use ($context) {
	$authors = $context->table('book')->group('author_id')->order('author_id')->fetchPairs('author_id', 'author_id');
	Assert::same(array(11, 12), array_values($authors));
});
assertQueries(array(
	'-- getColumns(book)',
	array(
		'SELECT [author_id] FROM [book] GROUP BY [author_id] ORDER BY [author_id]',
		'mysql' => 'SELECT * FROM [book] GROUP BY [author_id] ORDER BY [author_id]', ###
	),
));
