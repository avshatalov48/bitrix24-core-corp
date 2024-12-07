<?php

namespace Bitrix\AI\Facade;

use Bitrix\Main\Application;
use Bitrix\Main\Data;

class Cache
{
	private const TTL = 86400*365;

	private Data\Cache $cacheInstance;
	private Data\TaggedCache $cacheTaggedInstance;

	private string $cacheKey;
	private string $cachePath;
	private string $cacheId;

	/**
	 * Cache constructor.
	 *
	 * @param string $key Cache key.
	 * @param string|array $id Optional cache id.
	 */
	public function __construct(string $key, string|array $id = '')
	{
		$this->cacheInstance = Data\Cache::createInstance();
		$this->cacheTaggedInstance = Application::getInstance()->getTaggedCache();
		$this->cacheKey = $key;
		$this->cacheId = $id;
		$this->cachePath = "ai/$key";

		if (is_array($this->cacheId))
		{
			$this->cacheId = serialize($this->cacheId);
		}
	}

	/**
	 * Returns data from cache if exists.
	 *
	 * @return mixed
	 */
	public function getExists(): mixed
	{
		if ($this->cacheInstance->initCache(self::TTL, $this->cacheId, $this->cachePath))
		{
			return $this->cacheInstance->getVars();
		}

		return null;
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

		$this->cacheTaggedInstance->startTagCache($this->cachePath);
		$this->cacheTaggedInstance->registerTag($this->cacheKey);

		$this->cacheInstance->endDataCache($data);
		$this->cacheTaggedInstance->endTagCache();
	}

	/**
	 * Deletes cache by exists key.
	 *
	 * @param string $key Cache key.
	 * @return void
	 */
	public static function remove(string $key): void
	{
		if (defined('BX_COMP_MANAGED_CACHE'))
		{
			Application::getInstance()->getTaggedCache()->clearByTag($key);
		}
	}
}
