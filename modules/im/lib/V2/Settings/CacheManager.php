<?php

namespace Bitrix\Im\V2\Settings;

use Bitrix\Main\Data\Cache;

class CacheManager
{
	public const GENERAL_PRESET = 'generalPreset';
	public const NOTIFY_PRESET = 'notifyPreset';

	private const CACHE_TTL = 31536000; //one year
	private const BASE_CACHE_DIR = '/im/settings/';
	private const USER_MODE = 'user';
	private const PRESET_MODE = 'preset';

	private static array $staticCache = [];


	private string $mode;
	private int $entityId;
	private Cache $cache;

	public static function getUserCache(?int $userId = null): self
	{
		return new static(self::USER_MODE, $userId);
	}

	public static function getPresetCache(?int $presetId = null): self
	{
		return new static(self::PRESET_MODE, $presetId);
	}

	/**
	 * @param string $mode
	 * @param int|null $entityId
	 */
	private function __construct(string $mode, ?int $entityId = null)
	{
		$this->mode = $mode;

		if ($entityId !== null)
		{
			$this->setEntityId($entityId);
		}

		$this->cache = Cache::createInstance();
	}


	public function setEntityId(int $entityId): self
	{
		$this->entityId = $entityId;

		return $this;
	}

	/**
	 * @return array
	 *
	 * if UserCache is selected, it will return an array in the form:
	 * array{notifyPreset: int, generalPreset: int}
	 *
	 * if PresetCache is selected, it will return an array in the form:
	 * array{id:int, name: ?string, sort: int, userId: ?int, general: array, notify: array}
	 *
	 */
	public function getValue(): array
	{
		$result = [];
		$cacheName = $this->getCacheName();
		if (isset(static::$staticCache[$cacheName]))
		{
			return static::$staticCache[$cacheName];
		}
		if ($this->cache->initCache(self::CACHE_TTL, $cacheName, $this->getCacheDir()))
		{
			$result = $this->cache->getVars();
			static::$staticCache[$cacheName] = $result;
		}

		return $result;
	}

	public function setValue(array $value): self
	{
		$cacheName = $this->getCacheName();

		$this->cache->clean($cacheName, $this->getCacheDir());

		$this->cache->initCache(self::CACHE_TTL, $cacheName, $this->getCacheDir());
		$this->cache->startDataCache();
		$this->cache->endDataCache($value);
		static::$staticCache[$cacheName] = $value;

		return $this;
	}

	public function clearCache(): self
	{
		$cacheName = $this->getCacheName();
		$this->cache->clean($cacheName, $this->getCacheDir());
		unset(static::$staticCache[$cacheName]);

		return $this;
	}

	public function clearAll(): self
	{
		$this->cache->cleanDir($this->getCacheDir());
		static::$staticCache = [];

		return $this;
	}

	private function getCacheName(): string
	{
		return $this->mode . '_' . $this->entityId . '_v2';
	}

	private function getCacheDir(): string
	{
		return self::BASE_CACHE_DIR . $this->mode . '/';
	}

}