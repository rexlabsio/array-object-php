# Array Object

An interface for working fluently with array's.

ArrayObject provides a wrapper around PHP's built-in arrays that allow you methods for filtering, and retrieving items, and conveniently treats individual items and collections as the same object.

This is especially useful for working with JSON responses from API requests.

## Installation

```bash
composer require rexsoftware/array-object
```

## Usage

```
<?php

// Initialise from an Array
$obj = ArrayObject::fromArray([...]);

// Initialise from a JSON encoded string
$obj = ArrayObject::fromJson($str);

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
$obj->books->pluck('author'); // array [ 'George Orwell', 'Jane Austen' ]
$obj->books->count(); // 2
$obj->books->isCollection(); // true
$obj->books[0]; // Instance of ArrayObject
$obj->books[0]->isCollection(); // false
$obj->books[0]->id; // 1
```

#### get($key, $default = null)

The `get()` method allows you to fetch the value of a property, and receive a default if the value does not exist. It also supports depth which is indicated with a `.`:

```php
$obj->get('books.0.title'); // "1984"
$obj->get('books.1.title'); // "Pride and Prejudice"
$obj->get('books.0.missing', 'Defult value'); // "Default value"
```

You can also fetch the properties using object property syntax (we overload `__get()` to achieve this):

```php
$obj->books[0]->title;    // "1984"
$obj->books[1]->title;    // "Pride and Prejudice"
$obj->books[0]->missing;  // throws InvalidPropertyException
```

#### set($key, $value, $onlyIfExists = false)



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
$obj->books->each(function($obj) {
    echo "ID: {$obj->id}, title: {$obj->title}\n";
});

foreach ($obj->books as $obj) {
    echo "ID: {$obj->id}, title: {$obj->title}\n";
}
```

If you have a single item, it still works.

#### iterator

Since `ArrayObject` implements an Iterator interface, you can simply `foreach()` over the object.  This works for single items or collections.

```php
foreach ($obj->books as $obj) {
    echo "ID: {$obj->id}, title: {$obj->title}\n";
}
```

#### pluck($key)

#### count()

You can call count off any node:

```php
$obj->count(); // 1
$obj->books->count(); // 2
$obj->books[0]->count();
```

#### filter(callback|array $conditions)

Apply either a callback, or an array of where conditions, and only return items that match.

Using a callback, each item is an instance of ArrayObject:

```php
// Only return items with a title
$filteredBooksWithTitle = $obj->books->filter(function($item) {
  return $item->has('title');
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

#### isCollection()

Determines if the underlying array is a collection.

```php
$obj->books->isCollection(); // true
$obj->get('books.0')->isCollection(); // false
$obj->isCollection(); // false
```

#### unshift()

#### shift()

#### push()

#### pop()