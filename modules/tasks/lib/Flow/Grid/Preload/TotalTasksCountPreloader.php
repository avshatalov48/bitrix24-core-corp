<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Provider\TaskProvider;
use Bitrix\Tasks\Internals\Log\Logger;

class TotalTasksCountPreloader
{
	private static array $storage = [];

	private TaskProvider $provider;

	public function __construct()
	{
		$this->init();
	}

	final public function preload(int ...$flowIds): void
	{
		try
		{
			static::$storage = $this->provider->getTotalTasks($flowIds);
		}
		catch (SystemException $e)
		{
			Logger::logThrowable($e);
		}
	}

	final public function get(int $flowId): int
	{
		return static::$storage[$flowId];
	}

	private function init(): void
	{
		$this->provider = new TaskProvider();
	}
}