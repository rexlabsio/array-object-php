# Array Object 

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![Build Status](https://travis-ci.org/rexlabsio/array-object-php.svg?branch=master)](https://travis-ci.org/rexlabsio/array-object-php)
[![Code Coverage](https://scrutinizer-ci.com/g/rexlabsio/array-object-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/rexlabsio/array-object-php/?branch=master)
[![Packagist](https://img.shields.io/packagist/v/rexlabs/array-object.svg)](https://packagist.org/packages/rexlabs/array-object)


ArrayObject is a PHP library that provides a light-weight interface for working fluently with array's. 

ArrayObject provides a wrapper around PHP's built-in `array` type which includes methods for filtering, and retrieving items, and conveniently treats individual items and collections as the same object.

This is especially useful for working with JSON responses from API requests.

This library is used by the [Hyper Http Client](https://packagist.org/packages/rexlabs/hyper-http) to make extracting data from responses super intuitive.

## Installation

```bash
composer require rexlabs/array-object
```

## Dependencies

- PHP 7.0 (or greater)
- `rexlabs/utility-belt:^3.0`

## Usage

```php
<?php
require 'vendor/autoload.php';
use Rexlabs\ArrayObject\ArrayObject;

// Initialise from an Array
$obj = ArrayObject::fromArray([...]);

// Initialise from a JSON encoded string
$obj = ArrayObject::fromJson($str);

// Output to array
$arr = $obj->toArray();

// Output to JSON encoded string
$json = $obj->toJson();
```

The examples below are based on the follow input data.

```json
{
  "books": [
    {
      "id": 1,
      "title": "1984",
      "author": "George Orwell"
    },
    {
      "id": 2,
      "title": "Pride and Prejudice",
      "author": "Jane Austen"
    }
  ]
}
```

### Basics

```php
$obj->books; // Instance of ArrayObject
$obj->books->pluckArray('author'); // array [ 'George Orwell', 'Jane Austen' ]
$obj->pluckArray('books.author'); // array [ 'George Orwell', 'Jane Austen' ]
$obj->books->count(); // 2
$obj->books->isCollection(); // true
$obj->books[0]; // Instance of ArrayObject
$obj->books[0]->isCollection(); // false
$obj->books[0]->id; // 1
```

Note: All of the methods gracefully handle treating an individual item as an array.

#### get($key, $default = null)

The `get()` method allows you to fetch the value of a property, and receive a default if the value does not exist. It also supports depth which is indicated with a `.`:

```php
$obj->get('books.0');       // ArrayObject
$obj->get('books.0.title'); // "1984"
$obj->get('books.1.title'); // "Pride and Prejudice"
$obj->get('books.0.missing', 'Default value'); // "Default value"
```

You can also fetch the properties using object property syntax (we overload `__get()` to achieve this):

```php
$obj->books[0];           // ArrayObject
$obj->books[0]->title;    // "1984"
$obj->books[1]->title;    // "Pride and Prejudice"
$obj->books[0]->missing;  // throws InvalidPropertyException
```

#### getRaw($key, $default = null)

When a value fetched via `get('some.key')` is an array, it is boxed into an instance of `ArrayObject` .
The `getRaw()` method, however, does not perform any 'boxing' on the returned value.

```php
$obj->get('books');            // ArrayObject
$obj->getRaw('books');         // (array)
$obj->get('books.0.title');    // "1984"
$obj->getRaw('books.0.title'); // "1984"
```

#### set($key, $value, $onlyIfExists = false)

The `set()` method allows you to set a property using dot notation. It will automatically create the 
underlying array structure:

```php
$obj->set('some.deep.key', $value);  // Set nested property
$obj->set('some_key', $anotherArrayObject);  // Pass an ArrayObjectInterface as value
```

When the `$onlyIfExists` flag is passed as `true`, it will only set that property if the key already exists.

#### getOrFail($key)

Similar to `get()` but will throw a `InvalidPropertyException` when the key is not found.

#### has($key)

To test for the existence of an element, use the `has()` method:

```php
$obj->has('books'); // true
$obj->has('books.0.id'); // true
$obj->has('books.1.name'); // false
$obj->has('books.5'); // false
```

#### each(callback)

Looping over a collection can be acheived by passing a callback to `each()`:

```php
$obj->books->each(function($book) {
    echo "ID: {$book->id}, title: {$book->title}\n";
});

foreach ($obj->books as $book) {
    echo "ID: {$book->id}, title: {$book->title}\n";
}
```

If you have a single item, it still works.

#### iterator

Since `ArrayObject` implements an Iterator interface, you can simply `foreach()` over the object.  This works for single items or collections.

```php
foreach ($obj->books as $book) {
    echo "ID: {$book->id}, title: {$book->title}\n";
}
```

#### pluck($key)

Returns a new collection which contains the plucked property.

```php
$titles = $obj->books->pluck('title');  // ArrayObject
$arr = $titles->toArray();  // ['1984', 'Pride and Prejudice']
```

#### pluckArray($key)

Returns a new array of the plucked property.
This provides a shortcut from `pluck($key)->toArray()`:

```php
$arr = $obj->books->pluckArray('title');  // ['1984', 'Pride and Prejudice']
```

#### count()

You can call count off any node:

```php
$obj->count(); // 1
$obj->books->count(); // 2
$obj->books[0]->count(); // 1
```
Note: When called on a single item (ie. not a collection), it will return 1.

#### hasItems()

Returns true when the collection contains at least one item.

```php
$obj->hasItems();
```

Note: When called on a single item (ie. not a collection), it will return true

#### filter(callback|array $filter)

Apply either a callback, or an array of "where" conditions, and only return items that match.

Using a callback, each item is an instance of ArrayObject:

```php
// Only return items with a title
$filteredBooksWithTitle = $obj->books->filter(function($book) {
  return $book->has('title');
});
```

You can also specify a list of conditions using an array:

```php
// Only return items with a title
$filteredBooksWithTitle = $obj->books->filter(["title" => '1984']);
```

#### toArray()

Getting the original array is available via the `toArray()` method:

```php
$obj->books[0]->toArray(); // [ 'id' => 1, 'title' => '1984', 'author' => 'George Orwell' ]
$obj->get(1)->toArray(); // [ 'id' => 2, 'title' => 'Pride and Prejudice', 'author' => 'Jane Austen' ]
$obj->books->toArray(); // [ [ 'id' => 1, 'title' => '1984', 'author' => 'George Orwell' ], [ 'id' => 2, 'title' => 'Pride and Prejudice', 'author' => 'Jane Austen' ] ]
$obj->toArray(); // [ 'books' => [ [ 'id' => 1, 'title' => '1984', 'author' => 'George Orwell' ], [ 'id' => 2, 'title' => 'Pride and Prejudice', 'author' => 'Jane Austen' ] ] ]
```

#### toJson()

Returns a json encoded string of the underlying array:

```php
$json = $obj->toJson(); // '{ "some_prop": [ "val1", "val2" ] }`
```

#### isCollection()

Determines if the underlying array is a collection.

```php
$obj->books->isCollection(); // true
$obj->get('books.0')->isCollection(); // false
$obj->isCollection(); // false
```

#### unshift($val[, $val...])

Adds one or more items to the *start* of the collection.
If the array is not currently a collection it will be converted to a collection with one element.

```php
$obj->unshift('value')
    ->unshift('value1', 'value2', 'value3'); 
```

Note: you can pass `ArrayObject`'s as values.

#### shift()

Pulls the *first* item off the collection.  
If the array is not currently a collection it will be converted to a collection with one element. 

```php
$item = $obj->shift(); // ArrayObject
```

#### push($val[, $val...])

Adds one or more items to the *end* of the collection.
If the array is not currently a collection it will be converted to a collection with one element.

```php
$obj->push('value')
    ->push('value1', 'value2', 'value3'); 
```

Note: you can pass `ArrayObject`'s as values.

#### pop()

Pulls the *last* item off the collection.  
If the array is not currently a collection it will be converted to a collection with one element. 

```php
$item = $obj->pop();  // ArrayObject
```

## Contributing

Contributions are welcome, please submit a pull-request or create an issue.
Your submitted code should be formatted using PSR-1/PSR-2 standards.

## About

- Author: [Jodie Dunlop](https://github.com/jodiedunlop)
- License: [MIT](LICENSE)
- Copyright (c) 2018 Rex Software Pty Ltd
