<?php

namespace Bitrix\HumanResources\Util;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Web\Json;

class CacheManager implements \Bitrix\HumanResources\Contract\Util\CacheManager
{
	private Cache $bitrixCache;
	private const CACHE_DIR = 'cache/humanresources';
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
	public function getData(string $key, string $cacheSubDir = ''): mixed
	{
		$this->bitrixCache->forceRewriting(false);
		if (
			$this->bitrixCache->initCache(
				$this->ttl,
				$key,
				$cacheSubDir,
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
	 * @param string $key
	 * @param mixed $data
	 * @param string $cacheSubDir *
	 *
* @throws \Bitrix\Main\ArgumentException
	 */
	public function setData(string $key, mixed $data, string $cacheSubDir = ''): static
	{
		$this->bitrixCache->forceRewriting(true);
		if (
			$this->bitrixCache->startDataCache(
				$this->ttl,
				$key,
				$cacheSubDir,
				baseDir: self::CACHE_DIR,
			)
		)
		{
			$this->bitrixCache->endDataCache(Json::encode($data));
		}

		return $this;
	}

	/**
	 *
	 * @param string $key
	 * @param string $cacheSubDir *
	 *
* @return $this
	 */
	public function clean(string $key, string $cacheSubDir = ''): static
	{
		$this->bitrixCache->clean(
			$key,
			$cacheSubDir,
			self::CACHE_DIR
		);

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
	 * @return $this
	 */
	public function cleanDir(string $cacheSubDir): static
	{
		if (empty($cacheSubDir))
		{
			$this->bitrixCache->cleanDir($cacheSubDir, self::CACHE_DIR);

			return $this;
		}

		$this->bitrixCache->cleanDir($cacheSubDir, self::CACHE_DIR);

		return $this;
	}
}