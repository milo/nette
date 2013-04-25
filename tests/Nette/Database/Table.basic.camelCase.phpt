<?php

/**
 * Test: Nette\Database\Table: Basic operations with camelCase name conventions.
 *
 * @author     David Grudl
 * @author     Jan Skrasek
 * @package    Nette\Database
 * @dataProvider? databases.ini
 */

require __DIR__ . '/connect.inc.php'; // create $connection

loadSqlFile(__DIR__ . "/files/{$driverName}-nette_test2.sql");
$dao = new Nette\Database\SelectionFactory(
	$connection,
	new Nette\Database\Reflection\DiscoveredReflection($connection)
);



$titles = array();
foreach ($dao->table('nUsers')->order('nUserId') as $user) {
	foreach ($user->related('nUsers_nTopics')->order('nTopicId') as $userTopic) {
		$titles[$userTopic->nTopic->title] = $user->name;
	}
}

Assert::same(array(
	'Topic #1' => 'John',
	'Topic #3' => 'John',
	'Topic #2' => 'Doe',
), $titles);

Assert::same(reformat(array(
	'getColumns(nUsers)',
	'SELECT * FROM [nUsers] ORDER BY [nUserId]',
	'getTables',
	'getForeignKeys(nPriorities)',
	'getForeignKeys(nTopics)',
	'getForeignKeys(nUsers)',
	'getForeignKeys(nUsers_nTopics)',
	'getForeignKeys(nUsers_nTopics_alt)',
	'getColumns(nUsers_nTopics)',
	'SELECT * FROM [nUsers_nTopics] WHERE ([nUsers_nTopics].[nUserId] IN (1, 2)) ORDER BY [nUsers_nTopics].[nUserId], [nTopicId]',
	'getColumns(nTopics)',
	'SELECT * FROM [nTopics] WHERE ([nTopicId] IN (10, 12, 11))',
)), $monitor->getQueries());
