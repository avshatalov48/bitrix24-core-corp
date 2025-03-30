<?php

namespace Bitrix\Crm\Timeline\Bizproc;

use Bitrix\Bizproc\Integration\Intranet\Settings\Manager;
use Bitrix\Bizproc\Workflow\Entity\WorkflowUserCommentTable;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline;
use Bitrix\Crm\Timeline\Bizproc\Data\ChangedTaskStatus;
use Bitrix\Crm\Timeline\Bizproc\Data\ChangedWorkflowStatus;
use Bitrix\Crm\Timeline\Bizproc\Data\ChangedCommentStatus;
use Bitrix\Crm\Timeline\Bizproc\Data\CommentStatus;
use Bitrix\Crm\Timeline\TimelineEntry\Facade;
use Bitrix\Crm\Activity\Provider;
use Bitrix\Crm\Service\Timeline\Item\Bizproc\Workflow;
use Bitrix\Main\Analytics\AnalyticsEvent;

final class Controller extends Timeline\Controller
{
	use Workflow;

	private ?int $responsibleId = null;

	public function onWorkflowStatusChange(Timeline\Bizproc\Dto\WorkflowStatusChangedDto $request): void
	{
		$statusMethods = [
			\CBPWorkflowStatus::Created => 'onCreateWorkflow',
			\CBPWorkflowStatus::Completed => 'onCompleteWorkflow',
			\CBPWorkflowStatus::Terminated => 'onTerminateWorkflow',
		];

		$method = $statusMethods[$request->status] ?? null;
		if ($method && method_exists($this, $method))
		{
			$changedWorkflowStatus = ChangedWorkflowStatus::createFromRequest($request);
			if ($changedWorkflowStatus)
			{
				try
				{
					$this->$method($changedWorkflowStatus);

				} catch (\Throwable $error)
				{
				}
			}
		}
	}

	public function onTaskStatusChange(Timeline\Bizproc\Dto\TaskStatusChangedDto $request): void
	{
		$statusMethods = [
			\CBPTaskChangedStatus::Add => 'onCreateTask',
			\CBPTaskChangedStatus::Update => 'onUpdateTask',
			\CBPTaskChangedStatus::Delegate => 'onDelegatedTask',
			\CBPTaskChangedStatus::Delete => 'onDeletedTask',
		];

		$method = $statusMethods[$request->status] ?? null;
		if ($method && method_exists($this, $method))
		{
			$changedTaskStatus = ChangedTaskStatus::createFromRequest($request);
			if ($changedTaskStatus)
			{
				try
				{
					$this->$method($changedTaskStatus);

				} catch (\Throwable $error)
				{
				}
			}
		}
	}

	public function onCommentStatusChange(Timeline\Bizproc\Dto\CommentStatusChangedDto $request): void
	{
		$statusMethods = [
			CommentStatus::Created->value => 'onCreateComment',
			CommentStatus::Deleted->value => 'onDeleteComment',
			CommentStatus::Viewed->value => 'onViewedComment',
		];

		$method = $statusMethods[$request->status->value] ?? null;
		if ($method && method_exists($this, $method))
		{
			$changedCommentStatus = ChangedCommentStatus::createFromRequest($request);
			if ($changedCommentStatus)
			{
				try
				{
					$this->$method($changedCommentStatus);

				} catch (\Throwable $error)
				{
				}
			}
		}
	}

	public function onCreateTask(ChangedTaskStatus $changedTaskStatus): void
	{
		if ($changedTaskStatus->workflow->isWorkflowShowInTimeline())
		{
			$responsibleId = $this->getResponsibleId($changedTaskStatus->documentId);
			$createData = [
				'USERS' => $this->getUsers($changedTaskStatus->users),
				'TASK_ID' => $changedTaskStatus->task->id,
				'TASK_NAME' => $changedTaskStatus->task->name,
				'IS_TASK_PARTICIPANT' => in_array($responsibleId, $changedTaskStatus->users, true),
			];
			$authorId = (int)reset($createData['USERS'])['ID'];
			$data = $this->prepareWorkflowData($changedTaskStatus->workflow->id, $authorId, $createData);
			$this->createTimelineEntry($data, CategoryType::TASK_ADDED, $changedTaskStatus);

			$this->updateFacesInTaskActivity($changedTaskStatus->workflow);
			$this->createTaskActivity($changedTaskStatus);
		}
	}

