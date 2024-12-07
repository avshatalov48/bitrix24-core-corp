<?php

namespace Bitrix\Tasks\Flow\Efficiency;

class Cache
{
	protected const TTL = 3600;
	protected const DIR = 'tasks/flow/efficiency';

	protected \Bitrix\Main\Data\Cache $engine;

	public function __construct()
	{
		$this->init();
	}

	public function store(int $flowId, Range $range, int $efficiency): void
	{
		$key = $this->getKey($flowId, $range);
		$this->engine->forceRewriting(true);
		$this->engine->startDataCache(static::TTL, $key, static::DIR . '/' . $key);
		$this->engine->endDataCache([$flowId => $efficiency]);
		$this->engine->forceRewriting(false);

	}

	public function get(int $flowId, Range $range): ?int
	{
		$key = $this->getKey($flowId, $range);
		if (false === $this->engine->initCache(static::TTL, $key, static::DIR . '/' . $key))
		{
			return null;
		}

		$variables = $this->engine->getVars();
		return $variables[$flowId];
	}

	public function invalidate(int $flowId, Range $range): void
	{
		$this->engine->cleanDir(static::DIR . '/' .  $this->getKey($flowId, $range));
	}

	protected function getKey(int $flowId, Range $range): string
	{
		return $flowId . '_' . $range->from()->getTimestamp() . '_' . $range->to()->getTimestamp();
	}

	protected function init(): void
	{
		$this->engine = \Bitrix\Main\Data\Cache::createInstance();
	}
}