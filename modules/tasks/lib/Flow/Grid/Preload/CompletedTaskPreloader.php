<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Task\Status;

class CompletedTaskPreloader extends TaskDirectorPreloader
{
	protected const DAYS_LIMIT = 30;

	protected static array $storage = [];

	final public function preload(int ...$flowIds): void
	{
		$filter = [
			'@STATUS' => Status::STATUS_MAP[Status::FLOW_COMPLETED],
			'>=CLOSED_DATE' => (new DateTime())->add('-' . static::DAYS_LIMIT . ' days'),
		];

		$order = [
			'START_POINT' => 'ASC',
		];

		$this->load($filter, $order, ...$flowIds);
	}
}