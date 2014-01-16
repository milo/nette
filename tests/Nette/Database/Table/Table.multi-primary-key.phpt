<?php

/**
 * Test: Nette\Database\Table: Multi primary key support.
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
	foreach ($book->related('book_tag') as $bookTag) {
		if ($bookTag->tag->name === 'PHP') {
			$bookTag->delete();
		}
	}

	$count = $book->related('book_tag')->count();
	Assert::same(1, $count);

	$count = $book->related('book_tag')->count('*');
	Assert::same(1, $count);

	$count = $context->table('book_tag')->count('*');
	Assert::same(5, $count);
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
	'SELECT * FROM [book_tag] WHERE ([book_tag].[book_id] IN (1))',
	'-- getColumns(tag)',
	'SELECT * FROM [tag] WHERE ([id] IN (21, 22))',
	'DELETE FROM [book_tag] WHERE ([book_id] = 1) AND ([tag_id] = 21)',
	'SELECT COUNT(*), [book_tag].[book_id] FROM [book_tag] WHERE ([book_tag].[book_id] IN (1)) GROUP BY [book_tag].[book_id]',
	'SELECT COUNT(*) FROM [book_tag]',
));


test(function() use ($context) {
	$book = $context->table('book')->get(3);
	foreach ($related = $book->related('book_tag_alt') as $bookTag) {
	}
	$related->__destruct();

	$states = array();
	$book = $context->table('book')->get(3);
	foreach ($book->related('book_tag_alt') as $bookTag) {
		$states[] = $bookTag->state;
	}

	Assert::same(array(
		'public',
		'private',
		'private',
		'public',
	), $states);
});
assertQueries(array(
	'SELECT * FROM [book] WHERE ([book].[id] = 3)',
	'-- getColumns(book_tag_alt)',
	'SELECT * FROM [book_tag_alt] WHERE ([book_tag_alt].[book_id] IN (3))',
	'SELECT * FROM [book] WHERE ([book].[id] = 3)',
	'SELECT [book_id], [tag_id] FROM [book_tag_alt] WHERE ([book_tag_alt].[book_id] IN (3))',
	'SELECT * FROM [book_tag_alt] WHERE ([book_tag_alt].[book_id] IN (3))',
));


test(function() use ($context) {
	$context->table('book_tag')->insert(array(
		'book_id' => 1,
		'tag_id' => 21, // PHP tag
	));
	$count = $context->table('book_tag')->where('book_id', 1)->count('*');
	Assert::same(2, $count);
});
assertQueries(array(
	array(
		'INSERT INTO [book_tag] ([book_id], [tag_id]) VALUES (1, 21)',
		'sqlite' => 'INSERT INTO [book_tag] ([book_id], [tag_id]) SELECT 1, 21',
	),
	array(
		'pgsql' => '-- getColumns(book_tag)', ###
	),
	'SELECT * FROM [book_tag] WHERE ([book_id] = 1) AND ([tag_id] = 21)',
	'SELECT COUNT(*) FROM [book_tag] WHERE ([book_id] = 1)',
));