	private function createTaskActivity(ChangedTaskStatus $changedTaskStatus): void
	{
		$responsibleId = $this->getResponsibleId($changedTaskStatus->documentId);
		// create only if current responsible is member of task
		if (
			$responsibleId <= 0
			|| !in_array($responsibleId, $changedTaskStatus->users, true)
			|| ($changedTaskStatus->usersAdded && !in_array($responsibleId, $changedTaskStatus->usersAdded, true))
		)
		{
			return;
		}

		$command =
			(new Command\Task\CreateCommand($changedTaskStatus->task, $responsibleId))
				->setWorkflow($changedTaskStatus->workflow)
				->setBindings([
					['OWNER_ID' => $changedTaskStatus->entityId, 'OWNER_TYPE_ID' => $changedTaskStatus->entityTypeId],
				])
		;
		$command->execute();
	}

	private function onUpdateTask(ChangedTaskStatus $changedTaskStatus): void
	{
		if ($changedTaskStatus->isPartiallyCompleted || $changedTaskStatus->isFullyCompleted)
		{
			$this->onCompleteTask($changedTaskStatus);

			return;
		}

		if ($changedTaskStatus->isPartiallyUnCompleted)
		{
			$this->onUnCompleteTask($changedTaskStatus);

			return;
		}

		$this->updateFacesInTaskActivity($changedTaskStatus->workflow);

		if ($changedTaskStatus->usersAdded)
		{
			$this->onUsersAddedToTask($changedTaskStatus);
		}

		if ($changedTaskStatus->usersRemoved)
		{
			$this->onUsersRemovedFromTask($changedTaskStatus);
		}
	}

	public function onCompleteTask(ChangedTaskStatus $changedTaskStatus): void
	{
		$isWorkflowShowInTimeline = $changedTaskStatus->workflow->isWorkflowShowInTimeline();
		if ($changedTaskStatus->isPartiallyCompleted && $isWorkflowShowInTimeline)
		{
			$completeData = [
				'USERS' => $this->getUsers($changedTaskStatus->users),
				'TASK_ID' => $changedTaskStatus->task->id,
				'TASK_NAME' => $this->getTaskNameById($changedTaskStatus->task->id),
			];
			$authorId = (int)reset($completeData['USERS'])['ID'];
			$data = $this->prepareWorkflowData($changedTaskStatus->workflow->id, $authorId, $completeData);
			$this->createTimelineEntry($data, CategoryType::TASK_COMPLETED, $changedTaskStatus);
		}

		$this->markCompletedTaskActivity($changedTaskStatus);
		if ($changedTaskStatus->isFullyCompleted)
		{
			$this->updateFacesInTaskActivity($changedTaskStatus->workflow);
		}
	}

	public function markCompletedTaskActivity(ChangedTaskStatus $changedTaskStatus): void
	{
		if ($changedTaskStatus->isFullyCompleted)
		{
			$command = new Command\Task\MarkCompletedCommand($changedTaskStatus->task);
			$command->execute();
		}
		else
		{
			// part of task completed
			foreach ($changedTaskStatus->users as $userId)
			{
				$command = (new Command\Task\MarkCompletedCommand($changedTaskStatus->task, (int)$userId));
				$command->execute();
			}
		}
	}

	private function onUnCompleteTask(ChangedTaskStatus $changedTaskStatus): void
	{
		$responsibleId = $this->getResponsibleId($changedTaskStatus->documentId);
		// only for current responsible
		if ($responsibleId > 0 && in_array($responsibleId, $changedTaskStatus->users, true))
		{
			$this->markUnCompletedOrCreateTask($changedTaskStatus, $responsibleId);
		}
	}

	private function onUsersAddedToTask(ChangedTaskStatus $changedTaskStatus): void
	{
		$responsibleId = $this->getResponsibleId($changedTaskStatus->documentId);
		// only for current responsible
		if ($responsibleId > 0 && in_array($responsibleId, $changedTaskStatus->usersAdded, true))
		{
			$this->markUnCompletedOrCreateTask($changedTaskStatus, $responsibleId);
		}
	}

