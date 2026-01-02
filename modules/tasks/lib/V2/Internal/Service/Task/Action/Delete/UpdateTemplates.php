<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete;

use Bitrix\Main\Application;
use Bitrix\Tasks\Internals\Task\SortingTable;

class UpdateTemplates
{
	public function __invoke(array $fullTaskData): void
	{
		$taskId = (int)$fullTaskData['ID'];

		$connection = Application::getConnection();

		$parentId = $fullTaskData["PARENT_ID"] ?: "NULL";

		$sql = "
			UPDATE b_tasks_template 
			SET TASK_ID = NULL 
			WHERE TASK_ID = " . $taskId;
		$connection->queryExecute($sql);

		$sql = "
			UPDATE b_tasks_template 
			SET PARENT_ID = " . $parentId . " 
			WHERE PARENT_ID = " . $taskId;
		$connection->queryExecute($sql);
	}
}