<?php

/**
 * Test: Nette\Database\Table: Join.
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
	$apps = array();
	foreach ($context->table('book')->order('author.name, title') as $book) {  // SELECT `book`.* FROM `book` LEFT JOIN `author` ON `book`.`author_id` = `author`.`id` ORDER BY `author`.`name`, `title`
		$apps[$book->title] = $book->author->name;  // SELECT * FROM `author` WHERE (`author`.`id` IN (12, 11))
	}

	Assert::same(array(
		'Dibi' => 'David Grudl',
		'Nette' => 'David Grudl',
		'1001 tipu a triku pro PHP' => 'Jakub Vrana',
		'JUSH' => 'Jakub Vrana',
	), $apps);
});
assertQueries(array(
	'-- getColumns(book)',
	'-- getForeignKeys(book)',
	'-- getColumns(author)',
	'SELECT [book].* FROM [book] LEFT JOIN [author] ON [book].[author_id] = [author].[id] ORDER BY [author].[name], [title]',
	'SELECT * FROM [author] WHERE ([id] IN (12, 11))',
));


test(function() use ($context) {
	$joinSql = $context->table('book_tag')->where('book_id', 1)->select('tag.*')->getSql();
	Assert::same(reformat('SELECT [tag].* FROM [book_tag] LEFT JOIN [tag] ON [book_tag].[tag_id] = [tag].[id] WHERE ([book_id] = ?)'), $joinSql);
});
assertQueries(array(
	'-- getColumns(book_tag)',
	'-- getForeignKeys(book_tag)',
	'-- getColumns(tag)',
));


test(function() use ($context) {
	$joinSql = $context->table('book_tag')->where('book_id', 1)->select('Tag.id')->getSql();
	Assert::same(reformat('SELECT [Tag].[id] FROM [book_tag] LEFT JOIN [tag] AS [Tag] ON [book_tag].[tag_id] = [Tag].[id] WHERE ([book_id] = ?)'), $joinSql);
});
assertQueries(array(
));


test(function() use ($context) {
	$tags = array();
	foreach ($context->table('book_tag')->where('book.author.name', 'Jakub Vrana')->group('book_tag.tag_id')->order('book_tag.tag_id') as $book_tag) {  // SELECT `book_tag`.* FROM `book_tag` INNER JOIN `book` ON `book_tag`.`book_id` = `book`.`id` INNER JOIN `author` ON `book`.`author_id` = `author`.`id` WHERE (`author`.`name` = ?) GROUP BY `book_tag`.`tag_id`
		$tags[] = $book_tag->tag->name;  // SELECT * FROM `tag` WHERE (`tag`.`id` IN (21, 22, 23))
	}

	Assert::same(array(
		'PHP',
		'MySQL',
		'JavaScript',
	), $tags);
});
assertQueries(array(
	array(
		'SELECT [book_tag].[tag_id] FROM [book_tag] LEFT JOIN [book] ON [book_tag].[book_id] = [book].[id] LEFT JOIN [author] ON [book].[author_id] = [author].[id] WHERE ([author].[name] = \'Jakub Vrana\') GROUP BY [book_tag].[tag_id] ORDER BY [book_tag].[tag_id]',
		'mysql' => 	'SELECT [book_tag].* FROM [book_tag] LEFT JOIN [book] ON [book_tag].[book_id] = [book].[id] LEFT JOIN [author] ON [book].[author_id] = [author].[id] WHERE ([author].[name] = \'Jakub Vrana\') GROUP BY [book_tag].[tag_id] ORDER BY [book_tag].[tag_id]',
	),
	'SELECT * FROM [tag] WHERE ([id] IN (21, 22, 23))',
));


test(function() use ($context) {
	Assert::same(2, $context->table('author')->where('author_id', 11)->count(':book.id')); // SELECT COUNT(book.id) FROM `author` LEFT JOIN `book` ON `author`.`id` = `book`.`author_id` WHERE (`author_id` = 11)
});


test(function() use ($connection) {
	$context = new Nette\Database\Context(
		$connection,
		new Nette\Database\Reflection\DiscoveredReflection($connection)
	);

	$books = $context->table('book')->select('book.*, author.name, translator.name');
	iterator_to_array($books);
});
assertQueries(array(
	'SELECT COUNT([book].[id]) FROM [author] LEFT JOIN [book] ON [author].[id] = [book].[author_id] WHERE ([author_id] = 11)',
	'-- getColumns(book)',
	'-- getForeignKeys(book)',
	'-- getColumns(author)',
	'SELECT [book].*, [author].[name], [translator].[name] FROM [book] LEFT JOIN [author] ON [book].[author_id] = [author].[id] LEFT JOIN [author] AS [translator] ON [book].[translator_id] = [translator].[id]',
));
