<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\Internals\TaskTable;

class ParentTaskRepository implements ParentTaskRepositoryInterface
{
	public function getParentId(int $taskId): ?int
	{
		$row = TaskTable::query()
			->setSelect(['ID', 'PARENT_ID'])
			->where('ID', $taskId)
			->fetch()
		;

		if (!is_array($row))
		{
			return null;
		}

		return (int)$row['PARENT_ID'] > 0 ? (int)$row['PARENT_ID'] : null;
	}
}
