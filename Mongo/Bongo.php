<?php

namespace Reborn\Mongo;

use MongoDate;
use MongoID;
use Reborn\Mongo\Query;
use Reborn\Mongo\MongoManager;
use Reborn\Mongo\Relation\HasOne;
use Reborn\Mongo\Relation\BelongsTo;
use Reborn\Mongo\Relation\HasMany;
use Reborn\Mongo\Relation\BelongsToMany;
use Reborn\Util\Str;

/**
 * MongoDB Model Bongo Class
 * This Library is base on Illuminate's Eloquent Model.
 *
 * @package Reborn\Mongo
 * @author Myanmar Links Professional Web Development Team
 **/

class Bongo
{
	// Collection name for model
	// eg: protected $collection = 'blog_post';
	protected $collection;

	// Document attributes
	protected $attributes = array();

	// Original attibutes
	protected $original = array();

	// Search keys for search method
	protected $search = array();

	// Blacklists attributes
	protected $blacklists = array();

	// CreatedAt key name
	protected $created_at = 'created_at';

	// CreatedAt key name
	protected $updated_at = 'updated_at';

	// Key for MongoId as String
	protected $idString = 'id';

	// Use Timestamps
	protected $timestamps = true;

	// Object is News or not
	protected $isNew = true;

	// Relational attributes
	protected $relations = array();

	// Reborn\Mongo\Query
	protected $query;

	// Reborn\Connection
	protected $connection;

	/**
	 * Create a new Bongo Instance
	 *
	 * @param array $attrs Data attributes array
	 * @return Reborn\Bongo
	 */
	public function __construct($attrs = array())
	{
		$manager = new MongoManager();

		if (! $manager->isConnected()) {
			$this->connection = $manager->connect();
		} else {
			$this->connection = $manager->getConnection();
		}

		$this->buildQuery();

		if (! empty($attrs)) {
			$this->setAttributes((array)$attrs);
		}

		return $this;
	}

	/**
	 * Static method for create new.
	 *
	 * @param array $attrs Data attributes array
	 * @return Bongo
	 **/
	public static function create(array $attrs)
	{
		$ins = new static($attrs);

		$ins->save();

		return $ins;
	}

	/**
	 * Get the MongoDB Object
	 *
	 * @return \MongoDB
	 **/
	public function getDB()
	{
		return $this->query->getDB();
	}

	/**
	 * Get the MongoCollection Object
	 *
	 * @return \MongoCollection
	 **/
	public function getCollection()
	{
		return $this->query->getCollection();
	}

	/**
	 * Find the document result by _id from collection.
	 *
	 * @param string|Object $id MongoId String or MongoId Object
	 * @param array $keys If you want to find some key from document, use this
	 * @return Bongo Model Object
	 **/
	public static function find($id, $keys = array())
	{
		$ins = new static();

		return $ins->modelCollection()->find($id, $keys);
	}

	/**
	 * Find the all result from collection.
	 *
	 * @param array $keys If you want to find some key from document, use this
	 * @return Collection Object
	 **/
	public static function all($keys = array())
	{
		$ins = new static();

		return $ins->modelCollection()->all($keys);
	}

	/**
	 * HasOne (Relation) With method
	 *
	 * @param string $model Relation Model Name
	 * @param string $key Relation Key
	 * @return Reborn\Mongo\Relation\HasOne
	 **/
	public function hasOne($model, $key)
	{
		return new HasOne($model, $key, $this);
	}

	/**
	 * BelongsTo (Reserve of hasOne) With method
	 *
	 * @param string $model Relation Model Name
	 * @param string $key Relation Key
	 * @return Reborn\Mongo\Relation\BelongsTo
	 **/
	public function belongsTo($model, $key)
	{
		return new BelongsTo($model, $key, $this);
	}

	/**
	 * HasMany (Relation) With method
	 *
	 * @param string $model Relation Model Name
	 * @param string $key Relation Key
	 * @return Reborn\Mongo\Relation\HasMany
	 **/
	public function hasMany($model, $key)
	{
		return new HasMany($model, $key);
	}

	/**
	 * BelongsToMany (Reserve of hasOne) With method
	 *
	 * @param string $model Relation Model Name
	 * @param string $key Relation Key
	 * @return Reborn\Mongo\Relation\BelongsToMany
	 **/
	public function belongsToMany($model, $key)
	{
		return new BelongsToMany($model, $key, $this);
	}

	/**
	 * Set the Relational Data for $this Object
	 *
	 * @return void
	 **/
	public function setRelations($relKey, $data)
	{
		$this->relations[$relKey] = $data;

		return $this;
	}

	/**
	 * Get the Relational Data for $this Object
	 *
	 * @return void
	 **/
	public function getRelations($relKey)
	{
		if (isset($this->relations[$relKey])) {
			return $this->relations[$relKey];
		}

		return null;
	}

