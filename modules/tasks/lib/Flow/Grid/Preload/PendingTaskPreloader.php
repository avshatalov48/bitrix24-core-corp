<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Tasks\Flow\Task\Status;

class PendingTaskPreloader extends TaskDirectorPreloader
{
	protected static array $storage = [];

	final public function preload(int ...$flowIds): void
	{
		$filter = [
			'@STATUS' => Status::STATUS_MAP[Status::FLOW_PENDING]
		];

		$this->load($filter, ...$flowIds);
	}
}