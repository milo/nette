<?php

/**
 * Test: Nette\Database\Table: Basic operations.
 *
 * @author     Jakub Vrana
 * @author     Jan Skrasek
 * @package    Nette\Database
 * @dataProvider? databases.ini
 */

require __DIR__ . '/connect.inc.php'; // create $connection

loadSqlFile(__DIR__ . "/files/{$driverName}-nette_test1.sql");



$book = $dao->table('book')->where('id = ?', 1)->select('id, title')->fetch()->toArray();  // SELECT `id`, `title` FROM `book` WHERE (`id` = ?)
Assert::same(array(
	'id' => 1,
	'title' => '1001 tipu a triku pro PHP',
), $book);
Assert::same(array(reformat('SELECT [id], [title] FROM [book] WHERE ([id] = 1)')), $monitor->getQueries());


$book = $dao->table('book')->select('id, title')->where('id = ?', 1)->fetch()->toArray();  // SELECT `id`, `title` FROM `book` WHERE (`id` = ?)
Assert::same(array(
	'id' => 1,
	'title' => '1001 tipu a triku pro PHP',
), $book);
Assert::same(array(reformat('SELECT [id], [title] FROM [book] WHERE ([id] = 1)')), $monitor->getQueries());


$book = $dao->table('book')->get(1);
Assert::exception(function() use ($book) {
	$book->unknown_column;
}, 'Nette\MemberAccessException', 'Cannot read an undeclared column "unknown_column".');
Assert::same(array(reformat('SELECT * FROM [book] WHERE ([id] = 1)')), $monitor->getQueries());



$bookTags = array();
foreach ($dao->table('book') as $book) {  // SELECT * FROM `book`
	$bookTags[$book->title] = array(
		'author' => $book->author->name,  // SELECT * FROM `author` WHERE (`author`.`id` IN (11, 12))
		'tags' => array(),
	);

	foreach ($book->related('book_tag') as $book_tag) {  // SELECT * FROM `book_tag` WHERE (`book_tag`.`book_id` IN (1, 2, 3, 4))
		$bookTags[$book->title]['tags'][] = $book_tag->tag->name;  // SELECT * FROM `tag` WHERE (`tag`.`id` IN (21, 22, 23))
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
), $bookTags);

Assert::same(reformat(array(
	'SELECT * FROM [book]',
	'SELECT * FROM [author] WHERE ([id] IN (11, 12))',
	'SELECT * FROM [book_tag] WHERE ([book_tag].[book_id] IN (1, 2, 3, 4))',
	'SELECT * FROM [tag] WHERE ([id] IN (21, 22, 23))',
)), $monitor->getQueries());



$dao = new Nette\Database\SelectionFactory(
	$connection,
	new Nette\Database\Reflection\DiscoveredReflection($connection)
);

$book = $dao->table('book')->get(1);
Assert::exception(function() use ($book) {
	$book->test;
}, 'Nette\MemberAccessException', 'Cannot read an undeclared column "test".');

Assert::exception(function() use ($book) {
	$book->ref('test');
}, 'Nette\Database\Reflection\MissingReferenceException', 'No reference found for $book->test.');

Assert::exception(function() use ($book) {
	$book->related('test');
}, 'Nette\Database\Reflection\MissingReferenceException', 'No reference found for $book->related(test).');

Assert::same(reformat(array(
	'getColumns(book)',
	'SELECT * FROM [book] WHERE ([id] = 1)',
	'getForeignKeys(book)',
	'getForeignKeys(book)',
	'getTables',
	'getForeignKeys(author)',
	'getForeignKeys(book)',
	'getForeignKeys(book_tag)',
	'getForeignKeys(book_tag_alt)',
	'getForeignKeys(note)',
	'getForeignKeys(tag)',
)), $monitor->getQueries());
