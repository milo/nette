<?php

/**
 * Test: Nette\Database\Table: Update operations
 *
 * @author     Jakub Vrana
 * @author     Jan Skrasek
 * @dataProvider? ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/../connect.inc.php'; // create $connection

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/../files/{$driverName}-nette_test1.sql");
$monitor->start();


$author = $context->table('author')->get(12);  // SELECT * FROM `author` WHERE (`id` = ?)
$author->update(array(
	'name' => 'Tyrion Lannister',
));  // UPDATE `author` SET `name`='Tyrion Lannister' WHERE (`id` = 12)

$book = $context->table('book');

$book1 = $book->get(1);  // SELECT * FROM `book` WHERE (`id` = ?)
Assert::same('Jakub Vrana', $book1->author->name);  // SELECT * FROM `author` WHERE (`author`.`id` IN (11))
assertQueries(array(
	'-- getColumns(author)',
	'SELECT * FROM [author] WHERE ([author].[id] = 12)',
	'UPDATE [author] SET [name]=\'Tyrion Lannister\' WHERE ([author].[id] = 12)',
	'SELECT * FROM [author] WHERE ([author].[id] = 12)',
	'-- getColumns(book)',
	'SELECT * FROM [book] WHERE ([book].[id] = 1)',
	'-- getForeignKeys(book)',
	'SELECT * FROM [author] WHERE ([id] IN (11))',
));


$book2 = $book->insert(array(
	'author_id' => $author->getPrimary(),
	'title' => 'Game of Thrones',
));  // INSERT INTO `book` (`author_id`, `title`) VALUES (12, 'Game of Thrones')

Assert::same('Tyrion Lannister', $book2->author->name);  // SELECT * FROM `author` WHERE (`author`.`id` IN (12))
assertQueries(array(
	array(
		'INSERT INTO [book] ([author_id], [title]) VALUES (12, \'Game of Thrones\')',
		'sqlite' => 'INSERT INTO [book] ([author_id], [title]) SELECT 12, \'Game of Thrones\'',
	),
	array(
		'pgsql' => '-- getColumns(book)',
	),
	'SELECT * FROM [book] WHERE ([book].[id] = \'5\')',
	'SELECT * FROM [author] WHERE ([id] IN (12))',
));


$book2->update(array(
	'author_id' => $context->table('author')->get(12),  // SELECT * FROM `author` WHERE (`id` = ?)
));  // UPDATE `book` SET `author_id`=11 WHERE (`id` = '5')
Assert::same('Tyrion Lannister', $book2->author->name);  // NO SQL, SHOULD BE CACHED
assertQueries(array(
	'SELECT * FROM [author] WHERE ([author].[id] = 12)',
	'UPDATE [book] SET [author_id]=12 WHERE ([book].[id] = 5)',
	array(
		'pgsql' => 'SELECT * FROM [book] WHERE ([book].[id] = 5)',
		'sqlite' => 'SELECT * FROM [book] WHERE ([book].[id] = 5)',
		'sqlsrv' => 'SELECT * FROM [book] WHERE ([book].[id] = 5)',
	),
));


$book2->update(array(
	'author_id' => $context->table('author')->get(11),  // SELECT * FROM `author` WHERE (`id` = ?)
));  // UPDATE `book` SET `author_id`=11 WHERE (`id` = '5')
Assert::same('Jakub Vrana', $book2->author->name);  // SELECT * FROM `author` WHERE (`author`.`id` IN (11))
assertQueries(array(
	'SELECT * FROM [author] WHERE ([author].[id] = 11)',
	'UPDATE [book] SET [author_id]=11 WHERE ([book].[id] = 5)',
	'SELECT * FROM [book] WHERE ([book].[id] = 5)',
	'SELECT * FROM [author] WHERE ([id] IN (11))',
));


$book2->update(array(
	'author_id' => new Nette\Database\SqlLiteral('10 + 3'),
));  // UPDATE `book` SET `author_id`=13 WHERE (`id` = '5')

Assert::same('Geek', $book2->author->name);  // SELECT * FROM `author` WHERE (`author`.`id` IN (13))
Assert::same(13, $book2->author_id);
assertQueries(array(
	'UPDATE [book] SET [author_id]=10 + 3 WHERE ([book].[id] = 5)',
	'SELECT * FROM [book] WHERE ([book].[id] = 5)',
	'SELECT * FROM [author] WHERE ([id] IN (13))',
));


$tag = $context->table('tag')->insert(array(
	'name' => 'PC Game',
));  // INSERT INTO `tag` (`name`) VALUES ('PC Game')
assertQueries(array(
	'-- getColumns(tag)',
	array(
		'INSERT INTO [tag] ([name]) VALUES (\'PC Game\')',
		'sqlite' => 'INSERT INTO [tag] ([name]) SELECT \'PC Game\'',
	),
	array(
		'pgsql' => '-- getColumns(tag)',
	),
	'SELECT * FROM [tag] WHERE ([tag].[id] = \'25\')',
));


$tag->update(array(
	'name' => 'Xbox Game',
));  // UPDATE `tag` SET `name`='Xbox Game' WHERE (`id` = '24')
assertQueries(array(
	'UPDATE [tag] SET [name]=\'Xbox Game\' WHERE ([tag].[id] = 25)',
	'SELECT * FROM [tag] WHERE ([tag].[id] = 25)',
));


$bookTag = $book2->related('book_tag')->insert(array(
	'tag_id' => $tag,
));  // INSERT INTO `book_tag` (`tag_id`, `book_id`) VALUES (24, '5')
assertQueries(array(
	'-- getTables',
	'-- getForeignKeys(author)',
	'-- getForeignKeys(book)',
	'-- getForeignKeys(book_tag)',
	'-- getForeignKeys(book_tag_alt)',
	'-- getForeignKeys(note)',
	'-- getForeignKeys(tag)',
	'-- getColumns(book_tag)',
	array(
		'INSERT INTO [book_tag] ([tag_id], [book_id]) VALUES (25, 5)',
		'sqlite' => 'INSERT INTO [book_tag] ([tag_id], [book_id]) SELECT 25, 5',
	),
	array(
		'pgsql' => '-- getColumns(book_tag)',
	),
	'SELECT * FROM [book_tag] WHERE ([book_id] = 5) AND ([tag_id] = 25)',
));


$app = $context->table('book')->get(5);  // SELECT * FROM `book` WHERE (`id` = ?)
$tags = iterator_to_array($app->related('book_tag'));  // SELECT * FROM `book_tag` WHERE (`book_tag`.`book_id` IN (5))
Assert::same('Xbox Game', reset($tags)->tag->name);  // SELECT * FROM `tag` WHERE (`tag`.`id` IN (24))
assertQueries(array(
	'SELECT * FROM [book] WHERE ([book].[id] = 5)',
	'SELECT * FROM [book_tag] WHERE ([book_tag].[book_id] IN (5))',
	'SELECT * FROM [tag] WHERE ([id] IN (25))',
));
