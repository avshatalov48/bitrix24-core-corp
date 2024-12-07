<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Tasks\Flow\Time\DatePresenter;
use Bitrix\Tasks\Internals\Task\Status;

class AveragePendingTimePreloader extends AverageTimePreloader
{
	protected static array $storage = [];

	final public function preload(int ...$flowIds): void
	{
		$this->load(Status::PENDING, ...$flowIds);
	}

	final public function get(int $flowId): ?DatePresenter
	{
		return static::$storage[$flowId][Status::PENDING] ?? null;
	}
}