	/**
	 * Make EnsureIndex for This model's collecttion.
	 *
	 * @see MongoCollection::ensureIndex() in PHP.net
	 * @param array $indexer Index array
	 * @param array $options Index Options array.
	 * @return void
	 **/
	public static function ensureIndex($indexer, $options = array())
	{
		$ins = new static();

		$ins->getCollection()->ensureIndex($indexer, $options);
	}

	/**
	 * Delete Index for This model's collecttion.
	 *
	 * @see MongoCollection::deleteIndex() in PHP.net
	 * @param array $indexer Index array
	 * @return void
	 **/
	public static function deleteIndex($indexer)
	{
		$ins = new static();

		$ins->getCollection()->deleteIndex($indexer);
	}

	/**
	 * Search method for Bongo.
	 * This method will help easy way for search process.
	 * If you want to use this method,
	 * Don't forget to set Bongo's protected property "search"
	 *
	 * @param string $name Name of search property key
	 * @param string $values Value of search
	 * @param string $flag Flag key for MongoRegex
	 * @return Reborn\Mongo\Collection
	 **/
	public static function search($name, $values, $flag = 'im')
	{
		$ins = new static();
		if (!isset($ins->search[$name])) {
			return null;
		}

		$keys = (array)$ins->search[$name]['keys'];
		$operator = isset($ins->search[$name]['operator']) ?
						$ins->search[$name]['operator'] : 'or';

		$coll = $ins->modelCollection();

		foreach ($keys as $k => $v) {
			$coll->like($v, $values, $operator, $flag);
		}

		return $coll->get();
	}

	/**
	 * Set the attributes for this model.
	 *
	 * @param array $attrs
	 * @return void
	 **/
	public function setAttributes($attrs = array())
	{
		$idStr = $this->getIdString();
		if (!isset($attrs[$idStr]) and isset($attrs['_id'])) {
			$attrs[$idStr] = $this->mongoIdToString($attrs['_id']);
		}

		$this->attributes = $attrs;
	}

	/**
	 * Set the attributes's original value for this model.
	 *
	 * @param array $attrs
	 * @return void
	 **/
	public function setOriginal($attrs = array())
	{
		$this->original = $attrs;
	}

	/**
	 * Get the attributes's original value.
	 *
	 * @return array
	 **/
	public function getOriginal()
	{
		return $this->original;
	}

	/**
	 * Set the object is not news.
	 *
	 * @return void
	 */
	public function setOldies()
	{
		$this->isNew = false;
	}

	/**
	 * Check This Object is New or Not
	 *
	 * @return boolean
	 */
	public function isNewObj()
	{
		return $this->isNew;
	}

	/**
	 * This method call before Insert() in internal.
	 */
	public function beforeInsert() {}

	/**
	 * This method call after Insert() in internal.
	 */
	public function aftertInsert() {}

	/**
	 * This method call before update() in internal.
	 */
	public function beforeUpdate() {}

	/**
	 * This method call after update() in internal.
	 */
	public function afterUpdate() {}

	/**
	 * This method call before delete() in internal.
	 */
	public function beforeDelete() {}

	/**
	 * This method call after delete() in internal.
	 */
	public function afterDelete() {}

	/**
	 * Save the data to collection.
	 * If model isNew, process the new insert for data to collection
	 * If model is not New, process the update data to collection.
	 *
	 * @param null|array $attrs Data attributes array
	 * @return boolean
	 */
	public function save($attrs = array())
	{
		// If attrs is given for this method, fill with this attrs
		if (! empty($attrs)) {
			$this->setAttributes($attrs);
		}

		if (! $this->isNew) {
			// Make a update
			if ($this->useTimestamps()) {
				$this->attributes[$this->updated_at] = $this->getTimestamp();
			}

			$this->beforeUpdate();

			$result = $this->updating();

			$this->afterUpdate();

			return $result;
		} else {
			// make a save
			if ($this->useTimestamps()) {
				$this->attributes[$this->created_at] = $this->getTimestamp();
				$this->attributes[$this->updated_at] = $this->getTimestamp();
			}

			$this->beforeInsert();

			if ($id = $this->insert()) {
				$result = array_merge($this->attributes, array('_id' => $id));
				$this->setAttributes($result);

				$this->aftertInsert();

				return true;
			}

			return false;
		}
	}

	/**
	 * Insert the data to collection
	 *
	 * @return boolean
	 **/
	public function insert()
	{
		$data = $this->getWhitelistAttributes();

		return $this->modelCollection()->save($data);
	}

