<?php

/**
 * Test: Nette\Database\Table: Special case of caching
 *
 * @author     Jachym Tousek
 * @dataProvider? ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/../connect.inc.php'; // create $connection

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/../files/{$driverName}-nette_test1.sql");
$monitor->start();


for ($i = 1; $i <= 2; ++$i) {

	foreach ($context->table('author') as $author) {
		$author->name;
		foreach ($author->related('book', 'author_id') as $book) {
			$book->title;
		}
	}

	foreach ($context->table('author')->where('id', 13) as $author) {
		$author->name;
		foreach ($author->related('book', 'author_id') as $book) {
			$book->title;
		}
	}

}
assertQueries(array(
	'-- getColumns(author)',
	'SELECT * FROM [author]',
	'-- getColumns(book)',
	'SELECT * FROM [book] WHERE ([book].[author_id] IN (11, 12, 13))',
	'SELECT * FROM [author] WHERE ([id] = 13)',
	'SELECT * FROM [book] WHERE ([book].[author_id] IN (13))',
	'SELECT * FROM [author]',
	'SELECT * FROM [book] WHERE ([book].[author_id] IN (11, 12, 13))',
	'SELECT * FROM [author] WHERE ([id] = 13)',
	'SELECT * FROM [book] WHERE ([book].[author_id] IN (13))',
));
