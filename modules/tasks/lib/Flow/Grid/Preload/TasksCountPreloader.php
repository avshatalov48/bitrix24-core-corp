<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Internals\Task\Status;

class TasksCountPreloader
{
	private static array $storage = [];

	private FlowProvider $provider;

	public function __construct()
	{
		$this->init();
	}

	final public function preload(int $userId, int ...$flowIds): void
	{
		try
		{
			static::$storage = $this->provider->getFlowTasksCount(
				   $userId,
				   [Status::PENDING, Status::IN_PROGRESS, Status::SUPPOSEDLY_COMPLETED, Status::DEFERRED],
				...$flowIds
			);
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
		$this->provider = new FlowProvider();
	}
}