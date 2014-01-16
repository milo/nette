<?php

/**
 * Test: Nette\Database\Table\SqlBuilder: Escaping with SqlLiteral.
 *
 * @author     Jan Skrasek
 * @dataProvider? ../databases.ini
 */

use Tester\Assert;
use Nette\Database\SqlLiteral;

require __DIR__ . '/../connect.inc.php'; // create $connection

Nette\Database\Helpers::loadFromFile($connection, __DIR__ . "/../files/{$driverName}-nette_test1.sql");
$monitor->start();


test(function() use ($context, $driverName) {
	// Leave literals lower-cased, also not-delimiting them is tested.
	switch ($driverName) {
		case 'mysql':
			$literal = new SqlLiteral('year(now())');
			break;
		case 'pgsql':
			$literal = new SqlLiteral('extract(year from now())::int');
			break;
		case 'sqlite':
			$literal = new SqlLiteral("cast(strftime('%Y', date('now')) as integer)");
			break;
		case 'sqlsrv':
			$literal = new SqlLiteral('year(cast(current_timestamp as datetime))');
			break;
		default:
			Assert::fail("Unsupported driver $driverName");
	}

	$selection = $context
		->table('book')
		->select('? AS col1', 'hi there!')
		->select('? AS col2', $literal);

	$row = $selection->fetch();
	Assert::same('hi there!', $row['col1']);
	Assert::same((int) date('Y'), $row['col2']);
});
assertQueries(array(
	'-- getColumns(book)',
	array(
		'mysql' => 	"SELECT 'hi there!' AS [col1], year(now()) AS [col2] FROM [book]",
		'pgsql' => 	"SELECT 'hi there!' AS [col1], extract(year from now())::int AS [col2] FROM [book]",
		'sqlite' => "SELECT 'hi there!' AS [col1], cast(strftime('%Y', date('now')) as integer) AS [col2] FROM [book]",
		'sqlsrv' => "SELECT 'hi there!' AS [col1], year(cast(current_timestamp as datetime)) AS [col2] FROM [book]",
	),
));



test(function() use ($context) {
	$bookTagsCount = array();
	$books = $context
		->table('book')
		->select('book.title, COUNT(DISTINCT :book_tag.tag_id) AS tagsCount')
		->group('book.title')
		->having('COUNT(DISTINCT :book_tag.tag_id) < ?', 2)
		->order('book.title');

	foreach ($books as $book) {
		$bookTagsCount[$book->title] = $book->tagsCount;
	}

	Assert::same(array(
		'JUSH' => 1,
		'Nette' => 1,
	), $bookTagsCount);
});
assertQueries(array(
	'-- getTables',
	'-- getForeignKeys(author)',
	'-- getForeignKeys(book)',
	'-- getForeignKeys(book_tag)',
	'-- getForeignKeys(book_tag_alt)',
	'-- getForeignKeys(note)',
	'-- getForeignKeys(tag)',
	'SELECT [book].[title], COUNT(DISTINCT [book_tag].[tag_id]) AS [tagsCount] '
		. 'FROM [book] LEFT JOIN [book_tag] ON [book].[id] = [book_tag].[book_id] '
		. 'GROUP BY [book].[title] HAVING COUNT(DISTINCT [book_tag].[tag_id]) < 2 ORDER BY [book].[title]',
));


### Sqlsrv: SQLSTATE[42000]: [Microsoft][SQL Server Native Client 11.0][SQL Server]Incorrect syntax near '='
### Needs different test case
if ($driverName !== 'sqlsrv') {
	test(function() use ($context) { // Test placeholder for GroupedSelection
		$books = $context->table('author')->get(11)->related('book')->order('title = ? DESC', 'Test');
		foreach ($books as $book) {}

		$books = $context->table('author')->get(11)->related('book')->select('SUBSTR(title, ?)', 3);
		foreach ($books as $book) {}
	});
	assertQueries(array(
		'-- getColumns(author)',
		'SELECT * FROM [author] WHERE ([author].[id] = 11)',
		'SELECT * FROM [book] WHERE ([book].[author_id] IN (11)) ORDER BY [book].[author_id] DESC, [title] = \'Test\' DESC',
		'SELECT * FROM [author] WHERE ([author].[id] = 11)',
		'SELECT [book].[author_id], SUBSTR([title], 3) FROM [book] WHERE ([book].[author_id] IN (11))',
	));
}


test(function() use ($context, $driverName) {
	if ($driverName === 'mysql') {
		$authors = array();
		$selection = $context->table('author')->order('FIELD(name, ?)', array('Jakub Vrana', 'David Grudl', 'Geek'));
		foreach ($selection as $author) {
			$authors[] = $author->name;
		}

		Assert::same(array('Jakub Vrana', 'David Grudl', 'Geek'), $authors);
	}
});
assertQueries(array(
	array(
		'mysql' => "SELECT * FROM [author] ORDER BY FIELD([name], 'Jakub Vrana', 'David Grudl', 'Geek')",
	),
));
