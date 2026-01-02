<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Tasks\Internals\TaskTable;

class SubTaskRepository implements SubTaskRepositoryInterface
{
	public function containsSubTasks(int $parentId): bool
	{
		$result = TaskTable::query()
			->setSelect([new ExpressionField('EXISTS', 1)])
			->where('PARENT_ID', $parentId)
			->setLimit(1)
			->fetch()
		;

		return $result !== false;
	}
}
