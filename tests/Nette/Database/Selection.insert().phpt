<?php

/**
 * Test: Nette\Database\Table\Selection: Insert operations
 *
 * @author     Jakub Vrana
 * @author     Jan Skrasek
 * @package    Nette\Database
 * @dataProvider? databases.ini
 */

use Tester\Assert;

require __DIR__ . '/connect.inc.php'; // create $connection

loadSqlFile(__DIR__ . "/files/{$driverName}-nette_test1.sql");


$book = $dao->table('author')->insert(array(
	'name' => $connection->literal('LOWER(?)', 'Eddard Stark'),
	'web' => 'http://example.com',
	'born' => new \DateTime('2011-11-11'),
));  // INSERT INTO `author` (`name`, `web`) VALUES (LOWER('Eddard Stark'), 'http://example.com', '2011-11-11 00:00:00')
// id = 14

Assert::equal('eddard stark', $book->name);
Assert::equal(new Nette\DateTime('2011-11-11'), $book->born);
Assert::same(reformat(array(
	array(
		'mysql|pgsql' => "INSERT INTO [author] ([name], [web], [born]) VALUES (LOWER(?), 'http://example.com', '2011-11-11 00:00:00')",
		'sqlite' => "INSERT INTO [author] ([name], [web], [born]) SELECT LOWER(?), 'http://example.com', 1320966000",
	),
	array(
		'pgsql' => 'getColumns(author)',
		'mysql|sqlite' => NULL,
	),
	"SELECT * FROM [author] WHERE ([id] = '14')",
)), $monitor->getQueries());



$books = $dao->table('book');

$book1 = $books->get(1);  // SELECT * FROM `book` WHERE (`id` = ?)
Assert::same('Jakub Vrana', $book1->author->name);  // SELECT * FROM `author` WHERE (`author`.`id` IN (11))

$book2 = $books->insert(array(
	'title' => 'Dragonstone',
	'author_id' => $dao->table('author')->get(14),  // SELECT * FROM `author` WHERE (`id` = ?)
));  // INSERT INTO `book` (`title`, `author_id`) VALUES ('Dragonstone', 14)

Assert::same('eddard stark', $book2->author->name);  // SELECT * FROM `author` WHERE (`author`.`id` IN (11, 15))
Assert::same(reformat(array(
	'SELECT * FROM [book] WHERE ([id] = 1)',
	'SELECT * FROM [author] WHERE ([id] IN (11))',
	'SELECT * FROM [author] WHERE ([id] = 14)',
	array(
		'mysql|pgsql' => "INSERT INTO [book] ([title], [author_id]) VALUES ('Dragonstone', 14)",
		'sqlite' => "INSERT INTO [book] ([title], [author_id]) SELECT 'Dragonstone', 14",
	),
	array(
		'pgsql' => 'getColumns(book)',
		'mysql|sqlite' => NULL,
	),
	"SELECT * FROM [book] WHERE ([id] = '5')",
	'SELECT * FROM [author] WHERE ([id] IN (14))',
)), $monitor->getQueries());



// SQL Server throw PDOException because does not allow insert explicit value for IDENTITY column.
// This exception is about primary key violation.
if ($driverName !== 'sqlsrv') {
	Assert::exception(function() use ($dao) {
		$dao->table('author')->insert(array(
			'id' => 14,
			'name' => 'Jon Snow',
			'web' => 'http://example.com',
		));
	}, '\PDOException');
	Assert::same(array(), $monitor->getQueries());
}



switch ($driverName) {
	case 'mysql':
		$selection = $dao->table('author')->select('NULL, id, NULL, CONCAT(?, name), NULL',  'Biography: ');
		break;
	case 'pgsql':
		$selection = $dao->table('author')->select('nextval(?), id, NULL, ? || name, NULL', 'book_id_seq', 'Biography: ');
		break;
	case 'sqlite':
		$selection = $dao->table('author')->select('NULL, id, NULL, ? || name, NULL', 'Biography: ');
		break;
	case 'sqlsrv':
		$selection = $dao->table('author')->select('id, NULL, CONCAT(?, name), NULL', 'Biography: ');
		break;
	default:
		Assert::fail("Unsupported driver $driverName");
}
$dao->table('book')->insert($selection);
Assert::equal(4, $dao->table('book')->where('title LIKE', "Biography%")->count('*'));
Assert::same(reformat(array(
	array(
		'mysql' => 'INSERT INTO `book` SELECT NULL, `id`, NULL, CONCAT(?, `name`), NULL FROM `author`',
		'pgsql' => 'INSERT INTO "book" SELECT nextval(?), "id", NULL, ? || "name", NULL FROM "author"',
		'sqlite' => 'INSERT INTO [book] SELECT NULL, [id], NULL, ? || [name], NULL FROM [author]',
	),
	'SELECT COUNT(*) FROM [book] WHERE ([title] LIKE \'Biography%\')',
)), $monitor->getQueries());



// Insert into table without primary key
$dao = new Nette\Database\SelectionFactory(
	$connection,
	new Nette\Database\Reflection\DiscoveredReflection($connection)
);

$inserted = $dao->table('note')->insert(array(
	'book_id' => 1,
	'note' => 'Good one!',
));
Assert::equal(1, $inserted);
Assert::same(reformat(array(
	'getColumns(note)',
	array(
		'mysql|pgsql' => "INSERT INTO [note] ([book_id], [note]) VALUES (1, 'Good one!')",
		'sqlite' => "INSERT INTO [note] ([book_id], [note]) SELECT 1, 'Good one!'",
	),
)), $monitor->getQueries());
