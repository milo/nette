<?php

/**
 * Test: Nette\Database\Table: Shared related data caching.
 *
 * @author     Jan Skrasek
 * @dataProvider? ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/../connect.inc.php'; // create $connection

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/../files/{$driverName}-nette_test1.sql");
$monitor->start();


test(function() use ($context) {
	$books = $context->table('book');
	foreach ($books as $book) {
		foreach ($book->related('book_tag') as $bookTag) {
			$bookTag->tag;
		}
	}

	$tags = array();
	foreach ($books as $book) {
		foreach ($book->related('book_tag_alt') as $bookTag) {
			$tags[] = $bookTag->tag->name;
		}
	}

	Assert::same(array(
		'PHP',
		'MySQL',
		'JavaScript',
		'Neon',
	), $tags);
});
assertQueries(array(
	'-- getColumns(book)',
	'SELECT * FROM [book]',
	'-- getTables',
	'-- getForeignKeys(author)',
	'-- getForeignKeys(book)',
	'-- getForeignKeys(book_tag)',
	'-- getForeignKeys(book_tag_alt)',
	'-- getForeignKeys(note)',
	'-- getForeignKeys(tag)',
	'-- getColumns(book_tag)',
	'SELECT * FROM [book_tag] WHERE ([book_tag].[book_id] IN (1, 2, 3, 4))',
	'-- getColumns(tag)',
	'SELECT * FROM [tag] WHERE ([id] IN (21, 22, 23))',
	'-- getColumns(book_tag_alt)',
	'SELECT * FROM [book_tag_alt] WHERE ([book_tag_alt].[book_id] IN (1, 2, 3, 4))',
	'SELECT * FROM [tag] WHERE ([id] IN (21, 22, 23, 24))',
));


test(function() use ($context) {
	$authors = $context->table('author')->where('id', 11);
	$books = array();
	foreach ($authors as $author) {
		foreach ($author->related('book')->where('translator_id', NULL) as $book) {
			foreach ($book->related('book_tag') as $bookTag) {
				$books[] = $bookTag->tag->name;
			}
		}
	}
	Assert::same(array('JavaScript'), $books);

	foreach ($authors as $author) {
		foreach ($author->related('book')->where('NOT translator_id', NULL) as $book) {
			foreach ($book->related('book_tag')->order('tag_id') as $bookTag) {
				$books[] = $bookTag->tag->name;
			}
		}
	}
	Assert::same(array('JavaScript', 'PHP', 'MySQL'), $books);
});
assertQueries(array(
	'-- getColumns(author)',
	'SELECT * FROM [author] WHERE ([id] = 11)',
	'SELECT * FROM [book] WHERE ([book].[author_id] IN (11)) AND ([translator_id] IS NULL)',
	'SELECT * FROM [book_tag] WHERE ([book_tag].[book_id] IN (2))',
	'SELECT * FROM [tag] WHERE ([id] IN (23))',
	'SELECT * FROM [book] WHERE ([book].[author_id] IN (11)) AND (NOT [translator_id] IS NULL)',
	'SELECT * FROM [book_tag] WHERE ([book_tag].[book_id] IN (1)) ORDER BY [book_tag].[book_id], [tag_id]',
	'SELECT * FROM [tag] WHERE ([id] IN (21, 22))',
));


test(function() use ($context) {
	$context->query('UPDATE book SET translator_id = 12 WHERE id = 2');
	$author = $context->table('author')->get(11);

	foreach ($author->related('book')->limit(1) as $book) {
		$book->ref('author', 'translator_id')->name;
	}

	$translators = array();
	foreach ($author->related('book')->limit(2) as $book) {
		$translators[] = $book->ref('author', 'translator_id')->name;
	}
	sort($translators);

	Assert::same(array(
		'David Grudl',
		'Jakub Vrana',
	), $translators);
});
assertQueries(array(
	'UPDATE book SET translator_id = 12 WHERE id = 2',
	'SELECT * FROM [author] WHERE ([author].[id] = 11)',
	array(
		'SELECT * FROM [book] WHERE ([book].[author_id] IN (11)) LIMIT 1',
		'sqlsrv' => 'SELECT TOP 1 * FROM [book] WHERE ([book].[author_id] IN (11))',
	),
	'SELECT * FROM [author] WHERE ([id] IN (11))',
	array(
		'SELECT * FROM [book] WHERE ([book].[author_id] IN (11)) LIMIT 2',
		'sqlsrv' => 'SELECT TOP 2 * FROM [book] WHERE ([book].[author_id] IN (11))',
	),
	'SELECT * FROM [author] WHERE ([id] IN (11, 12))',
));
