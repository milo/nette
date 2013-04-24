<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Caching\Storages;

use Nette,
	Nette\Caching\Cache;



/**
 * Memcached storage.
 *
 * @author     David Grudl
 */
class MemcachedStorage extends Nette\Object implements Nette\Caching\IStorage
{
	/** @internal cache structure */
	const META_CALLBACKS = 'callbacks',
		META_DATA = 'data',
		META_DELTA = 'delta';

	/** @var \Memcache */
	private $memcache;

	/** @var string */
	private $prefix;

	/** @var IJournal */
	private $journal;

	/** @var callable */
	private $failureCallback;

	/** @var callable */
	private $errorHandler;



	/**
	 * Checks if Memcached extension is available.
	 * @return bool
	 */
	public static function isAvailable()
	{
		return extension_loaded('memcache');
	}



	public function __construct($host = 'localhost', $port = 11211, $prefix = '', IJournal $journal = NULL)
	{
		if (!static::isAvailable()) {
			throw new Nette\NotSupportedException("PHP extension 'memcache' is not loaded.");
		}

		$this->prefix = $prefix;
		$this->journal = $journal;
		$this->memcache = new \Memcache;
		if ($host) {
			$this->addServer($host, $port);
		}

		$_this = $this;
		$this->errorHandler = function($severity, $message) use ($_this) {
			restore_error_handler();

			$failureCallback = $_this->getFailureCallback();
			if ($failureCallback !== NULL) {
				preg_match('#: Server (.+) \(tcp ([0-9]+)#', $message, $m);
				return call_user_func($failureCallback, isset($m[1]) ? $m[1] : '', isset($m[2]) ? (int) $m[2] : 0, $_this->getConnection(), $message, $severity);
			}

			return FALSE;
		};
	}



	public function addServer($host = 'localhost', $port = 11211, $timeout = 1)
	{
		if ($this->memcache->addServer($host, $port, TRUE, 1, $timeout) === FALSE) {
			$error = error_get_last();
			throw new Nette\InvalidStateException("Memcache::addServer(): $error[message].");
		}
	}



	/**
	 * @return \Memcache
	 */
	public function getConnection()
	{
		return $this->memcache;
	}



	/**
	 * Sets failure callback for all added servers.
	 * @param  callable  function(string $host, int $port, \Memcache $memcache, string $message, int $errorSeverity)
	 */
	public function setFailureCallback($callback)
	{
		if ($callback !== NULL && !is_callable($callback)) {
			throw new Nette\InvalidArgumentException('Argument must be callable.');
		}
		$this->failureCallback = $callback;
	}



	public function getFailureCallback()
	{
		return $this->failureCallback;
	}



	/**
	 * Read from cache.
	 * @param  string key
	 * @return mixed|NULL
	 */
	public function read($key)
	{
		$key = $this->prefix . $key;
		set_error_handler($this->errorHandler);
		$meta = $this->memcache->get($key);
		restore_error_handler();
		if (!$meta) {
			return NULL;
		}

		// meta structure:
		// array(
		//     data => stored data
		//     delta => relative (sliding) expiration
		//     callbacks => array of callbacks (function, args)
		// )

		// verify dependencies
		if (!empty($meta[self::META_CALLBACKS]) && !Cache::checkCallbacks($meta[self::META_CALLBACKS])) {
			set_error_handler($this->errorHandler);
			$this->memcache->delete($key, 0);
			restore_error_handler();
			return NULL;
		}

		if (!empty($meta[self::META_DELTA])) {
			set_error_handler($this->errorHandler);
			$this->memcache->replace($key, $meta, 0, $meta[self::META_DELTA] + time());
			restore_error_handler();
		}

		return $meta[self::META_DATA];
	}



	/**
	 * Prevents item reading and writing. Lock is released by write() or remove().
	 * @param  string key
	 * @return void
	 */
	public function lock($key)
	{
	}



	/**
	 * Writes item into the cache.
	 * @param  string key
	 * @param  mixed  data
	 * @param  array  dependencies
	 * @return void
	 */
	public function write($key, $data, array $dp)
	{
		if (isset($dp[Cache::ITEMS])) {
			throw new Nette\NotSupportedException('Dependent items are not supported by MemcachedStorage.');
		}

		$key = $this->prefix . $key;
		$meta = array(
			self::META_DATA => $data,
		);

		$expire = 0;
		if (isset($dp[Cache::EXPIRATION])) {
			$expire = (int) $dp[Cache::EXPIRATION];
			if (!empty($dp[Cache::SLIDING])) {
				$meta[self::META_DELTA] = $expire; // sliding time
			}
		}

		if (isset($dp[Cache::CALLBACKS])) {
			$meta[self::META_CALLBACKS] = $dp[Cache::CALLBACKS];
		}

		if (isset($dp[Cache::TAGS]) || isset($dp[Cache::PRIORITY])) {
			if (!$this->journal) {
				throw new Nette\InvalidStateException('CacheJournal has not been provided.');
			}
			$this->journal->write($key, $dp);
		}

		set_error_handler($this->errorHandler);
		$this->memcache->set($key, $meta, 0, $expire);
		restore_error_handler();
	}



	/**
	 * Removes item from the cache.
	 * @param  string key
	 * @return void
	 */
	public function remove($key)
	{
		set_error_handler($this->errorHandler);
		$this->memcache->delete($this->prefix . $key, 0);
		restore_error_handler();
	}



	/**
	 * Removes items from the cache by conditions & garbage collector.
	 * @param  array  conditions
	 * @return void
	 */
	public function clean(array $conditions)
	{
		if (!empty($conditions[Cache::ALL])) {
			set_error_handler($this->errorHandler);
			$this->memcache->flush();
			restore_error_handler();

		} elseif ($this->journal) {
			foreach ($this->journal->clean($conditions) as $entry) {
				set_error_handler($this->errorHandler);
				$this->memcache->delete($entry, 0);
				restore_error_handler();
			}
		}
	}

}
