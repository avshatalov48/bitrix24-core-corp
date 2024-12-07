<?php

namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\Internal\FlowTaskTable;
use Bitrix\Tasks\Internals\Counter;

/**
 * Loader is responsible for fetching counters and flags from db and keeping them cached
 */
class Loader
{
	private int $userId;
	private int $flagCounted = 0;
	private int $flagCleared = 0;
	private array $rows;

	private const CACHE_PREFIX = 'tasks_scorer_cache_';
	private const CACHE_TTL = 10 * 60;
	private const CACHE_DIR = '/tasks/counterstate';

	private const FLAGS = [
		CounterDictionary::COUNTER_FLAG_CLEARED,
		CounterDictionary::COUNTER_FLAG_COUNTED
	];

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	public function isCounterFlag(string $type): bool
	{
		return in_array($type, self::FLAGS, true);
	}

	public function getRawCounters(): array
	{
		$this->fetchCounters();
		return $this->rows;
	}

	public function getTotalCounters(): int
	{
		return count($this->rows);
	}

	public function isCounted(): bool
	{
		return (bool) $this->flagCounted;
	}

	public function getClearedDate(): int
	{
		return $this->flagCleared;
	}

	public function resetCache(): void
	{
		$cache = Cache::createInstance();
		$cache->clean($this->getCacheTag(), $this->getCacheDir());
	}

	private function getCacheDir(): string
	{
		return self::CACHE_DIR . '/' . substr(md5($this->userId),2,2) . '/';
	}

	private function getCacheTag(): string
	{
		return self::CACHE_PREFIX . $this->userId;
	}

	private function fetchCounters(): void
	{
		$limit = Counter::getGlobalLimit();
		if ($limit === 0)
		{
			$this->rows = $this->getFlags();
			return;
		}

		$query = CounterTable::query()
			->setSelect([
				'VALUE',
				'TASK_ID',
				'GROUP_ID',
				'TYPE'
			])
			->where('USER_ID', $this->userId);

		if (FlowFeature::isOn())
		{
			$query->addSelect('FLOW.FLOW_ID', 'FLOW_ID');
			$query->registerRuntimeField(
				new Reference(
					'FLOW',
					FlowTaskTable::class,
					Join::on('this.TASK_ID', 'ref.TASK_ID'),
					['join_type' => 'LEFT']
				)
			);
		}

		$rowsFlag = null;
		if (!is_null($limit))
		{
			$rowsFlag = $this->getFlags();
			$query->setLimit($limit);
		}

		$this->rows = $query->exec()->fetchAll();
		if (!is_null($rowsFlag))
		{
			$this->rows = array_merge($this->rows, $rowsFlag);
		}
	}

	private function getFlags(): array
	{
		$rows = [];
		$cache = Cache::createInstance();

		if ($cache->initCache(self::CACHE_TTL, $this->getCacheTag(), $this->getCacheDir()))
		{
			$rows = $cache->getVars();
		}
		else
		{
			$rows = CounterTable::query()
				->setSelect([
					'VALUE',
					'TASK_ID',
					'GROUP_ID',
					'TYPE'
				])
				->where('USER_ID', $this->userId)
				->whereIn('TYPE', self::FLAGS)
				->setLimit(2)
				->fetchAll();

			if (!empty($rows))
			{
				$taggedCache = Application::getInstance()->getTaggedCache();
				$taggedCache->StartTagCache($this->getCacheDir());
				$taggedCache->RegisterTag($this->getCacheTag());

				$cache->startDataCache();
				$cache->endDataCache($rows);
				$taggedCache->EndTagCache();
			}
		}

		foreach ($rows as $row)
		{
			switch ($row['TYPE'])
			{
				case CounterDictionary::COUNTER_FLAG_COUNTED:
					$this->flagCounted = (int) $row['VALUE'];
					break;
				case CounterDictionary::COUNTER_FLAG_CLEARED:
					$this->flagCleared = (int) $row['VALUE'];
					break;
			}
		}

		return $rows;
	}
}