<?php


namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Activity\Entity;
use Bitrix\Crm\Activity\Provider\ToDo;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Kanban\EntityActivityDeadline;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Filter\DataProvider;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

trait ActivityTrait
{
	protected ?EntityActivities $entityActivities = null;

	public function getStagesList(): array
	{
		return $this->getEntityActivities()->getStagesList($this->getCategoryId());
	}

	protected function changeStageByActivity(string $stageId, int $id): Result
	{
		if (strpos($stageId, ':'))
		{
			[$prefix, $statusTypeId] = explode(':', $stageId);
		}
		else
		{
			$statusTypeId = $stageId;
		}

		$ownerTypeId = \CCrmOwnerType::ResolveID($this->getTypeName());
		switch ($statusTypeId)
		{
			/** @noinspection PhpMissingBreakStatementInspection */
			case EntityActivities::STAGE_OVERDUE:
				if ($this->hasOverdueActivities($ownerTypeId, $id))
				{
					return new Result();
				}
			case EntityActivities::STAGE_IDLE:
				$activities = $this->getAllActivities($ownerTypeId, $id);
				$result = new Result();

				if (empty($activities))
				{
					return $result;
				}

				$message = Loc::getMessage('CRM_KANBAN_AT_VIEW_MODE_MOVE_ITEM_TO_COLUMN_BLOCKED');

				return $result->addError(new Error($message));
			case EntityActivities::STAGE_PENDING:
			case EntityActivities::STAGE_THIS_WEEK:
			case EntityActivities::STAGE_NEXT_WEEK:
			case EntityActivities::STAGE_LATER:
				return $this->shiftOverdueActivities($ownerTypeId, $id, $statusTypeId);
		}

		throw new ArgumentException('StatusTypeId: ' . $statusTypeId . ' not known');
	}

	protected function hasOverdueActivities(int $ownerTypeId, int $ownerId): bool
	{
		$currentDate = (new DateTime())
			->toUserTime()
			->setTime(0, 0)
		;
		$activities = $this->getAllActivities($ownerTypeId, $ownerId);
		foreach ($activities as $activity)
		{
			$datetime = DateTime::createFromUserTime($activity['DEADLINE']);
			if ($datetime < $currentDate)
			{
				return true;
			}
		}

		return false;
	}

	protected function getAllActivities(int $ownerTypeId, int $ownerId): array
	{
		$result = $this->getActivitiesList($ownerTypeId, $ownerId);
		$activities = [];

		while($activity = $result->Fetch())
		{
			$activities[] = $activity;
		}

		return $activities;
	}

	protected function getActivitiesList(int $ownerTypeId, int $ownerId, ?int $limit = null)
	{
		$filter = [
			'=COMPLETED' => 'N',
			'=RESPONSIBLE_ID' => Container::getInstance()->getContext()->getUserId(),
			'BINDINGS' => [
				[
					'OWNER_TYPE_ID' => $ownerTypeId,
					'OWNER_ID' => $ownerId,
				],
			],
		];

		$arNavStartParams = ($limit ? ['nPageSize' => $limit] : false);

		return \CCrmActivity::GetList(
			['DEADLINE' => 'ASC'],
			$filter,
			false,
			$arNavStartParams,
			[
				'ID',
				'DEADLINE',
				'IS_INCOMING_CHANNEL',
			],
		);
	}

