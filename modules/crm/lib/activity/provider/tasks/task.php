<?php

namespace Bitrix\Crm\Activity\Provider\Tasks;

use Bitrix\Crm\Activity\Provider\Base;
use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Automation\Trigger\TaskStatusTrigger;
use Bitrix\Crm\Badge;
use Bitrix\Crm\EO_Activity;
use Bitrix\Crm\Integration\Tasks\Task2ActivityPriority;
use Bitrix\Crm\Integration\Tasks\Task2ActivityStatus;
use Bitrix\Crm\Integration\Tasks\TaskAccessController;
use Bitrix\Crm\Integration\Tasks\TaskHandler;
use Bitrix\Crm\Integration\Tasks\TaskObject;
use Bitrix\Crm\Integration\Tasks\TaskPathMaker;
use Bitrix\Crm\Integration\Tasks\TaskSliderFactory;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\Entity\TimelineBindingTable;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Integration\CRM\Timeline\Bindings;
use CCrmActivity;
use CCrmDateTimeHelper;
use CCrmLiveFeed;
use CCrmLiveFeedEvent;
use CCrmOwnerType;
use CCrmOwnerTypeAbbr;
use CSocNetLog;

final class Task extends Base
{
	use ActivityTrait;

	private const PROVIDER_ID = 'CRM_TASKS_TASK';
	private const PROVIDER_TYPE_ID = 'TASKS_TASK';
	private const SUBJECT = 'TASK';
	private const TASK_CRM_FIELD = 'UF_CRM_TASK';
	private const UPDATE_OPTIONS = ['SKIP_ASSOCIATED_ENTITY' => true, 'REGISTER_SONET_EVENT' => true];

	public static array $cache = [];

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
		return Loc::getMessage('TASKS_TASK_INTEGRATION_TASK_V2') ?? Loc::getMessage('TASKS_TASK_INTEGRATION_TASK');
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

	public static function getDefaultPingOffsets(array $params = []): array
	{
		return TodoPingSettingsProvider::DEFAULT_OFFSETS;
	}

	public function delete(int $activityId): void
	{
		CCrmActivity::Delete($activityId, false, true, ['MOVED_TO_RECYCLE_BIN' => true]);
		TimelineEntry::deleteByAssociatedEntity(CCrmOwnerType::Activity, $activityId);

		static::invalidateAll();
	}

	public function updateFiles(EO_Activity $activity, array $timelineParams): void
	{
		if (isset($timelineParams['TASK_FILE_IDS']))
		{
			$this->update($activity->getId(), [
				'STORAGE_ELEMENT_IDS' => $timelineParams['TASK_FILE_IDS'],
			]);
		}

		self::invalidate($this->getCacheKey($timelineParams['TASK_ID']));
	}

	public function updateDeadline(int $taskId, array $timelineParams): void
	{
		$activity = $this->find($taskId);
		if (!$activity)
		{
			return;
		}

		$desiredDeadline = (isset($timelineParams['DEADLINE']) && $timelineParams['DEADLINE'] instanceof DateTime)
			? $timelineParams['DEADLINE']->toString()
			: CCrmDateTimeHelper::GetMaxDatabaseDate(false);

		$this->update(
			$activity->getId(),
			[
				'END_TIME' => $desiredDeadline,
				'PROVIDER_ID' => $activity->getProviderId(),
			]
		);
	}

	public function prepareFields(int $taskId, Bindings $bindings, array $timelineParams): array
	{
		$bindings = $bindings->toArray('OWNER_ID', 'OWNER_TYPE_ID');
		$task = TaskObject::getObject($taskId);
		$status = (int)$task->getStatus();
		$fields = [
			'ASSOCIATED_ENTITY_ID' => $taskId,
			'BINDINGS' => $bindings,
			'RESPONSIBLE_ID' => $task->getResponsibleMemberId(),
			'SUBJECT' => $task->getTitle(),
			'SETTINGS' => $timelineParams,
			'DESCRIPTION' => $task->getDescription(),
			'START_TIME' => is_null($task->getStartDatePlan()) ? '' : $task->getStartDatePlan()->toString(),
			'END_TIME' => is_null($task->getEndDatePlan()) ? '' : $task->getEndDatePlan()->toString(),
			'PRIORITY' => Task2ActivityPriority::getPriority((int)$task->getPriority()),
			'COMPLETED' => $status === TaskActivityStatus::TASKS_STATE_COMPLETED || $status === TaskActivityStatus::TASKS_STATE_SUPPOSEDLY_COMPLETED,
			'AUTHOR_ID' => $timelineParams['AUTHOR_ID'],
		];

		if (!empty($timelineParams['TASK_FILE_IDS']))
		{
			$fields['STORAGE_ELEMENT_IDS'] = $timelineParams['TASK_FILE_IDS'];
		}

		return $fields;
	}