	private function markUnCompletedOrCreateTask(ChangedTaskStatus $changedTaskStatus, int $userId): void
	{
		$command = (new Command\Task\MarkUnCompletedCommand($changedTaskStatus->task, $userId));
		$result = $command->execute();

		// create task, if not found activity
		if ($result->getData()['find'] === false)
		{
			$this->createTaskActivity($changedTaskStatus);
		}
	}

	private function onUsersRemovedFromTask(ChangedTaskStatus $changedTaskStatus): void
	{
		foreach ($changedTaskStatus->usersRemoved as $userId)
		{
			$command = (new Command\Task\MarkCompletedCommand($changedTaskStatus->task, (int)$userId));
			$command->execute();
		}
	}

	public function onDelegatedTask(ChangedTaskStatus $changedTaskStatus): void
	{
		if ($changedTaskStatus->workflow->isWorkflowShowInTimeline())
		{
			$delegateData = [
				'USERS' => $this->getUsers($changedTaskStatus->users),
				'TASK_ID' => $changedTaskStatus->task->id,
				'TASK_NAME' => $this->getTaskNameById($changedTaskStatus->task->id),
				'USERS_REMOVED' => $this->getUsers($changedTaskStatus->usersRemoved),
			];
			$authorId = (int)reset($delegateData['USERS'])['ID'];
			$data = $this->prepareWorkflowData($changedTaskStatus->workflow->id, $authorId, $delegateData);
			$this->createTimelineEntry($data, CategoryType::TASK_DELEGATED, $changedTaskStatus);

			$this->createTaskActivity($changedTaskStatus);
		}

		$this->markDelegatedTaskActivity($changedTaskStatus);
		$this->updateFacesInTaskActivity($changedTaskStatus->workflow);
	}

	private function markDelegatedTaskActivity(ChangedTaskStatus $changedTaskStatus): void
	{
		foreach ($changedTaskStatus->usersRemoved as $userId)
		{
			$userId = (int)$userId;
			if ($userId > 0)
			{
				$command = new Command\Task\MarkDelegatedCommand($changedTaskStatus->task, $userId);
				$command->execute();
			}
		}
	}

	public function onDeletedTask(ChangedTaskStatus $changedTaskStatus): void
	{
		$this->markDeletedTaskActivity($changedTaskStatus);
	}

	private function markDeletedTaskActivity(ChangedTaskStatus $changedTaskStatus): void
	{
		$command = (new Timeline\Bizproc\Command\Task\MarkDeletedCommand($changedTaskStatus->task));
		$command->execute();
	}

	public function onCreateComment(ChangedCommentStatus $request): void
	{
		$comments = $this->getResponsibleUserComments($request->documentId, $request->workflow->id);
		if (!$comments)
		{
			return;
		}

		$commentData = [
			'UNREAD_CNT' => $comments['UNREAD_CNT'],
			'LAST_COMMENT_DATE' => $comments['MODIFIED']?->getTimestamp(),
			'USERS' => $this->getUsers([$request->authorId]),
			'COMMENTS_VIEWED' => false,
		];
		$data = $this->prepareWorkflowData($request->workflow->id, $request->authorId, $commentData);
		$provider = new Provider\Bizproc\Comment();
		$identifier = new ItemIdentifier($data->getEntityTypeId(), $data->getEntityId());
		$activity = $provider->find($request->workflow->id, $identifier);
		if ($activity)
		{
			\CCrmActivity::Update($activity['ID'], [
				'SETTINGS' => array_merge(
					$activity['SETTINGS'],
					$commentData
				),
			]);
		}
		elseif ($request->workflow->isWorkflowShowInTimeline())
		{
			$this->onCreate($data, CategoryType::COMMENT_ADDED);
			$this->createActivity($data, $provider, true);
		}
	}

