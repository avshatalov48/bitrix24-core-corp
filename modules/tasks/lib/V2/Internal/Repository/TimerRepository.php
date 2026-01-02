<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\Internals\Task\TimerTable;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Exception\Task\TimerNotFoundException;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\TimerMapper;

class TimerRepository implements TimerRepositoryInterface
{
	public function __construct(
		private readonly TimerMapper $timerMapper,
	)
	{

	}

	public function get(int $userId, int $taskId = 0): ?Entity\Task\Timer
	{
		$query = TimerTable::query()
			->setSelect(['TASK_ID', 'USER_ID', 'TIMER_STARTED_AT', 'TIMER_ACCUMULATOR'])
			->where('USER_ID', $userId);

		if ($taskId > 0)
		{
			$query->where('TASK_ID', $taskId);
		}

		$timer = $query->fetch();
		if (!is_array($timer))
		{
			return null;
		}

		return $this->timerMapper->mapToEntity($timer);
	}

	public function getByUserIds(array $userIds, int $taskId): Entity\Task\TimerCollection
	{
		if (empty($userIds))
		{
			return new Entity\Task\TimerCollection();
		}

		$query = TimerTable::query()
			->setSelect(['TASK_ID', 'USER_ID', 'TIMER_STARTED_AT', 'TIMER_ACCUMULATOR'])
			->whereIn('USER_ID', $userIds)
			->where('TASK_ID', $taskId);

		$timers = $query->fetchAll();

		return $this->timerMapper->mapToCollection($timers);
	}

	public function add(Entity\Task\Timer $timer): void
	{
		$data = $this->timerMapper->mapFromEntity($timer);

		$result = TimerTable::addInsertIgnore($data);

		if (!$result->isSuccess())
		{
			throw new TimerNotFoundException($result->getError()?->getMessage());
		}
	}

	public function upsert(Entity\Task\Timer $timer): void
	{
		$data = $this->timerMapper->mapFromEntity($timer);

		$result = TimerTable::addMerge($data);

		if (!$result->isSuccess())
		{
			throw new TimerNotFoundException($result->getError()?->getMessage());
		}
	}

	public function update(Entity\Task\Timer $timer): void
	{
		if ($timer->userId <= 0)
		{
			return;
		}

		TimerTable::updateByFilter(
			['USER_ID' => $timer->userId, 'TASK_ID' => $timer->taskId],
			['TIMER_STARTED_AT' => $timer->startedAtTs, 'TIMER_ACCUMULATOR' => $timer->seconds]
		);
	}
}