	public function updateDescription(array $timelineParams): void
	{
		$taskId = $timelineParams['TASK_ID'];
		if (is_null($taskId))
		{
			return;
		}

		$task = TaskObject::getObject($taskId);
		if (is_null($task))
		{
			return;
		}

		$description = $task->getDescription();
		if (is_null($description))
		{
			return;
		}

		$activity = $this->find($taskId);
		if (is_null($activity))
		{
			return;
		}

		if ($activity->getDescription() === $description)
		{
			return;
		}

		$this->update($activity->getId(),[
			'DESCRIPTION' => $description,
		]);
	}
	public function setEndTime(?EO_Activity $activity, ?DateTime $time): void
	{
		if (is_null($activity))
		{
			return;
		}

		$time = is_null($time) ? '' : $time->toString();
		$this->update($activity->getId(),[
			'END_TIME' => $time
		]);
	}


	public function updateByTask(array $timelineParams): void
	{
		$taskId = $timelineParams['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return;
		}

		$task = TaskObject::getObject($taskId);
		if (is_null($task))
		{
			return;
		}

		$activity = $this->find($taskId);
		if (is_null($activity))
		{
			return;
		}

		$updateData = [];
		$activityStartTime = is_null($activity->getStartTime()) ? '' : $activity->getStartTime()->toString();
		$taskStartDatePlan = is_null($task->getStartDatePlan()) ? '' : $task->getStartDatePlan()->toString();

		if ($activityStartTime !== $taskStartDatePlan)
		{
			$updateData['START_TIME'] = $taskStartDatePlan;
		}

		if ($activity->getResponsibleId() !== $task->getResponsibleMemberId())
		{
			$updateData['RESPONSIBLE_ID'] = $task->getResponsibleMemberId();
		}

		if ($activity->getSubject() !== $task->getTitle())
		{
			$updateData['SUBJECT'] = $task->getTitle();
		}

		if ($activity->getPriority() !== (int)$task->getPriority())
		{
			$updateData['PRIORITY'] = Task2ActivityPriority::getPriority((int)$task->getPriority());
		}

		if (!empty($updateData))
		{
			$updateData['ASSOCIATED_ENTITY_ID'] = $task->getId();
			$this->update($activity->getId(), $updateData);
		}
	}

	public function find(int $taskId, $force = false): ?EO_Activity
	{
		if ($taskId <= 0)
		{
			return null;

		}

		$key = $this->getCacheKey($taskId);
		if (isset(static::$cache[$key]) && !$force)
		{
			return static::$cache[$key];
		}

		$task = TaskObject::getObject($taskId, true);
		if (is_null($task))
		{
			return null;
		}

		try
		{
			$query = self::prepareQuery($taskId);
			self::$cache[$key] = $query->exec()->fetchObject();
		}
		catch (SystemException $exception)
		{
			return null;
		}

		return self::$cache[$key];
	}

	private static function prepareQuery(int $taskId)
	{
		$query = ActivityTable::query();
		$query
			->addSelect('ID')
			->addSelect('TYPE_ID')
			->addSelect('PROVIDER_ID')
			->addSelect('PROVIDER_TYPE_ID')
			->addSelect('COMPLETED')
			->addSelect('SUBJECT')
			->addSelect('RESPONSIBLE_ID')
			->addSelect('SETTINGS')
			->addSelect('STORAGE_TYPE_ID')
			->addSelect('STORAGE_ELEMENT_IDS')
			->addSelect('CREATED')
			->addSelect('LAST_UPDATED')
			->addSelect('START_TIME')
			->addSelect('END_TIME')
			->addSelect('PRIORITY')
			->where('ASSOCIATED_ENTITY_ID', $taskId)
			->where('PROVIDER_ID', self::getId())
			->where('PROVIDER_TYPE_ID', self::getProviderTypeId())
		;

		return $query;
	}
	public static function checkFields($action, &$fields, $id, $params = null)
	{
		$result = new Result();
		$taskId = $fields['ASSOCIATED_ENTITY_ID'] ?? null;
		if (is_null($taskId))
		{
			return $result;
		}

		$task = TaskObject::getObject($taskId);
		if (is_null($task))
		{
			return $result;
		}

		$deadline = $task->getDeadline();
		if (!is_null($deadline))
		{
			$fields['DEADLINE'] = $deadline->toString();
		}
		else
		{
			$fields['DEADLINE'] = CCrmDateTimeHelper::GetMaxDatabaseDate(false);
		}

		return $result;
	}

