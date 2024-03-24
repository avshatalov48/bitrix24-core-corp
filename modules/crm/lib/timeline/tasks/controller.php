<?php

namespace Bitrix\Crm\Timeline\Tasks;

use Bitrix\Crm\Activity\Provider\Tasks\Task;
use Bitrix\Crm\Activity\Provider\Tasks\Comment;
use Bitrix\Crm\Activity\Provider\Tasks\TaskActivityStatus;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\EO_Activity;
use Bitrix\Crm\Integration\Tasks\TaskCounter;
use Bitrix\Crm\Integration\Tasks\TaskObject;
use Bitrix\Crm\Integration\Tasks\TaskSearchIndex;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Search\SearchEnvironment;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\Crm\Timeline\FactoryBasedController;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\TimelineEntry\Facade;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Tasks\Integration\CRM\Timeline\Bindings;
use CCrmOwnerType;

final class Controller extends FactoryBasedController
{
	private static ?self $instance = null;
	private ActivityController $activityController;
	private Task $taskActivityProvider;
	private Comment $commentActivityProvider;

	protected function getTrackedFieldNames(): array
	{
		return [];
	}

	protected function __construct()
	{
		parent::__construct();
		$this->activityController = ActivityController::getInstance();
		$this->taskActivityProvider = new Task();
		$this->commentActivityProvider = new Comment();
	}

	public function prepareSearchContent(array $params): string
	{
		$typeId = (int)$params['TYPE_ID'];
		$sourceId = (int)$params['SOURCE_ID'];
		$taskId = (int)($typeId === TimelineType::TASK) * $sourceId;
		if ($taskId <= 0)
		{
			return '';
		}

		return SearchEnvironment::prepareToken(TaskSearchIndex::getTaskSearchIndex($taskId));
	}

	public static function getInstance(): self
	{
		if (is_null(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function onTaskCommentDeleted(Bindings $bindings, array $timelineParams): void
	{
		$this->refreshCommentActivity($bindings, $timelineParams);
	}

	public function onTaskDeadLineChanged(Bindings $bindings, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}
		$this->handleTaskEvent(CategoryType::DEADLINE_CHANGED, $bindings, $timelineParams);
	}

	public function onTaskAdded(Bindings $bindings, array $timelineParams): void
	{
		$this->handleTaskEvent(CategoryType::TASK_ADDED, $bindings, $timelineParams);
	}

	public function onTaskDescriptionChanged(Bindings $bindings, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::DESCRIPTION_CHANGED, $bindings, $timelineParams);
	}

	public function onTaskPriorityChanged(Bindings $bindings, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::PRIORITY_CHANGED, $bindings, $timelineParams);
	}

