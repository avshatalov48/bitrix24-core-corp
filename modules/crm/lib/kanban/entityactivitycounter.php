<?php


namespace Bitrix\Crm\Kanban;


use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\DateTime;

class EntityActivityCounter
{
	private int $entityTypeId;
	private array $entityIds;
	private array $deadlines;
	private array $incoming = [];
	private array $counters = [];
	private array $activities = [];
	private DateTime $deadlineDate;

	public function __construct(int $entityTypeId, array $entityIds, array $deadlines = [])
	{
		$this->entityTypeId = $entityTypeId;
		$this->entityIds = array_unique($entityIds);
		$this->deadlines = $deadlines;
		$this->deadlineDate = (new DateTime())
			->toUserTime()
			->add('-'.date('G').' hours')
			->add('-'.date('i').' minutes')
			->add('+1 day')
		;

		$this->prepareCounters();
	}

	private function prepareCounters(): void
	{
		if (empty($this->entityIds))
		{
			return;
		}

		$this->prepareActivities();

		$this->prepareDeadlines();
		$this->prepareIncomings();

		$this->prepareActivitiesCounters();
		$this->preparePseudoActivitiesCounters();
	}

	private function prepareActivities(): void
	{
		$list = \CCrmActivity::GetList(
			[],
			$this->getFilter(),
			false,
			false,
			[
				'ID',
				'COMPLETED',
				'OWNER_ID',
				'RESPONSIBLE_ID',
				'DEADLINE',
			]
		);

		while ($activity = $list->fetch())
		{
			if ($activity['DEADLINE'])
			{
				$activity['DEADLINE'] = new DateTime($activity['DEADLINE']);
			}

			$this->activities[] = $activity;
		}
	}

	private function getFilter(): array
	{
		$filter = [
			'BINDINGS' => [],
		];

		foreach ($this->entityIds as $id)
		{
			$filter['BINDINGS'][] = [
				'OWNER_ID' => $id,
				'OWNER_TYPE_ID' => $this->entityTypeId,
			];
		}

		return $filter;
	}

	private function prepareDeadlines(): void
	{
		$fetched = [];
		foreach ($this->activities as $activity)
		{
			if (!$this->isDeadlineActivity($activity))
			{
				continue;
			}

			$activityId = $activity['ID'];
			if (!isset($fetched[$activityId]))
			{
				$fetched[$activityId] = true;
				$ownerId = $activity['OWNER_ID'];
				if (!isset($this->deadlines[$ownerId]))
				{
					$this->deadlines[$ownerId] = [];
				}

				$activityId = (int)$activity['ID'];
				$this->deadlines[$ownerId][$activityId] = $activityId;
			}
		}
	}

	private function isDeadlineActivity(array $activity): bool
	{
		return ($activity['DEADLINE'] <= $this->deadlineDate && $activity['COMPLETED'] === 'N');
	}

	private function prepareIncomings(): void
	{
		$activitiesIds = [];
		foreach ($this->activities as $activity)
		{
			$activitiesIds[] = $activity['ID'];
		}

		if (empty($activitiesIds))
		{
			return;
		}

		$incomingList = IncomingChannelTable::getList([
			'select' => [
				'ACTIVITY_ID',
				'OWNER_ID' => 'BINDING.OWNER_ID',
			],
			'filter' => [
				'BINDING.OWNER_TYPE_ID' => $this->entityTypeId,
				'@BINDING.OWNER_ID' => $this->entityIds,
				'@ACTIVITY_ID' => $activitiesIds,
				'=COMPLETED' => 'N',
			],
			'runtime' => [
				new ReferenceField(
					'BINDING',
					ActivityBindingTable::class,
					Join::on('this.ACTIVITY_ID', 'ref.ACTIVITY_ID'),
					['join_type' => 'INNER']
				)
			]
		]);

		$fetched = [];
		while ($incoming = $incomingList->fetch())
		{
			$activityId = $incoming['ACTIVITY_ID'];
			if (!isset($fetched[$activityId]))
			{
				$fetched[$activityId] = true;
				$ownerId = $incoming['OWNER_ID'];
				if (!isset($this->incoming[$ownerId]))
				{
					$this->incoming[$ownerId] = [];
				}
				$this->incoming[$ownerId][] = (int)$incoming['ACTIVITY_ID'];
			}
		}
	}

