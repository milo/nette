<?php

/**
 * Test: Nette\Database\Table: Multi insert operations
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
	$context->table('author')->insert(array(
		array(
			'name' => 'Catelyn Stark',
			'web' => 'http://example.com',
			'born' => new DateTime('2011-11-11'),
		),
		array(
			'name' => 'Sansa Stark',
			'web' => 'http://example.com',
			'born' => new DateTime('2021-11-11'),
		),
	));  // INSERT INTO `author` (`name`, `web`, `born`) VALUES ('Catelyn Stark', 'http://example.com', '2011-11-11 00:00:00'), ('Sansa Stark', 'http://example.com', '2021-11-11 00:00:00')


	$context->table('book_tag')->where('book_id', 1)->delete();  // DELETE FROM `book_tag` WHERE (`book_id` = ?)
	$context->table('book')->get(1)->related('book_tag')->insert(array(  // SELECT * FROM `book` WHERE (`id` = ?)
		array('tag_id' => 21),
		array('tag_id' => 22),
		array('tag_id' => 23),
	));  // INSERT INTO `book_tag` (`tag_id`, `book_id`) VALUES (21, 1), (22, 1), (23, 1)
});
assertQueries(array(
	'-- getColumns(author)',
	array(
		"INSERT INTO [author] ([name], [web], [born]) VALUES ('Catelyn Stark', 'http://example.com', '2011-11-11 00:00:00'), ('Sansa Stark', 'http://example.com', '2021-11-11 00:00:00')",
		'sqlite' => "INSERT INTO [author] ([name], [web], [born]) SELECT 'Catelyn Stark', 'http://example.com', 1320966000 UNION ALL SELECT 'Sansa Stark', 'http://example.com', 1636585200",
	),
	array(
		'pgsql' => '-- getColumns(author)', ###
	),
	array(
		"SELECT * FROM [author] WHERE ([author].[id] = '15')",
		'mysql' => "SELECT * FROM [author] WHERE ([author].[id] = '14')",
	),
	'-- getColumns(book_tag)',
	'DELETE FROM [book_tag] WHERE ([book_id] = 1)',
	'-- getColumns(book)',
	'SELECT * FROM [book] WHERE ([book].[id] = 1)',
	'-- getTables',
	'-- getForeignKeys(author)',
	'-- getForeignKeys(book)',
	'-- getForeignKeys(book_tag)',
	'-- getForeignKeys(book_tag_alt)',
	'-- getForeignKeys(note)',
	'-- getForeignKeys(tag)',
	array(
		'INSERT INTO [book_tag] ([tag_id], [book_id]) VALUES (21, 1), (22, 1), (23, 1)',
		'sqlite' => 'INSERT INTO [book_tag] ([tag_id], [book_id]) SELECT 21, 1 UNION ALL SELECT 22, 1 UNION ALL SELECT 23, 1',
	),
	array(
		'pgsql' => '-- getColumns(book_tag)', ###
	),
));
