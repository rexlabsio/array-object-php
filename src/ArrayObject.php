<?php

namespace Rexlabs\ArrayObject;

use ArrayIterator;
use Rexlabs\ArrayObject\Exceptions\InvalidOffsetException;
use Rexlabs\ArrayObject\Exceptions\InvalidPropertyException;
use Rexlabs\ArrayObject\Exceptions\JsonDecodeException;
use Rexlabs\ArrayObject\Exceptions\JsonEncodeException;
use Rexlabs\UtilityBelt\ArrayUtility;
use Rexlabs\UtilityBelt\CollectionUtility;

/**
 * ArrayObject.
 *
 * @author Jodie Dunlop <jodie.dunlop@rexsoftware.com.au>
 * @copyright (c) 2018 Rex Software Pty Ltd.
 ** @license MIT
 */
class ArrayObject implements ArrayObjectInterface, \ArrayAccess, \Countable, \IteratorAggregate
{
    /** @var array */
    protected $data;

    /** @var bool */
    protected $isCollection;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->isCollection = true;

        if (!empty($data) && ArrayUtility::isAssoc($data)) {
            $this->isCollection = false;
        }
    }

    /**
     * @param string|mixed $json
     * @param int          $options
     *
     * @throws \Rexlabs\ArrayObject\Exceptions\JsonDecodeException
     *
     * @return static
     */
    public static function fromJson($json, int $options = 0)
    {
        $result = json_decode($json, true, 512, $options);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new JsonDecodeException(json_last_error_msg());
        }

        return new static(\is_array($result) ? $result : []);
    }

    /**
     * Fire callback for each member of the array.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function each(callable $callback)
    {
        if ($this->isCollection()) {
            foreach ($this->data as $val) {
                $callback($this->box($val));
            }
        } else {
            $callback($this->box($this->data));
        }

        return $this;
    }

    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    /**
     * Return a plain value or coerce into an ArrayObject.
     *
     * @param mixed $val
     *
     * @return ArrayObjectInterface|mixed
     */
    protected function box($val)
    {
        return \is_array($val) ? static::fromArray($val) : $val;
    }

    /**
     * @param array $array
     *
     * @return static
     */
    public static function fromArray(array $array)
    {
        return new static($array);
    }

    /**
     * Pluck a value by key from each result and return an ArrayObject.
     *
     * @param $key
     *
     * @return ArrayObjectInterface
     */
    public function pluck($key): ArrayObjectInterface
    {
        return static::fromArray($this->pluckArray($key));
    }

    /**
     * Pluck a value by key from each result and return an array.
     *
     * @param $key
     *
     * @return array
     */
    public function pluckArray($key): array
    {
        if ($this->isCollection()) {
            return array_map(function ($val) {
                return $this->box($val);
            }, CollectionUtility::pluck($this->data, $key));
        }

        // Non-collection
        return CollectionUtility::pluck([$this->data], $key);
    }

    /**
     * Return a new collection by filtering data within the current collection.
     *
     * @param mixed $filter
     *
     * @return ArrayObjectInterface
     */
    public function filter($filter): ArrayObjectInterface
    {
        return \is_callable($filter, true) ? $this->filterCallback($filter) : $this->filterConditions($filter);
    }

    /**
     * Return a new collection by filtering the data via a callback.
     *
     * @param callable|\Closure $fn
     *
     * @return ArrayObjectInterface
     */
    public function filterCallback(callable $fn): ArrayObjectInterface
    {
        return new static(
            array_values(
                array_filter(
                    $this->isCollection() ? $this->data : [$this->data],
                    function($item) use ($fn) {
                        return $fn($this->box($item));
                    }
                )
            )
        );
    }

    /**
     * Return a new collection by filtering the data against a list of conditions.
     *
     * @param array  $conditions
     * @param string $matchType
     * @param bool   $preserveKeys
     *
     * @return ArrayObjectInterface
     */
    public function filterConditions(
        array $conditions,
        string $matchType = CollectionUtility::MATCH_TYPE_LOOSE,
        $preserveKeys = false
    ): ArrayObjectInterface {
        return new static(CollectionUtility::filterWhere($this->isCollection() ? $this->data : [$this->data],
            $conditions, $matchType, $preserveKeys));
    }

    /**
     * Returns the first element in the collection.
     *
     * @return mixed
     */
    public function first()
    {
        if (!$this->isCollection()) {
            return $this;
        }

        return isset($this->data[0]) ? $this->box($this->data[0]) : null;
    }

    /**
     * Returns the last element in the collection.
     *
     * @return ArrayObjectInterface|null
     */
    public function last()
    {
        if (!$this->isCollection()) {
            return $this;
        }

        return isset($this->data[0]) ? $this->box(ArrayUtility::last($this->data)) : null;
    }

    /**
     * @param string $key
     *
     * @throws \Rexlabs\ArrayObject\Exceptions\InvalidPropertyException
     *
     * @return mixed
     */
    public function getOrFail($key)
    {
        if (!ArrayUtility::dotExists($this->data, $this->getNormalizedKey($key))) {
            throw new InvalidPropertyException("Missing property '$key'");
        }

        return $this->get($key);
    }

    /**
     * Forces key to be prefixed with an offset.
     *
     * @param $key
     *
     * @return string
     */
    protected function getNormalizedKey($key): string
    {
        // Keys within collections must be offset based
        if (!\is_int($key) && $this->isCollection() && !preg_match('/^\d+\./', $key)) {
            $key = "0.$key";
        }

        return $key;
    }

    /**
     * Returns the value for a given key.
     * @param string $key
     * @param mixed  $default
     *
     * @return ArrayObjectInterface|mixed
     */
    public function get($key, $default = null)
    {
        $result = ArrayUtility::dotRead($this->data, $this->getNormalizedKey($key));
        if ($result === null) {
            return $default;
        }

        return $this->box($this->getRaw($key, $default));
    }

    /**
     * Returns the un-boxed (raw) value for the given key.
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getRaw($key, $default = null)
    {
        $result = ArrayUtility::dotRead($this->data, $this->getNormalizedKey($key));
        if ($result === null) {
            return $default;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * @param string|mixed $key
     * @param mixed        $value
     * @param bool         $onlyIfExists
     *
     * @return $this
     */
    public function set($key, $value, $onlyIfExists = false)
    {
        if ($onlyIfExists && !$this->has($key)) {
            return $this;
        }
        $unboxedValue = $this->unbox($value);
        ArrayUtility::dotMutate($this->data, $key, $unboxedValue);

        return $this;
    }

    /**
     * Determine if the node contains a property.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key): bool
    {
        return ArrayUtility::dotRead($this->data, $this->getNormalizedKey($key)) !== null;
    }

    /**
     * Return a plain value or coerce into a Node or Collection object.
     *
     * @param mixed $val
     *
     * @return array|mixed
     */
    protected function unbox($val)
    {
        return $val instanceof ArrayObjectInterface ? $val->toArray() : $val;
    }

    /**
     * Pull the first item off the collection.
     * If the underlying data is not a collection, it will be converted to one.
     *
     * @throws \Rexlabs\ArrayObject\Exceptions\InvalidOffsetException
     *
     * @return mixed
     */
    public function shift()
    {
        $this->forceCollection();
        if (!$this->hasItems()) {
            throw new InvalidOffsetException('Cannot shift this array, no more items');
        }

        return $this->box(array_shift($this->data));
    }

    /**
     * Forces the underlying data-structure to become a collection.
     */
    protected function forceCollection()
    {
        if (!$this->isCollection()) {
            $this->isCollection = true;
            $this->data = [$this->data];
        }
    }

    /**
     * Determine if there are any more items in this array.
     *
     * @return bool
     */
    public function hasItems(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Return the total number of elements.
     *
     * @return int
     */
    public function count()
    {
        return $this->isCollection() ? \count($this->data) : 1;
    }

    /**
     * Add one or more items at the start of the collection.
     * If the underlying data is not a collection, it will be converted to one.
     *
     * @param array $values
     *
     * @throws \Rexlabs\ArrayObject\Exceptions\InvalidOffsetException
     *
     * @return mixed
     */
    public function unshift(...$values)
    {
        $this->forceCollection();
        $values = array_map([$this, 'unbox'], $values);
        array_unshift($this->data, ...$values);

        return $this;
    }

    /**
     * Add one or more items to the end of the collection.
     * If the underlying data is not a collection, it will be converted to one.
     *
     * @param array $values
     *
     * @return mixed
     */
    public function push(...$values)
    {
        $this->forceCollection();
        $values = array_map([$this, 'unbox'], $values);
        array_push($this->data, ...$values);

        return $this;
    }

    /**
     * Pull the last item off the end of the collection.
     * If the underlying data is not a collection, it will be converted to one.
     *
     * @throws \Rexlabs\ArrayObject\Exceptions\InvalidOffsetException
     *
     * @return mixed
     */
    public function pop()
    {
        $this->forceCollection();
        if (!$this->hasItems()) {
            throw new InvalidOffsetException('Cannot shift this array, no more items');
        }

        return $this->box(array_pop($this->data));
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Rexlabs\ArrayObject\Exceptions\InvalidOffsetException
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new InvalidOffsetException('Invalid offset: '.$offset);
        }
        if ($this->isCollection()) {
            return $this->box($this->data[$offset]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->isCollection() ? isset($this->data[$offset]) : ($offset === 0);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Rexlabs\ArrayObject\Exceptions\InvalidOffsetException
     */
    public function offsetSet($offset, $value)
    {
        if (!$this->isCollection()) {
            $this->forceCollection();
        }
        if ($this->offsetExists($offset) || $offset === \count($this->data)) {
            $this->data[$offset] = $this->unbox($value);
        } else {
            throw new InvalidOffsetException('Invalid offset: '.$offset);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Rexlabs\ArrayObject\Exceptions\InvalidOffsetException
     */
    public function offsetUnset($offset)
    {
        if (!$this->isCollection()) {
            $this->forceCollection();
        }
        if (!isset($this->data[$offset])) {
            throw new InvalidOffsetException('Cannot unset value of collection at index '.$offset);
        }
        unset($this->data[$offset]);

        // Fix indexes
        $this->data = array_values($this->data);
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator(array_map(function ($val) {
            return $this->box($val);
        }, $this->isCollection() ? $this->data : [$this->data]));
    }

    /**
     * @param int $options
     *
     * @throws \Rexlabs\ArrayObject\Exceptions\JsonEncodeException
     *
     * @return string|mixed
     */
    public function toJson(int $options = 0)
    {
        $json = json_encode($this->toArray(), $options);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new JsonEncodeException(json_last_error_msg());
        }

        return $json;
    }

    /**
     * Return the original array representation of the array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Return a JSON encoded representation of the internal array.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }
}