	/**
	 * Update the data to collection
	 *
	 * @return boolean
	 **/
	public function updating()
	{
		$data = $this->getWhitelistAttributes(true);

		return $this->modelCollection()->update($data, $this->attributes['_id']);
	}

	/**
	 * Increment the data.
	 *
	 * @param string $key
	 * @param int $value
	 * @return boolean
	 **/
	public function incerment($key, $value = 1)
	{
		return $this->modelCollection()
					->increment($key, $value, $this->attributes['_id']);
	}

	/**
	 * Decrement the data.
	 *
	 * @param string $key
	 * @param int $value
	 * @return boolean
	 **/
	public function decerment($key, $value = 1)
	{
		return $this->modelCollection()
					->decrement($key, $value, $this->attributes['_id']);
	}

	/**
	 * Push the data.
	 *
	 * @param string $key
	 * @param string $value
	 * @return boolean
	 **/
	public function push($key, $value)
	{
		return $this->modelCollection()
					->push($key, $value, $this->attributes['_id']);
	}

	/**
	 * AddToSet the data.
	 *
	 * @param string $key
	 * @param string $value
	 * @return boolean
	 **/
	public function addToSet($key, $value)
	{
		return $this->modelCollection()
					->addToSet($key, $value, $this->attributes['_id']);
	}

	/**
	 * Pop the data from key's data array.
	 *
	 * @param string $key
	 * @param int $postion Default position is 1. May be 1 (or) -1 only
	 * @return boolean
	 **/
	public function pop($key, $position = 1)
    {
        return $this->modelCollection()
        			->pop($key, $position, $this->attributes['_id']);
    }

    /**
	 * Removing Each Occurrence of a value.
	 *
	 * @param string $key
	 * @param string|array $value
	 * @return boolean
	 **/
	public function pull($key, $value)
	{
		return $this->modelCollection()
					->pull($key, $value, $this->attributes['_id']);
	}

	/**
	 * Deleting a Field with $unset.
	 *
	 * @param string $key
	 * @return boolean
	 **/
	public function nSet($key)
	{
		return $this->modelCollection()
					->nSet($key, $this->attributes['_id']);
	}

	/**
	 * Delete (Remove) the document from collection
	 *
	 * @return boolean
	 **/
	public function delete()
	{
		$this->beforeDelete();
		$result = $this->modelCollection()->delete($this->attributes['_id']);
		$this->afterDelete();

		return $result;
	}

	/**
	 * Get tht Whitelist Attributes
	 *
	 * @param boolean $fromDirty Get the attributes from Dirty
	 * @return array
	 **/
	public function getWhitelistAttributes($fromDirty = false)
	{
		if ($fromDirty) {
			$attrs = $this->getDirty();
		} else {
			$attrs = $this->attributes;
		}

		// First, clear the idString
		unset($attrs[$this->idString]);

		foreach ($this->getBlacklists() as $bl) {
			if (array_key_exists($bl, $this->attributes)) {
				unset($attrs[$bl]);
			}
		}

		return $attrs;
	}

	/**
	 * Get the blacklists attributes name
	 *
	 * @return array
	 **/
	public function getBlacklists()
	{
		return $this->blacklists;
	}

	/**
	 * Get the dirty attributes
	 * (Dirty attribute are changes attribute from original attribute)
	 *
	 * @return array
	 */
	public function getDirty()
	{
		$dirty = array();

		foreach ($this->attributes as $key => $value) {
			if ( ! array_key_exists($key, $this->original) or $value != $this->original[$key]) {
				$dirty[$key] = $value;
			}
		}

		return $dirty;
	}

	/**
	 * Check the Timestamps (use at create and update) is use or not
	 *
	 * @return boolean
	 */
	public function useTimestamps()
	{
		return $this->timestamps;
	}

	/**
	 * Get the MongoDate Timestamp
	 *
	 * @return MongoDate
	 */
	public function getTimestamp()
	{
		return new MongoDate();
	}

	/**
	 * Set the CreatedAt value by manual
	 *
	 * @param Date|string $time DateTime or time string
	 * @return void
	 */
	public function setCreatedAt($time)
	{
		$this->attributes[$this->created_at] = new MongoDate(strtotime($time));
	}

	/**
	 * Set the UpdatedAt value by manual
	 *
	 * @param Date|string $time DateTime or time string
	 * @return void
	 */
	public function setUpdatedAt($time)
	{
		$this->attributes[$this->updated_at] = new MongoDate(strtotime($time));
	}

	/**
	 * Get the all attributes from this object.
	 *
	 * @return array
	 **/
	public function getAllAttribute()
	{
		return $this->attributes;
	}

