<?php
namespace RexSoftware\ArrayObject\Test;

use PHPUnit\Framework\TestCase;
use RexSoftware\ArrayObject\ArrayObject;
use RexSoftware\ArrayObject\ArrayObjectInterface;
use RexSoftware\ArrayObject\Exceptions\InvalidPropertyException;

class ArrayObjectTest extends TestCase
{
    public function test_from_array()
    {
        $obj = ArrayObject::fromArray([
            'id' => 1234,
            'subject' => 'hello',
        ]);
        $this->assertArraySubset([
            'id' => 1234,
            'subject' => 'hello',
        ], $obj->toArray());
    }

    public function test_from_array_collection()
    {
        $obj = ArrayObject::fromArray([
            [
                'id' => 1234,
                'subject' => 'hello',
            ],
            [
                'id' => 3456,
                'subject' => 'bye',
            ],
        ]);
        $this->assertTrue($obj->isCollection());
        $this->assertCount(2, $obj);
        $this->assertArraySubset([
            [
                'id' => 1234,
                'subject' => 'hello',
            ],
            [
                'id' => 3456,
                'subject' => 'bye',
            ],
        ], $obj->toArray());
    }

    public function test_from_json()
    {
        $obj = ArrayObject::fromJson(json_encode([
            'id' => 1234,
            'subject' => 'hello',
        ]));
        $this->assertArraySubset([
            'id' => 1234,
            'subject' => 'hello',
        ], $obj->toArray());
    }

    public function test_from_json_collection()
    {
        $obj = ArrayObject::fromJson(json_encode([
            [
                'id' => 1234,
                'subject' => 'hello',
            ],
            [
                'id' => 3456,
                'subject' => 'bye',
            ],
        ]));
        $this->assertTrue($obj->isCollection());
        $this->assertCount(2, $obj);
        $this->assertArraySubset([
            [
                'id' => 1234,
                'subject' => 'hello',
            ],
            [
                'id' => 3456,
                'subject' => 'bye',
            ],
        ], $obj->toArray());
    }


    public function test_has()
    {
        $obj = ArrayObject::fromArray([
            'id' => 1234,
            'subject' => 'hello',
        ]);
        $this->assertTrue($obj->has('subject'));
        $this->assertTrue($obj->has('id'));
        $this->assertFalse($obj->has('missing_field'));
    }


