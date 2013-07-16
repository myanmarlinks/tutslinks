<?php

namespace Reborn\Mongo\Relation;

/**
 * BelongsTo class for Bongo
 *
 * @package Reborn\Mongo\Relation
 * @author Reborn CMS Development Team
 **/
class BelongsTo
{
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
		$instance = $this->model;

		return $instance::where($this->key, '=', $this->caller->id)
						->get()
						->first();
	}

} // END class BelongsTo
