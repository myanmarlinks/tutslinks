<?php

namespace Reborn\Mongo\Relation;

/**
 * BelongsToMany class for Bongo
 *
 * @package Reborn\Mongo\Relation
 * @author Reborn CMS Development Team
 **/
class BelongsToMany extends BelongsTo
{
	public function getValue()
	{
		$instance = $this->model;

		return $instance::whereIn($this->key, array($this->caller->id))
						->get();
	}

} // END class BelongsToMany
