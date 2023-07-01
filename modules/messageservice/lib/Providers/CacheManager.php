<?php

namespace Bitrix\MessageService\Providers;

use Bitrix\Main\Data\Cache;

class CacheManager
{
	public const CHANNEL_CACHE_ENTITY_ID = 'channel';
	private const BASE_CACHE_DIR = '/messageservice/';
	private const CACHE_TTL = 3600; //one hour
	private Cache $cache;
	private string $providerId;

	/**
	 * @param string $providerId
	 */
	public function __construct(string $providerId)
	{
		$this->cache = Cache::createInstance();
		$this->providerId = $providerId;
	}

	/**
	 * @param string $entityId
	 *
	 * @return array
	 */
	public function getValue(string $entityId): array
	{
		$result = [];
		if ($this->cache->initCache(self::CACHE_TTL, $entityId, $this->getCacheDir()))
		{
			$result = $this->cache->getVars();
		}

		return $result;
	}

	private function getCacheDir(): string
	{
		return self::BASE_CACHE_DIR . $this->providerId;
	}

	public function setValue(string $entityId, array $value): CacheManager
	{
		$cacheName = $entityId;

		$this->cache->clean($cacheName, $this->getCacheDir());

		$this->cache->initCache(self::CACHE_TTL, $cacheName, $this->getCacheDir());
		$this->cache->startDataCache();
		$this->cache->endDataCache($value);

		return $this;
	}

	public function deleteValue(string $entityId): CacheManager
	{
		$cacheName = $entityId;

		$this->cache->clean($cacheName, $this->getCacheDir());

		return $this;
	}


}