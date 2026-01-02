<?php
declare(strict_types=1);
namespace Bitrix\Disk\QuickAccess\Storage;

final class StorageFactory
{
	private const NULL_STORAGE = '';

	/**
	 * Create a storage instance based on configuration.
	 * 
	 * @param array $config Storage configuration
	 * @return ScopeStorage Storage instance
	 * @throws \InvalidArgumentException If storage type is invalid
	 */
	public static function create(array $config): ScopeStorage
	{
		return match ($config['type'] ?? '')
		{
			self::NULL_STORAGE => new NullStorage(),
			'redis' => new RedisScopeStorage($config),
			'memcache' => new MemcacheScopeStorage($config),
			default => throw new \InvalidArgumentException('Invalid storage type specified.'),
		};
	}
}