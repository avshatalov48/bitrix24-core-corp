<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\V2\Internal\Model\DeadlineChangeLogTable;
use Bitrix\Main\DB\SqlQueryException;

class DeadlineChangeLogRepository implements DeadlineChangeLogRepositoryInterface
{
	/**
	 * @inheritDoc
	 */
	public function append(
		int $taskId,
		int $userId,
		?DateTime $dateTime,
		?string $reason
	): void
	{
		$result = DeadlineChangeLogTable::add([
			'TASK_ID' => $taskId,
			'USER_ID' => $userId,
			'NEW_DEADLINE' => $dateTime,
			'REASON' => $reason,
			'CHANGED_AT' => new DateTime(),
		]);

		if (!$result->isSuccess())
		{
			throw new SqlQueryException(implode('; ', $result->getErrorMessages()));
		}
	}

	/**
	 * @inheritDoc
	 */
	public function countUserChanges(int $userId, int $taskId): int
	{
		$result = DeadlineChangeLogTable::query()
			->where('USER_ID', $userId)
			->where('TASK_ID', $taskId)
			->setSelect(['CNT'])
			->registerRuntimeField(
				new \Bitrix\Main\ORM\Fields\ExpressionField('CNT', 'COUNT(*)')
			)
			->fetch();

		return isset($result['CNT']) ? (int)$result['CNT'] : 0;
	}
}