	public function onDeleteComment(ChangedCommentStatus $request): void
	{
		$comments = $this->getResponsibleUserComments($request->documentId, $request->workflow->id);
		if (!$comments)
		{
			return;
		}

		$commentData = [
			'UNREAD_CNT' => $comments['UNREAD_CNT'],
			'LAST_COMMENT_DATE' => $comments['MODIFIED']?->getTimestamp(),
			'USERS' => $this->getUsers([$request->authorId]),
			'COMMENTS_VIEWED' => false,
		];
		$data = $this->prepareWorkflowData($request->workflow->id, $request->authorId, $commentData);
		$provider = new Provider\Bizproc\Comment();
		$identifier = new ItemIdentifier($data->getEntityTypeId(), $data->getEntityId());
		$activity = $provider->find($request->workflow->id, $identifier);
		if ($activity)
		{
			\CCrmActivity::Update($activity['ID'], [
				'SETTINGS' => array_merge(
					$activity['SETTINGS'],
					$commentData
				),
				'COMPLETED' => (int)$commentData['UNREAD_CNT'] === 0 ? 'Y' : 'N',
			]);
		}
	}

	public function onViewedComment(ChangedCommentStatus $request): void
	{
		$comments = $this->getResponsibleUserComments($request->documentId, $request->workflow->id);
		if (!$comments)
		{
			return;
		}

		$data = $this->prepareWorkflowData($request->workflow->id, $request->authorId);
		$identifier = new ItemIdentifier($data->getEntityTypeId(), $data->getEntityId());
		$provider = new Provider\Bizproc\Comment();
		$activity = $provider->find($request->workflow->id, $identifier);
		if ($activity)
		{
			$manager = new Manager();
			$isWaitForClosure = $manager->getControlValue($manager::WAIT_FOR_CLOSURE_COMMENTS_OPTION) === 'Y';
			\CCrmActivity::Update($activity['ID'], [
				'SETTINGS' => array_merge(
					$activity['SETTINGS'],
					['COMMENTS_VIEWED' => true]
				),
				'COMPLETED' => $isWaitForClosure ? 'N' : 'Y',
			]);
		}

		if ($request->workflow->isWorkflowShowInTimeline())
		{
			$this->onCreate($data, CategoryType::COMMENT_READ);
		}
	}

	public function onCreateWorkflow(ChangedWorkflowStatus $changedWorkflowStatus): void
	{
		if ($changedWorkflowStatus->workflow->isWorkflowShowInTimeline())
		{
			$data = $this->prepareWorkflowData($changedWorkflowStatus->workflow->id);
			$this->onCreate($data, CategoryType::WORKFLOW_STARTED);
		}

		$this->sendCreateWorkflowAnalytics($changedWorkflowStatus);
	}

	private function sendCreateWorkflowAnalytics(ChangedWorkflowStatus $changedWorkflowStatus): void
	{
		$trackable = [
			\CBPDocumentEventType::Manual,
			\CBPDocumentEventType::Create,
			\CBPDocumentEventType::Edit,
		];

		if (!in_array($changedWorkflowStatus->documentEventType, $trackable, true))
		{
			return;
		}

		[$entityTypeId] = \CCrmBizProcHelper::resolveEntityId($changedWorkflowStatus->documentId);

		$event = new AnalyticsEvent('process_run', 'crm', 'bizproc_operations');
		$event
			->setSection(strtolower(\CCrmOwnerType::resolveName($entityTypeId)))
			->setType(
				$changedWorkflowStatus->documentEventType === \CBPDocumentEventType::Manual ? 'manual' : 'auto'
			)
			->setP1('timelineMode_' . ($changedWorkflowStatus->workflow->isWorkflowShowInTimeline() ? 'yes' : 'no'))
			->send()
		;
	}

