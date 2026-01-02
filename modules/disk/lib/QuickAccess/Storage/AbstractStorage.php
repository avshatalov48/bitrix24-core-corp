<?php

declare(strict_types=1);

namespace Bitrix\Disk\QuickAccess\Storage;

use Bitrix\Main\Application;

abstract class AbstractStorage
{
	public const CONNECTION = 'disk.tokens.common';

	protected string $prefix;

	public function __construct(array $options)
	{
		$this->prefix = $options['keyPrefix'] ?? 'BX';
	}

	public function get(string $key, bool $immediateRemove = false): ?string
	{
		$internalKey = $this->getInternalKey($key);
		$value = $this->getRaw($internalKey);
		if ($immediateRemove && $value !== null)
		{
			$this->remove($key);
		}

		return $value;
	}

	abstract protected function getRaw(string $key): ?string;

	final public function set(string $key, string $value, int $ttl = 0): bool
	{
		$cacheKey = $this->getInternalKey($key);
		return $this->setRaw($cacheKey, $value, $ttl);
	}

	abstract protected function setRaw(string $key, string $value, int $ttl = 0): bool;

	final public function remove(string $key): bool
	{
		return $this->removeRaw($this->getInternalKey($key));
	}

	abstract protected function removeRaw(string $key): bool;

	final protected function getPrefix(): string
	{
		return $this->prefix;
	}

	final protected function getInternalKey(string $key): string
	{
		return $this->getPrefix() . $key;
	}

	final protected function createConnection(): mixed
	{
		$connectionPool = Application::getInstance()->getConnectionPool();
		$connection = $connectionPool->getConnection(static::CONNECTION);
		if (!$connection)
		{
			return null;
		}

		$resource = $connection->getResource();
		if (!$this->isExpectedResource($resource))
		{
			return null;
		}

		return $resource;
	}

	abstract protected function isExpectedResource($resource): bool;

	abstract protected function closeConnection(): void;

	final public function __destruct()
	{
		$this->closeConnection();
	}
}
