<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use CTaskDependence;

class DeleteDependencies
{
	public function __invoke(array $fullTaskData): void
	{
		CTaskDependence::DeleteByTaskID($fullTaskData['ID']);
		CTaskDependence::DeleteByDependsOnID($fullTaskData['ID']);
	}
}