	private function prepareActivitiesCounters(): void
	{
		$multiBindings = $this->getMultiBindings();
		foreach ($this->activities as $activity)
		{
			$ownerId = $activity['OWNER_ID'];
			$isCompleted = $activity['COMPLETED'];
			$responsibleId = $activity['RESPONSIBLE_ID'];

			$this->prepareCounter($ownerId, $isCompleted, $responsibleId);

			$activityId = (int)$activity['ID'];

			$isDeadlineActivity = $this->isDeadlineActivity($activity);

			if (isset($multiBindings[$activityId]))
			{
				foreach ($multiBindings[$activityId] as $activityOwnerId)
				{
					$this->prepareCounter($activityOwnerId, $isCompleted, $responsibleId);

					if ($isDeadlineActivity)
					{
						if (!isset($this->deadlines[$activityOwnerId]))
						{
							$this->deadlines[$activityOwnerId] = [];
						}

						$this->deadlines[$activityOwnerId][$activityId] = $activityId;
					}
				}
			}
		}
	}

	private function prepareCounter(int $ownerId, string $isCompleted, int $responsibleId): void
	{
		if (!isset($this->counters[$ownerId]))
		{
			$this->counters[$ownerId] = [];
		}

		if (!isset($this->counters[$ownerId][$isCompleted]))
		{
			$this->counters[$ownerId][$isCompleted] = 0;
		}

		$this->counters[$ownerId][$isCompleted]++;
		$this->counters[$ownerId]['byUser'][$responsibleId][$isCompleted]++;
	}

	private function getMultiBindings(): array
	{
		$activityBindings = \Bitrix\Crm\ActivityBindingTable::getList([
			'select' => [
				'ACTIVITY_ID',
				'OWNER_ID',
			],
			'filter' => [
				'OWNER_ID' => $this->entityIds,
				'OWNER_TYPE_ID' => $this->entityTypeId,
			],
		]);

		$bindings = [];
		while ($activityBinding = $activityBindings->fetch())
		{
			$activityId = $activityBinding['ACTIVITY_ID'];
			$ownerId = $activityBinding['OWNER_ID'];
			if (!isset($bindings[$activityId]))
			{
				$bindings[$activityId] = [];
			}

			$bindings[$activityId][$ownerId] = $ownerId;
		}

		return $bindings;
	}

	private function preparePseudoActivitiesCounters(): void
	{
		$waits = \Bitrix\Crm\Pseudoactivity\WaitEntry::getRecentIDsByOwner($this->entityTypeId, $this->entityIds);
		if ($waits)
		{
			foreach ($waits as $row)
			{
				$entityId = $row['OWNER_ID'];
				if (!isset($this->counters[$entityId]['N']))
				{
					$this->counters[$entityId]['N'] = 0;
				}
				$this->counters[$entityId]['N']++;
			}
		}
	}

	public function appendToEntityItems(&$items): void
	{
		$errors = $this->getDeadlines();

		if (\Bitrix\Main\Config\Option::get('crm', 'enable_entity_uncompleted_act', 'Y') === 'Y')
		{
			$incoming = $this->getIncoming();
		}
		else
		{
			$incoming = [];
		}

		$activityCounters = $this->getCounters();

		foreach ($activityCounters as $id => $activityCounter)
		{
			if (!isset($items[$id]))
			{
				continue;
			}

			[
				$items[$id]['activityProgress'],
				$items[$id]['activityTotal']
			] = $this->getCounterValuesFromActivities($activityCounter);

			$items[$id]['activitiesByUser'] = $this->getPreparedActivitiesByUser($activityCounters[$id] ?? []);

			$activityCounterTotal = [];
			if (isset($errors[$id]))
			{
				$activityCounterTotal = $errors[$id];
				$items[$id]['activityErrorTotal'] = count($errors[$id]);
			}
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
		return $this->deadlines;
	}

	public function getDeadlinesCount(int $entityId): int
	{
		return is_array($this->deadlines[$entityId]) ? count($this->deadlines[$entityId]) : 0;
	}

	public function getIncomingCount(int $entityId): int
	{
		return is_array($this->incoming[$entityId]) ? count($this->incoming[$entityId]) : 0;
	}

	public function getIncoming(): array
	{
		return $this->incoming;
	}

	public function getCounters(): array
	{
		return $this->counters;
	}

	private function getPreparedActivitiesByUser(array $activityCounters): array
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
				$preparedActivities[$userId]['activityTotal']
			] = $this->getCounterValuesFromActivities($activities);
		}

		return $preparedActivities;
	}

	private function getCounterValuesFromActivities(array $activities): array
	{
		return [
			$activities['N'] ?? 0,
			$activities['Y'] ?? 0,
		];
	}
}