	/**
	 * Get the attribute from object.
	 *
	 * @param string $key
	 * @return mixed
	 **/
	public function getAttribute($key)
	{
		// Check get value is equal $this->idString and have in $this->attributes
		// If don't have, set the idString
		if (($this->getIdString() == $key) and
			!array_key_exists($key, $this->attributes)) {
			$this->setAttributes($this->attributes);
		}

		$value = null;

		// If Key have in Attributes from class
		if (array_key_exists($key, $this->attributes)) {
			$value = $this->attributes[$key];
		}

		// If Key is Attribute Getter from class
		if ($this->hasAttributeGetter($key)) {
			return $this->{'get'.Str::camel($key)}($value);
		}

		// If Key is Method from class
		if (method_exists($this, $key)) {
			return $this->{$key}()->getValue();
		}

		return $value;
	}

	/**
	 * Check the has or not getAttributeGetter Method.
	 *
	 * @param string $key
	 * @return boolean
	 **/
	public function hasAttributeGetter($key)
	{
		return method_exists($this, 'get'.Str::camel($key));
	}

	/**
	 * Check this object need to relation
	 *
	 * @return boolean
	 **/
	public function needRelation()
	{
		return empty($this->relations) ? false : true;
	}

	/**
	 * Set the Attribute
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 **/
	public function setAttribute($key, $value)
	{
		if ($this->hasAttributeSetter($key)) {
			$value = $this->{'set'.Str::camel($key)}($value);
		}

		$this->attributes[$key] = $value;
	}

	/**
	 * Check the has or not setAttributeGetter Method.
	 *
	 * @param string $key
	 * @return boolean
	 **/
	public function hasAttributeSetter($key)
	{
		return method_exists($this, 'set'.Str::camel($key));
	}

	/**
	 * Convert the attributes from this object to Array
	 *
	 * @return array
	 **/
	public function toArray()
	{
		return (array)$this->attributes;
	}

	/**
	 * Convert the attributes from this object to Json String
	 *
	 * @return string
	 **/
	public function toJson()
	{
		return json_encode($this->toArray());
	}

	/**
	 * Convert MongoId to String
	 *
	 * @param MongoId $id
	 * @return string
	 **/
	protected function mongoIdToString($id)
	{
		$id = (array)$id;
		return $id['$id'];
	}

	/**
	 * Get string key name for MongoId
	 * Default is "id"
	 *
	 * @return string
	 **/
	protected function getIdString()
	{
		return $this->idString;
	}

	/**
	 * Build the Query Object and set the collection
	 * for Query Object
	 *
	 * @return Reborn\Mongo\Query
	 */
	protected function buildQuery()
	{
		$this->query = new Query($this->connection);

		$this->query->setCollection($this->collection);

		return $this->query;
	}

	/**
	 * Create Model Collection Object
	 *
	 * @return Reborn\Mongo\Collection
	 */
	protected function modelCollection()
	{
		$collection = new Collection(get_called_class(), $this->buildQuery());

		return $collection;
	}

	/**
	 * Magic method getter for this object
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->getAttribute($key);
	}

	/**
	 * Magic method setter for this object
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->setAttribute($key, $value);
	}

	/**
	 * Magic Method isset for this object's attributes properties
	 *
	 * @param string $key
	 * @return boolean
	 **/
	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * Magic Method unset for this object's attributes properties
	 *
	 * @param string $key
	 * @return boolean
	 **/
	public function __unset($key)
	{
		unset($this->attributes[$key]);
	}

	/**
	 * Magic method __toString
	 *
	 * @return string (Json String)
	 */
	public function __toString()
	{
		return $this->toJson();
	}


	/**
	 * Magic method __call
	 */
	public function __call($method, $parameters)
	{
		$query = $this->modelCollection();

		return call_user_func_array(array($query, $method), $parameters);
	}

	/**
	 * Magic method __callStatic for static call
	 */
	public static function __callStatic($method, $parameters)
	{
		$ins = new static;

		if (preg_match('/^(find|all|count)By(\w+)$/', $method, $matches)) {
			$method = '_'.$matches[1].'By';
			$keys = $matches[2];

			return self::$method($keys, $parameters);
		}

		return call_user_func_array(array($ins, $method), $parameters);
	}

	protected static function _findBy($keys, $params)
	{
		return static::byMethodPrepare($keys, $params)->get()->first();
	}

	protected static function _allBy($keys, $params)
	{
		return static::byMethodPrepare($keys, $params)->get();
	}

	protected static function _countBy($keys, $params)
	{
		return static::byMethodPrepare($keys, $params)->count();
	}

	protected static function byMethodPrepare($keys, $params)
	{
		$keys = explode('And', $keys);

		$ins = new static();
		$coll = $ins->modelCollection();

		foreach ($keys as $k => $val) {
			$coll->where(strtolower($val), '=', isset($params[$k]) ? $params[$k] : '');
		}

		return $coll;
	}

}
