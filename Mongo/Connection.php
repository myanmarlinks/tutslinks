<?php

namespace Reborn\Mongo;

use MongoClient;

/**
 * MongoDB Connection Class
 *
 * @package Reborn\Mongo
 * @author Myanmar Links Professional Web Development Team
 **/
class Connection
{

	/**
	 * MongoDB Connection variable
	 **/
	protected $connection;

	/**
	 * MongoDB Database variable
	 **/
	protected $db;

	/**
	 * Construct the MongoDB Connection.
	 * Parameter details are see at php manuel.
	 *
	 * @param string $server The server name.
	 * @param array $options An array of options for the connection.
	 * @return Reborn\Mongo\Connection
	 **/
	public function __construct($server, $options = array())
	{
		list($serverDSN, $options, $db) = $this->getServerValue($server, $options);

		$this->connection = new MongoClient($serverDSN, $options);

		$this->setDB($db);

		return $this;
	}

	/**
	 * Static method connect.
	 * This method is alias of constructor.
	 *
	 * @param string $server The server name.
	 * @param array $options An array of options for the connection.
	 * @return Reborn\Mongo\Connection
	 **/
	public static function connect($server, $options = array())
	{
		return new static($server, $options);
	}

	/**
	 * Get the Server DSN String and options array from given value.
	 *
	 * @param string $server The server name.
	 * @param array $options An array of options for the connection.
	 * @return array
	 **/
	public function getServerValue($server, $options = array())
	{
		$str = 'mongodb://';

		if ( strpos($server, 'mongodb://') === 0) {
			$server = str_replace('mongodb://', '', $server);
		}

		if (isset($options['username']) and isset($options['password'])) {
			$str .= "{".$options['username']."}:{".$options['password']."}@";
			unset($options['username']);
			unset($options['password']);
		}

		$str .= $server;

		if (isset($options['port'])) {
			$str .= ":{".$options['port']."}";
			unset($options['port']);
		}

		$database = $options['database'];
		unset($options['database']);

		$str.= "/{$database}";

		return array($str, $options, $database);
	}

	/**
	 * Get the mongoDB connection
	 *
	 * @return \Mongo
	 **/
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * Get the mongoDB databse
	 *
	 * @return \MongoDB
	 **/
	public function getDB()
	{
		return $this->db;
	}

	/**
	 * Get the mongoDB Version
	 *
	 * @return string
	 **/
	public function getVersion()
	{
		$adminDB = $this->connection->admin; //require admin priviledge

		$mongodb_info = $adminDB->command(array('buildinfo'=>true));

		return $mongodb_info['version'];
	}

	/**
	 * Select the Databse
	 *
	 * @param string $dbname Database name
	 * @return \MongoDB
	 **/
	public function setDB($dbname)
	{
		$this->db = $this->connection->{$dbname};
		return $this->db;
	}

	/**
	 * Select the Colection
	 *
	 * @param string $collection Collection name
	 * @return \MongoCollection
	 **/
	public function selectCollection($collection)
	{
		return $this->db->selectCollection($collection);
	}


} // END class
