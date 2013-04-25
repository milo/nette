<?php

/**
 * Test: Nette\Database\ResultSet: Fetch field.
 *
 * @author     David Grudl
 * @package    Nette\Database
 * @dataProvider? databases.ini
 */

require __DIR__ . '/connect.inc.php'; // create $connection

loadSqlFile(__DIR__ . "/files/{$driverName}-nette_test1.sql");


$res = $connection->query('SELECT name, id FROM author ORDER BY id');

Assert::same('Jakub Vrana', $res->fetchField());
Assert::same(12, $res->fetchField(1));
Assert::same('Geek', $res->fetchField('name'));

Assert::same(array('SELECT name, id FROM author ORDER BY id',), $monitor->getQueries());
