<?php

namespace Reborn\Mongo\Relation;

/**
 * HasMany class for Bongo
 *
 * @package Reborn\Mongo\Relation
 * @author Reborn CMS Development Team
 **/
class HasMany
{

	protected $model;

	protected $key;

	public function __construct($model, $key)
	{
		$this->model = $model;
		$this->key = $key;

		return $this;
	}

	public function getValue()
	{
		$model = $this->model;

		return $model::find($this->key);
	}

} // END class HasMany
