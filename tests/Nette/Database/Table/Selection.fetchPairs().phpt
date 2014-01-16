<?php

/**
 * Test: Nette\Database\Table: Fetch pairs.
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
	$apps = $context->table('book')->order('title')->fetchPairs('id', 'title');  // SELECT * FROM `book` ORDER BY `title`
	Assert::same(array(
		1 => '1001 tipu a triku pro PHP',
		4 => 'Dibi',
		2 => 'JUSH',
		3 => 'Nette',
	), $apps);
});
assertQueries(array(
	'-- getColumns(book)', 'SELECT * FROM [book] ORDER BY [title]'
));


test(function() use ($context) {
	$ids = $context->table('book')->order('id')->fetchPairs('id', 'id');  // SELECT * FROM `book` ORDER BY `id`
	Assert::same(array(
		1 => 1,
		2 => 2,
		3 => 3,
		4 => 4,
	), $ids);
});
assertQueries(array(
	'SELECT * FROM [book] ORDER BY [id]'
));


test(function() use ($context) {
	$context->table('author')->get(11)->update(array('born' => new DateTime('2002-02-20')));
	$context->table('author')->get(12)->update(array('born' => new DateTime('2002-02-02')));
	$list = $context->table('author')->where('born IS NOT NULL')->order('born')->fetchPairs('born', 'name');
	Assert::same(array(
		'2002-02-02 00:00:00' => 'David Grudl',
		'2002-02-20 00:00:00' => 'Jakub Vrana',
	), $list);
});
assertQueries(array(
	'-- getColumns(author)',
	'SELECT * FROM [author] WHERE ([author].[id] = 11)',
	array(
		"UPDATE [author] SET [born]='2002-02-20 00:00:00' WHERE ([author].[id] = 11)",
		'sqlite' => 'UPDATE [author] SET [born]=1014159600 WHERE ([author].[id] = 11)',
	),
	'SELECT * FROM [author] WHERE ([author].[id] = 11)',
	'SELECT * FROM [author] WHERE ([author].[id] = 12)',
	array(
		"UPDATE [author] SET [born]='2002-02-02 00:00:00' WHERE ([author].[id] = 12)",
		'sqlite' => 'UPDATE [author] SET [born]=1012604400 WHERE ([author].[id] = 12)',
	),
	'SELECT * FROM [author] WHERE ([author].[id] = 12)',
	'SELECT * FROM [author] WHERE ([born] IS NOT NULL) ORDER BY [born]',
));
