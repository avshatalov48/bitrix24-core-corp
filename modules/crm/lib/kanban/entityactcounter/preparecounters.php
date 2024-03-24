<?php

namespace Bitrix\Crm\Kanban\EntityActCounter;


use Bitrix\Crm\Kanban\Queries\QueryEntityActivityCounter;
use Bitrix\Main\Type\DateTime;

class PrepareCounters
{
	private QueryEntityActivityCounter $queryActivity;

	private int $entityTypeId;
	private array $entityIds;

	private Builder $builder;

	private const BINDINGS_LIMIT = 10000;

	public function __construct(int $entityTypeId, array $entityIds, array $deadlines = [])
	{
		$this->queryActivity = QueryEntityActivityCounter::getInstance();

		$this->builder = new Builder();

		$this->entityTypeId = $entityTypeId;
		$this->entityIds = array_unique($entityIds);
		$this->builder->deadlines = $deadlines;

	}

	public function prepareCounters(): CounterInfo
	{
		if (empty($this->entityIds))
		{
			return CounterInfo::createEmpty();
		}

		$activityBindings = $this->getActivityBindings();
		if ($activityBindings === null)
		{
			return CounterInfo::createEmpty()->setLimitIsExceeded();
		}

		$this->prepareActivities();

		$this->prepareDeadlines();
		$this->prepareIncomings();

		$this->prepareActivitiesCounters($activityBindings);
		$this->preparePseudoActivitiesCounters();

		return $this->builder->toCounterInfo();
	}

	private function prepareActivities(): void
	{
		$activities = $this->queryActivity->queryActivities($this->entityTypeId, $this->entityIds);

		foreach ($activities as $activity)
		{
			// have to use owner_id and owner_type_id from binding table instead activity table
			$activity['OWNER_ID'] = $activity['BIND_OWNER_ID'];
			$activity['OWNER_TYPE_ID'] = $activity['BIND_OWNER_TYPE_ID'];
			$this->builder->activities[] = $activity;
		}
	}

