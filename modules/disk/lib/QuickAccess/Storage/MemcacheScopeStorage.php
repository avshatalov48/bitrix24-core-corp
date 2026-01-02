<?php

declare(strict_types=1);

namespace Bitrix\Disk\QuickAccess\Storage;

use Bitrix\Main\Application;
use Bitrix\Main\Data\MemcacheConnection;

/**
 * Class MemcacheScopeStorage
 * Memcache-specific implementation of scope-based token storage
 */
final class MemcacheScopeStorage extends ScopeStorage
{
	public const CONNECTION = 'disk.tokens.memcache';

	private \Memcache $connection;

	public function __construct(array $options)
	{
		parent::__construct($options);

		$connectionPool = Application::getInstance()->getConnectionPool();
		$connectionPool->setConnectionParameters(self::CONNECTION, [
			'className' => MemcacheConnection::class,
			'host' => $options['host'] ?? '127.0.0.1',
			'port' => (int)($options['port'] ?? 11211),
			'connectionTimeout' => $options['connectionTimeout'] ?? 1,
			'servers' => $options['servers'] ?? [],
		]);
		$this->connection = $this->createConnection();
	}

	/**
	 * Check if the resource is of the expected type
	 *
	 * @param mixed $resource The resource to check
	 * @return bool True if the resource is valid, false otherwise
	 */
	protected function isExpectedResource($resource): bool
	{
		return $resource instanceof \Memcache;
	}

	/**
	 * Get raw value from storage
	 *
	 * @param string $key The key
	 * @return string|null The value or null if not found
	 */
	protected function getRaw(string $key): ?string
	{
		return $this->connection->get($key);
	}

	/**
	 * Set raw value in storage
	 *
	 * @param string $key The key
	 * @param string $value The value
	 * @param int $ttl TTL in seconds
	 * @return bool True on success, false on failure
	 */
	protected function setRaw(string $key, string $value, int $ttl = 0): bool
	{
		return $this->connection->set($key, $value, 0, $ttl ?: 1);
	}

	/**
	 * Remove a key from storage
	 *
	 * @param string $key The key to remove
	 * @return bool True on success, false on failure
	 */
	protected function removeRaw(string $key): bool
	{
		return $this->connection->delete($key);
	}

	/**
	 * Close the connection
	 */
	protected function closeConnection(): void
	{
		$this->connection->close();
	}
}