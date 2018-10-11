<?php

namespace Rexlabs\ArrayObject\Test;

use PHPUnit\Framework\TestCase;
use Rexlabs\ArrayObject\ArrayObject;
use Rexlabs\ArrayObject\ArrayObjectInterface;
use Rexlabs\ArrayObject\Exceptions\InvalidOffsetException;
use Rexlabs\ArrayObject\Exceptions\InvalidPropertyException;
use Rexlabs\ArrayObject\Exceptions\JsonDecodeException;
use Rexlabs\ArrayObject\Exceptions\JsonEncodeException;

class ArrayObjectTest extends TestCase
{
    public function test_from_array()
    {
        $obj = ArrayObject::fromArray([
            'id'      => 1234,
            'subject' => 'hello',
        ]);
        $this->assertArraySubset([
            'id'      => 1234,
            'subject' => 'hello',
        ], $obj->toArray());
    }

    public function test_from_array_collection()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1234,
                'subject' => 'hello',
            ],
            [
                'id'      => 3456,
                'subject' => 'bye',
            ],
        ]);
        $this->assertTrue($obj->isCollection());
        $this->assertCount(2, $obj);
        $this->assertArraySubset([
            [
                'id'      => 1234,
                'subject' => 'hello',
            ],
            [
                'id'      => 3456,
                'subject' => 'bye',
            ],
        ], $obj->toArray());
    }

    public function test_from_json()
    {
        // Associative
        $obj = ArrayObject::fromJson(json_encode([
            'id'      => 1234,
            'subject' => 'hello',
        ]));
        $this->assertArraySubset([
            'id'      => 1234,
            'subject' => 'hello',
        ], $obj->toArray());

        // Sequential array
        $obj = ArrayObject::fromJson(json_encode(['one', 2, 'three']));
        $this->assertArraySubset(['one', 2, 'three'], $obj->toArray());

        // Empty array
        $obj = ArrayObject::fromJson(json_encode(null));
        $this->assertEmpty($obj->toArray());

        $this->expectException(JsonDecodeException::class);
        ArrayObject::fromJson('x');
    }

    public function test_from_json_collection()
    {
        $obj = ArrayObject::fromJson(json_encode([
            [
                'id'      => 1234,
                'subject' => 'hello',
            ],
            [
                'id'      => 3456,
                'subject' => 'bye',
            ],
        ]));
        $this->assertTrue($obj->isCollection());
        $this->assertCount(2, $obj);
        $this->assertArraySubset([
            [
                'id'      => 1234,
                'subject' => 'hello',
            ],
            [
                'id'      => 3456,
                'subject' => 'bye',
            ],
        ], $obj->toArray());
    }

    public function test_has()
    {
        $obj = ArrayObject::fromArray([
            'id'      => 1234,
            'subject' => 'hello',
        ]);
        $this->assertTrue($obj->has('subject'));
        $this->assertTrue($obj->has('id'));
        $this->assertFalse($obj->has('missing_field'));
    }

    public function test_get()
    {
        $obj = ArrayObject::fromArray([
            'id'      => 1234,
            'subject' => 'hello',
            'sub'     => [
                'x' => 1,
                'y' => 2,
            ],
        ]);
        $this->assertEquals(1234, $obj->get('id'));
        $this->assertEquals('hello', $obj->get('subject'));
        $this->assertNull($obj->get('missing_field'));

        $this->assertInstanceOf(ArrayObject::class, $obj->get('sub'));
        $this->assertEquals(1, $obj->get('sub.x'));
        $this->assertEquals(1, $obj->get('sub')->get('x'));
        $this->assertEquals(2, $obj->get('sub.y'));
        $this->assertFalse($obj->get('sub.missing_field', false));
    }

    public function test_magic_getter()
    {
        $obj = ArrayObject::fromArray([
            'id'      => 1234,
            'subject' => 'hello',
            'sub'     => [
                'x' => 1,
                'y' => 2,
            ],
        ]);
        $this->assertEquals(1234, $obj->id);
        $this->assertEquals(1, $obj->sub->x);
        $this->assertEquals(2, $obj->sub->get('y'));
        $this->assertTrue($obj->sub->has('x'));
        $this->assertFalse($obj->sub->has('missing_field'));
        $this->assertEquals(null, $obj->sub->missing_field);
    }

    public function test_get_or_fail()
    {
        $obj = ArrayObject::fromArray([
            'id'      => 1234,
            'subject' => 'hello',
            'sub'     => [
                'x' => 1,
                'y' => 2,
            ],
        ]);
        $this->assertEquals('hello', $obj->getOrFail('subject'));
        $this->assertEquals(2, $obj->getOrFail('sub.y'));

        $this->expectException(InvalidPropertyException::class);
        $obj->getOrFail('invalid_key');
    }

    public function test_to_array_returns_same_array()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);
        $this->assertEquals([
            [
                'id'      => 1,
                'subject' => 'Hello',
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ], $obj->toArray());
    }

    public function test_collection()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);
        $this->assertTrue($obj->isCollection());
    }

    public function test_collection_each()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);

        $count = 0;
        $obj->each(function (ArrayObjectInterface $obj) use (&$count) {
            $this->assertNotNull($obj->id);
            $this->assertNotNull($obj->subject);
            if ($obj->has('sub')) {
                $this->assertNotNull($obj->sub->x);
            } else {
                $this->assertNull($obj->sub);
            }
            $count++;
        });
        $this->assertEquals(2, $count);
    }

    public function test_single_item_each()
    {
        $obj = ArrayObject::fromArray([
            'id'      => 1,
            'subject' => 'Hello',

        ]);

        $count = 0;
        $obj->each(function (ArrayObjectInterface $obj) use (&$count) {
            $this->assertEquals([
                'id'      => 1,
                'subject' => 'Hello',
            ], $obj->toArray());
            $count++;
        });
        $this->assertEquals(1, $count);
    }

    public function test_pluck()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
                'sub'     => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);
        $plucked = $obj->pluckArray('id');
        $this->assertTrue(is_array($plucked));
        $this->assertCount(2, $plucked);
        $this->assertEquals(1, $plucked[0]);
        $this->assertEquals(2, $plucked[1]);

        $plucked = $obj->pluckArray('sub');
        $this->assertTrue(is_array($plucked));
        $this->assertCount(2, $plucked);
        $this->assertEquals(50, $plucked[0]->x);
        $this->assertEquals(1, $plucked[1]->x);

        $plucked = ArrayObject::fromArray(['id' => 100, 'subject' => 'Single item'])->pluckArray('id');
        $this->assertTrue(\is_array($plucked));
        $this->assertEquals([100], $plucked);

        $plucked = $obj->pluck('subject');
        $this->assertInstanceOf(ArrayObject::class, $plucked);
        $this->assertTrue($plucked->isCollection());
    }

    public function test_filter()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
                'sub'     => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id'      => 3,
                'subject' => 'Welcome Back',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);

        // Filter on condition
        $filteredCollection = $obj->filter(['sub.x' => 1]);
        $this->assertInstanceOf(ArrayObjectInterface::class, $filteredCollection);
        $this->assertCount(2, $filteredCollection); // Implements \Countable
        // Property resolution resolves to the first node in the collection
        $this->assertEquals(2, $filteredCollection->id);

        // Filter via callback
        $filteredCollection = $obj->filter(function (ArrayObject $item) {
            return $item->id >= 2;
        });
        $this->assertCount(2, $filteredCollection);
        $this->assertEquals(2, $filteredCollection[0]->id);
        $this->assertEquals(3, $filteredCollection[1]->id);
    }

    public function test_collection_array_access()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
                'sub'     => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id'      => 3,
                'subject' => 'Welcome Back',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);

        // Implements ArrayAccess
        $this->assertEquals(100, $obj[0]->sub->y);
        $this->assertEquals('Goodbye!', $obj[1]->subject);
        $this->assertEquals(3, $obj[2]->id);
        $this->assertFalse(isset($obj[3]));
    }

    public function test_get_array_access()
    {
        $book = ArrayObject::fromArray([
            'id'     => 1,
            'title'  => '1984',
            'author' => 'George Orwell',
        ]);
        $book = $book[0];
        $this->assertEquals([
            'id'     => 1,
            'title'  => '1984',
            'author' => 'George Orwell',
        ], $book->toArray());
    }

    public function test_collection_has()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
                'sub'     => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                    'z' => 3,
                ],
            ],
            [
                'id'      => 3,
                'subject' => 'Welcome Back',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);

        $this->assertTrue($obj->has(2));
        $this->assertTrue($obj->has('2.subject'));
        $this->assertTrue($obj->has('subject'));
        $this->assertTrue($obj->has('0.id'));
        $this->assertFalse($obj->has('invalid_key'));
        $this->assertTrue($obj->has('1.sub.z'));
        $this->assertFalse($obj->has('2.sub.z'));
        $this->assertFalse($obj->has(3));
    }

    public function test_count_collection()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
                'sub'     => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id'      => 3,
                'subject' => 'Welcome Back',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);

        // Implements Countable
        $this->assertCount(3, $obj);
        $this->assertEquals(3, $obj->count());
    }

    public function test_count_single_node()
    {
        $obj = ArrayObject::fromArray([
            'id'      => 1,
            'subject' => 'Hello',
            'sub'     => [
                'x' => 50,
                'y' => 100,
            ],
        ]);
        $this->assertCount(1, $obj);
    }

    public function test_get_with_collection()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
                'sub'     => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id'      => 3,
                'subject' => 'Welcome Back',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);

        // When get() has no offset it will work on the first element in the list
        $this->assertEquals(1, $obj->get('id', 0));
        $this->assertEquals('Hello', $obj->get('subject'));
        $this->assertEquals('Hello', $obj->subject);
        $this->assertEquals(50, $obj->sub->x);

        // Get can be used to fetch by offset key, or an integer offset
        $this->assertEquals(2, $obj->get('2.sub.y'));
        $this->assertInstanceOf(ArrayObject::class, $obj->get(2));
        $this->assertNull($obj->get(3));
    }

    public function test_get_offset_with_collection()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
                'sub'     => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);
        $this->assertNotEmpty($obj[0]);
        $this->assertNotEmpty($obj[1]);

        $this->expectException(InvalidOffsetException::class);
        $invalidId = $obj[2]->id;
    }

    public function test_first_returns_first_item()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
                'sub'     => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id'      => 3,
                'subject' => 'Welcome Back',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);

        $first = $obj->first();
        $this->assertInstanceOf(ArrayObject::class, $first);
        $this->assertEquals([
            'id'      => 1,
            'subject' => 'Hello',
            'sub'     => [
                'x' => 50,
                'y' => 100,
            ],
        ], $first->toArray());

        // Individual item
        $first = $first->first();
        $this->assertInstanceOf(ArrayObject::class, $first);
        $this->assertEquals([
            'id'      => 1,
            'subject' => 'Hello',
            'sub'     => [
                'x' => 50,
                'y' => 100,
            ],
        ], $first->toArray());
    }

    public function test_last_on_collection()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
                'sub'     => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id'      => 3,
                'subject' => 'Welcome Back',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);

        $last = $obj->last();
        $this->assertInstanceOf(ArrayObject::class, $last);
        $this->assertEquals([
            'id'      => 3,
            'subject' => 'Welcome Back',
            'sub'     => [
                'x' => 1,
                'y' => 2,
            ],
        ], $last->toArray());

        $last = $last->last();
        $this->assertInstanceOf(ArrayObject::class, $last);
        $this->assertEquals([
            'id'      => 3,
            'subject' => 'Welcome Back',
            'sub'     => [
                'x' => 1,
                'y' => 2,
            ],
        ], $last->toArray());
    }

    public function test_collection_iterator()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
                'sub'     => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id'      => 3,
                'subject' => 'Welcome Back',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);
        foreach ($obj as $childObj) {
            $this->assertInstanceOf(ArrayObject::class, $childObj);
            $this->assertTrue($childObj->has('id'));
        }
    }

    public function test_can_set_via_array_access()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
                'sub'     => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id'      => 3,
                'subject' => 'Welcome Back',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);
        $obj[0] = $obj[0]->set('subject', 'Goodbye!');
        $obj[1] = $obj[1]->set('subject', 'Hello');
        $obj[2] = $obj[0];
        $this->assertEquals([
            [
                'id'      => 1,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id'      => 2,
                'subject' => 'Hello',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id'      => 1,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
        ], $obj->toArray());

        $book = ArrayObject::fromArray([
            'id'     => 1,
            'title'  => '1984',
            'author' => 'George Orwell',
        ]);
        $book[1] = ArrayObject::fromArray([
            'id'     => 2,
            'title'  => 'Pride and Prejudice',
            'author' => 'Jane Austen',
        ]);
        $this->assertCount(2, $book);

        $this->expectException(InvalidOffsetException::class);
        $book[3] = ArrayObject::fromArray([
            'id'     => 2,
            'title'  => 'Pride and Prejudice',
            'author' => 'Jane Austen',
        ]);
    }

    public function test_has_items()
    {
        $obj = ArrayObject::fromArray([
            [
                'id'      => 1,
                'subject' => 'Hello',
                'sub'     => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id'      => 2,
                'subject' => 'Goodbye!',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id'      => 3,
                'subject' => 'Welcome Back',
                'sub'     => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);
        $this->assertTrue($obj->hasItems());

        // Pop off all items
        $obj->pop();
        $obj->pop();
        $obj->pop();
        $this->assertFalse($obj->hasItems());

        // Empty Array
        $this->assertFalse(ArrayObject::fromArray([])->hasItems());

        // Associative array
        $this->assertTrue(ArrayObject::fromArray([
            'id'      => 3,
            'subject' => 'Welcome Back',
            'sub'     => [
                'x' => 1,
                'y' => 2,
            ],
        ])->hasItems());
    }

    public function test_shift()
    {
        $books = ArrayObject::fromArray([
            [
                'id'     => 1,
                'title'  => '1984',
                'author' => 'George Orwell',
            ],
            [
                'id'     => 2,
                'title'  => 'Pride and Prejudice',
                'author' => 'Jane Austen',
            ],
        ]);
        $book = $books->shift();
        $this->assertInstanceOf(ArrayObject::class, $book);
        $this->assertEquals([
            'id'     => 1,
            'title'  => '1984',
            'author' => 'George Orwell',
        ], $book->toArray());
        $this->assertCount(1, $books);

        $book = $books->shift();
        $this->assertInstanceOf(ArrayObject::class, $book);
        $this->assertEquals([
            'id'     => 2,
            'title'  => 'Pride and Prejudice',
            'author' => 'Jane Austen',
        ], $book->toArray());
        $this->assertCount(0, $books);

        // Book is not currently a collection, but it will be converted internally
        $newBook = $book->shift();
        $this->assertEquals([
            'id'     => 2,
            'title'  => 'Pride and Prejudice',
            'author' => 'Jane Austen',
        ], $newBook->toArray());
        $this->assertFalse($book->hasItems());

        $this->expectException(InvalidOffsetException::class);
        $result = $books->shift();
    }

    public function test_pop()
    {
        $books = ArrayObject::fromArray([
            [
                'id'     => 1,
                'title'  => '1984',
                'author' => 'George Orwell',
            ],
            [
                'id'     => 2,
                'title'  => 'Pride and Prejudice',
                'author' => 'Jane Austen',
            ],
        ]);

        $book = $books->pop();
        $this->assertInstanceOf(ArrayObject::class, $book);
        $this->assertEquals([
            'id'     => 2,
            'title'  => 'Pride and Prejudice',
            'author' => 'Jane Austen',
        ], $book->toArray());
        $this->assertCount(1, $books);

        $book = $books->pop();
        $this->assertInstanceOf(ArrayObject::class, $book);
        $this->assertEquals([
            'id'     => 1,
            'title'  => '1984',
            'author' => 'George Orwell',
        ], $book->toArray());
        $this->assertCount(0, $books);

        $this->expectException(InvalidOffsetException::class);
        $result = $books->pop();
    }

    public function test_unshift()
    {
        $books = ArrayObject::fromArray([
            [
                'id'     => 1,
                'title'  => '1984',
                'author' => 'George Orwell',
            ],
            [
                'id'     => 2,
                'title'  => 'Pride and Prejudice',
                'author' => 'Jane Austen',
            ],
        ]);

        $books->unshift([
            'id'     => 3,
            'title'  => 'To Kill a Mockingbird',
            'author' => 'Harper Lee',
        ]);
        $this->assertCount(3, $books);
        $this->assertEquals([
            'id'     => 3,
            'title'  => 'To Kill a Mockingbird',
            'author' => 'Harper Lee',
        ], $books->first()->toArray());

        $books->unshift([
            'id'     => 4,
            'title'  => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
        ]);
        $this->assertCount(4, $books);
        $this->assertEquals([
            'id'     => 4,
            'title'  => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
        ], $books->first()->toArray());

        $book = $books->first();
        $this->assertFalse($book->isCollection());
        $book->unshift([
            'id'     => 1,
            'title'  => '1984',
            'author' => 'George Orwell',
        ]);
        // Automatic conversion to collection
        $this->assertTrue($book->isCollection());
        $this->assertCount(2, $book);
    }

    public function test_push()
    {
        $books = ArrayObject::fromArray([
            [
                'id'     => 1,
                'title'  => '1984',
                'author' => 'George Orwell',
            ],
            [
                'id'     => 2,
                'title'  => 'Pride and Prejudice',
                'author' => 'Jane Austen',
            ],
        ]);

        $books->push([
            'id'     => 3,
            'title'  => 'To Kill a Mockingbird',
            'author' => 'Harper Lee',
        ]);
        $this->assertCount(3, $books);
        $this->assertEquals([
            'id'     => 3,
            'title'  => 'To Kill a Mockingbird',
            'author' => 'Harper Lee',
        ], $books->last()->toArray());

        $books->push([
            'id'     => 4,
            'title'  => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
        ]);
        $this->assertCount(4, $books);
        $this->assertEquals([
            'id'     => 4,
            'title'  => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
        ], $books->last()->toArray());

        $book = $books->last();
        $this->assertFalse($book->isCollection());
        $book->unshift([
            'id'     => 1,
            'title'  => '1984',
            'author' => 'George Orwell',
        ]);
        // Automatic conversion to collection
        $this->assertTrue($book->isCollection());
        $this->assertCount(2, $book);
    }

    public function test_unset()
    {
        $books = ArrayObject::fromArray([
            [
                'id'     => 1,
                'title'  => '1984',
                'author' => 'George Orwell',
            ],
            [
                'id'     => 2,
                'title'  => 'Pride and Prejudice',
                'author' => 'Jane Austen',
            ],
        ]);

        unset($books[0]);
        $this->assertTrue($books->isCollection());
        $this->assertCount(1, $books);

        unset($books[0]);
        $this->assertTrue($books->isCollection());
        $this->assertCount(0, $books);
        $this->assertFalse($books->hasItems());

        $book = ArrayObject::fromArray([
            'id'     => 1,
            'title'  => '1984',
            'author' => 'George Orwell',
        ]);
        $this->assertFalse($book->isCollection());
        unset($book[0]);
        $this->assertTrue($book->isCollection());
        $this->assertCount(0, $book);

        $this->expectException(InvalidOffsetException::class);
        unset($books[0]);
    }

    public function test_to_json()
    {
        $books = ArrayObject::fromArray([
            [
                'id'     => 1,
                'title'  => '1984',
                'author' => 'George Orwell',
            ],
            [
                'id'     => 2,
                'title'  => 'Pride and Prejudice',
                'author' => 'Jane Austen',
            ],
        ]);
        $this->assertEquals(json_encode([
            [
                'id'     => 1,
                'title'  => '1984',
                'author' => 'George Orwell',
            ],
            [
                'id'     => 2,
                'title'  => 'Pride and Prejudice',
                'author' => 'Jane Austen',
            ],
        ]), $books->toJson());

        $this->expectException(JsonEncodeException::class);
        ArrayObject::fromArray([
            'title' => "\xB1\x31", // Bad UTF-8 sequence
        ])->toJson();
    }

    public function test_string_casting()
    {
        $books = ArrayObject::fromArray([
            [
                'id'     => 1,
                'title'  => '1984',
                'author' => 'George Orwell',
            ],
            [
                'id'     => 2,
                'title'  => 'Pride and Prejudice',
                'author' => 'Jane Austen',
            ],
        ]);
        $this->assertEquals(json_encode([
            [
                'id'     => 1,
                'title'  => '1984',
                'author' => 'George Orwell',
            ],
            [
                'id'     => 2,
                'title'  => 'Pride and Prejudice',
                'author' => 'Jane Austen',
            ],
        ]), (string) $books);
    }

    public function test_magic_setter()
    {
        $book = ArrayObject::fromArray([
            'id'     => 1,
            'title'  => '1984',
            'author' => 'George Orwell',
        ]);
        $book->id = 2;
        $this->assertEquals(2, $book->id);
    }

    public function test_set_with_only_exists_option()
    {
        $book = ArrayObject::fromArray([
            'id'     => 1,
            'title'  => '1984',
            'author' => 'George Orwell',
        ]);
        $book->set('id', 3);
        $this->assertEquals(3, $book->id);
        $book->set('new_field', 'new_value');
        $this->assertTrue($book->has('new_field'));
        $book->set('another_field', 'another_value', true);
        $this->assertFalse($book->has('another_field'));
    }

    public function test_isset()
    {
        $book = ArrayObject::fromArray([
            'id'     => 1,
            'title'  => '1984',
            'author' => 'George Orwell',
        ]);
        $this->assertTrue(isset($book->id));
        $this->assertFalse(isset($book->missing_field));
    }

    public function test_can_get_unboxed_value()
    {
        $foo = ArrayObject::fromArray([
            'foo' => 'bar',
        ])->getRaw('foo');
        $this->assertEquals('bar', $foo);

        $foo = ArrayObject::fromArray([
            'foo' => [
                'bar',
            ],
        ])->getRaw('foo');
        $this->assertEquals(['bar'], $foo);
    }
}
