<?php

class QueryMonitor
{
	private $running = TRUE;

	private $queries = array();

	private $driverName;

	public function __construct($driverName)
	{
		$this->driverName = $driverName;
	}

	public function stop()
	{
		$this->running = FALSE;
	}

	public function start()
	{
		$this->running = TRUE;
	}

	public function query($sql)
	{
		if ($this->running) {
			$this->queries[] = $sql;
		}
	}

	public function reflection($query)
	{
		if ($this->running) {
			$this->queries[] = $query;
		}
	}

	public function getQueries($flush = TRUE)
	{
		$ret = $this->queries;
		$flush && ($this->queries = array());
		return $ret;
	}

	public function flush()
	{
		$this->queries = $this->sql = $this->reflections = array();
	}

}



class DriverWrapper extends Nette\Object implements Nette\Database\ISupplementalDriver
{
	private $monitor;

	private $driver;

	public function __construct(Nette\Database\ISupplementalDriver $driver, QueryMonitor $monitor)
	{
		$this->driver = $driver;
		$this->monitor = $monitor;
	}

	function delimite($name)
	{
		return $this->driver->delimite($name);
	}

	function formatBool($value)
	{
		return $this->driver->formatBool($value);
	}

	function formatDateTime(\DateTime $value)
	{
		return $this->driver->formatDateTime($value);
	}

	function formatLike($value, $pos)
	{
		return $this->driver->formatLike($value, $pos);
	}

	function applyLimit(&$sql, $limit, $offset)
	{
		return $this->driver->applyLimit($sql, $limit, $offset);
	}

	function normalizeRow($row)
	{
		return $this->driver->normalizeRow($row);
	}


	/********************* reflection ****************d*g**/


	function getTables()
	{
		$this->monitor->stop();
		$ret = $this->driver->getTables();
		$this->monitor->start();

		$this->monitor->reflection(__FUNCTION__);
		return $ret;
	}

	function getColumns($table)
	{
		$this->monitor->stop();
		$ret = $this->driver->getColumns($table);
		$this->monitor->start();

		$this->monitor->reflection(__FUNCTION__ . "($table)");
		return $ret;
	}

	function getIndexes($table)
	{
		$this->monitor->stop();
		$ret = $this->driver->getIndexes($table);
		$this->monitor->start();

		$this->monitor->reflection(__FUNCTION__ . "($table)");
		return $ret;
	}

	function getForeignKeys($table)
	{
		$this->monitor->stop();
		$ret = $this->driver->getForeignKeys($table);
		$this->monitor->start();

		$this->monitor->reflection(__FUNCTION__ . "($table)");
		return $ret;
	}

	function getColumnTypes(\PDOStatement $statement)
	{
		return $this->driver->getColumnTypes($statement);
	}

	function isSupported($item)
	{
		return $this->driver->isSupported($item);
	}

}
