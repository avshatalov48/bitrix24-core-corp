<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Tasks\Flow\Time\DatePresenter;
use Bitrix\Tasks\Internals\Task\Status;

class AverageCompletedTimePreloader extends AverageTimePreloader
{
	protected static array $storage = [];

	final public function preload(int ...$flowIds): void
	{
		$this->load(Status::COMPLETED, ...$flowIds);
	}

	final public function get(int $flowId): ?DatePresenter
	{
		return static::$storage[$flowId][Status::COMPLETED] ?? null;
	}
}