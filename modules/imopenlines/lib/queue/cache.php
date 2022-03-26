<?php
namespace Bitrix\ImOpenLines\Queue;

use Bitrix\Main\Application;

class Cache
{
	protected const CACHE_TIME = 86400;

	protected $userId = 0;
	protected $lineId = 0;

	protected $cache;
	protected $taggedCache;
	protected $isError = false;

	/**
	 * @param string $userId
	 * @param string $lineId
	 * @return string
	 */
	public static function getOperatorCacheTag(string $userId, string $lineId): string
	{
		return 'QUEUE_USER_DATA_' . $userId . '_' . $lineId;
	}

	/**
	 * @param string $userId
	 * @return string
	 */
	public static function getUserCountLinesCacheTag(string $userId): string
	{
		return 'QUEUE_USER_COUNT_LINES_' . $userId;
	}

	/**
	 * @param string $userId
	 * @return string
	 */
	public static function getUserIsOperatorCacheTag(string $userId): string
	{
		return 'QUEUE_USER_IS_OPERATOR_' . $userId;
	}

	public function __construct()
	{
		$application = Application::getInstance();
		if($application instanceof Application)
		{
			$this->cache = $application->getCache();
			$this->taggedCache = $application->getTaggedCache();
		}
		else
		{
			$this->isError = true;
		}
	}

	/**
	 * @param string $userId
	 */
	public function setUserId(string $userId): void
	{
		$this->userId = $userId;
	}

	/**
	 * @param string $lineId
	 */
	public function setLineId(string $lineId): void
	{
		$this->lineId = $lineId;
	}

	/**
	 * @return string
	 */
	public function getCacheIdQueueOperatorData(): string
	{
		return md5(serialize([$this->lineId, $this->userId]));
	}

	/**
	 * @return string
	 */
	public function getCacheDirQueueOperatorData(): string
	{
		return '/imopenlines/queue/';
	}

	/**
	 * @return string
	 */
	public function getCacheIdCountLinesOperator(): string
	{
		return $this->userId;
	}

	/**
	 * @return string
	 */
	public function getCacheIdIsOperator(): string
	{
		return $this->userId;
	}

	/**
	 * @return string
	 */
	public function getCacheDirCountLinesOperator(): string
	{
		return '/imopenlines/queue/count/';
	}

	/**
	 * @return string
	 */
	public function getCacheDirIsOperator(): string
	{
		return '/imopenlines/queue/operators/';
	}

	/**
	 * @return bool
	 */
	public function validQueueOperatorData(): bool
	{
		$result = false;

		if (
			$this->isError === false
			&& !empty($this->userId)
			&& !empty($this->lineId)
		)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function validCountLinesOperator(): bool
	{
		$result = false;

		if (
			$this->isError === false
			&& !empty($this->userId)
		)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function validIsOperator(): bool
	{
		$result = false;

		if (
			$this->isError === false
			&& !empty($this->userId)
		)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function initCacheQueueOperatorData(): bool
	{
		$result = false;
		if ($this->validQueueOperatorData())
		{
			$result = $this->cache->initCache(
				self::CACHE_TIME,
				$this->getCacheIdQueueOperatorData(),
				$this->getCacheDirQueueOperatorData()
			);
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function initCacheCountLinesOperator(): bool
	{
		$result = false;
		if ($this->validCountLinesOperator())
		{
			$result = $this->cache->initCache(
				self::CACHE_TIME,
				$this->getCacheIdCountLinesOperator(),
				$this->getCacheDirCountLinesOperator()
			);
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function initCacheIsOperator(): bool
	{
		$result = false;
		if ($this->validIsOperator())
		{
			$result = $this->cache->initCache(
				self::CACHE_TIME,
				$this->getCacheIdIsOperator(),
				$this->getCacheDirIsOperator()
			);
		}

		return $result;
	}

	/**
	 * @return mixed
	 */
	public function getVarsQueueOperatorData()
	{
		$result = false;
		if ($this->validQueueOperatorData())
		{
			$result = $this->cache->getVars();
		}

		return $result;
	}

	/**
	 * @return mixed
	 */
	public function getVarsCountLinesOperator()
	{
		$result = false;
		if ($this->validCountLinesOperator())
		{
			$result = $this->cache->getVars();
		}

		return $result;
	}


	/**
	 * @return mixed
	 */
	public function getVarsIsOperator()
	{
		$result = false;
		if ($this->validIsOperator())
		{
			$result = $this->cache->getVars();

			if ($result !== true)
			{
				$countLines = $this->getVarsCountLinesOperator();

				if ($countLines > 0)
				{
					$result = true;
				}
			}
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function startCacheQueueOperatorData(): bool
	{
		$result = false;
		if ($this->validQueueOperatorData())
		{
			$result = true;

			$this->cache->startDataCache();
			$this->taggedCache->startTagCache($this->getCacheDirQueueOperatorData());
			$this->taggedCache->registerTag(self::getOperatorCacheTag($this->userId, $this->lineId));
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function startCacheCountLinesOperator(): bool
	{
		$result = false;
		if ($this->validCountLinesOperator())
		{
			$result = true;

			$this->cache->startDataCache();
			$this->taggedCache->startTagCache($this->getCacheDirCountLinesOperator());
			$this->taggedCache->registerTag(self::getUserCountLinesCacheTag($this->userId));
		}

		return $result;
	}


	/**
	 * @return bool
	 */
	public function startCacheIsOperator(): bool
	{
		$result = false;
		if ($this->validIsOperator())
		{
			$result = true;

			$this->cache->startDataCache();
			$this->taggedCache->startTagCache($this->getCacheDirIsOperator());
			$this->taggedCache->registerTag(self::getUserIsOperatorCacheTag($this->userId));
		}

		return $result;
	}

	/**
	 * @param mixed $data
	 * @return bool
	 */
	public function endCacheQueueOperatorData($data = []): bool
	{
		$result = false;
		if ($this->validQueueOperatorData())
		{
			$result = true;

			$this->taggedCache->endTagCache();
			$this->cache->endDataCache($data);
		}

		return $result;
	}

	/**
	 * @param $data
	 * @return bool
	 */
	public function endCacheCountLinesOperator($data): bool
	{
		$result = false;
		if ($this->validCountLinesOperator())
		{
			$result = true;

			$this->taggedCache->endTagCache();
			$this->cache->endDataCache($data);
		}

		return $result;
	}

	/**
	 * @param bool $data
	 * @return bool
	 */
	public function endCacheIsOperator(bool $data): bool
	{
		$result = false;
		if ($this->validIsOperator())
		{
			$result = true;

			$this->taggedCache->endTagCache();
			$this->cache->endDataCache($data);
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function delete(): bool
	{
		$result = false;

		if($this->isError === false)
		{
			$result = true;

			if(
				!empty($this->userId)
				&& !empty($this->lineId)
			)
			{
				$this->taggedCache->clearByTag(self::getOperatorCacheTag($this->userId, $this->lineId));
			}

			if (!empty($this->userId))
			{
				$this->taggedCache->clearByTag(self::getUserCountLinesCacheTag($this->userId));
				$this->taggedCache->clearByTag(self::getUserIsOperatorCacheTag($this->userId));
			}
		}

		return $result;
	}
}