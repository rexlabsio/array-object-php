<?php
namespace RexSoftware\ArrayObject;


interface ArrayObjectInterface
{
    /**
     * @param array $array
     * @return static
     */
    public static function fromArray(array $array);

    /**
     * @param string|mixed $json
     * @param int          $options
     * @return static
     */
    public static function fromJson($json, int $options = 0);

    /**
     * Determine if this object is a collection or a single node
     * @return bool
     */
    public function isCollection(): bool;

    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value);

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool;

    /**
     * @return ArrayObjectInterface|null
     */
    public function parent();
    /**
     * @param callable $callback
     * @return $this
     */
    public function each(callable $callback);

    /**
     * @param array $conditions
     * @return ArrayObjectInterface|null
     */
    public function filter(array $conditions);

    /**
     * @param string $key
     * @return ArrayObjectInterface
     */
    public function pluck($key): ArrayObjectInterface;

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

}