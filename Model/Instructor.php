<?php

namespace Course\Model;

use Reborn\Mongo\Bongo;

class Instructor extends Bongo
{
    // MongoDB Collection name for model
    protected $collection = 'instructor';

    // Key name for MongoId as String
    // Because MongoId is object, so we need id string for view
    protected $idString = 'id';

    // If you doesn't want timestamp,you can set this value is false
    // Default value is true;
    // If you need timestamp, forget this properties in your model
    protected $timestamps = true;
}