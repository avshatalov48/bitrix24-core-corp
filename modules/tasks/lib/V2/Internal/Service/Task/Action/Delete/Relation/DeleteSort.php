<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use Bitrix\Tasks\Internals\Task\SortingTable;

class DeleteSort
{
	public function __invoke(array $fullTaskData): void
	{
		SortingTable::deleteByTaskId($fullTaskData['ID']);
	}
}