	public function updateStatus(int $taskId, string $desiredStatus): void
	{
		$activity = $this->find($taskId, true);
		if (!$activity)
		{
			// nothing to update
			return;
		}

		$settings = $activity->getSettings();
		if (!is_array($settings) || !isset($settings['ACTIVITY_STATUS']))
		{
			// no status
			return;
		}

		$currStatus = $settings['ACTIVITY_STATUS'];
		$taskActivityStatus = new TaskActivityStatus();
		if (!$taskActivityStatus->isAllowedStatusChange($desiredStatus, $currStatus))
		{
			// can't update due to task activity status logic
			return;
		}

		$settings['ACTIVITY_STATUS'] = $desiredStatus;
		$this->update(
			$activity->getId(),
			[
				'SETTINGS' => $settings,
			]
		);
	}

	public static function updateAssociatedEntity($entityId, array $activity, array $options = []): Result
	{
		$result = new Result();

		$taskId = (int)$entityId;
		$responsibleId = (int)($activity['RESPONSIBLE_ID'] ?? null);

		if ($taskId <= 0 || $responsibleId <= 0)
		{
			$result->addError(new Error('Wrong task or responsible id.'));
			return $result;
		}

		$task = TaskObject::getObject($taskId);
		if (is_null($task))
		{
			return $result;
		}
		$bindings = $activity['BINDINGS'] ?? [];
		$crmFields = self::prepareBindingsToTask($bindings);
		$taskCrmFields = $task->getCrmFields();

		$updateData = [];

		$status = (int)$task->getStatus();
		if (
			$status !== TaskActivityStatus::TASKS_STATE_COMPLETED
			&& $status !== TaskActivityStatus::TASKS_STATE_SUPPOSEDLY_COMPLETED
			&& $activity['COMPLETED'] === 'Y'
			&& $task->getTaskControl()
		)
		{
			if ($task->getResponsibleMemberId() === $task->getCreatedByMemberId())
			{
				$updateData['STATUS'] = TaskActivityStatus::TASKS_STATE_COMPLETED;
			}
			else
			{
				$updateData['STATUS'] = TaskActivityStatus::TASKS_STATE_SUPPOSEDLY_COMPLETED;
			}
		}
		elseif (
			$status !== TaskActivityStatus::TASKS_STATE_COMPLETED
			&& $status !== TaskActivityStatus::TASKS_STATE_SUPPOSEDLY_COMPLETED
			&& $activity['COMPLETED'] === 'Y'
			&& !$task->getTaskControl()
		)
		{
			$updateData['STATUS'] = TaskActivityStatus::TASKS_STATE_COMPLETED;
		}
		elseif (
			$status === TaskActivityStatus::TASKS_STATE_COMPLETED
			&& $activity['COMPLETED'] === 'N'
		)
		{
			$updateData['STATUS'] = TaskActivityStatus::TASKS_STATE_PENDING;
		}
		elseif (
			$status === TaskActivityStatus::TASKS_STATE_SUPPOSEDLY_COMPLETED
			&& $activity['COMPLETED'] === 'N'
		)
		{
			$updateData['STATUS'] = TaskActivityStatus::TASKS_STATE_PENDING;
		}

		if (
			!empty(array_diff($crmFields, $taskCrmFields))
			|| !empty(array_diff($taskCrmFields, $crmFields))
		)
		{
			$updateData[self::TASK_CRM_FIELD] = $crmFields;
		}

		if ($task->getTitle() !== $activity['SUBJECT'])
		{
			$updateData['TITLE'] = $activity['SUBJECT'];
		}

		if ($task->getDescription() !== $activity['DESCRIPTION'])
		{
			$updateData['DESCRIPTION'] = $activity['DESCRIPTION'];
		}

		if ($task->getResponsibleMemberId() !== (int)$activity['RESPONSIBLE_ID'])
		{
			$updateData['RESPONSIBLE_ID'] = $activity['RESPONSIBLE_ID'];
		}

		$startDatePlan = is_null($task->getStartDatePlan()) ? '' : $task->getStartDatePlan()->toString();
		if ($activity['START_TIME'] !== $startDatePlan)
		{
			$updateData['START_DATE_PLAN'] = $activity['START_TIME'];
		}

		$endDatePlan = is_null($task->getEndDatePlan()) ? '' : $task->getEndDatePlan()->toString();
		if ($activity['END_TIME'] !== $endDatePlan)
		{
			$updateData['END_DATE_PLAN'] = $activity['END_TIME'];
		}

		if (!empty($updateData))
		{
			$executorId = (int)($options['EXECUTOR_ID'] ?? null);
			$executorId = $executorId > 0 ? $executorId : $responsibleId;

			$handler = TaskHandler::getHandler($executorId)
				->withAutoClose();

			try
			{
				$task = $handler->update($taskId, $updateData);

				if (
					$task !== false
					&& $task->isCompleted()
					&& ($updateData['STATUS'] ?? null) === TaskActivityStatus::TASKS_STATE_COMPLETED
				)
				{
					self::onTaskComplete((int)($activity['OWNER_TYPE_ID'] ?? null));
				}
			}
			catch (\Exception $exception)
			{
				$result->addError(new Error($exception->getMessage()));
			}
		}

		return $result;
	}

