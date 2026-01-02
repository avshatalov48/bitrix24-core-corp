<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use Bitrix\Tasks\Internals\Task\ParameterTable;

class DeleteParameters
{
	public function __invoke(array $fullTaskData): void
	{
		ParameterTable::deleteList(['=TASK_ID' => $fullTaskData['ID']]);
	}
}