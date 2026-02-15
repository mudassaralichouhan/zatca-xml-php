<?php

namespace App\Contracts;

interface StorageInterface
{
    /**
     * Get an item from storage
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key);

    /**
     * Store an item in storage
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl Time to live in seconds
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null): bool;

    /**
     * Delete an item from storage
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Check if an item exists in storage
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Increment a counter
     *
     * @param string $key
     * @param int $value
     * @param int|null $ttl
     * @return int|bool
     */
    public function increment(string $key, int $value = 1, ?int $ttl = null);

    /**
     * Clear all items from storage
     *
     * @return bool
     */
    public function clear(): bool;

    /**
     * Get multiple items
     *
     * @param array $keys
     * @return array
     */
    public function getMultiple(array $keys): array;

    /**
     * Set multiple items
     *
     * @param array $items
     * @param int|null $ttl
     * @return bool
     */
    public function setMultiple(array $items, ?int $ttl = null): bool;

    /**
     * Delete multiple items
     *
     * @param array $keys
     * @return bool
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * Check if the storage is available
     *
     * @return bool
     */
    public function isAvailable(): bool;
}
