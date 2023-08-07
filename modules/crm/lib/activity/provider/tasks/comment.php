<?php

namespace Bitrix\Crm\Activity\Provider\Tasks;

use Bitrix\Crm\Activity\Provider\Base;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\EO_Activity;
use Bitrix\Crm\Integration\Tasks\TaskObject;
use Bitrix\Crm\Integration\Tasks\TaskViewedTable;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\Crm\Timeline\Entity\EO_Timeline;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\Tasks\CategoryType;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Integration\CRM\Timeline\Bindings;
use CCrmActivity;

final class Comment extends Base
{
	use ActivityTrait;

	private const PROVIDER_ID = 'CRM_TASKS_TASK_COMMENT';
	private const PROVIDER_TYPE_ID = 'TASKS_TASK_COMMENT';
	private const SUBJECT = 'COMMENT';
	public static array $cache = [];

	private ActivityController $activityController;

	public function __construct()
	{
		$this->activityController = new ActivityController();
	}

	public static function getId(): string
	{
		return self::PROVIDER_ID;
	}

	public static function getProviderTypeId(): string
	{
		return self::PROVIDER_TYPE_ID;
	}

	public static function getSubject(): string
	{
		return self::SUBJECT;
	}

	public static function getName()
	{
		return Loc::getMessage('TASKS_TASK_INTEGRATION_TASK_COMMENT_V2') ?? Loc::getMessage('TASKS_TASK_INTEGRATION_TASK_COMMENT');
	}

	public static function getTypes(): array
	{
		return [
			[
				'NAME' => self::getName(),
				'PROVIDER_ID' => self::getId(),
				'PROVIDER_TYPE_ID' => self::getProviderTypeId(),
			],
		];
	}

	public function prepareFields(int $taskId, int $responsibleId, ItemIdentifier $identifier, array $timelineParams): array
	{
		$task = TaskObject::getObject($taskId);
		return [
			'ASSOCIATED_ENTITY_ID' => $taskId,
			'BINDINGS' => [
				[
					'OWNER_ID' => $identifier->getEntityId(),
					'OWNER_TYPE_ID' => $identifier->getEntityTypeId(),
				],
			],
			'STORAGE_ELEMENT_IDS' => $timelineParams['TASK_FILE_IDS'],
			'RESPONSIBLE_ID' => $responsibleId,
			'SUBJECT' => $task->getTitle(),
			'SETTINGS' => $timelineParams,
			'IS_INCOMING_CHANNEL' => 'Y',
		];
	}

	public function delete(int $activityId): void
	{
		CCrmActivity::Delete($activityId, false);

		self::invalidateAll();
	}

	public function deleteByItem(int $taskId, ItemIdentifier $identifier): void
	{
		$activity = $this->find($taskId, $identifier);
		if (!is_null($activity))
		{
			$this->delete($activity->getId());
		}
	}


	public function update(EO_Activity $activity, array $timelineParams): void
	{
		CCrmActivity::Update($activity->getId(), [
			'SETTINGS' => $timelineParams,
			'STORAGE_ELEMENT_IDS' => $timelineParams['TASK_FILE_IDS'],
		]);

		self::invalidate($this->getCacheKey($timelineParams['TASK_ID']));
	}

	public function refresh(EO_Activity $activity, Bindings $bindings, array $timelineParams): void
	{
		$taskId = $timelineParams['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return;
		}

		$responsibleId = $activity->getResponsibleId();
		if (is_null($responsibleId))
		{
			return;
		}

		if (isset($timelineParams['TASK_FILE_IDS']))
		{
			CCrmActivity::Update($activity->getId(), [
				'STORAGE_ELEMENT_IDS' => $timelineParams['TASK_FILE_IDS'],
			]);

			self::invalidate($this->getCacheKey($taskId));
		}
		foreach ($bindings as $identifier)
		{
			$this->activityController->sendPullEventOnUpdateScheduled($identifier, $activity->collectValues());

		}
	}

	public function getAssociatedTimelineEntry(EO_Activity $activity): ?EO_Timeline
	{
		$query = TimelineTable::query();
		$query
			->setSelect(['ID'])
			->where('ASSOCIATED_ENTITY_TYPE_ID', \CCrmOwnerType::Activity)
			->where('ASSOCIATED_ENTITY_ID', $activity->getId())
			->where('TYPE_CATEGORY_ID', CategoryType::COMMENT_ADD)
			->setOrder(['CREATED' => 'DESC'])
			->setLimit(1)
		;

		return $query->exec()->fetchObject();
	}

	public function find(int $taskId, ItemIdentifier $identifier): ?EO_Activity
	{
		if ($taskId <= 0)
		{
			return null;

		}

		$key = $this->getCacheKey($taskId);
		if (isset(self::$cache[$key]))
		{
			return self::$cache[$key];
		}

		$task = TaskObject::getObject($taskId);
		if (is_null($task))
		{
			return null;
		}

		try
		{
			$query = ActivityTable::query();
			$query
				->addSelect('ID')
				->addSelect('TYPE_ID')
				->addSelect('PROVIDER_ID')
				->addSelect('PROVIDER_TYPE_ID')
				->addSelect('COMPLETED')
				->addSelect('RESPONSIBLE_ID')
				->addSelect('SETTINGS')
				->addSelect('STORAGE_TYPE_ID')
				->addSelect('STORAGE_ELEMENT_IDS')
				->where('ASSOCIATED_ENTITY_ID', $taskId)
				->where('PROVIDER_ID', self::getId())
				->where('OWNER_ID', $identifier->getEntityId())
				->where('OWNER_TYPE_ID', $identifier->getEntityTypeId())
			;

			self::$cache[$key] = $query->exec()->fetchObject();
		}
		catch (SystemException $exception)
		{
			return null;
		}

		return self::$cache[$key];
	}

	public static function updateAssociatedEntity($entityId, array $activity, array $options = []): Result
	{
		$isCompleted = $activity['COMPLETED'] === 'Y';
		if ($isCompleted)
		{
			$taskId = (int)($activity['ASSOCIATED_ENTITY_ID'] ?? null);
			$responsibleId = (int)($activity['RESPONSIBLE_ID'] ?? null);

			if ($taskId > 0 && $responsibleId > 0)
			{
				TaskViewedTable::set($taskId, $responsibleId);
			}
		}

		return new Result();
	}

	public function createActivity(string $typeId, array $fields, array $options = []): Result
	{
		$taskId = $fields['ASSOCIATED_ENTITY_ID'];
		$userId = $fields['RESPONSIBLE_ID'];
		if (!$this->canCreateActivity($taskId, $userId))
		{
			return new Result();
		}

		return parent::createActivity($typeId, $fields, $options);
	}

	private function canCreateActivity(int $taskId, int $userId): bool
	{
		$task = TaskObject::getObject($taskId);
		if (is_null($task))
		{
			return false;
		}

		return TaskObject::isMember($taskId, $userId);
	}
}