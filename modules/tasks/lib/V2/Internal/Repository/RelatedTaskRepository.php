<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\DB\SqlException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\Internals\Task\RelatedTable;

class RelatedTaskRepository implements RelatedTaskRepositoryInterface
{
	public function getRelatedTaskIds(int $taskId): array
	{
		$rows = RelatedTable::query()
			->setSelect(['DEPENDS_ON_ID'])
			->where('TASK_ID', $taskId)
			->fetchAll()
		;

		$ids = array_column($rows, 'DEPENDS_ON_ID');

		Collection::normalizeArrayValuesByInt($ids, false);

		return $ids;
	}
	public function containsRelatedTasks(int $taskId): bool
	{
		$result = RelatedTable::query()
			->setSelect([new ExpressionField('EXISTS', 1)])
			->where('TASK_ID', $taskId)
			->fetch()
		;

		return $result !== false;
	}

	public function save(int $taskId, array $relatedTaskIds): void
	{
		Collection::normalizeArrayValuesByInt($relatedTaskIds, false);
		if (empty($relatedTaskIds))
		{
			return;
		}

		$rows = [];
		foreach ($relatedTaskIds as $relatedTaskId)
		{
			$rows[] = [
				'TASK_ID' => $taskId,
				'DEPENDS_ON_ID' => $relatedTaskId,
			];
		}

		$result = RelatedTable::addInsertIgnoreMulti($rows);
		if (!$result->isSuccess())
		{
			throw new SqlException($result->getError()?->getMessage());
		}
	}

	public function deleteByTaskId(int $taskId): void
	{
		RelatedTable::deleteByFilter(['TASK_ID' => $taskId]);
	}

	public function deleteByRelatedTaskIds(int $taskId, array $relatedTaskIds): void
	{
		Collection::normalizeArrayValuesByInt($relatedTaskIds, false);
		if (empty($relatedTaskIds))
		{
			return;
		}

		RelatedTable::deleteByFilter(['TASK_ID' => $taskId, 'DEPENDS_ON_ID' => $relatedTaskIds]);
	}
}
