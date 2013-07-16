<?php

namespace Reborn\Mongo;

use MongoId;
use MongoRegex;
use Reborn\Mongo\Connection;

/**
 * MongoDB Query Class
 *
 * @package Reborn\Mongo
 * @author Myanmar Links Professional Web Development Team
 **/

class Query
{
	protected $connection;

	protected $db;

	protected $collection;

	protected $whereKeys = array(
			'>' => '$gt',
			'<' => '$lt',
			'>=' => '$gte',
			'<=' => '$lte',
			'!=' => '$ne'
		);

	protected $wheres = array();

	protected $limit;

	protected $skip;

	protected $order;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
		$this->db = $connection->getDB();
	}

	/**
	 * Get the MongoDB Object
	 *
	 * @return \MongoDB
	 **/
	public function getDB()
	{
		return $this->db;
	}

	/**
	 * Set the MongoCollection Object
	 *
	 * @param string $collection Collection name
	 * @return void
	 **/
	public function setCollection($collection)
	{
		$this->collection = $collection;
	}

	/**
	 * Get the MongoCollection Object
	 *
	 * @return \MongoCollection
	 **/
	public function getCollection()
	{
		return $this->db->{$this->collection};
	}

	public function find($id, $keys = array())
	{
		if (! $id instanceof MongoId) {
			$id = new MongoId($id);
		}

		return $this->where('_id', '=', $id)->limit(1)->get($keys);
	}

	public function where($key, $operator, $value)
	{
		if ('=' == $operator) {
			$this->wheres[$key] = $value;
		} elseif (array_key_exists($operator, $this->whereKeys)) {
			$this->wheres[$key][$this->whereKeys[$operator]] = $value;
		}

		return $this;
	}

	public function andWhere($key, $value)
	{
		$this->wheres['$and'][] = array($key => $value);
		return $this;
	}

	public function orWhere($key, $value)
	{
		$this->wheres['$or'][] = array($key => $value);
		return $this;
	}

	public function norWhere($key, $value)
	{
		$this->wheres['$nor'][] = array($key => $value);
		return $this;
	}

	public function whereIn($key, array $value)
	{
		$this->wheres[$key]['$in'] = array_values($value);
		return $this;
	}

	public function whereNotIn($key, array $value)
	{
		$this->wheres[$key]['$nin'] = array_values($value);
		return $this;
	}

	public function whereAll($key, array $value)
	{
		$this->wheres[$key]['$all'] = array_values($value);
		return $this;
	}

	public function size($key, $val)
	{
		$this->wheres[$key]['$size'] = (int) $val;
		return $this;
	}

	public function elemMatch($key, $value)
	{
		$this->wheres[$key]['$elemMatch'] = (array) $value;
		return $this;
	}

	public function like($key, $value, $operator = '=', $flag = 'im')
	{
		if (is_array($value)) {
            $value = array_map(function($v) use($flag) {
                return ($v instanceof MongoRegex)
                		? $v
                		: new MongoRegex('/'.$v.'/'.$flag);
            }, $value);
        } else {
        	$value = ($value instanceof MongoRegex)
        				? $value
        				: new MongoRegex('/'.$value.'/'.$flag);
        }

		if ('=' === $operator) {
			$this->where($key, '=', $value);
		} elseif ('or' === $operator
				|| 'nor' === $operator
				|| 'and' === $operator) {
			$this->{$operator.'Where'}($key, $value);
		} elseif ('all' === $operator
				|| 'in' === $operator
				|| 'notIn' === $operator) {
			$operator = 'where'.ucfirst($operator);
			$this->{$operator}($key, (array)$value);
		}

		return $this;
	}

	public function limit($limit)
	{
		$this->limit = $limit;
		return $this;
	}

	public function skip($skip)
	{
		$this->skip = $skip;
		return $this;
	}

	public function sortDesc($key)
	{
		return $this->sort($key, 'desc');
	}

	public function sortAsc($key)
	{
		return $this->sort($key, 'asc');
	}

	public function sort($key, $direction)
	{
		if ('asc' == $direction) {
			$direction = 1;
		} elseif ('desc' == $direction) {
			$direction = -1;
		}

		$this->order[$key] = $direction;

		return $this;
	}

	public function distinct($key)
	{
		$result = $this->db->command(array(
							'distinct' => $this->collection,
							'key' => $key,
							'query' => $this->wheres));

		return $result['values'];
	}

	public function group($keys, $initial, $reduce, $finalize = null)
	{
		$ks = $this->prepareKeys((array) $keys);

		if (! in_array('_id', $keys)) {
			unset($ks['_id']);
		}

		// Change options to condition at MongoDriver 1.3.*
		$options = array('condition' => $this->wheres);

		if (! is_null($finalize)) {
			$options['finalize'] = $finalize;
		}

		$g = $this->db->{$this->collection}
				->group($ks, (object)$initial, $reduce, $options);

		return $g['retval'];
	}

	public function exists($key, $exists = true)
	{
		$this->wheres[$key] = array('$exists' => $exists);
		return $this;
	}

	public function notExists($key)
	{
		$this->exists($key, false);
		return $this;
	}

	public function slice($key, $value)
	{
		$cond = (object) array($key => array('$slice' => $value));
		$results = $this->db->{$this->collection}->find($this->wheres, $cond);

		if (!is_null($this->order)) {
			$results = $results->sort($this->order);
		}

		if (!is_null($this->skip)) {
			$results = $results->skip($this->skip);
		}

		if (!is_null($this->limit)) {
			$results = $results->limit($this->limit);
		}

		return new DocumentIterator($results);
	}

	public function get($keys = array())
	{
		$ks = $this->prepareKeys((array) $keys);

		$results = $this->db->{$this->collection}->find($this->wheres, $ks);

		if (!is_null($this->order)) {
			$results = $results->sort($this->order);
		}

		if (!is_null($this->skip)) {
			$results = $results->skip($this->skip);
		}

		if (!is_null($this->limit)) {
			$results = $results->limit($this->limit);
		}

		return new DocumentIterator($results);
	}

	public function prepareKeys($keys = array())
	{
		$ks = array();
		foreach ($keys as $k) {
			$ks[$k] = 1;
		}

		if (count($ks) and !isset($ks['_id'])) {
			$ks['_id'] = false;
		}

		return $ks;
	}

	public function first($keys = array())
	{
		return $this->limit(1)->get($keys);
	}

	public function count()
	{
		return $this->get()->count();
	}

	/**
	 * Get the Mongo Explain Method
	 */
	public function explain()
	{
		return $this->get()->explain();
	}

	public function save($data)
	{
		$options = array('safe' => true);

		$result = $this->db->{$this->collection}->insert($data, $options);

		if (1 == (int)$result['ok']) {
			return $data['_id'];
		}

		return false;
	}

	/*public function saveAll($data)
	{
		//dump($data, true);
		$result = $this->db->{$this->collection}->batchInsert($data);

		dump($result, true);
	}*/

	public function increment($key, $value = 1, $id = null)
	{
		$data = array($key => (int)$value);
		return $this->update($data, $id, '$inc');
	}

	public function decrement($key, $value = 1, $id = null)
	{
		$data = array($key => -(int)$value);
		return $this->update($data, $id, '$inc');
	}

	public function push($key, $value, $id = null)
	{
		$data = array($key => $value);
		if (is_array($value)) {
			$type = '$pushAll';
		} else {
			$type = '$push';
		}
		return $this->update($data, $id, $type);
	}

	public function addToSet($key, $value, $id = null)
	{
		if (is_array($value)) {
			$data = array($key => array('$each' => $value));
		} else {
			$data = array($key => $value);
		}

		return $this->update($data, $id, '$addToSet');
	}

	public function pop($key, $position = 1, $id = null)
	{
		$data = array($key => $position);

		return $this->update($data, $id, '$pop');
	}

	public function pull($key, $value, $id = null)
	{
		$data = array($key => $value);
		if (is_array($value)) {
			$type = '$pullAll';
		} else {
			$type = '$pull';
		}

		return $this->update($data, $id, $type);
	}

	public function nSet($key, $id = null)
	{
		$data = array($key => 1);

		return $this->update($data, $id, '$unset');
	}

	public function update(array $data, $id = null, $type = '$set')
	{
		$update = array($type => $data);

		$options = array('multiple' => true, 'safe' => true);

		if (empty($this->wheres)){
			if(is_null($id)) {
				throw new \Exception("Doesn't find key for update process");
			} else {
				$criteria = array('_id' => $id);
			}
		} else {
			$criteria = $this->wheres;
		}

		$result = $this->db->{$this->collection}
							->update($criteria, $update, $options);

		if (1 == (int)$result['ok']) {
			return true;
		}

		return false;
	}

	public function delete($id = null)
	{
		$options = array('safe' => true);

		if (empty($this->wheres)){
			if(is_null($id)) {
				throw new \Exception("Doesn't find key for update process");
			} else {
				if (! $id instanceof MongoId) {
					$id = new MongoId($id);
				}
				$criteria = array('_id' => $id);
			}
		} else {
			$criteria = $this->wheres;
		}

		return $this->db->{$this->collection}->remove($criteria);
	}

	public function sum($key)
	{

	}

	public function max($key)
	{

	}

	public function min($key)
	{

	}

	public function aggregation()
	{

	}

	public function toObj()
	{
		$results = $this->get();

		return arrToObject($results);
	}

	public function toJson()
	{
		$results = $this->get();

		return json_encode($results);
	}
}
