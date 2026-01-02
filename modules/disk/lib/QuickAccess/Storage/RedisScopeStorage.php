<?php

declare(strict_types=1);

namespace Bitrix\Disk\QuickAccess\Storage;

use Bitrix\Main\Application;
use Bitrix\Main\Data\RedisConnection;

/**
 * Class RedisScopeStorage
 * Redis-specific implementation of scope-based token storage
 */
final class RedisScopeStorage extends ScopeStorage
{
	public const CONNECTION = 'disk.tokens.redis';

	private \Redis|\RedisCluster $connection;
	private array $expireAtSet = [];

	public function __construct(array $options)
	{
		parent::__construct($options);

		$connectionPool = Application::getInstance()->getConnectionPool();
		$connectionPool->setConnectionParameters(self::CONNECTION, [
			'className' => RedisConnection::class,
			'host' => $options['host'] ?? '127.0.0.1',
			'port' => (int)($options['port'] ?? 6379),
			'servers' => $options['servers'] ?? [],
			'serializer' => $options['serializer'] ?? null,
			'failover' => $options['failover'] ?? null,
			'timeout' => $options['timeout'] ?? null,
			'readTimeout' => $options['readTimeout'] ?? null,
			'persistent' => $options['persistent'] ?? null,
		]);
		$this->connection = $this->createConnection();

		if (isset($options['compression']) || defined('Redis::COMPRESSION_LZ4'))
		{
			$this->connection->setOption(
				\Redis::OPT_COMPRESSION,
				$options['compression'] ?? \Redis::COMPRESSION_LZ4
			);
			$this->connection->setOption(
				\Redis::OPT_COMPRESSION_LEVEL,
				$options['compression_level'] ?? \Redis::COMPRESSION_ZSTD_MAX
			);
		}

		if (isset($options['serializer']) || defined('Redis::SERIALIZER_IGBINARY'))
		{
			$this->connection->setOption(
				\Redis::OPT_SERIALIZER,
				$options['serializer'] ?? \Redis::SERIALIZER_IGBINARY
			);
		}
	}

	/**
	 * Optimized implementation for Redis to use atomic operations for scope management using sorted sets
	 *
	 * @param string $userToken The user token
	 * @param string $scope The scope to add
	 * @param int $ttl TTL in seconds
	 * @return bool True on success, false on failure
	 */
	public function addScope(string $userToken, string $scope, int $ttl = self::DEFAULT_SCOPE_TTL): bool
	{
		$key = $this->getInternalKey($this->getUserScopesKey($userToken));
		$expirationTime = time() + $ttl;

		try
		{
			$this->connection->zAdd($key, $expirationTime, $scope);
			
			if (!isset($this->expireAtSet[$key]))
			{
				$this->connection->expireAt($key, $expirationTime);
				$this->expireAtSet[$key] = true;
			}

			return true;
		}
		catch (\RedisException|\RedisClusterException $e)
		{
			return parent::addScope($userToken, $scope, $ttl);
		}
	}

	/**
	 * Check if a user has a particular scope
	 * Overrides parent method to use Redis sorted sets
	 *
	 * @param string $userToken The user token
	 * @param string $scope The scope to check
	 * @return bool True if the user has the scope, false otherwise
	 */
	public function hasScope(string $userToken, string $scope): bool
	{
		$key = $this->getInternalKey($this->getUserScopesKey($userToken));
		$currentTime = time();

		try
		{
			// Get the expiration time for this scope
			$expirationTime = $this->connection->zScore($key, $scope);

			if (!$expirationTime)
			{
				return false;
			}

			// Check if the scope has expired
			if ($expirationTime <= $currentTime)
			{
				// Remove expired scope
				$this->connection->zRem($key, $scope);

				return false;
			}
		}
		catch (\RedisException|\RedisClusterException)
		{
			return false;
		}

		return true;
	}

	/**
	 * Get all scopes for a user
	 * Overrides parent method to use Redis sorted sets
	 *
	 * @param string $userToken The user token
	 * @return array Array of scopes with their expiration times
	 */
	public function getUserScopes(string $userToken): array
	{
		$key = $this->getInternalKey($this->getUserScopesKey($userToken));
		$currentTime = time();

		try
		{
			$scopes = $this->connection->zRangeByScore($key, $currentTime + 1, '+inf', ['withscores' => true]);

			// Remove expired scopes
			$this->connection->zRemRangeByScore($key, '-inf', (string)$currentTime);
		}
		catch (\RedisException|\RedisClusterException)
		{
			return [];
		}

		return $scopes ?: [];
	}

	/**
	 * Optimized implementation for Redis to efficiently clean up expired scopes
	 * using the ZREMRANGEBYSCORE command, and limit number of scopes to MAX_USER_SCOPES
	 *
	 * @param string $userToken The user token
	 * @return bool True on success, false on failure
	 */
	public function cleanupExpiredScopes(string $userToken): bool
	{
		$key = $this->getInternalKey($this->getUserScopesKey($userToken));
		$currentTime = time();

		try
		{
			$this->connection->zRemRangeByScore($key, '-inf', (string)$currentTime);

			$currentCount = $this->connection->zCard($key);
			if ($currentCount > self::MAX_USER_SCOPES)
			{
				$excess = $currentCount - self::MAX_USER_SCOPES;
				$this->connection->zRemRangeByRank($key, 0, $excess - 1);
			}

			return true;
		}
		catch (\RedisException|\RedisClusterException)
		{
			return false;
		}
	}

	/**
	 * Check if the resource is of the expected type
	 *
	 * @param mixed $resource The resource to check
	 * @return bool True if the resource is valid, false otherwise
	 */
	protected function isExpectedResource($resource): bool
	{
		return ($resource instanceof \Redis) || ($resource instanceof \RedisCluster);
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
		return $this->connection->setex($key, $ttl ?: 1, $value);
	}

	/**
	 * Remove a key from storage
	 *
	 * @param string $key The key to remove
	 * @return bool True on success, false on failure
	 */
	protected function removeRaw(string $key): bool
	{
		return $this->connection->del($key);
	}

	/**
	 * Close the connection
	 */
	protected function closeConnection(): void
	{
		$this->connection->close();
	}
}