	private static function onTaskComplete(int $ownerTypeId): void
	{
		$ownerName = strtolower(\CCrmOwnerType::ResolveName($ownerTypeId));

		if (empty($ownerName))
		{
			return;
		}

		$analyticsEvent = new AnalyticsEvent('task_complete', 'tasks', 'task_operations');
		$analyticsEvent
			->setType('task')
			->setElement('complete_button')
			->setSection('crm')
			->setSubSection($ownerName)
			->send()
		;
	}

	// public function updateEndTime(array $timelineParams): void
	// {
	// 	$taskId = $timelineParams['TASK_ID'] ?? null;
	// 	if (is_null($taskId))
	// 	{
	// 		return;
	// 	}
	//
	// 	$task = TaskObject::getObject($taskId);
	// 	if (is_null($task))
	// 	{
	// 		return;
	// 	}
	//
	// 	$closedDate = $task->getClosedDate();
	// }

	public function complete(EO_Activity $activity): void
	{
		CCrmActivity::Complete($activity->getId(), true, self::UPDATE_OPTIONS);
		self::invalidateAll();
	}

	public function renew(int $activityId): void
	{
		$this->update(
			$activityId,
			[
				'COMPLETED' => 'N',
			],
		);

		self::invalidateAll();
	}

	public function getCompletedActivityEntryId(int $activityId, int $taskId): int
	{
		$completedActivityQuery = TimelineTable::query();
		$completedActivityQuery
			->setSelect(['ID'])
			->where('SOURCE_ID', $taskId)
			->where('ASSOCIATED_ENTITY_ID', $activityId)
			->where('ASSOCIATED_ENTITY_CLASS_NAME', self::getId())
			->where('TYPE_ID', TimelineType::ACTIVITY)
			->where('TYPE_CATEGORY_ID', \CCrmActivityType::Provider)
			->where('ASSOCIATED_ENTITY_TYPE_ID', \CCrmActivityType::Provider)
			->setLimit(1)
		;

		$completedActivity = $completedActivityQuery->exec()->fetchObject();
		if (is_null($completedActivity))
		{
			return 0;
		}

		return $completedActivity->getId();
	}
	public static function syncBadges(int $activityId, array $activityFields, array $bindings): void
	{
		$taskStatus = new TaskActivityStatus();
		$status = $activityFields['SETTINGS']['ACTIVITY_STATUS'] ?? null;
		if (!$status || !$taskStatus->isStatusValid($status))
		{
			return;
		}

		$badge = Container::getInstance()->getBadge(
			Badge\Type\TaskStatus::TASK_STATUS_TYPE,
			$status,
		);

		$sourceIdentifier = new Badge\SourceIdentifier(
			Badge\SourceIdentifier::CRM_OWNER_TYPE_PROVIDER,
			CCrmOwnerType::Activity,
			$activityId,
		);

		foreach ($bindings as $singleBinding)
		{
			$itemIdentifier = new ItemIdentifier((int)$singleBinding['OWNER_TYPE_ID'], (int)$singleBinding['OWNER_ID']);
			$badge->unbindWithAnyValue($itemIdentifier, $sourceIdentifier);
			$badge->upsert($itemIdentifier, $sourceIdentifier);
		}
	}

	public static function canUseLiveFeedEvents($providerTypeId = null): bool
	{
		return true;
	}

