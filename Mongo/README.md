Bongo (MongoDB Simple ODM)
===========================

Bongo is simple ODM for MongoDB. This project is purpose for Myanmar Links Professional Web Development Team's private use.

***Requirement***
MongoDB - PHP Extension 1.3.*

***Credit : ***
Bongo is base on Illuminate Eloquent and ROR's Active Record.

### Conten List for this documentation

- [How to use Bongo](#how-to-use-bongo)
- [Find by id](#find-by-id)
- [Find all result from DB](#find-all-result-from-db)
- [Find result from DB using where](#find-result-from-db-using-where)
- [Bongo supported WHERE list](#bongo-supported-where-list)
- [Using limit, skip and sort](#using-limit-skip-and-sort)
- [Find by id for custom field value](#find-by-id-for-custom-field-value)
- [Insert or Update the data](#insert-or-update-the-data)
- [Increment and Decrement the data](#increment-and-decrement-the-data)
- [Other Update Feature from Mongo](#push-and-pop-the-data-array)
- [Delete the document](#delete-the-document)
- [Callback methods for Insert, Update and Delete](#callback-methods-for-insert-update-and-delete)
- [Count the result](#count-the-result)
- [Like query from DB](#like-query-from-db)
- [Exists and NotExists key at document](#exists-and-notexists-key-at-document)
- [Mongo Distinct](#mongo-distinct)
- [Mongo Index](#mongo-index)

## How to use Bongo

First your class is need to extends the Bongo Class (Bongo is Model Class).

eg:

```php
namespace Product\Model;

use Reborn\Mongo\Bongo;

class Product extends Bongo
{
	// MongoDB Collection name for model
	protected $collection = 'items';

	// Key name for MongoId as String
	// Because MongoId is object, so we need id string for view
	protected $idString = 'id';

	// If you doesn't want timestamp,you can set this value is false
	// Default value is true;
	// If you need timestamp, forget this properties in your model
	protected $timestamps = true;
}
```

### Find by id

```php
$product = Product::find($id);
```

### Find all result from DB

```php
$product = Product::all();
```

### Find result from DB using where

Sometime we need to find data from DB base on Where key is equal, not equal,
greater than, less than, greater than and equal and less than and equal with value.
OK. Now you can create easily these process at Bongo.

Supported operator types -

Type                    | Key
------------------------|----------------------
Equal                   | =
Not Equal               | !=
Greater than            | >
Greater Than and equal  | >=
Less Than               | <
Less Than and Equal     | <=

```php
// Return all result by item_name = iPhone
$product = Product::where('item_name', '=', 'iPhone')->get();

// Return one result by item_name = iPhone
$product = Product::where('item_name', '=', 'iPhone')->first();
```

### Bongo supported WHERE list

This is supported WHERE lists from Bongo Library.

- orWhere($key, $value)
- norWhere($key, $value)
- whereAll($key array $value)
- whereIn($key, array $value)
- whereNotIn($key, array $value)

```php
	// Example orWhere [Return result item key is iPhone or Sony]
	$p = Product::where('item', '=', 'iPhone')->orWhere('item' => 'Sony')->get();

	// Example norWhere [Return result item key is iPhone and item is not Sony]
	$p = Product::where('item', '=', 'iPhone')->norWhere('item' => 'Sony')->get();

	// Example whereAll [Return result state key having YGN and MDY]
	$user = User::whereAll('state', array('YGN', 'MDY'))->get();

	// Example whereIn [Return result state key having YGN or MDY]
	$user = User::whereIn('state', array('YGN', 'MDY'))->get();

	// Example whereIn [Return result state key does not have YGN or MDY]
	$user = User::whereNotIn('state', array('YGN', 'MDY'))->get();
```

### Using limit, skip and sort

```php
$product = Product::where('item_name', '=', 'iPhone')
				->limit(5)
				->skip(2)
				->sort('created_at', 'desc')
				->get();
```
Alias method of sort($key, 'desc') is sortDesc($key)

```php
$product = Product::where('item_name', '=', 'iPhone')
					->sortDesc($key) // is equal sort($key, 'desc')
					->get();
```
Alias method of sort($key, 'asc') is sortAsc($key)

```php
$product = Product::where('item_name', '=', 'iPhone')
					->sortAsc($key) // is equal sort($key, 'asc')
					->get();
```

### Find by id for custom field value

```php
// Find by id for item_name key and owner only return
$product = Product::find($id, array('item_name', 'owner'));

// If you want to use only one key
$product = Product::find($id, 'item_name');
```

### Insert or Update the data

```php
// Insert
$product = new Product();
$product->item_name = 'iPhone 5';
$product->owner = 'Khayusaki';
$product->save();
// Saving sedult in collecction
{
	'_id' => MongoID(),
	'item_name' => 'iPhone 5',
	'owner' => 'Khayusaki',
	'created_at' => MongoDate()
}

// Update
$product = Product::where('owner', '=', 'khayusaki')->get();
$product->item_name = 'Sony Errisson';
$product->save();
// Saving sedult in collecction
{
	'_id' => MongoID(),
	'item_name' => 'Sony Errisson',
	'owner' => 'Khayusaki',
	'created_at' => MongoDate(),
	'updated_at' => MongoDate()
}
```

### Increment and Decrement the data

Increment and Decrement is method of MongoDB '$inc'.

```php
{ id: 1234, name: Nyan, age: 26}
$user = User::find(1234);
$user->increment('age', 2); // Increment the +2 for age key
// Now data is { id: 1234, name: Nyan, age: 28}

$user->decrement('age', 2); // Decrement the -2 for age key
// Now data is { id: 1234, name: Nyan, age: 26}

// If you want to +1 or -1 for key
// Second Parameter's default value is 1.
$user->increment('age'); // Now data is { id: 1234, name: Nyan, age: 27}
// (or)
//$user->decrement('age'); // Now data is { id: 1234, name: Nyan, age: 26}
```

### Other Update Feature from Mongo

MongoDB have so many update feature. They are '$push', '$pushAll', '$addToSet',
'$pop', '$pull', '$pullAll'.

***Note : *** These process is work in array data only.

**What is push and pushAll**

'$push' operator lets you append a single value to a specified field. If the field is an existing array, the data will append, if the field does not exist it will be created.
'$pushAll' is same with '$push', but pushAll for multiple value.

But you will use these two process by one method at Mongo. It is Mongo::push();

***Example***

```php
{ id: 1234, name: Nyan, state: ['YGN', 'MDY']}
$user = User::find(1234);
$user->push('state', 'NPT'); // Push "NPT" data to state data array
// Now data is { id: 1234, name: Nyan, state: ['YGN', 'MDY', 'NPT']}
```

**How to Push multiple data?**

```php
{ id: 1234, name: Nyan, state: ['YGN', 'MDY']}
$user = User::find(1234);
$user->push('state', array('NPT', 'BG')); // Push multiple data with array
// Now data is { id: 1234, name: Nyan, state: ['YGN', 'MDY', 'NPT', 'BG']}
```

**What is addToSet**


### Delete the document

```php
$p = Product::find($id);
$p->delete();
```

### Callback methods for Insert, Update and Delete

You can add custom callback method for Insert, Update and Delete process.
Bongo have 6 callback method for these 3 process.
You can define these methods at your Model Class

* beforeInsert() { Call this method before Insert new document }
* afterInsert() { Call this method after Insert new document }
* beforeUpdate() { Call this method before Update exists document }
* afterUpdate() { Call this method after Update exists document }
* beforeDelete() { Call this method before Delete the document }
* afterDelete() { Call this method after Delete the document }

```php
class MyModel extends Bongo
{
	public function beforeInsert()
	{
		echo 'I am callback for before Insert Method Process';
	}
}
```

### Count the result

```php
// Count data record where price is larger than 600.
$total_items = Product::where('price', '>', 600)->count();

// Count all data record from collection
$all_count = Product::count();
```

### Like query from DB

```php
$search_items = Product::like('item_name', 'galaxy')->get();
// return result by item_name(key) like galaxy(value)
// eg: galaxy SII, galaxy mini, galaxy SIII, etc..
```

### Exists and NotExists key at document

This method is MongoDB's $exists.

```php
	This is sample document collection (name : mmlinks)
	{
		_id : MongoId(),
		name : 'Soe Thiha Naung',
		role : 'CEO',
	},
	{
		_id : MongoId(),
		name : 'Nyan Lynn Htut',
		role : 'Web Developer',
		age : 26
	}

$findHaveAge = MmLinks::exists('age')->get();
// return result
	{
		_id : MongoId(),
		name : 'Nyan Lynn Htut',
		role : 'Web Developer',
		age : 26
	}

$findNotHaveAge = MmLinks::notExists('age')->get();
// return result
	{
		_id : MongoId(),
		name : 'Soe Thiha Naung',
		role : 'CEO',
	},
```

### Mongo Distinct

Finding all of the distinct values for a key.

```php
/*
	This is sample document collection (name : mmlinks)
	{
		_id : MongoId(),
		name : 'Soe Thiha Naung',
		role : 'CEO'
	},
	{
		_id : MongoId(),
		name : 'Nyan Lynn Htut',
		role : 'Web Developer'
	},
	{
		_id : MongoId(),
		name : 'Lijia Li',
		role : 'Web Developer'
	},
	{
		_id : MongoId(),
		name : 'Thet Paing Oo',
		role : 'Web Developer'
	},
	{
		_id : MongoId(),
		name : 'Khay',
		role : 'Web Developer'
	},
	{
		_id : MongoId(),
		name : 'Yan Naing',
		role : 'General Officer'
	},
	{
		_id : MongoId(),
		name : 'Hein Zaw Htet',
		role : 'All Designer'
	}
*/

	$roles = MmLinks::distinct('role'); // (role) is key for distinct

	var_dump($roles);
	// Output
	array(
			0 => 'CEO',
			1 => 'Web Developer',
			2 => 'General Officer',
			3 => 'All Dsigner'
		)
```

### Mongo Index

Indexing method usage for MongoDB in Bongo Library.
See detail usage for this method at MongoCollection::ensureIndex() and MongoCollection::deleteIndex() from php.net

```php
	// Make Index for car collection
	// This index will make name with "make_1" at system.indexes collection
	Car::ensureIndex(array('make' => 1));

	// Delete make_1 index from car collection
	Car::deleteIndex(array('make' => 1));
```
