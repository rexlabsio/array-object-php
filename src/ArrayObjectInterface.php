<?php

namespace Rexlabs\ArrayObject;

interface ArrayObjectInterface
{
    /**
     * @param array $array
     *
     * @return static
     */
    public static function fromArray(array $array);

    /**
     * @param string|mixed $json
     * @param int          $options
     *
     * @return static
     */
    public static function fromJson($json, int $options = 0);

    /**
     * Determine if this object is a collection or a single node.
     *
     * @return bool
     */
    public function isCollection(): bool;

    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($key, $value);

    /**
     * @param $key
     *
     * @return bool
     */
    public function has($key): bool;

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function each(callable $callback);

    /**
     * @param mixed $filter
     *
     * @return ArrayObjectInterface|null
     */
    public function filter($filter);

    /**
     * @param string $key
     *
     * @return ArrayObjectInterface
     */
    public function pluck($key): self;

    /**
     * @return int
     */
    public function count();

    /**
     * @return ArrayObject|null
     */
    public function first();

    /**
     * @return ArrayObject|null
     */
    public function last();

    /**
     * Pull the first item off the collection.
     * If the underlying data is not a collection, it will be converted to one.
     *
     * @return mixed
     */
    public function shift();

    /**
     * Add one or more items at the start of the collection.
     * If the underlying data is not a collection, it will be converted to one.
     *
     * @param array $values
     *
     * @return $this
     */
    public function unshift(...$values);

    /**
     * Add one or more items to the end of the collection.
     * If the underlying data is not a collection, it will be converted to one.
     *
     * @param array $values
     *
     * @return $this
     */
    public function push(...$values);

    /**
     * Pull the last item off the end of the collection.
     * If the underlying data is not a collection, it will be converted to one.
     *
     * @return mixed
     */
    public function pop();
}
