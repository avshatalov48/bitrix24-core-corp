<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use Bitrix\Tasks\Internals\Task\SearchIndexTable;

class DeleteSearchIndex
{
	public function __invoke(array $fullTaskData): void
	{
		SearchIndexTable::deleteList(['=TASK_ID' => $fullTaskData['ID']]);
	}
}