<?php

namespace Bitrix\AI\Cache;

use Bitrix\Main\Application;
use Bitrix\Main\Data;

final class EngineResultCache
{
	private Data\Cache $cacheInstance;

	private string $cachePath;

	/**
	 * Cache engine constructor.
	 *
	 * @param string $key
	 * @param int $ttl
	 */
	public function __construct(string $key, int $ttl = 3600)
	{
		$this->cacheInstance = Data\Cache::createInstance();
		$this->cachePath = 'ai/engine';
		$this->cacheInstance->initCache($ttl, $key, $this->cachePath);
	}

	/**
	 * Returns data from cache if exists.
	 *
	 * @return mixed
	 */
	public function getExists(): mixed
	{
		return $this->cacheInstance->getVars();
	}

	/**
	 * Stores data to cache by instance key.
	 *
	 * @param mixed $data
	 * @return void
	 */
	public function store(mixed $data): void
	{
		$this->cacheInstance->startDataCache();
		$this->cacheInstance->endDataCache($data);
	}

	/**
	 * Deletes cache by exists key.
	 *
	 * @param string $key Cache key.
	 * @return void
	 */
	public function remove(string $key): void
	{
		Application::getInstance()->getCache()->clean($key,$this->cachePath);
	}
}
