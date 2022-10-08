<?php

namespace Bitrix\Tasks\Scrum\Service;

class CacheService
{
	const COMPLETED_SPRINT = 'completedSprint';
	const ITEM_TASKS = 'itemTasks';
	const EPICS = 'epics';
	const STATS = 'stats';
	const TEAM_STATS = 'teamStats';

	/** @var \CPHPCache */
	private $cache;

	private $cacheTime;
	private $cacheId;
	private $cacheDir;
	private $rootDir;

	private $map = [
		CacheService::COMPLETED_SPRINT => [
			'id' => 'tasks-scrum-sprint-',
			'dir' => '/tasks/scrum/sprints/',
			'time' => (3600 * 24),
		],
		CacheService::ITEM_TASKS => [
			'id' => 'tasks-scrum-item-tasks-',
			'dir' => '/tasks/scrum/tasks/',
			'time' => (3600 * 24),
		],
		CacheService::EPICS => [
			'id' => 'tasks-scrum-epic-',
			'dir' => '/tasks/scrum/epics/',
			'time' => (3600 * 24),
		],
		CacheService::STATS => [
			'id' => 'tasks-scrum-stats-',
			'dir' => '/tasks/scrum/stats/',
			'time' => (3600 * 24),
		],
		CacheService::TEAM_STATS => [
			'id' => 'tasks-scrum-team-stats-',
			'rootDir' => '/tasks/scrum/team-stats/',
			'dir' => '/tasks/scrum/team-stats/',
			'time' => (3600 * 24),
		],
	];

	public function __construct(int $id, string $typeId, ?string $extraKey = null)
	{
		$this->cache = new \CPHPCache;

		if (!isset($this->map[$typeId]))
		{
			throw new ArgumentNullException('An unsupported type was passed');
		}

		$this->cacheTime = $this->map[$typeId]['time'];
		$this->cacheId = $this->map[$typeId]['id'] . $id;
		$this->cacheDir = $this->map[$typeId]['dir'] . $id;

		if (isset($this->map[$typeId]['rootDir']))
		{
			$this->rootDir = $this->map[$typeId]['dir'] . $id;
		}

		if ($extraKey)
		{
			$this->cacheId = $this->cacheId . '-' . $extraKey;
			$this->cacheDir = $this->cacheDir . '/' . $extraKey;
		}
	}

	public function init(): bool
	{
		return $this->cache->initCache($this->cacheTime, $this->cacheId, $this->cacheDir);
	}

	public function getData(): array
	{
		return $this->cache->getVars();
	}

	public function start(): void
	{
		$this->cache->startDataCache($this->cacheTime, $this->cacheId, $this->cacheDir);
	}

	public function end(array $data): void
	{
		$this->cache->endDataCache($data);
	}

	public function clean()
	{
		$this->cache->cleanDir($this->cacheDir);
	}

	public function cleanRoot()
	{
		if ($this->rootDir)
		{
			$this->cache->cleanDir($this->rootDir);
		}
	}
}