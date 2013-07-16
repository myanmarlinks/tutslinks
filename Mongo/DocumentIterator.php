<?php

namespace Reborn\Mongo;

use Iterator,
	Countable;

/**
 * DocumentIterator class for Bongo Library
 *
 * @package Bongo
 * @author Nyan Lynn Htut (lynnhtut87@gmail.com)
 **/
class DocumentIterator implements Iterator, Countable
{
	/**
	 * Variable for MongoCursor
	 *
	 * @var MongoCursor
	 **/
	protected $cursor;

	public function __construct($cursor)
	{
		$this->cursor = $cursor;
	}

	/**
	 * Mongo Explain Method
	 */
	public function explain()
	{
		return $this->cursor->explain();
	}

	/**
	 * Get the MongoCursor
	 *
	 * @return MongoCursor
	 **/
	public function getCursor()
	{
		return $this->cursor;
	}

	public function current()
	{
		return $this->cursor->current();
	}

	public function count()
	{
		return $this->cursor->count();
	}

	public function key()
	{
		return $this->cursor->key();
	}

	public function next()
	{
		$this->cursor->next();
	}

	public function rewind()
	{
		$this->cursor->rewind();
	}

	public function valid()
	{
		return $this->cursor->valid();
	}

} // END class DocumentIterator implements Iterator, Countable
