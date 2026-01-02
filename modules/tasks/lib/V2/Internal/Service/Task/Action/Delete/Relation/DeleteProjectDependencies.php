<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;

class DeleteProjectDependencies
{
	public function __invoke(array $fullTaskData): void
	{
		$taskId = $fullTaskData['ID'];

		$select = ['TASK_ID'];
		$filter = [
			"=TASK_ID" => $taskId,
			"DEPENDS_ON_ID" => $taskId,
		];

		$tableResult = ProjectDependenceTable::getList(["select" => $select, "filter" => $filter]);

		if (ProjectDependenceTable::checkItemLinked($taskId) || $tableResult->fetch())
		{
			ProjectDependenceTable::deleteLink($taskId, $taskId);
		}
	}
}