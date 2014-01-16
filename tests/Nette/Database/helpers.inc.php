<?php

class QueryMonitor
{
	/** @var bool */
	private $running = FALSE;

	/** @var int */
	private $level = 0;

	/** @var array|NULL  NULL means 'has not been started' */
	private $queries;


	public function start()
	{
		if ($this->queries === NULL && $this->level >= 0) {
			$this->queries = array();
		}
		$this->running = ++$this->level > 0;
	}


	public function stop()
	{
		$this->running = --$this->level > 0;
	}


	/**
	 * @param  string
	 */
	public function query($sql)
	{
		if ($this->running) {
			$this->queries[] = $sql;
		}
	}


	/**
	 * @param  string
	 */
	public function reflection($query)
	{
		if ($this->running) {
			$this->queries[] = "-- $query";
		}
	}


	/**
	 * @param  bool
	 * @return array|NULL
	 */
	public function getQueries($flush = TRUE)
	{
		$ret = $this->queries;
		if ($flush && $this->queries !== NULL) {
			$this->queries = array();
		}
		return $ret;
	}

}



class DriverWrapper extends Nette\Object implements Nette\Database\ISupplementalDriver
{
	/** @var Nette\Database\ISupplementalDriver */
	private $driver;

	/** QueryMonitor */
	private $monitor;


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

	function formatDateTime(/*\DateTimeInterface*/ $value)
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


	/********************* reflection *********************/


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