	private function prepareDeadlines(): void
	{
		$fetched = [];
		foreach ($this->builder->activities as $activity)
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
				$ownerTypeId = $activity['OWNER_TYPE_ID'];

				if ((int)$ownerTypeId !== $this->entityTypeId)
				{
					continue;
				}

				if (!isset($this->builder->deadlines[$ownerId]))
				{
					$this->builder->deadlines[$ownerId] = [];
				}

				$activityId = (int)$activity['ID'];
				$this->builder->deadlines[$ownerId][$activityId] = $activityId;
			}
		}
	}

	private function prepareIncomings(): void
	{
		$activitiesIds = [];
		$activityResponsibleMap = [];
		foreach ($this->builder->activities as $activity)
		{
			$activitiesIds[] = $activity['ID'];
			$activityResponsibleMap[$activity['ID']] = (int)$activity['RESPONSIBLE_ID'];
		}

		if (empty($activitiesIds))
		{
			return;
		}
		$incomingList = $this->queryActivity
			->queryIncomingActivities($this->entityTypeId, $this->entityIds, $activitiesIds);

		$this->builder->incomingByResponsible = [];

		foreach ($incomingList as $incoming)
		{
			$ownerId = $incoming['OWNER_ID'];
			if (!isset($this->builder->incoming[$ownerId]))
			{
				$this->builder->incoming[$ownerId] = [];
			}
			$this->builder->incoming[$ownerId][] = (int)$incoming['ACTIVITY_ID'];

			$actResponsibleId = $activityResponsibleMap[$incoming['ACTIVITY_ID']] ?? null;
			if ($actResponsibleId !== null)
			{
				if (!isset($this->builder->incomingByResponsible[$ownerId][$actResponsibleId]))
				{
					$this->builder->incomingByResponsible[$ownerId][$actResponsibleId] = 0;
				}
				$this->builder->incomingByResponsible[$ownerId][$actResponsibleId]++;
			}
		}
	}

	private function prepareActivitiesCounters(array $activityBindings): void
	{
		$multiBindings = $this->getMultiBindings($activityBindings);

		$activities = [];
		// removing duplicate "activities" and left only valuable fields before prepare counters
		foreach ($this->builder->activities as $activity)
		{
			$id = $activity['ID'];
			if (isset($activities[$id]))
			{
				continue;
			}

			$activities[$id] = [
				'ID' => $activity['ID'],
				'COMPLETED' => $activity['COMPLETED'],
				'RESPONSIBLE_ID' => $activity['RESPONSIBLE_ID'],
				'LIGHT_COUNTER_AT' => $activity['LIGHT_COUNTER_AT'],
			];
		}

		foreach ($activities as $activity)
		{
			$isCompleted = $activity['COMPLETED'];
			$responsibleId = $activity['RESPONSIBLE_ID'];
			$isDeadlineActivity = $this->isDeadlineActivity($activity);

			$activityId = (int)$activity['ID'];

			if (isset($multiBindings[$activityId]))
			{
				foreach ($multiBindings[$activityId] as $activityOwnerId)
				{
					$this->prepareCounter($activityOwnerId, $isCompleted, $responsibleId, $isDeadlineActivity);

					if ($isDeadlineActivity)
					{
						if (!isset($this->builder->deadlines[$activityOwnerId]))
						{
							$this->builder->deadlines[$activityOwnerId] = [];
						}

						$this->builder->deadlines[$activityOwnerId][$activityId] = $activityId;
					}
				}
			}
		}
	}

	private function prepareCounter(int $ownerId, string $isCompleted, int $responsibleId, bool $isDeadlineActivity): void
	{
		if (!isset($this->builder->counters[$ownerId]))
		{
			$this->builder->counters[$ownerId] = [];
		}

		if ($isDeadlineActivity)
		{
			if (!isset($this->builder->counters[$ownerId]['D']))
			{
				$this->builder->counters[$ownerId]['D'] = 0;
			}
			$this->builder->counters[$ownerId]['D']++;
		}

		if (!isset($this->builder->counters[$ownerId][$isCompleted]))
		{
			$this->builder->counters[$ownerId][$isCompleted] = 0;
		}

		$this->builder->counters[$ownerId][$isCompleted]++;
		if (!isset($this->builder->counters[$ownerId]['byUser']))
		{
			$this->builder->counters[$ownerId]['byUser'] = [$responsibleId => []];
		}

		$byUserValue = $this->builder->counters[$ownerId]['byUser'][$responsibleId][$isCompleted] ?? 0;
		$this->builder->counters[$ownerId]['byUser'][$responsibleId][$isCompleted] = $byUserValue + 1;

		if ($isDeadlineActivity)
		{
			$byUserError = $this->builder->counters[$ownerId]['byUser'][$responsibleId]['D'] ?? 0;
			$this->builder->counters[$ownerId]['byUser'][$responsibleId]['D'] = $byUserError + 1;
		}
	}

	private function getActivityBindings(): ?array
	{
		$activityBindings = $this->queryActivity->queryBindings($this->entityTypeId, $this->entityIds, self::BINDINGS_LIMIT);

		if (count($activityBindings) >= self::BINDINGS_LIMIT)
		{
			return null;
		}

		return $activityBindings;
	}

	private function getMultiBindings(array $activityBindings): array
	{
		$bindings = [];
		foreach ($activityBindings as $activityBinding)
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
		$waits = $this->queryActivity->queryWaits($this->entityTypeId, $this->entityIds);
		if ($waits)
		{
			foreach ($waits as $row)
			{
				$entityId = $row['OWNER_ID'];
				if (!isset($this->builder->counters[$entityId]['N']))
				{
					$this->builder->counters[$entityId]['N'] = 0;
				}
				$this->builder->counters[$entityId]['N']++;
			}
		}
	}

	private function isDeadlineActivity(array $activity): bool
	{
		if ($activity['COMPLETED'] === 'Y')
		{
			return false;
		}

		if ($activity['LIGHT_COUNTER_AT'] instanceof DateTime)
		{
			return $activity['LIGHT_COUNTER_AT']->getTimestamp() < (new DateTime())->getTimestamp();
		}
		return false;
	}



}