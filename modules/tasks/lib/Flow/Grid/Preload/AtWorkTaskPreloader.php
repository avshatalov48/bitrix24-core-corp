<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Tasks\Flow\Task\Status;

class AtWorkTaskPreloader extends TaskDirectorPreloader
{
	protected static array $storage = [];

	final public function preload(int ...$flowIds): void
	{
		$filter = [
			'@STATUS' => Status::STATUS_MAP[Status::FLOW_AT_WORK]
		];

		$order = [
			'DATE_START' => 'DESC',
		];

		$this->load($filter, $order, ...$flowIds);
	}
}