	public function onCompleteWorkflow(ChangedWorkflowStatus $changedWorkflowStatus): void
	{
		if ($changedWorkflowStatus->workflow->isWorkflowShowInTimeline())
		{
			$workflowId = $changedWorkflowStatus->workflow->id;

			$settings = $this->getEfficiencyData($workflowId);
			$authorId = $this->getWorkflowAuthor($workflowId);
			$responsibleId = $this->getResponsibleId($changedWorkflowStatus->documentId);
			if (empty($authorId))
			{
				$authorId = $responsibleId;
			}
			$workflowAuthor = $this->getUser($authorId);

			$settings['WORKFLOW_STATUS'] = \CBPWorkflowStatus::Completed;
			$settings['WORKFLOW_STATUS_NAME'] = \CBPWorkflowStatus::Out(\CBPWorkflowStatus::Completed);
			$settings['WORKFLOW_AUTHOR'] = $workflowAuthor;

			$data = $this->prepareWorkflowData($workflowId, additionalData: $settings);
			$workflowParticipants = \CBPTaskService::getWorkflowParticipants($changedWorkflowStatus->workflow->id);
			$isManually = $changedWorkflowStatus->documentEventType === \CBPDocumentEventType::Manual;
			$isWorkflowParticipant = in_array($responsibleId, $workflowParticipants, true);
			$isAuthorResponsible = ($authorId === $responsibleId);
			if (
				($isManually && $isAuthorResponsible)
				|| ($isManually && !$isAuthorResponsible && $isWorkflowParticipant)
				|| (!$isManually && $isWorkflowParticipant)
			)
			{
				$provider = new Provider\Bizproc\Workflow();
				$this->createActivity($data, $provider);
			}

			$this->onCreate($data, CategoryType::WORKFLOW_COMPLETED);
		}

		$this->updateFacesInTaskActivity($changedWorkflowStatus->workflow);
	}

	public function onTerminateWorkflow(ChangedWorkflowStatus $changedWorkflowStatus): void
	{
		if ($changedWorkflowStatus->workflow->isWorkflowShowInTimeline())
		{
			$data = $this->prepareWorkflowData($changedWorkflowStatus->workflow->id);
			$this->onCreate($data, CategoryType::WORKFLOW_TERMINATED);
		}

		$this->updateFacesInTaskActivity($changedWorkflowStatus->workflow);
	}

	private function updateFacesInTaskActivity(Timeline\Bizproc\Data\Workflow $workflow): void
	{
		$command = new Timeline\Bizproc\Command\Task\UpdateFacesCommand($workflow);
		$command->execute();
	}

	private function prepareWorkflowData(string $workflowId, int $authorId = 0, array $additionalData = [])
	{
		$fields = \CBPStateService::getWorkflowStateInfo($workflowId);

		if (!is_array($fields))
		{
			throw new \Bitrix\Main\SystemException('Workflow state info should be an array');
		}

		[$entityTypeId, $entityId] = \CCrmBizProcHelper::resolveEntityId($fields['DOCUMENT_ID']);
		$status = (int)$fields['WORKFLOW_STATUS'];

		$settings = [
			'WORKFLOW_ID' => $fields['ID'],
			'WORKFLOW_TEMPLATE_ID' => $fields['WORKFLOW_TEMPLATE_ID'],
			'WORKFLOW_TEMPLATE_NAME' => $fields['WORKFLOW_TEMPLATE_NAME'],
			'WORKFLOW_STATUS' => $status,
			'WORKFLOW_STATUS_NAME' => \CBPWorkflowStatus::Out($status),
		];

		if (!empty($additionalData))
		{
			$settings = array_merge($settings, $additionalData);
		}

		if (empty($authorId))
		{
			$authorId = $fields['STARTED_BY'] ?? $this->getResponsibleId($fields['DOCUMENT_ID']);
		}

		return new TimelineParams(
			$workflowId,
			$entityTypeId,
			(int)$entityId,
			$settings,
			(int)$authorId
		);
	}

	private function createActivity(
		TimelineParams $data,
		Provider\Base $provider,
		$incoming = false
	): \Bitrix\Main\Result
	{
		return $provider->createActivity(
			$provider::getProviderTypeId(),
			[
				'BINDINGS' => [
					[
						'OWNER_ID' => $data->getEntityId(),
						'OWNER_TYPE_ID' => $data->getEntityTypeId(),
					],
				],
				'RESPONSIBLE_ID' => $data->getAuthorId(),
				'SUBJECT' => $provider::getSubject(),
				'SETTINGS' => $data->getSettings(),
				'ORIGIN_ID' => $data->getWorkflowId(),
				'IS_INCOMING_CHANNEL' => $incoming ? 'Y' : 'N',
			]
		);
	}

