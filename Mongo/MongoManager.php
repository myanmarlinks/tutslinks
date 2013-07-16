<?php

namespace Reborn\Mongo;

use Reborn\Mongo\Connection;
use Reborn\Mongo\Query;

/**
 * MongoDB Manager Class
 *
 * @package Reborn\Mongo
 * @author Myanmar Links Professional Web Development Team
 **/

class MongoManager
{
	protected static $connection;

	protected $configs = array();

	public function __construct()
	{
		$this->configs = require __DIR__.DIRECTORY_SEPARATOR.'config.php';
	}

	public function connect($options = array())
	{
		$this->configs = $options = array_merge($this->configs, $options);
		$server = $options['server'];
		unset($options['server']);
		static::$connection = new Connection($server, $options);

		return static::$connection;
	}

	public function isConnected()
	{
		return (static::$connection instanceof Connection);
	}

	public function getConnection()
	{
		return static::$connection;
	}

	public function getConfig()
	{
		return $this->configs;
	}
}
