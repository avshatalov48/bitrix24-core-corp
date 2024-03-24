<?php


namespace Bitrix\Crm\Kanban;


class EntityActivityCounter
{
	private EntityActCounter\CounterInfo $counterInfo;

	public function __construct(int $entityTypeId, array $entityIds, array $deadlines = [])
	{
		$entityIds = array_unique($entityIds);
		$prepare = new EntityActCounter\PrepareCounters($entityTypeId, $entityIds, $deadlines);
		$this->counterInfo = $prepare->prepareCounters();
	}

	/**
	 * @param $items
	 * @return void
	 *
	 * Will be added some keys to each item
	 * 	- activityProgress: number of uncompleted activities
	 * 	- activityTotal: number of completed activities
	 * 	- activityError: number of activities with lit counters
	 * 	- activityIncomingTotal: number of incoming activities
	 * 	- activityCounterTotal: number of lit counters. activityIncomingTotal + activityTotal
	 * 	- activitiesByUser: associative array of activity summary by user
	 * 		USER_ID int:   user properties mean the same as entity properties
	 *			- activityProgress:
	 * 			- activityTotal:
	 * 			- activityError:
	 * 			- incoming:
	 * 			- activityCounterTotal:
	 */
	public function appendToEntityItems(&$items): void
	{
		$errors = $this->getDeadlines();

		$incoming = $this->getIncoming();

		$activityCounters = $this->getCounters();

		foreach ($activityCounters as $id => $activityCounter)
		{
			if (!isset($items[$id]))
			{
				continue;
			}

			[
				$items[$id]['activityProgress'],
				$items[$id]['activityTotal'],
				$items[$id]['activityError']
			] = $this->getCounterValuesFromActivities($activityCounter);

			$items[$id]['activitiesByUser'] = $this->getPreparedActivitiesByUser($activityCounter, $id);

			$activityCounterTotal = [];
			if (isset($errors[$id]))
			{
				$activityCounterTotal = $errors[$id];
			}
			$items[$id]['activityIncomingTotal'] = 0;
			if (isset($incoming[$id]))
			{
				$activityCounterTotal = array_unique(array_merge($activityCounterTotal, $incoming[$id]));
				$items[$id]['activityIncomingTotal'] = count($incoming[$id]);
			}
			$items[$id]['activityCounterTotal'] = count($activityCounterTotal);
		}
	}

	public function getDeadlines(): array
	{
		return $this->counterInfo->deadlines();
	}

	public function getDeadlinesCount(int $entityId): int
	{
		return (
			(isset($this->getDeadlines()[$entityId]) && is_array($this->getDeadlines()[$entityId]))
				? count($this->getDeadlines()[$entityId])
				: 0
		);
	}

	public function getIncomingCount(int $entityId): int
	{
		return (
			(isset($this->getIncoming()[$entityId]) && is_array($this->getIncoming()[$entityId]))
				? count($this->getIncoming()[$entityId])
				: 0
		);
	}

	public function getIncoming(): array
	{
		return $this->counterInfo->incoming();
	}

	public function getCounters(): array
	{
		return $this->counterInfo->counters();
	}

	public function isLimitIsExceeded(): bool
	{
		return $this->counterInfo->isLimitIsExceeded();
	}

	private function getPreparedActivitiesByUser(array $activityCounters, string $activityId): array
	{
		if (!isset($activityCounters['byUser']))
		{
			return [];
		}

		$preparedActivities = [];

		foreach ($activityCounters['byUser'] as $userId => $activities)
		{
			[
				$preparedActivities[$userId]['activityProgress'],
				$preparedActivities[$userId]['activityTotal'],
				$preparedActivities[$userId]['activityError'],
			] = $this->getCounterValuesFromActivities($activities);

			$preparedActivities[$userId]['incoming'] = $this->counterInfo->incomingByResponsible()[$activityId][$userId] ?? 0;

			$preparedActivities[$userId]['activityCounterTotal']
				= $preparedActivities[$userId]['incoming'] + $preparedActivities[$userId]['activityError'];
		}

		return $preparedActivities;
	}

	private function getCounterValuesFromActivities(array $activities): array
	{
		return [
			$activities['N'] ?? 0,
			$activities['Y'] ?? 0,
			$activities['D'] ?? 0, // D - means Deadlined, count overdue activities
		];
	}
}
