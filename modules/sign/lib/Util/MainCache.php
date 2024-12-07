<?php

namespace Bitrix\Sign\Util;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Web\Json;
use Bitrix\Sign\Contract;

class MainCache implements Contract\Util\Cache
{
	private Cache $bitrixCache;
	private const CACHE_DIR = 'sign';
	private int $ttl = 3600;

	public function __construct()
	{
		$this->bitrixCache = Cache::createInstance();
	}

	public function get(string $key, mixed $default = null): mixed
	{
		$this->bitrixCache->forceRewriting(false);
		if (
			$this->bitrixCache->initCache(
				$this->ttl,
				$key,
				self::CACHE_DIR
			)
		)
		{
			try
			{
				return Json::decode($this->bitrixCache->getVars());
			}
			catch (ArgumentException $e)
			{
				return $default;
			}
		}

		return $default;
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function set(string $key, mixed $data, ?int $ttl = null): static
	{
		$this->bitrixCache->forceRewriting(true);
		if (
			$this->bitrixCache->startDataCache(
				$ttl ?? $this->ttl,
				$key,
				self::CACHE_DIR
			)
		)
		{
			$this->bitrixCache->endDataCache(Json::encode($data));
		}

		return $this;
	}

	public function delete(string $key): static
	{
		$this->bitrixCache->clean($key, self::CACHE_DIR);

		return $this;
	}

	public function setTtl(int $ttl): static
	{
		$this->ttl = $ttl;

		return $this;
	}
}