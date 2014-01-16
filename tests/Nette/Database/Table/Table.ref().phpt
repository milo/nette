<?php

/**
 * Test: Nette\Database\Table: Reference ref().
 *
 * @author     Jan Skrasek
 * @dataProvider? ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/../connect.inc.php'; // create $connection

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/../files/{$driverName}-nette_test1.sql");
$monitor->start();


Assert::same('Jakub Vrana', $context->table('book')->get(1)->ref('author')->name);
assertQueries(array(
	'-- getColumns(book)',
	'SELECT * FROM [book] WHERE ([book].[id] = 1)',
	'-- getForeignKeys(book)',
	'-- getColumns(author)',
	'SELECT * FROM [author] WHERE ([id] IN (11))',
));


test(function() use ($context) {
	$book = $context->table('book')->get(1);
	$book->update(array(
		'translator_id' => 12,
	));


	$book = $context->table('book')->get(1);
	Assert::same('David Grudl', $book->ref('author', 'translator_id')->name);
	Assert::same('Jakub Vrana', $book->ref('author', 'author_id')->name);
});
assertQueries(array(
	'SELECT * FROM [book] WHERE ([book].[id] = 1)',
	'UPDATE [book] SET [translator_id]=12 WHERE ([book].[id] = 1)',
	'SELECT * FROM [book] WHERE ([book].[id] = 1)',
	'SELECT * FROM [book] WHERE ([book].[id] = 1)',
	'SELECT * FROM [author] WHERE ([id] IN (12))',
	'SELECT * FROM [author] WHERE ([id] IN (11))',
));


test(function() use ($context) {
	Assert::null($context->table('book')->get(2)->ref('author', 'translator_id'));
});
assertQueries(array(
	'SELECT * FROM [book] WHERE ([book].[id] = 2)'
));
