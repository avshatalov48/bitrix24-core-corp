<?php

namespace Bitrix\Tasks\Flow\Controllers\Task\Cache;

use Bitrix\Main\Data\Cache;

class TaskCountCache
{
	protected const TTL = 3600;
	protected const DIR = 'tasks/flow/controllers/task/cache';

	protected Cache $engine;

	public function __construct()
	{
		$this->init();
	}

	public function store(array $parameters, int $totalTaskCount): void
	{
		$key = $this->getKey($parameters);
		$this->engine->forceRewriting(true);
		$this->engine->startDataCache(static::TTL, $key, static::DIR . '/' . $key);
		$this->engine->endDataCache([$parameters['FLOW_ID'] => $totalTaskCount]);
		$this->engine->forceRewriting(false);
	}

	public function get(array $parameters): ?int
	{
		$key = $this->getKey($parameters);
		if (false === $this->engine->initCache(static::TTL, $key, static::DIR . '/' . $key))
		{
			return null;
		}

		$variables = $this->engine->getVars();
		return $variables[$parameters['FLOW_ID']];
	}

	public function invalidate(array $parameters): void
	{
		$this->engine->cleanDir(static::DIR . '/' .  $this->getKey($parameters));
	}

	protected function getKey(array $parameters): string
	{
		$dateKey = !empty($parameters['>=CLOSED_DATE'])
			? '_' . $parameters['>=CLOSED_DATE']->format('Y-m-d')
			: '';

		return $parameters['FLOW_ID']
			. '_' . implode('_' , $parameters['REAL_STATUS'])
			. $dateKey;
	}

	protected function init(): void
	{
		$this->engine = Cache::createInstance();
	}
}