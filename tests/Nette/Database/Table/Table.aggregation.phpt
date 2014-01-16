<?php

/**
 * Test: Nette\Database\Table: Aggregation functions.
 *
 * @author     Jakub Vrana
 * @author     Jan Skrasek
 * @dataProvider? ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/../connect.inc.php';

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/../files/{$driverName}-nette_test1.sql");
$monitor->start();


test(function() use ($context) {
	$count = $context->table('book')->count('*');
	Assert::same(4, $count);
});
assertQueries(array(
	'-- getColumns(book)',
	'SELECT COUNT(*) FROM [book]',
));


test(function() use ($context) {
	$tags = array();
	foreach ($context->table('book') as $book) {
		$count = $book->related('book_tag')->count('*');
		$tags[$book->title] = $count;
	}

	Assert::same(array(
		'1001 tipu a triku pro PHP' => 2,
		'JUSH' => 1,
		'Nette' => 1,
		'Dibi' => 2,
	), $tags);
});
assertQueries(array(
	'SELECT * FROM [book]',
	'-- getTables',
	'-- getForeignKeys(author)',
	'-- getForeignKeys(book)',
	'-- getForeignKeys(book_tag)',
	'-- getForeignKeys(book_tag_alt)',
	'-- getForeignKeys(note)',
	'-- getForeignKeys(tag)',
	'-- getColumns(book_tag)',
	'SELECT COUNT(*), [book_tag].[book_id] FROM [book_tag] WHERE ([book_tag].[book_id] IN (1, 2, 3, 4)) GROUP BY [book_tag].[book_id]',
));


test(function() use ($context) {
	$authors = $context->table('author')->where(':book.translator_id IS NOT NULL')->group('author.id');
	Assert::same(2, count($authors));
	Assert::same(2, $authors->count('DISTINCT author.id'));
});
assertQueries(array(
	'-- getColumns(author)',
	array(
		'SELECT [author].[id] FROM [author] LEFT JOIN [book] ON [author].[id] = [book].[author_id] WHERE ([book].[translator_id] IS NOT NULL) GROUP BY [author].[id]',
		'mysql' => 	'SELECT [author].* FROM [author] LEFT JOIN [book] ON [author].[id] = [book].[author_id] WHERE ([book].[translator_id] IS NOT NULL) GROUP BY [author].[id]',
	),
	'SELECT COUNT(DISTINCT [author].[id]) FROM [author] LEFT JOIN [book] ON [author].[id] = [book].[author_id] WHERE ([book].[translator_id] IS NOT NULL)',
));