    public function test_get()
    {
        $obj = ArrayObject::fromArray([
            'id' => 1234,
            'subject' => 'hello',
            'sub' => [
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
            'id' => 1234,
            'subject' => 'hello',
            'sub' => [
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
            'id' => 1234,
            'subject' => 'hello',
            'sub' => [
                'x' => 1,
                'y' => 2,
            ],
        ]);
        $this->expectException(InvalidPropertyException::class);
        $obj->getOrFail('invalid_key');
        $this->expectException(InvalidPropertyException::class);
        $obj->getOrFail('sub.invalid_key');
    }

    public function test_to_array_returns_same_array()
    {
        $obj = ArrayObject::fromArray([
            [
                'id' => 1,
                'subject' => 'Hello',
            ],
            [
                'id' => 2,
                'subject' => 'Goodbye!',
                'sub' => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);
        $this->assertEquals([
            [
                'id' => 1,
                'subject' => 'Hello',
            ],
            [
                'id' => 2,
                'subject' => 'Goodbye!',
                'sub' => [
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
                'id' => 1,
                'subject' => 'Hello',
            ],
            [
                'id' => 2,
                'subject' => 'Goodbye!',
                'sub' => [
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
                'id' => 1,
                'subject' => 'Hello',
            ],
            [
                'id' => 2,
                'subject' => 'Goodbye!',
                'sub' => [
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

    public function test_pluck()
    {
        $obj = ArrayObject::fromArray([
            [
                'id' => 1,
                'subject' => 'Hello',
                'sub' => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id' => 2,
                'subject' => 'Goodbye!',
                'sub' => [
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
    }

    public function test_filter()
    {
        $obj = ArrayObject::fromArray([
            [
                'id' => 1,
                'subject' => 'Hello',
                'sub' => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id' => 2,
                'subject' => 'Goodbye!',
                'sub' => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id' => 3,
                'subject' => 'Welcome Back',
                'sub' => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);

        $filteredCollection = $obj->filter(['sub.x' => 1]);
        $this->assertInstanceOf(ArrayObjectInterface::class, $filteredCollection);
        $this->assertCount(2, $filteredCollection); // Implements \Countable

        // Property resolution resolves to the first node in the collection
        $this->assertEquals(2, $filteredCollection->id);

    }

    public function test_collection_array_access()
    {
        $obj = ArrayObject::fromArray([
            [
                'id' => 1,
                'subject' => 'Hello',
                'sub' => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id' => 2,
                'subject' => 'Goodbye!',
                'sub' => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id' => 3,
                'subject' => 'Welcome Back',
                'sub' => [
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

    public function test_collection_has()
    {
        $obj = ArrayObject::fromArray([
            [
                'id' => 1,
                'subject' => 'Hello',
                'sub' => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id' => 2,
                'subject' => 'Goodbye!',
                'sub' => [
                    'x' => 1,
                    'y' => 2,
                    'z' => 3,
                ],
            ],
            [
                'id' => 3,
                'subject' => 'Welcome Back',
                'sub' => [
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
                'id' => 1,
                'subject' => 'Hello',
                'sub' => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id' => 2,
                'subject' => 'Goodbye!',
                'sub' => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id' => 3,
                'subject' => 'Welcome Back',
                'sub' => [
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
            'id' => 1,
            'subject' => 'Hello',
            'sub' => [
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
                'id' => 1,
                'subject' => 'Hello',
                'sub' => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id' => 2,
                'subject' => 'Goodbye!',
                'sub' => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id' => 3,
                'subject' => 'Welcome Back',
                'sub' => [
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
                'id' => 1,
                'subject' => 'Hello',
                'sub' => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id' => 2,
                'subject' => 'Goodbye!',
                'sub' => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);
        $this->assertNotEmpty($obj[0]);
        $this->assertNotEmpty($obj[1]);
        $this->assertEmpty($obj[2]);
    }

    public function test_first_on_collection()
    {
        $obj = ArrayObject::fromArray([
            [
                'id' => 1,
                'subject' => 'Hello',
                'sub' => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id' => 2,
                'subject' => 'Goodbye!',
                'sub' => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id' => 3,
                'subject' => 'Welcome Back',
                'sub' => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);

        $this->assertEquals([
            'id' => 1,
            'subject' => 'Hello',
            'sub' => [
                'x' => 50,
                'y' => 100,
            ],
        ], $obj->first()->toArray());
    }

    public function test_last_on_collection()
    {
        $obj = ArrayObject::fromArray([
            [
                'id' => 1,
                'subject' => 'Hello',
                'sub' => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id' => 2,
                'subject' => 'Goodbye!',
                'sub' => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id' => 3,
                'subject' => 'Welcome Back',
                'sub' => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);

        $this->assertEquals([
            'id' => 3,
            'subject' => 'Welcome Back',
            'sub' => [
                'x' => 1,
                'y' => 2,
            ],
        ], $obj->last()->toArray());
    }

    public function test_collection_iterator()
    {
        $obj = ArrayObject::fromArray([
            [
                'id' => 1,
                'subject' => 'Hello',
                'sub' => [
                    'x' => 50,
                    'y' => 100,
                ],
            ],
            [
                'id' => 2,
                'subject' => 'Goodbye!',
                'sub' => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
            [
                'id' => 3,
                'subject' => 'Welcome Back',
                'sub' => [
                    'x' => 1,
                    'y' => 2,
                ],
            ],
        ]);
        foreach ($obj as $obj) {
            $this->assertInstanceOf(ArrayObject::class, $obj);
            $this->assertTrue($obj->has('id'));
        }
    }

}