	public function onTaskDisapproved(Bindings $bindings, array $timelineParams): void
	{
		$taskId = $timelineParams['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return;
		}

		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$activity = $this->taskActivityProvider->find($taskId);
		if (is_null($activity))
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::DISAPPROVED, $bindings, $timelineParams);
		$completedActivityEntryId = $this->taskActivityProvider->deleteLogEntry($activity->getId(), $taskId);
		if ($completedActivityEntryId > 0)
		{
			foreach ($bindings as $identifier)
			{
				$this->sendPullEventOnDelete($identifier, $completedActivityEntryId);
			}
		}
	}

	public function onTaskResponsibleChanged(Bindings $bindings, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::RESPONSIBLE_CHANGED, $bindings, $timelineParams);
	}

	public function onTaskAccompliceAdded(Bindings $bindings, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::ACCOMPLICE_ADDED, $bindings, $timelineParams);
	}

	public function onTaskAuditorAdded(Bindings $bindings, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::AUDITOR_ADDED, $bindings, $timelineParams);
	}

	public function onTaskGroupChanged(Bindings $bindings, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::GROUP_CHANGED, $bindings, $timelineParams);
	}

	public function onTaskExpired(Bindings $bindings, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::EXPIRED, $bindings, $timelineParams);
	}

	public function onTaskResultAdded(Bindings $bindings, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::RESULT_ADDED, $bindings, $timelineParams);
	}

	public function onTaskStatusChanged(Bindings $bindings, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::STATUS_CHANGED, $bindings, $timelineParams);
	}

	public function onTaskChecklistAdded(Bindings $bindings, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::CHECKLIST_ADDED, $bindings, $timelineParams);
	}

	public function onTaskViewed(Bindings $bindings, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::VIEWED, $bindings, $timelineParams);
	}

	public function onTaskPingSent(Bindings $bindings, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::PING_SENT, $bindings, $timelineParams);
	}

	public function onTaskCommentAdded(Bindings $bindings, array $timelineParams): void
	{
		$bindings = $this->filterBindings($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleCommentActivity($bindings, CategoryType::COMMENT_ADD, $timelineParams);
	}

	public function onTaskRenew(Bindings $bindings, array $timelineParams): void
	{
		$taskId = $timelineParams['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return;
		}
		$bindings = $this->filterBindings($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$activity = $this->taskActivityProvider->find($taskId);
		$task = TaskObject::getObject($taskId);
		if (is_null($task))
		{
			return;
		}
		$status = (int)$task->getStatus();
		if ($status !== TaskActivityStatus::TASKS_STATE_PENDING)
		{
			return;
		}

		$endDatePlan = $task->getEndDatePlan();
		$this->taskActivityProvider->setEndTime($activity, $endDatePlan);
		$this->taskActivityProvider->renew($activity->getId());
		$completedActivityEntryId = $this->taskActivityProvider->getCompletedActivityEntryId($activity->getId(), $taskId);
		if ($completedActivityEntryId > 0)
		{
			TimelineEntry::delete($completedActivityEntryId);
			foreach ($bindings as $identifier)
			{
				$this->sendPullEventOnDelete($identifier, $completedActivityEntryId);
			}
		}
	}

	public function onTaskDeleted(Bindings $bindings, array $timelineParams): void
	{
		$taskId = $timelineParams['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return;
		}

		$taskActivity = $this->taskActivityProvider->find($taskId);
		if (is_null($taskActivity))
		{
			return;
		}

		$this->taskActivityProvider->delete($taskActivity->getId());

		foreach ($bindings as $identifier)
		{
			$this->commentActivityProvider->deleteByItem($taskId, $identifier);
		}
	}

	public function onTaskBindingsUpdated(Bindings $bindings, array $timelineParams): void
	{
		$taskId = $timelineParams['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return;
		}

		$oldActivity = $this->getOldTaskActivity($taskId);
		if (!is_null($oldActivity))
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::BINDINGS_UPDATED, $bindings, $timelineParams);
	}

	public function OnTaskDatePlanUpdated(Bindings $bindings, array $timelineParams): void
	{
		$bindings = $this->filterBindings($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::DATE_PLAN__UPDATED, $bindings, $timelineParams);
	}

	public function onTaskTitleUpdated(Bindings $bindings, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$this->handleTaskEvent(CategoryType::TITLE_UPDATED, $bindings, $timelineParams);
	}
	public function onTaskFilesUpdated(Bindings $bindings, array $timelineParams): void
	{
		$taskId = $timelineParams['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return;
		}

		$bindings = $this->filterBindings($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$activity = $this->taskActivityProvider->find($taskId);
		$this->taskActivityProvider->updateFiles($activity, $timelineParams);
	}

	public function onTaskCompleted(Bindings $bindings, array $timelineParams): void
	{
		$taskId = $timelineParams['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return;
		}

		$bindings = $this->filterBindings($bindings, $timelineParams);

		if ($bindings->isEmpty())
		{
			return;
		}

		$activity = $this->taskActivityProvider->find($taskId);
		if (is_null($activity))
		{
			return;
		}

		$closedDate = TaskObject::getObject($taskId)->getClosedDate();
		$this->taskActivityProvider->setEndTime($activity, $closedDate);
		$this->taskActivityProvider->complete($activity);
	}

	public function refreshTaskActivity(Bindings $bindings, array $timelineParams): void
	{
		$taskId = $timelineParams['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return;
		}

		if ($bindings->isEmpty())
		{
			return;
		}

		$activity = $this->taskActivityProvider->find($taskId, true);
		if (!is_null($activity))
		{
			$activity = $activity->collectValues();
			foreach ($bindings as $identifier)
			{
				$responsibleId = $this->getAssignedByEntity($identifier);
				$this->activityController->sendPullEventOnUpdateScheduled($identifier, $activity, $responsibleId);
			}
		}
	}

	public function refreshCommentActivity(Bindings $bindings, array $timelineParams): void
	{
		$taskId = $timelineParams['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return;
		}

		$bindings = $this->filterBindings($bindings, $timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}

		$taskActivity = $this->taskActivityProvider->find($taskId);
		foreach ($bindings as $identifier)
		{
			$activity = $this->commentActivityProvider->find($taskId, $identifier);
			if (!is_null($activity))
			{
				$unreadCommentsCount = TaskCounter::getCommentsCount($taskId, $activity->getResponsibleId());
				if ($unreadCommentsCount === 0)
				{
					$associatedTimelineEntry = $this->commentActivityProvider->getAssociatedTimelineEntry($taskActivity);
					TimelineEntry::delete($associatedTimelineEntry->getId());
					$this->commentActivityProvider->delete($activity->getId());
					$this->sendPullEventOnDelete($identifier, $associatedTimelineEntry->getId());
				}
				else
				{
					$this->commentActivityProvider->refresh($activity, $bindings, $timelineParams);
				}
			}
		}
	}

	public function onTaskAllCommentViewed(Bindings $bindings, array $timelineParams): void
	{
		$timelineParams = $this->filterParams($timelineParams);
		if ($bindings->isEmpty())
		{
			return;
		}
		$taskId = $timelineParams['TASK_ID'];

		foreach ($bindings as $identifier)
		{
			$responsibleId = $this->getAssignedByEntity($identifier);
			if (is_null($responsibleId))
			{
				return;
			}
			$authorId = $timelineParams['AUTHOR_ID'];

			if ($responsibleId === $authorId)
			{
				$unreadCommentsCount = TaskCounter::getCommentsCount($taskId, $responsibleId);
				if ($unreadCommentsCount === 0)
				{
					$activity = $this->commentActivityProvider->find($taskId, $identifier);
					if (!is_null($activity))
					{
						$this->commentActivityProvider->delete($activity->getId());
						$timelineParams['SKIP_BINDINGS_UPDATE'] = true;
						$this->handleTaskEvent(CategoryType::ALL_COMMENT_VIEWED, new Bindings(...[$identifier]), $timelineParams);
					}
				}
			}
		}
	}

	protected function handleTaskEvent(int $typeCategoryId, Bindings $bindings, array $timelineParams): void
	{
		if ($typeCategoryId === CategoryType::TASK_ADDED)
		{
			$this->handleTaskActivityOnNewTask($bindings, $typeCategoryId, $timelineParams);
			return;
		}
		$this->handleTaskTimeline($typeCategoryId, $timelineParams, $bindings);
		$this->handleTaskActivity($typeCategoryId, $timelineParams, $bindings);
	}

	private function handleTaskTimeline(int $typeCategoryId, array $timelineParams, Bindings $bindings): void
	{
		if (isset($timelineParams['IGNORE_IN_LOGS']) && $timelineParams['IGNORE_IN_LOGS'] === true)
		{
			return;
		}

		$timelineEntry = $this->getTimelineEntryFacade()->create(
			Facade::TASK,
			[
				'TYPE_CATEGORY_ID' => $typeCategoryId,
				'AUTHOR_ID' => $timelineParams['AUTHOR_ID'] ?? null,
				'SETTINGS' => $timelineParams,
				'BINDINGS' => $bindings,
			],
		);

		if ($timelineEntry === 0)
		{
			return;
		}

		foreach ($bindings as $identifier)
		{
			$this->sendPullEventOnAdd($identifier, $timelineEntry);
		}
	}

	private function handleTaskActivity(int $typeCategoryId, array $params, Bindings $bindings): void
	{
		$taskId = $params['TASK_ID'] ?? null;
		if(is_null($taskId))
		{
			return;
		}

		$desiredStatus = null;
		switch ($typeCategoryId)
		{
			case CategoryType::DEADLINE_CHANGED:
				$this->taskActivityProvider->updateDeadline($taskId, $params);
				$desiredStatus = TaskActivityStatus::STATUS_DEADLINE_CHANGED;
				break;

			case CategoryType::VIEWED:
				$desiredStatus = TaskActivityStatus::STATUS_VIEWED;
				break;

			case CategoryType::STATUS_CHANGED:
				$desiredStatus = (new TaskActivityStatus())->onStatusChange(
					(int)$params['TASK_CURRENT_STATUS'],
					$params['IS_EXPIRED'] ?? false
				);
				break;

			case CategoryType::RESULT_ADDED:
				$desiredStatus = TaskActivityStatus::STATUS_RESULT_ADDED;
				break;

			case CategoryType::EXPIRED:
				$desiredStatus = TaskActivityStatus::STATUS_EXPIRED;
				break;

			case CategoryType::DESCRIPTION_CHANGED:
				$this->taskActivityProvider->updateDescription($params);
				$desiredStatus = TaskActivityStatus::STATUS_UPDATED;
				break;

			case CategoryType::RESPONSIBLE_CHANGED:
			case CategoryType::ACCOMPLICE_ADDED:
			case CategoryType::AUDITOR_ADDED:
			case CategoryType::CHECKLIST_ADDED:
			case CategoryType::GROUP_CHANGED:
			case CategoryType::TASK_UPDATED:
				$desiredStatus = TaskActivityStatus::STATUS_UPDATED;
				break;

			case CategoryType::DISAPPROVED:
				$activity = $this->taskActivityProvider->find($taskId);
				if (!is_null($activity))
				{
					$this->taskActivityProvider->renew($activity->getId());
				}
				break;
		}

		if ($desiredStatus && $this->isActivityStatusUpdateRequired($params, $bindings, $desiredStatus))
		{
			$this->taskActivityProvider->updateStatus(
				$taskId,
				$desiredStatus
			);
		}

		if (!isset($params['SKIP_BINDINGS_UPDATE']) || $params['SKIP_BINDINGS_UPDATE'] === false)
		{
			$this->taskActivityProvider->updateBindings($bindings, $this->getCurrentBindings($params), $params);
		}

		$this->taskActivityProvider->updateByTask($params);

		if (isset($params['REFRESH_TASK_ACTIVITY']) && $params['REFRESH_TASK_ACTIVITY'] === true)
		{
			$this->refreshTaskActivity($bindings, $params);
		}
	}

	private function handleTaskActivityOnNewTask(Bindings $bindings, int $typeId, array $timelineParams): void
	{
		$taskId = $timelineParams['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return;
		}

		$firstIdentifier = $bindings->getFirst();
		$responsibleId = $this->getAssignedByEntity($firstIdentifier);
		if (is_null($responsibleId))
		{
			return;
		}

		$activity = $this->taskActivityProvider->find($taskId);
		if (is_null($activity) || $activity->getCompleted())
		{
			$authorId = $timelineParams['AUTHOR_ID'] ?? 0;
			$taskResponsibleId = $timelineParams['RESPONSIBLE_ID'] ?? 0;
			$timelineParams['ACTIVITY_STATUS'] = ($authorId === $taskResponsibleId)
				? TaskActivityStatus::STATUS_VIEWED
				: TaskActivityStatus::STATUS_CREATED;

			$result = $this->taskActivityProvider->createActivity(
				Task::getProviderTypeId(),
				$this->taskActivityProvider->prepareFields($taskId, $bindings, $timelineParams)
			);
			if ($result->isSuccess())
			{
				$timelineParams['ASSOCIATED_ENTITY_TYPE_ID'] = CCrmOwnerType::Activity;
				$timelineParams['ASSOCIATED_ENTITY_ID'] = $result->getData()['id'];
				$this->handleTaskTimeline($typeId, $timelineParams, $bindings);
			}
		}
	}

	private function handleCommentActivity(Bindings $bindings, int $typeId, array $timelineParams): void
	{
		[$bindings, $timelineParams] = $this->prepareParams($bindings, $timelineParams);

		foreach ($bindings as $identifier)
		{
			$taskId = $timelineParams['TASK_ID'] ?? null;
			if (is_null($taskId))
			{
				return;
			}

			$fromUser = $timelineParams['FROM_USER'] ?? 0;
			$responsibleId = $this->getAssignedByEntity($identifier);

			if (is_null($responsibleId))
			{
				return;
			}

			if ($fromUser === $responsibleId)
			{
				continue;
			}

			$activity = $this->commentActivityProvider->find($taskId, $identifier);

			if (is_null($activity) || $activity->getCompleted())
			{
				$result = $this->commentActivityProvider->createActivity(
					Comment::getProviderTypeId(),
					$this->commentActivityProvider->prepareFields($taskId, $responsibleId, $identifier, $timelineParams)
				);

				if ($result->isSuccess())
				{
					$timelineParams['SKIP_BINDINGS_UPDATE'] = true;
					$this->handleTaskEvent($typeId, new Bindings(...[$identifier]), $timelineParams);
				}
			}
			else
			{
				$this->commentActivityProvider->update($activity, $timelineParams);
			}
		}
	}

	public function getAssignedByEntity(?ItemIdentifier $identifier): ?int
	{
		if (is_null($identifier))
		{
			return null;
		}

		$factory = Container::getInstance()->getFactory($identifier->getEntityTypeId());
		if (!is_null($factory))
		{
			$assignedByFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_ASSIGNED);
			$data = $factory->getDataClass()::getList([
				'select' => [
					$assignedByFieldName
				],
				'filter' => [
					Item::FIELD_NAME_ID => $identifier->getEntityId()
				],
				'limit' => 1,
			])->fetch() ?? [];

			return $data[$assignedByFieldName] ?? null;
		}

		return null;
	}

	public function prepareHistoryDataModel(array $data, array $options = null): array
	{
		$data = array_merge($data, is_array($data['SETTINGS']) ? $data['SETTINGS'] : []);

		return parent::prepareHistoryDataModel($data, $options);
	}

	private function prepareParams(Bindings $bindings, array $timelineParams): array
	{
		$taskId = $timelineParams['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return [new Bindings(), []];
		}

		$activity = $this->taskActivityProvider->find($timelineParams['TASK_ID']);
		if (is_null($activity))
		{
			return [new Bindings(), []];
		}

		$timelineParams = $this->filterParams($timelineParams);
		$bindings = $this->filterBindings($bindings, $timelineParams);

		return [$bindings, $timelineParams];
	}

	private function filterParams(array $timelineParams): array
	{
		$taskId = $timelineParams['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return [];
		}

		$activity = $this->taskActivityProvider->find($timelineParams['TASK_ID']);
		if (is_null($activity))
		{
			return [];
		}

		$timelineParams['ASSOCIATED_ENTITY_TYPE_ID'] = CCrmOwnerType::Activity;
		$timelineParams['ASSOCIATED_ENTITY_ID'] = $activity->getId();

		return $timelineParams;
	}

	private function filterBindings(Bindings $bindings, array $timelineParams): Bindings
	{
		$activity = $this->taskActivityProvider->find($timelineParams['TASK_ID']);
		if (is_null($activity))
		{
			return new Bindings();
		}
		$query = ActivityBindingTable::query();
		$query
			->setSelect(['ID', 'OWNER_ID', 'OWNER_TYPE_ID'])
			->where('ACTIVITY_ID', $activity->getId())
		;

		$currentBindings = $query->exec()->fetchCollection();
		$result = new Bindings();
		foreach ($currentBindings as $activityIdentifier)
		{
			$identifier = new ItemIdentifier($activityIdentifier->getOwnerTypeId(), $activityIdentifier->getOwnerId());
			if ($bindings->contains($identifier))
			{
				$result->add($identifier);
			}

		}

		return $result;
	}

	public function getCurrentBindings(array $timelineParams): Bindings
	{
		$activity = $this->taskActivityProvider->find($timelineParams['TASK_ID']);
		if (is_null($activity))
		{
			return new Bindings();
		}
		$query = ActivityBindingTable::query();
		$query
			->setSelect(['ID', 'OWNER_ID', 'OWNER_TYPE_ID'])
			->where('ACTIVITY_ID', $activity->getId())
		;

		$currentBindings = $query->exec()->fetchCollection();
		$result = new Bindings();
		foreach ($currentBindings as $activityIdentifier)
		{
			$result->add(new ItemIdentifier($activityIdentifier->getOwnerTypeId(), $activityIdentifier->getOwnerId()));
		}

		return $result;
	}

	private function isActivityStatusUpdateRequired(array $params, Bindings $bindings, string $desiredStatus): bool
	{
		$authorId = $params['AUTHOR_ID'] ?? 0;

		if ($bindings->isEmpty())
		{
			return false;
		}

		$updateByParams = (!isset($params['UPDATE_ACTIVITY_STATUS']) || $params['UPDATE_ACTIVITY_STATUS'] === true);

		if ($updateByParams === false)
		{
			return false;
		}

		if (in_array($desiredStatus, TaskActivityStatus::STATUSES_MANAGER_CAN_UPDATE, true))
		{
			return true;
		}

		foreach ($bindings as $identifier)
		{
			$responsibleId = $this->getAssignedByEntity($identifier);

			if ($responsibleId === $authorId)
			{
				return false;
			}
		}

		return true;
	}

	private function getOldTaskActivity(int $taskId): ?EO_Activity
	{
		$query = ActivityTable::query();
		$query
			->setSelect(['ID'])
			->where('PROVIDER_ID', \Bitrix\Crm\Activity\Provider\Task::getId())
			->where('PROVIDER_TYPE_ID', \Bitrix\Crm\Activity\Provider\Task::getTypeId([]))
			->where('ASSOCIATED_ENTITY_ID', $taskId)
			->where('TYPE_ID', \CCrmActivityType::Task)
		;

		$activity = $query->exec()->fetchObject();

		return $activity;
	}
}