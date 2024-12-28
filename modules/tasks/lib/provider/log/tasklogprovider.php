<?php

namespace Bitrix\Tasks\Provider\Log;

use Bitrix\Tasks\Provider\Exception\Log\TaskLogProviderException;
use Bitrix\Tasks\Control\Log\TaskLog;
use Bitrix\Tasks\Control\Log\TaskLogCollection;
use Exception;

class TaskLogProvider
{
	/**
	 * Get a list of TaskLogs from task history
	 *
	 * @throws TaskLogProviderException
	 */
	public function getList(TaskLogQuery $taskLogQuery): TaskLogCollection
	{
		$taskLogCollection = new TaskLogCollection();

		try
		{
			$listTaskLogData = TaskLogQueryBuilder::build($taskLogQuery)->exec()->fetchAll();
		}
		catch (Exception $e)
		{
			throw new TaskLogProviderException($e->getMessage());
		}

		foreach ($listTaskLogData as $taskLogData)
		{
			$taskLogCollection->add(new TaskLog($taskLogData));
		}

		return $taskLogCollection;
	}
}
