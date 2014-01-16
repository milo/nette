<?php

/**
 * Test: Nette\Database\Table\Selection: Insert operations
 *
 * @author     Jakub Vrana
 * @author     Jan Skrasek
 * @dataProvider? ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/../connect.inc.php'; // create $connection

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/../files/{$driverName}-nette_test1.sql");
$monitor->start();


$book = $context->table('author')->insert(array(
	'name' => $context->literal('LOWER(?)', 'Eddard Stark'),
	'web' => 'http://example.com',
	'born' => new \DateTime('2011-11-11'),
));  // INSERT INTO `author` (`name`, `web`) VALUES (LOWER('Eddard Stark'), 'http://example.com', '2011-11-11 00:00:00')
// id = 14

Assert::equal('eddard stark', $book->name);
Assert::equal(new Nette\DateTime('2011-11-11'), $book->born);
assertQueries(array(
	'-- getColumns(author)',
	array(
		"INSERT INTO [author] ([name], [web], [born]) VALUES (LOWER(?), 'http://example.com', '2011-11-11 00:00:00')",
		'sqlite' => "INSERT INTO [author] ([name], [web], [born]) SELECT LOWER(?), 'http://example.com', 1320966000",
	),
	array(
		'pgsql' => '-- getColumns(author)', ###
	),
	"SELECT * FROM [author] WHERE ([author].[id] = '14')",
));


$books = $context->table('book');

$book1 = $books->get(1);  // SELECT * FROM `book` WHERE (`id` = ?)
Assert::same('Jakub Vrana', $book1->author->name);  // SELECT * FROM `author` WHERE (`author`.`id` IN (11))

$book2 = $books->insert(array(
	'title' => 'Dragonstone',
	'author_id' => $context->table('author')->get(14),  // SELECT * FROM `author` WHERE (`id` = ?)
));  // INSERT INTO `book` (`title`, `author_id`) VALUES ('Dragonstone', 14)

Assert::same('eddard stark', $book2->author->name);  // SELECT * FROM `author` WHERE (`author`.`id` IN (11, 15))
assertQueries(array(
	'-- getColumns(book)',
	'SELECT * FROM [book] WHERE ([book].[id] = 1)',
	'-- getForeignKeys(book)',
	'SELECT * FROM [author] WHERE ([id] IN (11))',
	'SELECT * FROM [author] WHERE ([author].[id] = 14)',
	array(
		"INSERT INTO [book] ([title], [author_id]) VALUES ('Dragonstone', 14)",
		'sqlite' => "INSERT INTO [book] ([title], [author_id]) SELECT 'Dragonstone', 14",
	),
	array(
		'pgsql' => '-- getColumns(book)', ###
	),
	"SELECT * FROM [book] WHERE ([book].[id] = '5')",
	'SELECT * FROM [author] WHERE ([id] IN (14))',
));


// SQL Server throw PDOException because does not allow insert explicit value for IDENTITY column.
// This exception is about primary key violation.
if ($driverName !== 'sqlsrv') {
	Assert::exception(function() use ($context) {
		$context->table('author')->insert(array(
			'id' => 14,
			'name' => 'Jon Snow',
			'web' => 'http://example.com',
		));
	}, '\PDOException');
}


switch ($driverName) {
	case 'mysql':
		$selection = $context->table('author')->select('NULL, id, NULL, CONCAT(?, name), NULL',  'Biography: ');
		break;
	case 'pgsql':
		$selection = $context->table('author')->select('nextval(?), id, NULL, ? || name, NULL', 'book_id_seq', 'Biography: ');
		break;
	case 'sqlite':
		$selection = $context->table('author')->select('NULL, id, NULL, ? || name, NULL', 'Biography: ');
		break;
	case 'sqlsrv':
		$selection = $context->table('author')->select('id, NULL, CONCAT(?, name), NULL', 'Biography: ');
		break;
	default:
		Assert::fail("Unsupported driver $driverName");
}
$context->table('book')->insert($selection);
Assert::equal(4, $context->table('book')->where('title LIKE', "Biography%")->count('*'));
assertQueries(array(
	array(
		'mysql' => 'INSERT INTO [book] SELECT NULL, [id], NULL, CONCAT(?, [name]), NULL FROM [author]',
		'pgsql' => 'INSERT INTO [book] SELECT nextval(?), [id], NULL, ? || [name], NULL FROM [author]',
		'sqlite' => 'INSERT INTO [book] SELECT NULL, [id], NULL, ? || [name], NULL FROM [author]',
		'sqlsrv' => 'INSERT INTO [book] SELECT [id], NULL, CONCAT(?, [name]), NULL FROM [author]',
	),
	"SELECT COUNT(*) FROM [book] WHERE ([title] LIKE 'Biography%')",
));


// Insert into table without primary key
$context = new Nette\Database\Context(
	$connection,
	new Nette\Database\Reflection\DiscoveredReflection($connection)
);

$inserted = $context->table('note')->insert(array(
	'book_id' => 1,
	'note' => 'Good one!',
));
Assert::equal(1, $inserted);
assertQueries(array(
	'-- getColumns(note)',
	array(
		"INSERT INTO [note] ([book_id], [note]) VALUES (1, 'Good one!')",
		'sqlite' => "INSERT INTO [note] ([book_id], [note]) SELECT 1, 'Good one!'",
	)
));
