<?php

namespace Bitrix\Tasks\Internals\Task\WorkTime\Decorator;

use Bitrix\Tasks\Internals\Task\WorkTime\WorkTimeService;

class WorkTimeServiceDecorator extends WorkTimeService
{
	protected WorkTimeService $source;

	public function __construct(WorkTimeService $source)
	{
		$this->source = $source;
	}
}