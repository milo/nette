<?php

/**
 * Test: Nette\Database\Table: Refetching rows with all columns
 *
 * @author     Jan Skrasek
 * @dataProvider? ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/../connect.inc.php'; // create $connection

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/../files/{$driverName}-nette_test1.sql");
$monitor->start();


$books = $context->table('book')->order('id DESC')->limit(2);
foreach ($books as $book) {
	$book->title;
}
$books->__destruct();


$books = $context->table('book')->order('id DESC')->limit(2);
foreach ($books as $book) {
	$book->title;
}

$context->table('book')->insert(array(
	'title' => 'New book #1',
	'author_id' => 11,
));
$context->table('book')->insert(array(
	'title' => 'New book #2',
	'author_id' => 11,
));

foreach ($books as $book) {
	$book->title;
	$book->author->name;
}

### No assertions?

assertQueries(array(
	'-- getColumns(book)',
	array(
		'SELECT * FROM [book] ORDER BY [id] DESC LIMIT 2',
		'sqlsrv' => 'SELECT TOP 2 * FROM [book] ORDER BY [id] DESC',
	),
	array(
		'SELECT [id], [title] FROM [book] ORDER BY [id] DESC LIMIT 2',
		'sqlsrv' => 'SELECT TOP 2 [id], [title] FROM [book] ORDER BY [id] DESC',
	),
	array(
		"INSERT INTO [book] ([title], [author_id]) VALUES ('New book #1', 11)",
		'sqlite' => "INSERT INTO [book] ([title], [author_id]) SELECT 'New book #1', 11",
	),
	array(
		'pgsql' => '-- getColumns(book)', ###
	),
	"SELECT * FROM [book] WHERE ([book].[id] = '5')",
	array(
		"INSERT INTO [book] ([title], [author_id]) VALUES ('New book #2', 11)",
		'sqlite' => "INSERT INTO [book] ([title], [author_id]) SELECT 'New book #2', 11",
	),
	array(
		'pgsql' => '-- getColumns(book)', ###
	),
	"SELECT * FROM [book] WHERE ([book].[id] = '6')",
	'SELECT * FROM [book] WHERE ([book].[id] IN (4, 3)) ORDER BY [id] DESC',
	'-- getForeignKeys(book)',
	'-- getColumns(author)',
	'SELECT * FROM [author] WHERE ([id] IN (12))',
));
