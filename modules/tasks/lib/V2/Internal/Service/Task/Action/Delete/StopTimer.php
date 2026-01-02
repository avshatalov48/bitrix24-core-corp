<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete;

use Bitrix\Tasks\Internals\Task\Status;

class StopTimer
{
	public function __invoke(array $fullTaskData)
	{
		$taskId = (int)$fullTaskData['ID'];

		$timer = \CTaskTimerManager::getInstance($fullTaskData['CREATED_BY']);
		$timer->stop($taskId);

		$timer = \CTaskTimerManager::getInstance($fullTaskData['RESPONSIBLE_ID']);
		$timer->stop($taskId);

		$accomplices = $fullTaskData['ACCOMPLICES'];
		if (isset($accomplices) && !empty($accomplices))
		{
			foreach ($accomplices as $accompliceId)
			{
				$accompliceTimer = \CTaskTimerManager::getInstance($accompliceId);
				$accompliceTimer->stop($taskId);
			}
		}
	}
}