<?php

/**
 * Test: Nette\Database\Table: Basic operations with DiscoveredReflection.
 *
 * @author     Jakub Vrana
 * @author     Jan Skrasek
 * @dataProvider? ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/../connect.inc.php'; // create $connection

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/../files/{$driverName}-nette_test1.sql");
$monitor->start();


test(function() use ($context) {
	$appTags = array();
	foreach ($context->table('book') as $book) {
		$appTags[$book->title] = array(
			'author' => $book->author->name,
			'tags' => array(),
		);

		foreach ($book->related('book_tag') as $book_tag) {
			$appTags[$book->title]['tags'][] = $book_tag->tag->name;
		}
	}

	Assert::same(array(
		'1001 tipu a triku pro PHP' => array(
			'author' => 'Jakub Vrana',
			'tags' => array('PHP', 'MySQL'),
		),
		'JUSH' => array(
			'author' => 'Jakub Vrana',
			'tags' => array('JavaScript'),
		),
		'Nette' => array(
			'author' => 'David Grudl',
			'tags' => array('PHP'),
		),
		'Dibi' => array(
			'author' => 'David Grudl',
			'tags' => array('PHP', 'MySQL'),
		),
	), $appTags);
});
assertQueries(array(
	'-- getColumns(book)',
	'SELECT * FROM [book]',
	'-- getForeignKeys(book)',
	'-- getColumns(author)',
	'SELECT * FROM [author] WHERE ([id] IN (11, 12))',
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
));


test(function() use ($context) {
	$books = array();
	foreach ($context->table('author') as $author) {
		foreach ($author->related('book') as $book) {
			$books[$book->title] = $author->name;
		}
	}

	Assert::same(array(
		'1001 tipu a triku pro PHP' => 'Jakub Vrana',
		'JUSH' => 'Jakub Vrana',
		'Nette' => 'David Grudl',
		'Dibi' => 'David Grudl',
	), $books);
});
assertQueries(array(
	'SELECT * FROM [author]',
	'SELECT * FROM [book] WHERE ([book].[author_id] IN (11, 12, 13))'
));


test(function() use ($context) {
	$book = $context->table('book')->get(1);
	Assert::same('Jakub Vrana', $book->translator->name);
});
assertQueries(array(
	'SELECT * FROM [book] WHERE ([book].[id] = 1)',
	'SELECT * FROM [author] WHERE ([id] IN (11))',
));


test(function() use ($context) {
	$book = $context->table('book')->get(2);
	Assert::true(isset($book->author_id));
	Assert::false(empty($book->author_id));

	Assert::false(isset($book->translator_id));
	Assert::true(empty($book->translator_id));
	Assert::false(isset($book->test));

	Assert::false(isset($book->author));
	Assert::false(isset($book->translator));
	Assert::true(empty($book->author));
	Assert::true(empty($book->translator));
});
assertQueries(array(
	'SELECT * FROM [book] WHERE ([book].[id] = 2)'
));


test(function() use ($connection, $context) {
	if (
		$connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' &&
		($lowerCase = $connection->query('SHOW VARIABLES LIKE "lower_case_table_names"')->fetch()) &&
		$lowerCase->Value != 0
	) {
		// tests case-insensitive reflection
		$books = array();
		foreach ($context->table('Author') as $author) {
			foreach ($author->related('book') as $book) {
				$books[$book->title] = $author->name;
			}
		}

		Assert::same(array(
			'1001 tipu a triku pro PHP' => 'Jakub Vrana',
			'JUSH' => 'Jakub Vrana',
			'Nette' => 'David Grudl',
			'Dibi' => 'David Grudl',
		), $books);
	}
});
assertQueries(array(
	array(
		'mysql' => 'SHOW VARIABLES LIKE "lower_case_table_names"'
	),
));


test(function() use ($context) {
	$count = $context->table('book')->where('translator.name LIKE ?', '%David%')->count();
	Assert::same(2, $count);
	$count = $context->table('book')->where('author.name LIKE ?', '%David%')->count();
	Assert::same(2, $count);
});
assertQueries(array(
	"SELECT [book].* FROM [book] LEFT JOIN [author] AS [translator] ON [book].[translator_id] = [translator].[id] WHERE ([translator].[name] LIKE '%David%')",
	"SELECT [book].* FROM [book] LEFT JOIN [author] ON [book].[author_id] = [author].[id] WHERE ([author].[name] LIKE '%David%')",
));
