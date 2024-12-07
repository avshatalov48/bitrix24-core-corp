<?php

namespace Bitrix\HumanResources\Util;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Web\Json;

class CacheManager implements \Bitrix\HumanResources\Contract\Util\CacheManager
{
	private Cache $bitrixCache;
	private const CACHE_DIR = 'cache/humanresources';
	private string $cacheSubDir = '';
	private int $ttl = 3600;

	public function __construct()
	{
		$this->bitrixCache = Cache::createInstance();
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getData(string $key): mixed
	{
		$this->bitrixCache->forceRewriting(false);
		if (
			$this->bitrixCache->initCache(
				$this->ttl,
				$key,
				$this->cacheSubDir,
				self::CACHE_DIR,
			)
		)
		{
			try
			{
				return Json::decode($this->bitrixCache->getVars());
			}
			catch (ArgumentException $e)
			{
				return null;
			}
		}

		return null;
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function setData(string $key, mixed $data): static
	{
		$this->bitrixCache->forceRewriting(true);
		if (
			$this->bitrixCache->startDataCache(
				$this->ttl,
				$key,
				$this->cacheSubDir,
				baseDir: self::CACHE_DIR,
			)
		)
		{
			$this->bitrixCache->endDataCache(Json::encode($data));
		}

		return $this;
	}

	/**
	 * @param string $key
	 *
	 * @return $this
	 */
	public function clean(string $key): static
	{
		$this->bitrixCache->clean($key, $this->cacheSubDir, self::CACHE_DIR);

		return $this;
	}

	/**
	 * @param int $ttl
	 *
	 * @return $this
	 */
	public function setTtl(int $ttl): static
	{
		$this->ttl = $ttl;

		return $this;
	}

	/**
	 * @param string $dir
	 *
	 * @return $this
	 */
	public function setDir(string $dir): static
	{
		$this->cacheSubDir = str_starts_with($dir, "/") ? $dir : "/$dir";

		return $this;
	}

	/**
	 * @return $this
	 */
	public function cleanDir(): static
	{
		$this->bitrixCache->cleanDir($this->cacheSubDir, self::CACHE_DIR);

		return $this;
	}
}