	protected function shiftOverdueActivities(int $ownerTypeId, int $ownerId, string $statusTypeId): Result
	{
		$result = new Result();

		if (!\CCrmActivity::CheckUpdatePermission($ownerTypeId, $ownerId))
		{
			return $result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_ACTIVITY_COMPLETE_FORBIDDEN')));
		}

		$activityDeadline = null;

		$activity = $this->getActivityData($ownerTypeId, $ownerId);
		if ($activity)
		{
			$hasOnlyIncomingActivities = false;
			$activityDeadline = DateTime::createFromUserTime($activity['DEADLINE']);
		}
		else
		{
			$hasOnlyIncomingActivities = true;
		}

		if (!$activityDeadline)
		{
			$activityDeadline = new DateTime();
		}

		$deadline = $this->getDeadline($statusTypeId, $activityDeadline);
		if ($deadline)
		{
			if ($hasOnlyIncomingActivities)
			{
				$result = (new Entity\ToDo(new ItemIdentifier($ownerTypeId, $ownerId), new ToDo\ToDo()))
					->createWithDefaultSubjectAndDescription($deadline);
			}
			else
			{
				$activityFields = \CCrmActivity::GetByID($activity['ID']);
				\CCrmActivity::PostponeToDate($activityFields, $deadline, true);
			}
		}

		return $result;
	}

	protected function getActivityData(int $ownerTypeId, int $ownerId): ?array
	{
		return $this->getNearestActivity($ownerTypeId, $ownerId);
	}

	protected function getNearestActivity(int $ownerTypeId, int $ownerId): ?array
	{
		$activity = $this->getActivitiesList($ownerTypeId, $ownerId, 1)->Fetch();
		if (empty($activity) || $activity['IS_INCOMING_CHANNEL'] === 'Y')
		{
			return null;
		}

		return $activity;
	}

	/**
	 * @param string $statusTypeId
	 * @param DateTime $activityDeadline
	 * @return DateTime|null
	 */
	public function getDeadline(string $statusTypeId, DateTime $activityDeadline): ?DateTime
	{
		return (new EntityActivityDeadline())
			->setCurrentDeadline($activityDeadline)
			->getDeadline($statusTypeId)
		;
	}

	protected function getEntityActivities(): EntityActivities
	{
		if (!$this->entityActivities)
		{
			$this->entityActivities = new EntityActivities($this->getTypeId(), $this->getCategoryId());
		}

		return $this->entityActivities;
	}

	public function getStageFieldName(): string
	{
		return EntityActivities::ACTIVITY_STAGE_ID;
	}

	public function getDbStageFieldName(): string
	{
		return 'STAGE_ID';
	}

	public function fillStageTotalSums(array $filter, array $runtime, array &$stages): void
	{
		foreach ($stages as &$stage)
		{
			$stage['count'] = $this->getEntityActivities()->calculateTotalForStage($stage['id'], $filter);
		}
	}

	public function getItems(array $parameters): \CDBResult
	{
		$parameters = $this->getEntityActivities()->prepareItemsListParams($parameters);

		$columnId = $parameters['columnId'] ?? '';
		$filter = $parameters['filter'] ?? [];

		return $this->getEntityActivities()->prepareItemsResult($columnId, parent::getItems($parameters), $filter);
	}

	public function applyCountersFilter(array &$filter, DataProvider $provider): void
	{
		// do nothing, $filter['ACTIVITY_COUNTER'] will be applied in $this->getItems()
	}

	// temporary not used, but maybe still useful
	/*protected function completeAllActivities(int $ownerTypeId, int $ownerId): Result
	{
		$result = new Result();

		$userPermissions = Container::getInstance()->getUserPermissions();
		if (!\CCrmActivity::CheckCompletePermission($ownerTypeId, $ownerId, $userPermissions))
		{
			return $result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_ACTIVITY_COMPLETE_FORBIDDEN')));
		}

		$activityIds = $this->getEntityActivityIds($ownerTypeId, $ownerId);
		foreach ($activityIds as $activityId)
		{
			\CCrmActivity::Complete($activityId, true, ['REGISTER_SONET_EVENT' => true]);
		}

		return $result;
	}

	protected function getEntityActivityIds(int $ownerTypeId, int $ownerId): array
	{
		$result = $this->getActivitiesList($ownerTypeId, $ownerId);

		$ids = [];

		while($item = $result->Fetch())
		{
			$ids[] = $item['ID'];
		}

		return $ids;
	}*/
}
