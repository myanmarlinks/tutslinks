<?php

namespace Reborn\Mongo\Relation;

/**
 * HasOne class for Bongo
 *
 * @package Reborn\Mongo\Relation
 * @author Reborn CMS Development Team
 **/
class HasOne
{

	protected $model;

	protected $key;

	protected $caller;

	public function __construct($model, $key, $caller)
	{
		$this->model = $model;
		$this->key = $key;
		$this->caller = $caller;

		return $this;
	}

	public function getValue()
	{
		$model = $this->model;

		if ($data = $this->caller->getRelations($model)) {
			echo 'I am GetCache.<br>';
			return $data;
		}
		$old = memory_get_usage();

		$result = $model::find($this->key);
		$mem = memory_get_usage();
			echo 'Query is '.abs($mem - $old).'<br>';
		echo 'I am Finder.<br>';
		$this->caller->setRelations($model, $result);

		return $result;
	}

} // END class HasOne
