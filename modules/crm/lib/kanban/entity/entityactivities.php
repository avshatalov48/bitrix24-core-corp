<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Entity\EntityManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class EntityActivities
{
	public const ACTIVITY_STAGE_ID = 'ACTIVITY_STAGE_ID';

	public const STAGE_OVERDUE = 'OVERDUE';
	public const STAGE_PENDING = 'PENDING';
	public const STAGE_THIS_WEEK = 'THIS_WEEK';
	public const STAGE_NEXT_WEEK = 'NEXT_WEEK';
	public const STAGE_IDLE = 'IDLE';
	public const STAGE_LATER = 'LATER';

	protected int $entityTypeId;
	protected ?int $categoryId = null;

	public function __construct(int $entityTypeId, ?int $categoryId = null)
	{
		$this->entityTypeId = $entityTypeId;
		$this->categoryId = $categoryId;
	}

	public function getStagesList(?int $categoryId = 0): array
	{
		$stageList = [
			[
				'STATUS_ID' => self::STAGE_OVERDUE,
				'NAME' => Loc::getMessage('KANBAN_ACTIVITY_STAGE_OVERDUE'),
				'COLOR' => '#ff5752',
				'BLOCKED_INCOMING_MOVING' => true,
			],
			[
				'STATUS_ID' => self::STAGE_PENDING,
				'NAME' => Loc::getMessage('KANBAN_ACTIVITY_STAGE_PENDING'),
				'COLOR' => '#7bd500',
			],
			[
				'STATUS_ID' => self::STAGE_THIS_WEEK,
				'NAME' => Loc::getMessage('KANBAN_ACTIVITY_STAGE_THIS_WEEK'),
				'COLOR' => '#2fc6f6',
			],
			[
				'STATUS_ID' => self::STAGE_NEXT_WEEK,
				'NAME' => Loc::getMessage('KANBAN_ACTIVITY_STAGE_NEXT_WEEK'),
				'COLOR' => '#55d0e0',
			],
			[
				'STATUS_ID' => self::STAGE_IDLE,
				'NAME' => Loc::getMessage('KANBAN_ACTIVITY_STAGE_IDLE'),
				'COLOR' => '#9eacc2',
				'BLOCKED_INCOMING_MOVING' => true,
			],
			[
				'STATUS_ID' =>self::STAGE_LATER,
				'NAME' => Loc::getMessage('KANBAN_ACTIVITY_STAGE_LATER'),
				'COLOR' => '#3373bb',
			],
		];

		$this->prepareStagesList($stageList, $categoryId);

		return $stageList;
	}

	protected function prepareStagesList(array &$items, ?int $categoryId = 0): void
	{
		foreach ($items as &$item)
		{
			$item['STATUS_TYPE_ID'] = $item['STATUS_ID'];

			if ($categoryId)
			{
				$item['STATUS_ID'] = $this->getStatusIdByCategoryId($item['STATUS_ID'], $categoryId);
			}
		}

		unset($item);
	}

	protected function getStatusIdByCategoryId(string $statusId, ?int $categoryId = 0): string
	{
		if (!$categoryId)
		{
			return $statusId;
		}

		return 'C' . $categoryId . ':' . $statusId;
	}

	public function prepareItemsListParams(array $params): array
	{
		$filter = $params['filter'] ?? [];
		$stageId = $filter[self::ACTIVITY_STAGE_ID] ?? null;
		if (!isset($stageId) && isset($filter['ID'][0]))
		{
			$stageId = $this->getActivityStageIdByEntityId($filter['ID'][0]);
		}

		if (!$stageId)
		{
			return $params;
		}
		unset($filter[self::ACTIVITY_STAGE_ID]);

		$params['columnId'] = $stageId;
		$params['filter'] = $this->prepareCounterFilter($stageId, $filter);

		return $params;
	}

	public function calculateTotalForStage(string $stageId, array $filter): int
	{
		$entityManager = EntityManager::resolveByTypeID($this->entityTypeId);
		if (!$entityManager)
		{
			return 0;
		}
		$stagesIds = array_column($this->getStagesList($this->categoryId), 'STATUS_ID');
		if (!in_array($stageId, $stagesIds, true))
		{
			return 0;
		}

		// if today is last week day, STAGE_THIS_WEEK cannot contain items:
		if ($stageId === $this->getStatusIdByCategoryId(self::STAGE_THIS_WEEK, $this->categoryId))
		{
			$lastWeekDay = $this->getLastWeekDay(new DateTime());
			if ($lastWeekDay->getTimestamp() === (new Date())->getTimestamp())
			{
				return 0;
			}
		}

		if ($this->isStageSkippedByActivitiesFilter($stageId, $this->getActivityCounterFilterValue($filter)))
		{
			return 0;
		}

		$stageFilter = $this->prepareCounterFilter($stageId, $filter);

		return $entityManager->getCount([
			'filter' => $stageFilter,
		]);
	}

	public function prepareItemsResult(string $columnId, \CDBResult $rawResult, array $filter = []): \CDBResult
	{
		$items = [];

		while ($item = $rawResult->Fetch())
		{
			$item[self::ACTIVITY_STAGE_ID] = (
				empty($columnId)
					? $this->getStatusIdByCategoryId(self::STAGE_IDLE, $this->categoryId)
					: $columnId
			);
			$items[$item['ID']] = $item;
		}

		// @todo temporary
		if ($columnId === '')
		{
			$itemIds = array_column($items, 'ID');

			if (!empty($itemIds))
			{
				$minDeadlines = $this->fetchMinDeadlinesData($itemIds);
				foreach ($minDeadlines as $minDeadlineItem)
				{
					$activityStageId = $this->getActivityStageIdByDeadlineAndIncoming($minDeadlineItem['MIN_DEADLINE'], $minDeadlineItem['HAS_ANY_INCOMING_CHANEL'] === 'Y');

					$items[$minDeadlineItem['ENTITY_ID']][self::ACTIVITY_STAGE_ID] = $activityStageId;
				}
			}
		}

		$result = new \CDBResult();
		$result->InitFromArray($items);

		return $result;
	}

	private function getActivityStageIdByDeadlineAndIncoming(?DateTime $deadline, bool $hasAnyIncomingChannel): string
	{
		if ($deadline === null && !$hasAnyIncomingChannel)
		{
			return $this->getStatusIdByCategoryId(self::STAGE_IDLE, $this->categoryId);
		}

		$userDeadline = Datetime::createFromTimestamp($deadline->getTimestamp());
		$userDeadline->toUserTime();
		$userDeadlineDate = Date::createFromTimestamp($userDeadline->getTimestamp());

		$dateTime = $this->getUserCurrentDateTime(new DateTime());
		$userCurrentDate = Date::createFromTimestamp($dateTime->getTimestamp());
		if ($userCurrentDate->getTimestamp() > $userDeadlineDate->getTimestamp())
		{
			return $this->getStatusIdByCategoryId(self::STAGE_OVERDUE, $this->categoryId);
		}

		// today
		$userTomorrowDate = $this->getTomorrowDay($dateTime);
		if (($userTomorrowDate->getTimestamp() > $userDeadlineDate->getTimestamp()) || $hasAnyIncomingChannel)
		{
			return $this->getStatusIdByCategoryId(self::STAGE_PENDING, $this->categoryId);
		}

		// this week
		$userThisWeekDate = $this->getLastWeekDay($dateTime);
		if ($userThisWeekDate->getTimestamp() >= $userDeadlineDate->getTimestamp())
		{
			return $this->getStatusIdByCategoryId(self::STAGE_THIS_WEEK, $this->categoryId);
		}

		// next week
		$userNextWeekDate = Date::createFromTimestamp($userThisWeekDate->add('+7 days')->getTimestamp());
		if ($userNextWeekDate->getTimestamp() >= $userDeadlineDate->getTimestamp())
		{
			return $this->getStatusIdByCategoryId(self::STAGE_NEXT_WEEK, $this->categoryId);
		}

		return $this->getStatusIdByCategoryId(self::STAGE_LATER, $this->categoryId);
	}

	protected function prepareCounterFilter(string $stage, array $filter): array
	{
		$counter = $this->getEntityCounterForStage($stage, $this->getActivityCounterFilterValue($filter));
		if (!isset($counter))
		{
			return ['ID' => -1]; // @todo temporary do nothing
		}

		$entity = EntityManager::resolveByTypeID($this->entityTypeId);

		$counterUserIds = [];
		$excludeUsers = false;
		if (isset($filter['ASSIGNED_BY_ID']))
		{
			if (is_array($filter['ASSIGNED_BY_ID']))
			{
				if (in_array('all-users', $filter['ASSIGNED_BY_ID'], true))
				{
					$counterUserIds = [];
					unset($filter['ASSIGNED_BY_ID']);
				}
				elseif (in_array('other-users', $filter['ASSIGNED_BY_ID'], true))
				{
					if (isset($filter['ACTIVITY_COUNTER']))
					{
						$counterUserIds[] = Container::getInstance()->getContext()->getUserId();
						$excludeUsers = true;
					}
					else
					{
						$filter['!ASSIGNED_BY_ID'] = Container::getInstance()->getContext()->getUserId();
					}
					unset($filter['ASSIGNED_BY_ID']);
				}
				else
				{
					$counterUserIds = array_filter($filter['ASSIGNED_BY_ID'], 'is_numeric');
				}
			}
			elseif($filter['ASSIGNED_BY_ID'] > 0)
			{
				$counterUserIds[] = $filter['ASSIGNED_BY_ID'];
			}
		}
		else
		{
			$counterUserIds[] = Container::getInstance()->getContext()->getUserId();
		}

		return array_merge(
			$filter,
			$counter->prepareEntityListFilter(
				[
					'MASTER_ALIAS' => $entity->getDbTableAlias(),
					'MASTER_IDENTITY' => 'ID',
					'USER_IDS' => $counterUserIds,
					'EXCLUDE_USERS' => $excludeUsers,
				]
			)
		);
	}

	private function getEntityCounterForStage(string $stageId, array $activityCounterFilterValues): ?\Bitrix\Crm\Counter\EntityCounter
	{
		$counterExtras = [];
		if (!is_null($this->categoryId))
		{
			$counterExtras['CATEGORY_ID'] = $this->categoryId;
		}
		if ($this->isStageSkippedByActivitiesFilter($stageId, $activityCounterFilterValues))
		{
			return null;
		}

		switch ($stageId)
		{
			case $this->getStatusIdByCategoryId(self::STAGE_OVERDUE, $this->categoryId):
				if (in_array(EntityCounterType::INCOMING_CHANNEL, $activityCounterFilterValues))
				{
					$counterExtras['HAS_ANY_INCOMING_CHANEL'] = true;
				}

				return \Bitrix\Crm\Counter\EntityCounterFactory::create(
					$this->entityTypeId,
					EntityCounterType::OVERDUE,
					0,
					$counterExtras
				);
			case $this->getStatusIdByCategoryId(self::STAGE_PENDING, $this->categoryId):
				$counterType = EntityCounterType::UNDEFINED;
				if (in_array(EntityCounterType::PENDING, $activityCounterFilterValues) || empty($activityCounterFilterValues))
				{
					$counterType |= EntityCounterType::PENDING;
				}

				if (in_array(EntityCounterType::READY_TODO, $activityCounterFilterValues))
				{
					$counterType |= EntityCounterType::READY_TODO;
					$counterExtras['PERIOD_FROM'] = new DateTime();
					$counterExtras['PERIOD_TO'] = new DateTime();
				}
				if (in_array(EntityCounterType::INCOMING_CHANNEL, $activityCounterFilterValues) || empty($activityCounterFilterValues))
				{
					$counterType |= EntityCounterType::INCOMING_CHANNEL;
					if (count($activityCounterFilterValues) === 1) // only INCOMING_CHANNEL type in filter
					{
						$counterExtras['HAS_ANY_INCOMING_CHANEL'] = true;
					}
				}

				return \Bitrix\Crm\Counter\EntityCounterFactory::create(
					$this->entityTypeId,
					// also show INCOMING_CHANNEL with PENDING
					$counterType,
					0,
					array_merge(
						$counterExtras,
						[
							'ONLY_MIN_DEADLINE' => true,
							'ONLY_MIN_INCOMING_CHANNEL' => true,
							'INCOMING_CHANNEL_PERIOD_FROM' => \CCrmDateTimeHelper::getUserDate(new DateTime())
						],
					),
				);
			case $this->getStatusIdByCategoryId(self::STAGE_THIS_WEEK, $this->categoryId):

				$counterType = EntityCounterType::PENDING;
				$lastWeekDay = $this->getLastWeekDay(new DateTime());
				$tomorrow = $this->getTomorrowDay(new DateTime());

				if (in_array(EntityCounterType::READY_TODO, $activityCounterFilterValues))
				{
					$counterType = EntityCounterType::READY_TODO;
				}

				$counterExtras['PERIOD_FROM'] = $tomorrow;
				$counterExtras['PERIOD_TO'] = $lastWeekDay;
				$counterExtras['HAS_ANY_INCOMING_CHANEL'] = false;
				$counterExtras['ONLY_MIN_DEADLINE'] = true;
				return \Bitrix\Crm\Counter\EntityCounterFactory::create(
					$this->entityTypeId,
					$counterType,
					0,
					$counterExtras
				);
			case $this->getStatusIdByCategoryId(self::STAGE_NEXT_WEEK, $this->categoryId):
				$counterType = EntityCounterType::PENDING;
				$lastWeekDay = $this->getLastWeekDay(new DateTime());
				$nextWeekFirstDay = (clone $lastWeekDay)->add('+1 day');
				$nextWeekLastDay = (clone $lastWeekDay)->add('+7 days');

				if (in_array(EntityCounterType::READY_TODO, $activityCounterFilterValues))
				{
					$counterType = EntityCounterType::READY_TODO;
				}

				$counterExtras['PERIOD_FROM'] = $nextWeekFirstDay;
				$counterExtras['PERIOD_TO'] = $nextWeekLastDay;
				$counterExtras['HAS_ANY_INCOMING_CHANEL'] = false;
				$counterExtras['ONLY_MIN_DEADLINE'] = true;
				return \Bitrix\Crm\Counter\EntityCounterFactory::create(
					$this->entityTypeId,
					$counterType,
					0,
					$counterExtras
				);
			case $this->getStatusIdByCategoryId(self::STAGE_IDLE, $this->categoryId):
				return \Bitrix\Crm\Counter\EntityCounterFactory::create(
					$this->entityTypeId,
					EntityCounterType::IDLE,
					0,
					$counterExtras
				);
			case $this->getStatusIdByCategoryId(self::STAGE_LATER, $this->categoryId):
				$counterType = EntityCounterType::PENDING;
				if (in_array(EntityCounterType::READY_TODO, $activityCounterFilterValues))
				{
					$counterType = EntityCounterType::READY_TODO;
				}
				$counterExtras['PERIOD_FROM'] = $this->getLastWeekDay(new DateTime())->add('+8 days');
				$counterExtras['PERIOD_TO'] = (new DateTime())->setDate(9990, 1, 1)->disableUserTime();
				$counterExtras['HAS_ANY_INCOMING_CHANEL'] = false;
				$counterExtras['ONLY_MIN_DEADLINE'] = true;
				return \Bitrix\Crm\Counter\EntityCounterFactory::create(
					$this->entityTypeId,
					$counterType,
					0,
					$counterExtras
				);
		}

		return null;
	}

	private function getActivityCounterFilterValue(array $filter): array
	{
		$counterFilterValues = $filter['ACTIVITY_COUNTER'] ?? [];
		if (!is_array($counterFilterValues))
		{
			$counterFilterValues = EntityCounterType::splitType($counterFilterValues);
		}
		$counterFilterValues = array_map('intval', $counterFilterValues);

		return $counterFilterValues;
	}

	private function isStageSkippedByActivitiesFilter(string $stageId, array $activityCounterFilterValues): bool
	{
		if (empty($activityCounterFilterValues))
		{
			return false; // not skipped because need to show all stages
		}

		if (
			in_array(EntityCounterType::READY_TODO, $activityCounterFilterValues)
			&& $stageId !== $this->getStatusIdByCategoryId(self::STAGE_IDLE, $this->categoryId)
			&& $stageId !== $this->getStatusIdByCategoryId(self::STAGE_OVERDUE, $this->categoryId)
		)
		{
			return false;
		}

		if ($stageId === $this->getStatusIdByCategoryId(self::STAGE_OVERDUE, $this->categoryId))
		{
			if (in_array(EntityCounterType::OVERDUE, $activityCounterFilterValues, true))
			{
				return false;
			}

			if (in_array(EntityCounterType::READY_TODO, $activityCounterFilterValues, true))
			{
				return true;
			}

			if (in_array(EntityCounterType::INCOMING_CHANNEL, $activityCounterFilterValues, true))
			{
				return false;
			}
		}

		if (
			$stageId === $this->getStatusIdByCategoryId(self::STAGE_PENDING, $this->categoryId)
			&& (
				in_array(EntityCounterType::PENDING, $activityCounterFilterValues, true)
				|| in_array(EntityCounterType::INCOMING_CHANNEL, $activityCounterFilterValues, true)
			)
		)
		{
			return false;
		}

		if (
			$stageId === $this->getStatusIdByCategoryId(self::STAGE_IDLE, $this->categoryId)
			&& in_array(EntityCounterType::IDLE, $activityCounterFilterValues, true)
		)
		{
			return false;
		}

		return true;
	}

	private function getTomorrowDay(DateTime $today): Date
	{
		$date = $this->getUserCurrentDateTime($today);
		$date->add('+1 day');

		return Date::createFromTimestamp($date->getTimestamp());
	}

	private function getLastWeekDay(DateTime $daySomewhereInWeek): Date
	{
		$date = $this->getUserCurrentDateTime($daySomewhereInWeek);
		$weekStartDay = (int)Application::getInstance()->getContext()->getCulture()->getWeekStart();
		$todayDay = (int)$date->format('w');
		$daysToAdd = ($todayDay >= $weekStartDay)
			? (6 - $todayDay + $weekStartDay)
			: $weekStartDay - $todayDay - 1
		;
		if ($daysToAdd > 0)
		{
			$date->add('+' . $daysToAdd . ' days');
		}

		return Date::createFromTimestamp($date->getTimestamp());
	}

	private function getUserCurrentDateTime(DateTime $daySomewhereInWeek): DateTime
	{
		$userTimezoneDay = Datetime::createFromTimestamp($daySomewhereInWeek->getTimestamp());
		$userTimezoneDay->toUserTime();
		return DateTime::createFromTimestamp($userTimezoneDay->getTimestamp());
	}

	private function fetchMinDeadlinesData(array $itemIds)
	{
		return EntityUncompletedActivityTable::getList([
			'select' => [
				'ENTITY_ID',
				'MIN_DEADLINE',
				'IS_INCOMING_CHANNEL',
				'HAS_ANY_INCOMING_CHANEL',
			],
			'filter' => [
				'ENTITY_TYPE_ID' => $this->entityTypeId,
				'@ENTITY_ID' => $itemIds,
				'=RESPONSIBLE_ID' => 0,
			],
		]);
	}

	private function getActivityStageIdByEntityId(int $entityId): ?string
	{
		$minDeadlineItem = $this->fetchMinDeadlinesData([$entityId])->fetch();

		return is_array($minDeadlineItem)
			? $this->getActivityStageIdByDeadlineAndIncoming($minDeadlineItem['MIN_DEADLINE'], $minDeadlineItem['HAS_ANY_INCOMING_CHANEL'] === 'Y')
			: null;
	}
}