	private function getUsers(array $userIds): array
	{
		if (!$userIds)
		{
			return [];
		}

		$userIterator = \Bitrix\Main\UserTable::query()
			->setSelect(['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'])
			->setFilter(['ID' => $userIds])
			->exec()
		;
		$users = [];
		while ($user = $userIterator->fetchObject())
		{
			$users[] = [
				'ID' => $user['ID'],
				'FULL_NAME' => \CUser::FormatName(
					\CSite::GetNameFormat(),
					[
						'NAME' => $user->getName(),
						'LAST_NAME' => $user->getLastName(),
						'SECOND_NAME' => $user->getSecondName(),
					],
					false,
					false
				),
			];
		}

		return $users;
	}

	private function getTaskNameById(int $taskId): ?string
	{
		$task = \Bitrix\Bizproc\Workflow\Task\TaskTable::getRow([
			'filter' => ['ID' => $taskId],
			'select' => ['NAME'],
		]);
		if (isset($task))
		{
			return $task['NAME'];
		}

		return null;
	}

	private function getCommentData(string $workflowId, int $userId): array|false
	{
		return WorkflowUserCommentTable::query()
			->setFilter([
				'=WORKFLOW_ID' => $workflowId,
				'=USER_ID' => $userId,
			])
			->setSelect([
				'MODIFIED',
				'UNREAD_CNT',
			])
			->fetch()
		;
	}

	private function getResponsibleUserComments(array $documentId, string $workflowId): ?array
	{
		$responsibleId = $this->getResponsibleId($documentId);
		$info = $this->getCommentData($workflowId, $responsibleId);

		if (!$info)
		{
			return null;
		}

		return $info;
	}

	private function createTimelineEntry(
		TimelineParams $data,
		int $categoryType,
		ChangedTaskStatus $changedTaskStatus
	)
	{
		$responsibleId = $this->getResponsibleId($changedTaskStatus->documentId);
		$userIds = array_column($changedTaskStatus->task->getUsers(), 'ID');
		$isUserTaskParticipant = in_array($responsibleId, $userIds, true);
		$isAccessControlEnabled = $changedTaskStatus->task->getAccessControl();

		if (!$isUserTaskParticipant && $isAccessControlEnabled)
		{
			return;
		}

		$this->onCreate($data, $categoryType);
	}

	public function onCreate(TimelineParams $data, int $typeCategoryId): void
	{
		if (empty($data->getEntityTypeId()) && empty($data->getEntityId()))
		{
			return;
		}

		$bindings[] = [
			'ENTITY_TYPE_ID' => $data->getEntityTypeId(),
			'ENTITY_ID' => $data->getEntityId(),
		];
		$authorId = $data->getAuthorId();
		$params = [
			'TYPE_CATEGORY_ID' => $typeCategoryId,
			'ENTITY_TYPE_ID' => $data->getEntityTypeId(),
			'ENTITY_ID' => $data->getEntityId(),
			'AUTHOR_ID' => ($authorId > 0) ? $authorId : self::getCurrentOrDefaultAuthorId(),
			'SETTINGS' => $data->getSettings(),
			'BINDINGS' => $bindings,
		];

		if ($data->hasAssociatedEntityTypeId())
		{
			$params['ASSOCIATED_ENTITY_TYPE_ID'] = $data->getAssociatedEntityTypeId();
		}

		if ($data->hasAssociatedEntityId())
		{
			$params['ASSOCIATED_ENTITY_ID'] = $data->getAssociatedEntityId();
		}

		$timelineEntryId = $this->getTimelineEntryFacade()->create(
			Facade::BIZPROC,
			$params
		);
		if ($timelineEntryId <= 0)
		{
			return;
		}

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$timelineEntryId
			);
		}
	}

	public function prepareHistoryDataModel(array $data, array $options = null): array
	{
		return $data;
	}

	private function getResponsibleId(array $documentId): int
	{
		if ($this->responsibleId !== null)
		{
			return $this->responsibleId;
		}

		$this->responsibleId = \CCrmBizProcHelper::getDocumentResponsibleId($documentId);

		return $this->responsibleId;
	}
}
