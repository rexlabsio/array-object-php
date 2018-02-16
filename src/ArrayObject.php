<?php
namespace RexSoftware\ArrayObject;

use ArrayIterator;
use BkvFoundry\UtilityBelt\ArrayUtility;
use BkvFoundry\UtilityBelt\CollectionUtility;
use RexSoftware\ArrayObject\Exceptions\InvalidOffsetException;
use RexSoftware\ArrayObject\Exceptions\InvalidPropertyException;
use RexSoftware\ArrayObject\Exceptions\JsonDecodeException;
use RexSoftware\ArrayObject\Exceptions\JsonEncodeException;

class ArrayObject implements ArrayObjectInterface, \ArrayAccess, \Countable, \IteratorAggregate
{
    /** @var ArrayObjectInterface */
    protected $parent;

    /** @var array */
    protected $data;

    /** @var bool */
    protected $isCollection;

    public function __construct(array $data, ArrayObjectInterface $parent = null)
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
     * @return static
     * @throws \RexSoftware\ArrayObject\Exceptions\JsonDecodeException
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
     * Fire callback for each member of the array
     * @param callable $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        if ($this->isCollection()) {
            foreach ($this->data as $val) {
                $callback($this->transformValue($val));
            }
        } else {
            $callback($this->transformValue($this->data));
        }

        return $this;
    }

    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    /**
     * Return a plain value or coerce into a Node or Collection object
     * @param $val
     * @return ArrayObjectInterface|mixed
     */
    protected function transformValue($val)
    {
        return \is_array($val) ? static::fromArray($val) : $val;
    }

    /**
     * @param array $array
     * @return static
     */
    public static function fromArray(array $array)
    {
        return new static($array);
    }

    /**
     * Pluck a value by key from each result and return an ArrayObject
     * @param $key
     * @return ArrayObjectInterface
     */
    public function pluck($key): ArrayObjectInterface
    {
        return static::fromArray($this->pluckArray($key));
    }

    /**
     * Pluck a value by key from each result and return an array
     * @param $key
     * @return array
     */
    public function pluckArray($key): array
    {
        if ($this->isCollection()) {
            return array_map(function ($val) {
                return $this->transformValue($val);
            }, CollectionUtility::pluck($this->data, $key));
        }

        // Non-collection
        return CollectionUtility::pluck($this->data, $key);
    }

    /**
     * Return a new collection by filtering node data within the current collection
     * @param array $conditions
     * @return ArrayObjectInterface
     */
    public function filter(array $conditions): ArrayObjectInterface
    {
        return new static(CollectionUtility::filterWhere($this->isCollection() ? $this->data : [$this->data],
            $conditions));
    }

    /**
     * Returns the first element in the collection
     * @return mixed
     */
    public function first()
    {
        if (!$this->isCollection()) {
            return $this;
        }

        return isset($this->data[0]) ? $this->transformValue($this->data[0]) : null;
    }

    /**
     * Returns the last element in the collection
     * @return ArrayObjectInterface|null
     */
    public function last()
    {
        if (!$this->isCollection()) {
            return $this;
        }

        return isset($this->data[0]) ? $this->transformValue(ArrayUtility::last($this->data)) : null;
    }

    /**
     * Return the total number of elements
     * @return int
     */
    public function count()
    {
        return $this->isCollection() ? \count($this->data) : 1;
    }

    /**
     * @param string $key
     * @return mixed
     * @throws \RexSoftware\ArrayObject\Exceptions\InvalidPropertyException
     */
    public function getOrFail($key)
    {
        $result = ArrayUtility::dotRead($this->data, $this->getNormalizedKey($key));
        if ($result === null) {
            throw new InvalidPropertyException("Missing property '$key'");
        }

        return $this->transformValue($result);
    }

    /**
     * @return ArrayObjectInterface|null
     */
    public function parent()
    {
        return $this->parent;
    }

    /**
     * @inheritdoc
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $result = ArrayUtility::dotRead($this->data, $this->getNormalizedKey($key));
        if ($result === null) {
            return $default;
        }

        return $this->transformValue($result);
    }

    /**
     * @param string|mixed $key
     * @param mixed        $value
     * @return $this
     */
    public function set($key, $value, $onlyIfExists = false)
    {
        if ($onlyIfExists && !$this->has($key)) {
            return $this;
        }

        // TODO: Need ArrayUtil::dotWrite()
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Determine if the node contains a property
     * @param string $key
     * @return bool
     */
    public function has($key): bool
    {
        return ArrayUtility::dotRead($this->data, $this->getNormalizedKey($key)) !== null;
    }

    /**
     * Forces key to be prefixed with an offset
     * @param $key
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
     * Pull the first item off the collection.
     * If the underlying data is not a collection, it will be converted to one.
     * @return mixed
     */
    public function shift()
    {
        $this->forceCollection();

        return array_shift($this->data);
    }

    /**
     * Forces the underluing data-structure to become a collection
     */
    protected function forceCollection()
    {
        if (!$this->isCollection()) {
            $this->isCollection = true;
            $this->data = [$this->data];
        }
    }

    /**
     * Add one or more items at the start of the collection.
     * If the underlying data is not a collection, it will be converted to one.
     * @param array $values
     * @return mixed
     */
    public function unshift(...$values)
    {
        $this->forceCollection();
        array_unshift($this->data, ...$values);

        return $this;
    }

    /**
     * Add one or more items to the end of the collection.
     * If the underlying data is not a collection, it will be converted to one.
     * @param array $values
     * @return mixed
     */
    public function push(...$values)
    {
        $this->forceCollection();
        array_push($this->data, ...$values);

        return $this;
    }

    /**
     * Pull the last item off the end of the collection.
     * If the underlying data is not a collection, it will be converted to one.
     * @return mixed
     */
    public function pop()
    {
        $this->forceCollection();

        return array_pop($this->data);
    }

    /**
     * @inheritdoc
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        $val = null;
        if ($this->offsetExists($offset)) {
            if ($this->isCollection()) {
                $val = $this->transformValue($this->data[$offset]);
            } else {
                $val = $this;
            }
        }

        return $val;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $this->isCollection() ? isset($this->data[$offset]) : ($offset === 0);
    }

    /**
     * @inheritdoc
     * @throws \RexSoftware\ArrayObject\Exceptions\InvalidOffsetException
     */
    public function offsetSet($offset, $value)
    {
        if (!$this->isCollection()) {
            throw new InvalidOffsetException('Cannot set a value by offset on a non-collection');
        }
        if ($this->offsetExists($offset)) {
            $this->data[$offset] = $value;
        }
    }

    /**
     * @inheritdoc
     * @throws \RexSoftware\ArrayObject\Exceptions\InvalidOffsetException
     */
    public function offsetUnset($offset)
    {
        if (!$this->isCollection()) {
            throw new InvalidOffsetException('Cannot set a value by offset on a node that is node a collection');
        }
        unset($this->data[$offset]);
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator(array_map(function ($val) {
            return $this->transformValue($val);
        }, $this->isCollection() ? $this->data : [$this->data]));
    }

    /**
     * @param int $options
     * @return string|mixed
     * @throws \RexSoftware\ArrayObject\Exceptions\JsonEncodeException
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
     * Return the original array representation of the array
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Return a JSON encoded representation of the internal array
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }

    /**
     * Convert an array to either a Node or a Collection object
     * @param array $array
     * @return ArrayObjectInterface
     */
    protected function toArrayObject(array $array): ArrayObjectInterface
    {
        return new static($array);
    }
}