	public static function createLiveFeedLog($entityId, array $activity, array &$logFields)
	{
		// return \Bitrix\Crm\Activity\Provider\Task::createLiveFeedLog($entityId, $activity, $logFields);
		$taskId = (int)$entityId;
		$activityId = (int)($activity['ID'] ?? null);
		if (
			$taskId <= 0
			|| !Loader::includeModule('tasks')
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return false;
		}

		$eventId = 0;
		$task = \CTasks::GetByID($taskId)->Fetch();
		if ($task === false)
		{
			return false;
		}

		if (!empty($task['UF_TASK_WEBDAV_FILES']))
		{
			$logFields['UF_SONET_LOG_DOC'] = $task['UF_TASK_WEBDAV_FILES'];
		}

		$log = CSocNetLog::getList([], [
				'EVENT_ID' => 'tasks',
				'SOURCE_ID' => $task['ID'],
			],
			['ID']
		)->Fetch();

		if ($log !== false)
		{
			$eventId = (int)CCrmLiveFeed::convertTasksLogEvent([
				'LOG_ID' => $log['ID'],
				'ACTIVITY_ID' => $activityId,
				'PARENTS' => (!empty($logFields['PARENTS']) ? $logFields['PARENTS'] : []),
			]);
		}

		elseif (!empty($task['GROUP_ID']))
		{
			$sites = [];
			$result = \CSocNetGroup::getSite($task['GROUP_ID']);
			if ($result !== false)
			{
				while ($site = $result->fetch())
				{
					$sites[] = $site['LID'];
				}
			}
			if (!empty($sites))
			{
				$logFields['SITE_ID'] = $sites;
			}
		}

		if ($eventId === 0)
		{
			$logFields['USER_ID'] = $task['CREATED_BY'];
			$eventId = CCrmLiveFeed::createLogEvent(
				$logFields,
				CCrmLiveFeedEvent::Add,
				['ACTIVITY_PROVIDER_ID' => 'TASKS']
			);
		}

		if ($eventId > 0)
		{
			$taskParticipant = array_unique(
				array_merge(
					[$task['CREATED_BY'], $task['RESPONSIBLE_ID']],
					$task['ACCOMPLICES'] ?? [],
					$task['AUDITORS'] ?? []
				)
			);

			$socnetRights = array_map(
				static fn(int $userId): string => 'U' . $userId,
				$taskParticipant
			);

			if (!empty($task['GROUP_ID']))
			{
				$socnetRights = array_merge(
					$socnetRights,
					['SG' . $task['GROUP_ID']]
				);
			}

			\CSocNetLogRights::DeleteByLogID($eventId);
			\CSocNetLogRights::Add($eventId, $socnetRights);
		}

		return $eventId;
	}

	public function updateBindings(Bindings $newBindings, Bindings $previousBindings, array $timelineParams): void
	{
		$taskId = $timelineParams['TASK_ID'];
		$activity = $this->find($taskId, true);
		$task = TaskObject::getObject($taskId);
		if (is_null($task))
		{
			return;
		}

		if (is_null($activity))
		{
			$timelineParams['ACTIVITY_STATUS'] = Task2ActivityStatus::getStatus((int)$task->getStatus());
			$result = $this->createActivity(
				self::getProviderTypeId(),
				$this->prepareFields($taskId, $newBindings, $timelineParams),
			);
			$activityId = $result->getData()['id'] ?? null;
		}
		else
		{
			$activityId = $activity->getId();
		}

		if (is_null($activityId))
		{
			return;
		}

		if ($newBindings->isEmpty())
		{
			$this->delete($activityId);
		}
		elseif (!$newBindings->isEquals($previousBindings))
		{
			$this->update($activityId, [
				'BINDINGS' => $newBindings->toArray('OWNER_ID', 'OWNER_TYPE_ID'),
			]);

			$ids = $this->getIdsToDelete($previousBindings->getDiff($newBindings), $taskId);
			foreach ($ids as $id)
			{
				TimelineBindingTable::deleteByOwner($id);
			}
		}
	}

	public function update(int $activityId, array $fields): void
	{
		CCrmActivity::Update($activityId, $fields, false, true, self::UPDATE_OPTIONS);
	}

	public function getIdsToDelete(Bindings $toRemove, int $taskId): array
	{
		$timelineEntryIdsByTaskId = [];
		foreach ($toRemove as $identifier)
		{
			$query = TimelineTable::query();
			$query
				->setSelect(['ID', 'BINDINGS'])
				->where('BINDINGS.ENTITY_ID', $identifier->getEntityId())
				->where('BINDINGS.ENTITY_TYPE_ID', $identifier->getEntityTypeId())
				->where('TYPE_ID', TimelineType::TASK)
				->where('SOURCE_ID', $taskId)
			;
			$timelineEntryIdsByTaskId = $query->exec()->fetchCollection()->getIdList();
		}

		return $timelineEntryIdsByTaskId;
	}

