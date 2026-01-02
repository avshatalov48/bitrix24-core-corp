<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Gantt;

use Bitrix\Tasks\Processor\Task\Scheduler;
use Bitrix\Tasks\V2\Internal\Exception\Task\RecountDateException;
use Exception;

class ScheduleService
{
	public function recountDates(int $taskId, int $userId): void
	{
		$scheduler = Scheduler::getInstance($userId);

		try
		{
			$scheduler->defineTaskDates($taskId)->save();
		}
		catch (Exception $e)
		{
			throw new RecountDateException($e->getMessage());
		}
	}
}
