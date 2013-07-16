<?php

namespace Reborn\Mongo;

use ArrayIterator;
use Iterator;
use Countable;
use ArrayAccess;
use MongoId;

/**
 * MongoDB Model Collection Class
 * This Library is base on Illuminate's Eloquent Model.
 *
 * @package Reborn\Mongo
 * @author Myanmar Links Professional Web Development Team
 **/

class Collection implements Iterator, ArrayAccess, Countable
{
	protected $items;

    protected $model;

    protected $query;

	private $pos = 0;

    //protected $methods = array('get', 'first', 'pluck', 'distinct');

    public function __construct($model, Query $query)
    {
        $this->query = $query;
        $this->model = $model;
    }

    public function find($id, $keys = array())
    {
        if (is_array($id)) {
            $id = array_map(function($value) {
                return ($value instanceof MongoID) ? $value : new MongoID($value);
            }, $id);
            $this->query->whereIn('_id', $id);
            return $this->get($keys);
        }
        $id = ($id instanceof MongoID) ? $id : new MongoID($id);
        $this->query->where('_id','=', $id);
        $this->query->limit(1);

        return $this->get($keys)->first();
    }

    public function all($keys = array())
    {
        return $this->get($keys);
    }

    public function first()
    {
        return count($this->items) > 0 ? reset($this->items) : null;
    }

    public function last()
    {
        return count($this->items) > 0 ? end($this->items) : null;
    }

    public function isEmpty()
    {
        return (count($this->items) > 0) ? false : true;
    }

    public function slice($key, $value)
    {
        $result = $this->query->slice($key, $value);
        $this->buildModel($this->model, $result);
        return $this;
    }

    public function get($keys = array())
    {
        $result = $this->query->get((array)$keys);
        $this->buildModel($this->model, $result);
        return $this;
    }

    public function distinct($key)
    {
        return $this->query->distinct($key);
    }

    public function group($keys, $initial, $reduce, $finalize = null)
    {
        return $this->query->group($keys, $initial, $reduce, $finalize);
    }

    public function save(array $data)
    {
        return $this->query->save($data);
    }

    public function incerment($key, $value = 1, $id = null)
    {
        return $this->query->incerment($key, $value, $id);
    }

    public function decerment($key, $value = 1, $id = null)
    {
        return $this->query->decerment($key, $value, $id);
    }

    public function push($key, $value, $id = null)
    {
        return $this->query->push($key, $value, $id);
    }

    public function addToSet($key, $value, $id = null)
    {
        return $this->query->addToSet($key, $value, $id);
    }

    public function pop($key, $position = 1, $id = null)
    {
        return $this->query->pop($key, $position, $id);
    }

    public function pull($key, $value, $id = null)
    {
        return $this->query->pull($key, $value, $id);
    }

    public function nSet($key, $id = null)
    {
        return $this->query->nSet($key, $id);
    }

    public function update(array $data, $id = null)
    {
        return $this->query->update($data, $id);
    }

    public function delete($id = null)
    {
        return $this->query->delete($id);
    }

    public function explain()
    {
        return $this->query->explain();
    }

    public function count()
    {
        return $this->query->count();
    }

    public function each($callback)
    {
        array_map($callback, $this->items);
    }

    public function buildModel($model, $items)
    {
        foreach ($items as $item) {
            $model = new $model;
            $model->setAttributes($item);
            $model->setOriginal($item);
            $model->setOldies();
            $this->items[] = $model;
        }
    }

    public function __call($method, $parameters)
    {
        call_user_func_array(array($this->query, $method), $parameters);

        return $this;
    }

    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    public function offsetSet($key, $value)
    {
        $this->items[$key] = $value;
    }

    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

	public function rewind() {
        $this->pos = 0;
    }

    public function current() {
        return $this->items[$this->pos];
    }

    public function key() {
        return $this->pos;
    }

    public function next() {
        ++$this->pos;
    }

    public function valid() {
        return isset($this->items[$this->pos]);
    }

	public function __toString()
	{
		return $this->toJson();
	}

    public function toArray()
    {
        if (is_null($this->items)) {
            return array();
        }

        return array_map(function($val)
                {
                    return $val->toArray();

                }, $this->items);
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }
}