	public static function deleteAssociatedEntity($entityId, array $activity, array $options = []): Result
	{
		$result = new Result();
		if (isset($options['SKIP_TASKS']) && $options['SKIP_TASKS'] === true)
		{
			return $result;
		}

		$taskId = (int)$entityId;
		$responsibleId = (int)($activity['RESPONSIBLE_ID'] ?? null);
		$activityId = (int)($activity['ID'] ?? null);
		if ($taskId <= 0 || $responsibleId <=0 || $activityId <=0)
		{
			$result->addError(new Error('Wrong task or responsible or activity id.'));
			return $result;
		}

		TimelineEntry::deleteByAssociatedEntity(CCrmOwnerType::Activity, $activity['ID']);

		$bindings = $activity['BINDINGS'] ?? [];
		$commentProvider = new Comment();
		foreach ($bindings as $item)
		{
			$slaveActivity = $commentProvider->find(
				$taskId,
				new ItemIdentifier($item['OWNER_TYPE_ID'], $item['OWNER_ID'])
			);

			if (!is_null($slaveActivity))
			{
				$commentProvider->delete($slaveActivity->getId());
			}
		}

		return $result;
	}

	public static function rebindAssociatedEntity($entityId, $oldOwnerTypeId, $newEntityTypeId, $oldOwnerId, $newOwnerId): Result
	{
		$result = new Result();
		$taskId = (int)$entityId;
		if ($taskId <= 0)
		{
			$result->addError(new Error('Wrong task id.'));
			return $result;
		}

		$task = TaskObject::getObject($taskId, true);
		if(is_null($task))
		{
			$result->addError(new Error('No task data.'));
			return $result;
		}

		try
		{
			$entityBindings = $task->getCrmFields();
			$entityIndex = -1;
			$length = count($entityBindings);

			for($i = 0; $i < $length; ++$i)
			{
				$entityInfo = CCrmOwnerType::ParseEntitySlug($entityBindings[$i]);
				if(
					is_array($entityInfo)
					&& $entityInfo['ENTITY_TYPE_ID'] === $oldOwnerTypeId
					&& $entityInfo['ENTITY_ID'] === $oldOwnerId
				)
				{
					$entityIndex = $i;
					break;
				}
			}

			if($entityIndex >= 0)
			{
				$entityBindings[$entityIndex] = CCrmOwnerTypeAbbr::ResolveByTypeID($newEntityTypeId).'_'.$newOwnerId;
				$handler = TaskHandler::getHandler();
				$handler->update($taskId, [
					self::TASK_CRM_FIELD => $entityBindings
				]);
			}
		}
		catch (\Exception $exception)
		{
			$result->addError(new Error($exception->getMessage()));
		}

		return $result;
	}


	public static function processRestorationFromRecycleBin(array $activityFields, array $params = null): Result
	{
		$result = new Result();
		$taskId = (int)($activityFields['ASSOCIATED_ENTITY_ID'] ?? null);
		if ($taskId <= 0)
		{
			return $result;
		}

		$bindings = $activityFields['BINDINGS'] ?? [];
		if (empty($bindings))
		{
			return $result;
		}

		$task = TaskObject::getObject($taskId);
		if (is_null($task))
		{
			return $result;
		}

		try
		{
			$crmFields = array_unique(array_merge($task->getCrmFields(), self::prepareBindingsToTask($bindings)));

			TaskHandler::getHandler()->update($taskId,[
				self::TASK_CRM_FIELD => $crmFields
			]);

			$activity = self::prepareQuery($taskId)->exec()->fetchObject();

			$provider = new self();
			$provider->deleteLogEntry((int)$activity?->getId(), $taskId);
			$provider->update((int)$activity?->getId(), ['STORAGE_ELEMENT_IDS' => $activityFields['STORAGE_ELEMENT_IDS']]);

			$result->setData(['entityId' => (int)$activity?->getId()]);
		}
		catch (\Exception $exception)
		{
			$result->addError(new Error($exception->getMessage()));
		}

		return $result;
	}

	public static function processMovingToRecycleBin(array $activityFields, array $params = null): Result
	{
		$result = new Result();

		$taskId = (int)($activityFields['ASSOCIATED_ENTITY_ID'] ?? null);
		if ($taskId <= 0)
		{
			$result->addError(new Error('Wrong task id.'));
			return $result;
		}

		try
		{
			TaskHandler::getHandler()->update(
				$taskId,
				[
					self::TASK_CRM_FIELD => []
				]
			);
		}
		catch (\Exception $exception)
		{
			$result->addError(new Error($exception->getMessage()));
			return $result;
		}

		$result->setData(['isDeleted' => true]);

		return $result;
	}

