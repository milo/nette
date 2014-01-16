<?php

/**
 * Test: Nette\Database\Table\GroupedSelection: Insert operations
 *
 * @author     Jan Skrasek
 * @dataProvider? ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/../connect.inc.php'; // create $connection

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/../files/{$driverName}-nette_test1.sql");
$monitor->start();


test(function() use ($context) {
	$book = $context->table('book')->get(1);
	$book->related('book_tag')->insert(array('tag_id' => 23));

	Assert::equal(3, $book->related('book_tag')->count());
	Assert::equal(3, $book->related('book_tag')->count('*'));

	$book->related('book_tag')->where('tag_id', 23)->delete();
});
assertQueries(array(
	'-- getColumns(book)',
	'SELECT * FROM [book] WHERE ([book].[id] = 1)',
	'-- getTables',
	'-- getForeignKeys(author)',
	'-- getForeignKeys(book)',
	'-- getForeignKeys(book_tag)',
	'-- getForeignKeys(book_tag_alt)',
	'-- getForeignKeys(note)',
	'-- getForeignKeys(tag)',
	'-- getColumns(book_tag)',
	array(
		'INSERT INTO [book_tag] ([tag_id], [book_id]) VALUES (23, 1)',
		'sqlite' => 'INSERT INTO [book_tag] ([tag_id], [book_id]) SELECT 23, 1',
	),
	array(
		'pgsql' => '-- getColumns(book_tag)', ###
	),
	'SELECT * FROM [book_tag] WHERE ([book_id] = 1) AND ([tag_id] = 23)',
	'SELECT * FROM [book_tag] WHERE ([book_tag].[book_id] IN (1))',
	'SELECT COUNT(*), [book_tag].[book_id] FROM [book_tag] WHERE ([book_tag].[book_id] IN (1)) GROUP BY [book_tag].[book_id]',
	'DELETE FROM [book_tag] WHERE ([book_tag].[book_id] IN (1)) AND ([tag_id] = 23) AND ([book_id] = 1)',
));


test(function() use ($context) { // test counting already fetched rows
	$book = $context->table('book')->get(1);
	iterator_to_array($book->related('book_tag'));
	$book->related('book_tag')->insert(array('tag_id' => 23));
	Assert::equal(3, $book->related('book_tag')->count());
});
assertQueries(array(
	'SELECT * FROM [book] WHERE ([book].[id] = 1)',
	'SELECT * FROM [book_tag] WHERE ([book_tag].[book_id] IN (1))',
	array(
		'INSERT INTO [book_tag] ([tag_id], [book_id]) VALUES (23, 1)',
		'sqlite' => 'INSERT INTO [book_tag] ([tag_id], [book_id]) SELECT 23, 1',
	),
	array(
		'pgsql' => '-- getColumns(book_tag)', ###
	),
	'SELECT * FROM [book_tag] WHERE ([book_id] = 1) AND ([tag_id] = 23)',
));