	private static function prepareBindingsToTask(array $bindings): array
	{
		$crmTaskFields = [];
		foreach($bindings as $binding)
		{
			$entityTypeId = (int)($binding['OWNER_TYPE_ID'] ?? null);
			$entityId = (int)($binding['OWNER_ID'] ?? null);

			if($entityId <= 0 || !CCrmOwnerType::IsDefined($entityTypeId))
			{
				continue;
			}

			$type = CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);
			if ($type === \CCrmOwnerTypeAbbr::Undefined)
			{
				continue;
			}
			$crmTaskFields[] = $type . '_' . $entityId;
		}

		return $crmTaskFields;
	}

	public function getEditAction(int $activityId, int $userId = 0): string
	{
		if (!Loader::includeModule('tasks'))
		{
			return '';
		}

		$query = ActivityTable::query();
		$query
			->setSelect(['ID', 'ASSOCIATED_ENTITY_ID'])
			->where('ID', $activityId)
		;

		$activity = $query->exec()->fetchObject();
		if (is_null($activity))
		{
			return '';
		}

		$taskId = $activity->getAssociatedEntityId();
		if (is_null($taskId))
		{
			return '';
		}

		$factory = TaskSliderFactory::getFactory();
		if (is_null($factory))
		{
			return '';
		}

		$factory
			->setAction($factory::EDIT_ACTION)
			->skipEvents()
		;
		$slider = $factory->createEntitySlider(
			$taskId,
			$factory::TASK,
			$userId,
			$factory::PERSONAL_CONTEXT
		);

		return $slider->getJs();
	}

	public static function isTask(): bool
	{
		return true;
	}

	public static function isActivityEditable(array $activity = [], int $userId = 0): bool
	{
		$taskId = (int)($activity['ASSOCIATED_ENTITY_ID'] ?? null);
		if ($taskId <= 0)
		{
			return false;
		}

		return TaskAccessController::canEdit($taskId, $userId);
	}

	public static function onTriggered(int $taskId, ?array $currentTaskFields, ?array $previousTaskFields): bool
	{
		if ($taskId <= 0 || !Loader::includeModule('tasks'))
		{
			return false;
		}

		$itemIterator = \CTasks::getByID($taskId, false);
		$task = $itemIterator->fetch();
		if (!$task)
		{
			return false;
		}

		$isStatusChanged = (isset($currentTaskFields['STATUS'])
			&& (string)$currentTaskFields['STATUS']
			!== (string)$previousTaskFields['STATUS']);
		$listIterator = \CCrmActivity::getList(
			[],
			[
				'=TYPE_ID' => \CCrmActivityType::Provider,
				'=PROVIDER_ID' => self::getId(),
				// '=PROVIDER_TYPE_ID' => self::getProviderTypeId(),
				'=ASSOCIATED_ENTITY_ID' => $taskId,
				'CHECK_PERMISSIONS' => 'N',
			]
		);

		$isFound = false;
		$taskBindings = [];

		while ($activity = $listIterator->fetch())
		{
			$isFound = true;
			self::legacySetBindings($task, $activity);
			if (isset($activity['BINDINGS']) && count($activity['BINDINGS']) > 0)
			{
				\CCrmActivity::update($activity['ID'], $activity, false, true, self::UPDATE_OPTIONS);
				\CCrmLiveFeed::syncTaskEvent($activity, $task);
				$taskBindings = $activity['BINDINGS'];
			}
		}

		if (!$isFound)
		{
			return true;
		}

		if ($isStatusChanged && $taskBindings)
		{
			TaskStatusTrigger::execute($taskBindings, ['TASK' => $task]);
		}

		return true;
	}

	private static function legacySetBindings(array &$taskFields, array &$activity): void
	{
		$taskOwners = $taskFields['UF_CRM_TASK'] ?? [];
		$ownerData = [];
		if (!is_array($taskOwners))
		{
			$taskOwners = [$taskOwners];
		}
		$activity['BINDINGS'] = [];
		if (\CCrmActivity::tryResolveUserFieldOwners($taskOwners, $ownerData, \CCrmUserType::getTaskBindingField()))
		{
			$bindingMap = [];
			foreach ($ownerData as $ownerInfo)
			{
				$ownerTypeId = \CCrmOwnerType::resolveID($ownerInfo['OWNER_TYPE_NAME']);
				$ownerId = (int)$ownerInfo['OWNER_ID'];

				$bindingMap["{$ownerTypeId}_{$ownerId}"] = [
					'OWNER_TYPE_ID' => $ownerTypeId,
					'OWNER_ID' => $ownerId,
				];
			}
			$bindings = array_values($bindingMap);
			if (count($bindings) > 1)
			{
				//Lead and Deals will be at beginning of list for take activity ownership
				usort(
					$bindings,
					function ($a, $b) {
						if ($a['OWNER_TYPE_ID'] == $b['OWNER_TYPE_ID'])
						{
							return 0;
						}
						return $a['OWNER_TYPE_ID'] > $b['OWNER_TYPE_ID'] ? 1 : -1;
					}
				);
			}
			$activity['BINDINGS'] = $bindings;
		}

		if (!empty($activity['BINDINGS']))
		{
			//Check for owner change
			$ownerTypeId = isset($activity['OWNER_TYPE_ID']) ? (int)$activity['OWNER_TYPE_ID']
				: \CCrmOwnerType::Undefined;
			$ownerId = isset($activity['OWNER_ID']) ? (int)$activity['OWNER_ID'] : 0;
			$ownerIsFound = false;
			foreach ($activity['BINDINGS'] as $binding)
			{
				if ($binding['OWNER_TYPE_ID'] === $ownerTypeId && $binding['OWNER_ID'] === $ownerId)
				{
					$ownerIsFound = true;
					break;
				}
			}

			if (!$ownerIsFound)
			{
				$binding = $activity['BINDINGS'][0];
				$activity['OWNER_TYPE_ID'] = $binding['OWNER_TYPE_ID'];
				$activity['OWNER_ID'] = $binding['OWNER_ID'];
			}
		}
	}

	public static function checkCompletePermission($entityId, array $activity, $userId): ?bool
	{
		$taskId = (int)$entityId;
		if ($taskId <= 0)
		{
			return true;
		}

		$task = TaskObject::getObject($taskId);
		if (is_null($task))
		{
			return true;
		}

		if ($task->getZombie())
		{
			return true;
		}

		$status = (int)$task->getStatus();
		if ($status === TaskActivityStatus::TASKS_STATE_COMPLETED)
		{
			return true;
		}

		if (!TaskAccessController::canCompleteResult($taskId, $userId))
		{
			$uri = new Uri(TaskPathMaker::getPathMaker($taskId, $userId)->makeEntityPath());
			$uri->addParams(['RID' => 0]);

			$message = Loc::getMessage('TASKS_TASK_ERROR_REQUIRE_RESULT', [
				'#TASK_URL#' => $uri->getUri(),
			]);
			self::setCompletionDeniedError($message);

			return false;
		}

		return TaskAccessController::canComplete($taskId, $userId);
	}

	public static function getKey(): string
	{
		return self::getId() . '.' . self::getProviderTypeId() . '.*';
	}

	public function deleteLogEntry(int $activityId, int $taskId): int
	{
		$logEntryId = $this->getCompletedActivityEntryId($activityId, $taskId);
		if ($logEntryId > 0)
		{
			TimelineEntry::delete($logEntryId);
			return $logEntryId;
		}

		return 0;
	}

	public static function onAfterUpdate(
		int $id,
		array $changedFields,
		array $oldFields,
		array $newFields,
		array $params = null
	)
	{
		$taskId = $newFields['ASSOCIATED_ENTITY_ID'] ?? 0;
		if ($taskId <= 0)
		{
			return;
		}

		$task = TaskObject::getObject($taskId);
		if (is_null($task))
		{
			return;
		}

		$bindings = $newFields['BINDINGS'] ?? [];
		if (empty($bindings))
		{
			return;
		}
		$taskCrmFields = $task->getCrmFields();
		$crmFields = array_unique(array_merge($taskCrmFields, self::prepareBindingsToTask($bindings)));
		if (
			empty(array_diff($crmFields, $taskCrmFields))
			&& empty(array_diff($taskCrmFields, $crmFields))
		)
		{
			return;
		}

		TaskHandler::getHandler()->update($taskId,[
			self::TASK_CRM_FIELD => $crmFields
		]);
	}

	/**
	 * There are two kind of task. Old with type = 3 and new with provider_id = CRM_TASKS_TASK
	 * We have to query both when selected 'Task' in the filter.
	 */
	public static function transformTaskInFilter(
		array &$filter,
		string $typeFieldName = 'TYPE_ID',
		bool $allTaskBased = false
	): void
	{
		if (
			is_array($filter[$typeFieldName] ?? null)
			&& in_array(\CCrmActivityType::Task, $filter[$typeFieldName])
		)
		{
			$filter[$typeFieldName][] = $allTaskBased
				? self::getId() . '.*.*'
				: Task::getKey();
		}
	}
}
