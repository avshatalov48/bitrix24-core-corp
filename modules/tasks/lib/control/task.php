<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Im\V2\Service\Locator;
use Bitrix\Im\V2\Service\Messenger;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\Control\Exception\TaskAddException;
use Bitrix\Tasks\Control\Exception\TaskNotFoundException;
use Bitrix\Tasks\Control\Exception\TaskUpdateException;
use Bitrix\Tasks\Control\Handler\TaskFieldHandler;
use Bitrix\Tasks\Control\Handler\TariffFieldHandler;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\Helper\Analytics;
use Bitrix\Tasks\Integration\Bizproc\Listener;
use Bitrix\Tasks\Integration\CRM\TimeLineManager;
use Bitrix\Tasks\Integration\Disk;
use Bitrix\Tasks\Integration\Forum\Task\Topic;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Integration\SocialNetwork\Log;
use Bitrix\Tasks\Integration\SocialNetwork\User;
use Bitrix\Tasks\Internals\CacheConfig;
use Bitrix\Tasks\Internals\Counter\CounterService;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Notification\Controller;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\FavoriteTable;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\Internals\Task\ProjectLastActivityTable;
use Bitrix\Tasks\Internals\Task\RegularParametersTable;
use Bitrix\Tasks\Internals\Task\Result\Exception\ResultNotFoundException;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Internals\Task\SortingTable;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Kanban\TaskStageTable;
use Bitrix\Tasks\Member\Service\TaskMemberService;
use Bitrix\Tasks\Processor\Task\AutoCloser;
use Bitrix\Tasks\Processor\Task\Scheduler;
use Bitrix\Tasks\Replication\Task\Regularity\Exception\RegularityException;
use Bitrix\Tasks\Replication\Task\Regularity\Time\Service\RegularityService;
use Bitrix\Tasks\Replication\Replicator\RegularTaskReplicator;
use Bitrix\Tasks\Replication\Repository\TaskRepository;
use Bitrix\Tasks\Scrum\Internal\ItemTable;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util;
use Bitrix\Main\Localization\Loc;
use CAdminException;
use CApplicationException;
use CIBlock;
use CIBlockElement;
use CIBlockElementRights;
use CSearch;
use CSocNetGroup;
use CTaskAssertException;
use CTaskDependence;
use CTaskFiles;
use CTaskLog;
use CTaskMembers;
use CTaskNotifications;
use CTaskReminders;
use CTasks;
use CTaskSync;
use CTaskTags;
use CTaskTimerManager;
use CWebDavIblock;
use Exception;

class Task
{
	private const REGEX_TAG = '/\s#([^\s,\[\]<>]+)/is';
	private const FIELD_SCENARIO = 'SCENARIO_NAME';
	private const FIELD_REGULAR_PARAMETERS = 'REGULAR_PARAMS';

	private $taskId = 0;

	private $ufManager;
	private $cacheManager;
	private $application;

	private $needCorrectDatePlan = false;
	private $correctDatePlanDependent = false;
	private $fromAgent = false;
	private $fromWorkFlow = false;
	private $checkFileRights = false;
	private $cloneAttachments = false;
	private $skipExchangeSync = false;
	private $byPassParams = [];
	private $needAutoclose = false;
	private $skipNotifications = false;
	private $skipRecount = false;
	private $skipComments = false;
	private $skipPush = false;
	private $skipBP = false;
	private $isAddedComment = false;

	private $eventGuid;
	/** @var TaskObject */
	private $task;
	private $shiftResult;
	private $fullTaskData;
	private $eventTaskData;
	private $sourceTaskData;
	private $legacyOperationResultData;
	private $changes;

	private $occurUserId;

	private array $skipTimeZoneFields = [];

	public function __construct(private int $userId)
	{
		global $USER_FIELD_MANAGER;
		global $CACHE_MANAGER;
		global $APPLICATION;

		$this->ufManager = $USER_FIELD_MANAGER;
		$this->cacheManager = $CACHE_MANAGER;
		$this->application = $APPLICATION;
		$this->eventGuid = sha1(uniqid('AUTOGUID', true));
	}

	public function setByPassParams(array $params): self
	{
		$this->byPassParams = $params;
		return $this;
	}

	public function setEventGuid(string $guid): self
	{
		$this->eventGuid = $guid;
		return $this;
	}

	public function withSkipExchangeSync(): self
	{
		$this->skipExchangeSync = true;
		return $this;
	}

	public function withCorrectDatePlan(): self
	{
		$this->needCorrectDatePlan = true;
		return $this;
	}

	public function fromAgent(): self
	{
		$this->fromAgent = true;
		return $this;
	}

	public function fromWorkFlow(): self
	{
		$this->fromWorkFlow = true;
		return $this;
	}

	public function withFilesRights(): self
	{
		$this->checkFileRights = true;
		return $this;
	}

	public function withCloneAttachments(): self
	{
		$this->cloneAttachments = true;
		return $this;
	}

	public function withSkipNotifications(): self
	{
		$this->skipNotifications = true;
		return $this;
	}

	public function withAutoClose(): self
	{
		$this->needAutoclose = true;
		return $this;
	}

	public function withSkipRecount(): self
	{
		$this->skipRecount = true;
		return $this;
	}

	public function withSkipComments(): self
	{
		$this->skipComments = true;
		return $this;
	}

	public function withSkipPush(): self
	{
		$this->skipPush = true;
		return $this;
	}

	public function withCorrectDatePlanDependent(): self
	{
		$this->correctDatePlanDependent = true;
		return $this;
	}

	public function skipBP(): self
	{
		$this->skipBP = true;
		return $this;
	}

	public function skipDeadlineTimeZone(): static
	{
		$this->skipTimeZoneFields[] = 'DEADLINE';
		return $this;
	}

	public function getLegacyOperationResultData(): ?array
	{
		return $this->legacyOperationResultData;
	}

	/**
	 * @throws TaskAddException
	 * @throws TaskNotFoundException
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws CTaskAssertException
	 * @throws Exception
	 */
	public function add(array $fields): TaskObject
	{
		$this->reset();

		try
		{
			$fields = $this->prepareFields($fields);
		}
		catch (TaskFieldValidateException $exception)
		{
			$message = $exception->getMessage();
			$this->application->ThrowException(new CAdminException([
				['text' => $message],
			]));

			throw new TaskAddException($exception->getMessage());
		}

		if (!$fields)
		{
			throw new TaskAddException(Loc::getMessage('TASKS_UNKNOWN_ADD_ERROR'));
		}

		$fields = $this->cloneDiskAttachments($fields);
		$this->checkUserFields($fields);
		$fields = $this->correctDatePlan($fields);
		$fields = $this->onBeforeAdd($fields);

		try
		{
			$task = $this->insert($fields);
			$this->taskId = $task->getId();
			$fields['ID'] = $this->taskId;
		}
		catch (Exception $exception)
		{
			$this->handleAnalytics($fields, false);

			throw new TaskAddException($exception->getMessage());
		}

		$this->setStageId($task);
		$this->setScenario($fields);
		$this->addToFavorite($fields);
		$this->setMembers($fields);
		$this->addParameters($fields);
		$this->addFiles($fields);
		$this->setTags($fields);
		$this->setUserFields($fields);
		$this->addWebdavFiles($fields);
		$this->sendAddNotifications($fields);
		$this->setRegularParameters($fields);

		UserOption\Task::onTaskAdd($fields);
		CounterService::addEvent(EventDictionary::EVENT_AFTER_TASK_ADD, $fields);
		CTaskSync::AddItem($fields);

		$this->addLog();
		$fields = $this->onTaskAdd($fields);
		$this->setSearchIndex();
		$this->resetCache();
		$this->updateLastActivity();
		$this->postAddComment($fields);
		$this->sendAddPush($fields);
		$this->saveDependencies($fields);
		$this->pinInStage(true);

		$this->sendAddIntegrationEvent($fields);
		$this->handleAnalytics($fields);

		return $task;
	}

	/**
	 * @throws TaskNotFoundException
	 * @throws TaskUpdateException
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ResultNotFoundException
	 * @throws CTaskAssertException
	 * @throws Exception
	 */
	public function update(int $taskId, array $fields): TaskObject|bool
	{
		$this->reset();

		if ($taskId < 1)
		{
			return false;
		}

		$this->taskId = $taskId;

		CounterService::getInstance()->collectData($this->taskId);

		$taskBeforeUpdate = $this->fetchTaskObjectById($this->taskId);

		if (!$taskBeforeUpdate)
		{
			return false;
		}

		try
		{
			$fields = $this->prepareFields($fields);
		}
		catch (TaskFieldValidateException $exception)
		{
			$message = $exception->getMessage();
			$this->application->ThrowException(new CAdminException([
				['text' => $message],
			]));

			throw new TaskUpdateException($exception->getMessage());
		}

		if (
			Util\UserField::checkContainsUFKeys($fields)
			&& !$this->ufManager->CheckFields(Util\UserField\Task::getEntityCode(), $this->taskId, $fields,
				$this->userId)
		)
		{
			$message = $this->getApplicationError(Loc::getMessage('TASKS_UNKNOWN_UPDATE_ERROR'));
			throw new TaskUpdateException($message);
		}

		$fields = $this->updateTags($fields);

		$fields = $this->onBeforeUpdate($fields);
		$fields = $this->updateDatePlan($fields);

		try
		{
			$task = $this->save($fields);
		}
		catch (Exception $exception)
		{
			LogFacade::logThrowable($exception);
			throw new TaskUpdateException($exception->getMessage());
		}

		if (null === $task)
		{
			return false;
		}

		$this->changes = $this->getChanges($fields);

		$this->setStageId($task);
		$this->setMembers($fields, $this->changes);
		$this->setRegularParameters($fields);
		$this->updateParameters($fields);
		$this->saveFiles($fields);
		$this->setTags($fields);
		$this->updateDepends($fields);
		$this->updateUserFields($fields);

		$fields = $this->reloadTaskData($fields);

		$this->stopTimer();
		$this->saveUpdateLog();
		$this->autoCloseTasks($fields);
		$this->sendUpdateNotifications($fields);
		$this->updateSearchIndex($fields);

		CTaskSync::UpdateItem($fields, $this->sourceTaskData);
		$fields = $this->onUpdate($fields);
		$this->resetCache();
		$this->updateViewDate();
		UserOption\Task::onTaskUpdate($this->sourceTaskData, $fields);
		$this->updateCounters();
		$this->closeResult();
		$this->pinInStage();
		$this->updateTopicTitle();

		$updateComment = $this->postUpdateComments($fields);
		$this->sendUpdatePush($updateComment);

		$this->sendUpdateIntegrationEvent($fields, $taskBeforeUpdate);
		$this->replicate();

		return $task;
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws LoaderException
	 * @throws NotImplementedException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws CTaskAssertException
	 * @throws Exception
	 */
	public function delete(int $taskId): bool
	{
		$this->reset();

		if ($taskId < 1)
		{
			return false;
		}
		$this->taskId = $taskId;

		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return false;
		}

		$safeDelete = $this->proceedSafeDelete($taskData);

		CounterService::getInstance()->collectData($this->taskId);

		if (!$this->onBeforeDelete())
		{
			return false;
		}

		$taskObject = TaskRegistry::getInstance()->getObject($taskId, true)?->fillAllMemberIds();
		$timeLineManager = new TimeLineManager($taskId, $this->userId);
		$timeLineManager->onTaskDeleted();

		$this->stopTimer(true);
		$this->deleteRelations();

		if (!$safeDelete)
		{
			$this->unsafeDeleteRelations();
		}

		$this->resetCache();
		$this->updateAfterDelete();

		if (!$this->skipExchangeSync)
		{
			CTaskSync::DeleteItem($taskData);
		}

		$this->sendDeletePush();

		$deleteEventParameters = [
			'FLOW_ID' => $taskObject->fillFlowTask()?->getFlowId(),
			'USER_ID' => $this->userId,
		];

		$this->onTaskDelete($deleteEventParameters);

		if (!$safeDelete)
		{
			TaskTable::delete($taskId);
		}
		else
		{
			$sql = "DELETE FROM b_tasks WHERE ID = " . $taskId;
			Application::getConnection()->query($sql);
		}

		ScenarioTable::delete($taskId);

		$tagService = new Tag($this->userId);
		$tagService->unlinkTags($taskId);

		CTaskNotifications::SendDeleteMessage($taskData, $safeDelete, $taskObject);

		CounterService::addEvent(
			EventDictionary::EVENT_AFTER_TASK_DELETE,
			$taskData
		);

		$timeLineManager->save();
		$this->sendDeleteIntegrationEvent($safeDelete);

		return true;
	}

	/**
	 * @throws TaskNotFoundException
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function sendAddIntegrationEvent(array $fields): void
	{
		(new TimeLineManager($this->taskId, $this->userId))
			->onTaskCreated()
			->save();

		if (!$this->skipBP)
		{
			Listener::onTaskAdd($this->taskId, $fields);
		}

		$application = Application::getInstance();

		if (
			array_key_exists('IM_CHAT_ID', $fields)
			&& $fields['IM_CHAT_ID'] > 0
			&& Loader::includeModule('im')
			&& method_exists(Messenger::class, 'registerTask')
		)
		{
			$task = $this->getTask();
			$application
			&& $application->addBackgroundJob(
				function () use ($fields, $task) {
					$messageId = 0;

					if (isset($fields['IM_MESSAGE_ID']) && $fields['IM_MESSAGE_ID'] > 0)
					{
						$messageId = $fields['IM_MESSAGE_ID'];
					}

					Locator::getMessenger()->registerTask($fields['IM_CHAT_ID'], $messageId, $task);
				}
			);
		}
	}

	/**
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws TaskNotFoundException
	 */
	private function sendUpdateIntegrationEvent(array $fields, TaskObject $taskBeforeUpdate): void
	{
		$application = Application::getInstance();

		(new TimeLineManager($this->taskId, $this->userId))
			->onTaskUpdated($taskBeforeUpdate)
			->save();

		if (!$this->skipBP)
		{
			Listener::onTaskUpdate($this->taskId, $fields, $this->eventTaskData);
		}

		if (
			Loader::includeModule('im')
			&& method_exists(Messenger::class, 'updateTask')
		)
		{
			$task = $this->getTask();
			$application
			&& $application->addBackgroundJob(
				function () use ($task) {
					Locator::getMessenger()->updateTask($task);
				}
			);
		}
	}

	/**
	 * @throws LoaderException
	 */
	private function sendDeleteIntegrationEvent(bool $saveDelete): void
	{
		$application = Application::getInstance();

		$taskData = $this->getFullTaskData();

		if (
			$taskData
			&& Loader::includeModule('im')
			&& method_exists(Messenger::class, 'unregisterTask')
		)
		{
			$application
			&& $application->addBackgroundJob(
				function () use ($taskData, $saveDelete) {
					Locator::getMessenger()->unregisterTask($taskData, $saveDelete);
				}
			);
		}
	}

	private function getApplicationError(string $default = ''): string
	{
		$e = $this->application->GetException();

		if (is_a($e, CApplicationException::class))
		{
			$message = $e->GetString();
			$message = explode('<br>', $message);
			return $message[0];
		}

		if (
			!is_object($e)
			|| !isset($e->messages)
			|| !is_array($e->messages)
		)
		{
			return $default;
		}

		$message = array_shift($e->messages);

		if (
			is_array($message)
			&& isset($message['text'])
		)
		{
			$message = $message['text'];
		}
		elseif (!is_string($message))
		{
			$message = $default;
		}

		return $message;
	}

	private function updateTopicTitle(): void
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		if ($taskData['TITLE'] === $this->sourceTaskData['TITLE'])
		{
			return;
		}

		Topic::updateTopicTitle($taskData['FORUM_TOPIC_ID'], $taskData['TITLE']);
	}

	private function pinInStage(bool $addNew = false)
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		$newUsers = [];
		foreach (['CREATED_BY', 'RESPONSIBLE_ID', 'AUDITORS', 'ACCOMPLICES'] as $key)
		{
			if (array_key_exists($key, $taskData) && isset($taskData[$key]))
			{
				if (!is_array($taskData[$key]))
				{
					$taskData[$key] = [$taskData[$key]];
				}
				$newUsers = array_merge($newUsers, $taskData[$key]);
			}
		}
		StagesTable::pinInStage($this->taskId, $newUsers);

		if (
			$addNew
			|| !$taskData['GROUP_ID']
			|| (int)$taskData['GROUP_ID'] === (int)$this->sourceTaskData['GROUP_ID']
		)
		{
			return;
		}

		StagesTable::pinInStage(
			$this->taskId,
			[
				'CREATED_BY' => $this->sourceTaskData['CREATED_BY'],
			],
			true
		);
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function sendUpdatePush($updateComment): void
	{
		if ($this->skipPush)
		{
			return;
		}
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		$newParticipants = $this->getParticipants($taskData);
		$oldParticipants = $this->getParticipants($this->sourceTaskData);
		$participants = array_unique(array_merge($newParticipants, $oldParticipants));
		$removedParticipants = array_unique(array_diff($oldParticipants, $newParticipants));

		$before = [];
		$after = [];

		foreach ($this->changes as $field => $value)
		{
			$before[$field] = $value['FROM_VALUE'];
			$after[$field] = $value['TO_VALUE'];
		}

		$before['GROUP_ID'] = (int)$this->sourceTaskData['GROUP_ID'];
		$after['GROUP_ID'] = (int)$taskData['GROUP_ID'];

		$lastResult = ResultManager::getLastResult($this->taskId);

		$params = [
			'TASK_ID' => $this->taskId,
			'USER_ID' => $this->userId,
			'BEFORE' => $before,
			'AFTER' => $after,
			'TS' => time(),
			'event_GUID' => $this->eventGuid,
			'params' => [
				'HIDE' => (!array_key_exists('HIDE', $this->byPassParams) || $this->byPassParams['HIDE']),
				'updateCommentExists' => $updateComment,
				'removedParticipants' => array_values($removedParticipants),
			],
			'taskRequireResult' => ResultManager::requireResult($this->taskId) ? "Y" : "N",
			'taskHasResult' => $lastResult ? "Y" : "N",
			'taskHasOpenResult' => ($lastResult && (int)$lastResult['STATUS'] === ResultTable::STATUS_OPENED) ? "Y"
				: "N",
		];

		try
		{
			PushService::addEvent($participants, [
				'module_id' => 'tasks',
				'command' => PushCommand::TASK_UPDATED,
				'params' => $params,
			]);
		}
		catch (Exception $exception)
		{
			LogFacade::logThrowable($exception);
			return;
		}
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function postUpdateComments(array $fields): bool
	{
		$updateComment = false;
		if ($this->skipComments)
		{
			return false;
		}

		$fieldsForComments = [
			'STATUS',
			'CREATED_BY',
			'RESPONSIBLE_ID',
			'ACCOMPLICES',
			'AUDITORS',
			'DEADLINE',
			'GROUP_ID',
			'FLOW_ID',
		];
		$changesForUpdate = array_intersect_key($this->changes, array_flip($fieldsForComments));

		if (empty($changesForUpdate))
		{
			return false;
		}

		$commentPoster = CommentPoster::getInstance($this->taskId, $this->getOccurUserId());
		if ($commentPoster)
		{
			if (!($isDeferred = $commentPoster->getDeferredPostMode()))
			{
				$commentPoster->enableDeferredPostMode();
			}

			$commentPoster->postCommentsOnTaskUpdate($this->sourceTaskData, $fields, $changesForUpdate);
			$updateComment =
				$commentPoster->getCommentByType(Comment::TYPE_UPDATE)
				|| $commentPoster->getCommentByType(Comment::TYPE_STATUS);

			if (!$isDeferred)
			{
				$commentPoster->disableDeferredPostMode();
				$commentPoster->postComments();
				$commentPoster->clearComments();
			}
		}

		return $updateComment;
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ResultNotFoundException
	 */
	private function closeResult(): void
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		if (in_array((int)$taskData['STATUS'], [Status::COMPLETED, Status::SUPPOSEDLY_COMPLETED], true))
		{
			(new ResultManager($this->getOccurUserId()))->close($this->taskId);
		}
	}

	private function updateCounters(): void
	{
		if ($this->skipRecount)
		{
			return;
		}
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		CounterService::addEvent(
			EventDictionary::EVENT_AFTER_TASK_UPDATE,
			[
				'OLD_RECORD' => $this->sourceTaskData,
				'NEW_RECORD' => $taskData,
				'PARAMS' => $this->byPassParams,
			]
		);
	}

	/**
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function updateViewDate(): void
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		$newParticipants = $this->getParticipants($taskData);
		$oldParticipants = $this->getParticipants($this->sourceTaskData);
		$addedParticipants = array_unique(array_diff($newParticipants, $oldParticipants));

		if (
			!empty($addedParticipants)
			&& $viewedDate = \Bitrix\Tasks\Comments\Task::getLastCommentTime($this->taskId)
		)
		{
			foreach ($addedParticipants as $userId)
			{
				ViewedTable::set($this->taskId, $userId, $viewedDate);
			}
		}
	}

	private function getParticipants(array $taskData): array
	{
		return array_unique(
			array_merge(
				[
					$taskData["CREATED_BY"],
					$taskData["RESPONSIBLE_ID"],
				],
				$taskData["ACCOMPLICES"],
				$taskData["AUDITORS"]
			)
		);
	}

	private function updateSearchIndex(array $fields): void
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		$mergedFields = array_merge($taskData, $fields);

		CTasks::Index($mergedFields, $fields["TAGS"]);
		SearchIndex::setTaskSearchIndex($this->taskId);
	}

	/**
	 * @throws TaskUpdateException
	 */
	private function onUpdate(array $fields): array
	{
		$fields['META:PREV_FIELDS'] = $this->sourceTaskData;

		try
		{
			foreach (GetModuleEvents('tasks', 'OnTaskUpdate', true) as $event)
			{
				ExecuteModuleEventEx($event, [$this->taskId, &$fields, &$this->eventTaskData]);
			}
		}
		catch (Exception $exception)
		{
			LogFacade::logThrowable($exception);
			throw new TaskUpdateException(
				$this->getApplicationError(Loc::getMessage('TASKS_UNKNOWN_UPDATE_ERROR'))
			);
		}

		unset($fields['META:PREV_FIELDS']);

		return $fields;
	}

	private function sendUpdateNotifications(array $fields): void
	{
		if ($this->skipNotifications)
		{
			return;
		}

		if (!$this->sourceTaskData)
		{
			return;
		}

		$fullTaskData = $this->getFullTaskData();

		$notificationFields = array_merge($fields, ['CHANGED_BY' => $this->getOccurUserId()]);
		$statusChanged = $fullTaskData['STATUS_CHANGED'] ?? false;

		if ($statusChanged)
		{
			$status = (int)$fullTaskData['REAL_STATUS'] ?? null;
			CTaskNotifications::SendStatusMessage(
				$this->sourceTaskData,
				$status,
				$notificationFields
			);
		}

		CTaskNotifications::SendUpdateMessage(
			$notificationFields,
			$this->sourceTaskData,
			false,
			$this->byPassParams
		);
	}

	/**
	 * @return void
	 */
	private function reset(): void
	{
		$this->eventGuid = null;
		$this->task = null;
		$this->shiftResult = null;
		$this->fullTaskData = null;
		$this->eventTaskData = null;
		$this->sourceTaskData = null;
		$this->legacyOperationResultData = null;
		$this->changes = null;
	}

	/**
	 * @throws TaskNotFoundException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function reloadTaskData(array $fields): array
	{
		$this->sourceTaskData = $this->getFullTaskData();
		$this->getFullTaskData(true);

		if (!$this->fullTaskData)
		{
			return $fields;
		}

		$currentStatus = (int)$this->fullTaskData['REAL_STATUS'];
		$prevStatus = (int)$this->sourceTaskData['REAL_STATUS'];
		$statusChanged =
			$currentStatus !== $prevStatus
			&& $currentStatus >= Status::NEW
			&& $currentStatus <= Status::DECLINED;

		if ($statusChanged)
		{
			$this->fullTaskData['STATUS_CHANGED'] = true;

			if ($currentStatus === Status::DECLINED)
			{
				$this->fullTaskData['DECLINE_REASON'] = $fields['DECLINE_REASON'];
			}
		}

		$fields['ID'] = $this->taskId;

		$this->getTask(true);
		$scenarioObject = $this->task->getScenario();
		$fields['SCENARIO'] = is_null($scenarioObject) ? ScenarioTable::SCENARIO_DEFAULT
			: $scenarioObject->getScenario();

		return $fields;
	}

	/**
	 * @throws ArgumentException
	 */
	private function autoCloseTasks(array $fields): void
	{
		if (
			!array_key_exists('STATUS', $fields)
			|| (int)$fields['STATUS'] !== Status::COMPLETED
		)
		{
			return;
		}
		if (!$this->needAutoclose)
		{
			return;
		}

		$closer = AutoCloser::getInstance($this->userId);
		$closeResult = $closer->processEntity($this->taskId, $fields);
		if ($closeResult->isSuccess())
		{
			$closeResult->save(['!ID' => $this->taskId]);
		}
	}

	/**
	 * @throws TaskNotFoundException
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function updateDepends(array $fields): void
	{
		if (array_key_exists('DEPENDS_ON', $fields))
		{
			$dependence = new Dependence($this->userId, $this->taskId);
			$dependence->setPrevious($fields['DEPENDS_ON']);
		}

		// backward compatibility with PARENT_ID
		if (array_key_exists('PARENT_ID', $fields))
		{
			// PARENT_ID changed, reattach subtree from previous location to new one
			\Bitrix\Tasks\Internals\Helper\Task\Dependence::attach($this->taskId, intval($fields['PARENT_ID']));
		}

		if ($this->correctDatePlanDependent)
		{
			if (!$this->shiftResult)
			{
				$this->shiftResult = Scheduler::getInstance($this->userId)->processEntity($this->taskId, $fields);
			}
			$saveResult = $this->shiftResult->save(['!ID' => $this->taskId]);
			if ($saveResult->isSuccess())
			{
				$this->legacyOperationResultData['SHIFT_RESULT'] = $this->shiftResult->exportData();
			}
		}
	}

	/**
	 * @throws Exception
	 */
	private function saveFiles(array $fields): void
	{
		if (
			isset($fields["FILES"])
			&& (isset($this->changes["NEW_FILES"]) || isset($this->changes["DELETED_FILES"]))
		)
		{
			$arNotDeleteFiles = $fields["FILES"];
			CTaskFiles::DeleteByTaskID($this->taskId, $arNotDeleteFiles);
			$this->addFiles($fields);
		}
	}

	private function saveUpdateLog(): void
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		foreach ($this->changes as $key => $value)
		{
			$arLogFields = [
				"TASK_ID" => $this->taskId,
				"USER_ID" => $this->getOccurUserId(),
				"CREATED_DATE" => $taskData["CHANGED_DATE"],
				"FIELD" => $key,
				"FROM_VALUE" => $value["FROM_VALUE"],
				"TO_VALUE" => $value["TO_VALUE"],
			];

			$log = new CTaskLog();
			$log->Add($arLogFields);
		}
	}

	private function getChanges(array $fields): array
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return [];
		}

		if (isset($taskData['DURATION_PLAN']))
		{
			unset($taskData['DURATION_PLAN']);
		}
		if (isset($fields['DURATION_PLAN']))
		{
			// at this point, $arFields['DURATION_PLAN'] in seconds
			$fields['DURATION_PLAN_SECONDS'] = $fields['DURATION_PLAN'];
			unset($fields['DURATION_PLAN']);
		}

		return CTaskLog::GetChanges($taskData, $fields);
	}

	/**
	 * @throws TaskUpdateException
	 */
	private function onBeforeUpdate(array $fields): array
	{
		$this->eventTaskData = $this->getFullTaskData();
		if (!$this->eventTaskData)
		{
			return $fields;
		}

		foreach (GetModuleEvents('tasks', 'OnBeforeTaskUpdate', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, [$this->taskId, &$fields, &$this->eventTaskData]) === false)
			{
				$message = $this->getApplicationError(Loc::getMessage('TASKS_UNKNOWN_UPDATE_ERROR'));
				throw new TaskUpdateException($message);
			}
		}

		return $fields;
	}

	private function updateTags(array $fields): array
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return $fields;
		}

		if (!array_key_exists('TAGS', $fields))
		{
			$fields['TAGS'] = $taskData['TAGS'];
		}
		if (!array_key_exists('TITLE', $fields))
		{
			$fields['TITLE'] = $taskData['TITLE'];
		}
		if (!array_key_exists('DESCRIPTION', $fields))
		{
			$fields['DESCRIPTION'] = $taskData['DESCRIPTION'];
		}

		$fields['TAGS'] = $this->parseTags($fields);

		return $fields;
	}

	/**
	 * @throws SqlQueryException
	 */
	private function onTaskDelete(array $parameters = []): void
	{
		$parameters = array_merge($parameters, $this->byPassParams);

		foreach (GetModuleEvents('tasks', 'OnTaskDelete', true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, [$this->taskId, $parameters]);
		}
		if (!$this->skipBP)
		{
			Listener::onTaskDelete($this->taskId);
		}
		ItemTable::deactivateBySourceId($this->taskId);
	}

	/**
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws CTaskAssertException
	 */
	private function sendDeletePush(): void
	{
		if (!Loader::includeModule('pull'))
		{
			return;
		}

		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		$pushRecipients = array_unique(
			array_merge(
				[
					$taskData["CREATED_BY"],
					$taskData["RESPONSIBLE_ID"],
				],
				$taskData["ACCOMPLICES"],
				$taskData["AUDITORS"]
			)
		);

		$groupId = (isset($taskData['GROUP_ID']) && $taskData['GROUP_ID'] > 0) ? (int)$taskData['GROUP_ID'] : 0;
		if ($groupId > 0)
		{
			$pushRecipients = array_unique(
				array_merge(
					$pushRecipients,
					User::getUsersCanPerformOperation($groupId, 'view_all')
				)
			);
		}

		$flowId = (isset($taskData['FLOW_ID']) && (int) $taskData['FLOW_ID']) ? (int) $taskData['FLOW_ID'] : 0;

		PushService::addEvent($pushRecipients, [
			'module_id' => 'tasks',
			'command' => PushCommand::TASK_DELETED,
			'params' => [
				'TASK_ID' => $this->taskId,
				'FLOW_ID' => $flowId,
				'TS' => time(),
				'event_GUID' => $this->eventGuid,
				'BEFORE' => [
					'GROUP_ID' => $groupId,
				],
			],
		]);
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws CTaskAssertException
	 */
	private function updateAfterDelete(): void
	{
		$connection = Application::getConnection();

		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		SortingTable::fixSiblingsEx($this->taskId);

		$parentId = $taskData["PARENT_ID"] ?: "NULL";

		$sql = "
			UPDATE b_tasks_template 
			SET TASK_ID = NULL 
			WHERE TASK_ID = " . $this->taskId;
		$connection->queryExecute($sql);

		$sql = "
			UPDATE b_tasks_template 
			SET PARENT_ID = " . $parentId . " 
			WHERE PARENT_ID = " . $this->taskId;
		$connection->queryExecute($sql);

		$sql = "
			UPDATE b_tasks 
			SET PARENT_ID = " . $parentId . " 
			WHERE PARENT_ID = " . $this->taskId;
		$connection->queryExecute($sql);
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws NotImplementedException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function unsafeDeleteRelations(): void
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		CTaskFiles::DeleteByTaskID($this->taskId);
		CTaskTags::DeleteByTaskID($this->taskId);
		FavoriteTable::deleteByTaskId($this->taskId, ['LOW_LEVEL' => true]);
		SortingTable::deleteByTaskId($this->taskId);
		UserOption::deleteByTaskId($this->taskId);
		TaskStageTable::clearTask($this->taskId);
		TaskCheckListFacade::deleteByEntityIdOnLowLevel($this->taskId);

		(new ResultManager($this->userId))->deleteByTaskId($this->taskId);

		ViewedTable::deleteList([
			'=TASK_ID' => $this->taskId,
		]);
		ParameterTable::deleteList([
			'=TASK_ID' => $this->taskId,
		]);
		SearchIndexTable::deleteList([
			'=TASK_ID' => $this->taskId,
		]);

		TemplateDependenceTable::deleteList([
			'=DEPENDS_ON_ID' => $this->taskId,
		]);

		Topic::delete($taskData["FORUM_TOPIC_ID"]);
		$this->ufManager->Delete(Util\UserField\Task::getEntityCode(), $this->taskId);

		if (Loader::includeModule('socialnetwork'))
		{
			Log::deleteLogByTaskId($this->taskId);
		}
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws CTaskAssertException
	 * @throws LoaderException
	 */
	private function deleteRelations(): void
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		CTaskMembers::DeleteAllByTaskID($this->taskId);
		CTaskDependence::DeleteByTaskID($this->taskId);
		CTaskDependence::DeleteByDependsOnID($this->taskId);
		CTaskReminders::DeleteByTaskID($this->taskId);
		RegularParametersTable::deleteByTaskId($this->taskId);

		$tableResult = ProjectDependenceTable::getList([
			"select" => ['TASK_ID'],
			"filter" => [
				"=TASK_ID" => $this->taskId,
				"DEPENDS_ON_ID" => $this->taskId,
			],
		]);

		if (ProjectDependenceTable::checkItemLinked($this->taskId) || $tableResult->fetch())
		{
			ProjectDependenceTable::deleteLink($this->taskId, $this->taskId);
		}

		$children = \Bitrix\Tasks\Internals\Helper\Task\Dependence::getSubTree($this->taskId)
			->find(['__PARENT_ID' => $this->taskId])
			->getData();
		\Bitrix\Tasks\Internals\Helper\Task\Dependence::delete($this->taskId);

		if (
			$taskData['PARENT_ID']
			&& !empty($children)
		)
		{
			foreach ($children as $child)
			{
				\Bitrix\Tasks\Internals\Helper\Task\Dependence::attach($child['__ID'], $taskData['PARENT_ID']);
			}
		}

		if (
			$taskData['PARENT_ID']
			&& $taskData['START_DATE_PLAN']
			&& $taskData['END_DATE_PLAN']
		)
		{
			// we need to scan for parent bracket tasks change...
			$scheduler = Scheduler::getInstance($this->userId);
			// we could use MODE => DETACH here, but there we can act in more effective way by
			// re-calculating tree of PARENT_ID after removing link between ID and PARENT_ID
			// we also do not need to calculate detached tree
			// it is like DETACH_AFTER
			$shiftResult = $scheduler->processEntity($taskData['PARENT_ID']);
			if ($shiftResult->isSuccess())
			{
				$shiftResult->save();
			}
		}

		if (Loader::includeModule("search"))
		{
			CSearch::DeleteIndex("tasks", $this->taskId);
		}

		if (Loader::includeModule('socialnetwork'))
		{
			Log::hideLogByTaskId($this->taskId);
		}
	}

	private function stopTimer(bool $force = false): void
	{
		$taskData = $this->getFullTaskData();

		if (!$taskData)
		{
			return;
		}

		if (
			!$force
			&& !in_array((int)$taskData['STATUS'], [Status::COMPLETED, Status::SUPPOSEDLY_COMPLETED], true)
		)
		{
			return;
		}

		$timer = CTaskTimerManager::getInstance($taskData['CREATED_BY']);
		$timer->stop($this->taskId);

		$timer = CTaskTimerManager::getInstance($taskData['RESPONSIBLE_ID']);
		$timer->stop($this->taskId);

		$accomplices = $taskData['ACCOMPLICES'];
		if (isset($accomplices) && !empty($accomplices))
		{
			foreach ($accomplices as $accompliceId)
			{
				$accompliceTimer = CTaskTimerManager::getInstance($accompliceId);
				$accompliceTimer->stop($this->taskId);
			}
		}
	}

	private function onBeforeDelete(): bool
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return false;
		}
		foreach (GetModuleEvents('tasks', 'OnBeforeTaskDelete', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, [$this->taskId, $taskData, $this->byPassParams]) === false)
			{
				return false;
			}
		}
		return true;
	}

	private function proceedSafeDelete($taskData): bool
	{
		try
		{
			if (!$taskData)
			{
				return false;
			}
			if (!Loader::includeModule('recyclebin'))
			{
				return false;
			}
			return \Bitrix\Tasks\Integration\Recyclebin\Task::OnBeforeTaskDelete($this->taskId, $taskData);
		}
		catch (Exception $exception)
		{
			LogFacade::logThrowable($exception);
			return false;
		}
	}

	/**
	 * @throws TaskNotFoundException
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function saveDependencies(array $fields): void
	{
		if (array_key_exists('DEPENDS_ON', $fields))
		{
			$dependence = new Dependence($this->userId, $this->taskId);
			$dependence->setPrevious($fields['DEPENDS_ON']);
		}

		$parentId = 0;
		if (array_key_exists('PARENT_ID', $fields))
		{
			$parentId = (int)$fields['PARENT_ID'];
		}

		// backward compatibility with PARENT_ID
		if ($parentId)
		{
			\Bitrix\Tasks\Internals\Helper\Task\Dependence::attachNew($this->taskId, $parentId);
		}

		if (!$this->shiftResult)
		{
			return;
		}

		$shiftResult = $this->shiftResult;
		if ($parentId)
		{
			$childrenCountDbResult = CTasks::GetChildrenCount([], $parentId);
			$fetchedChildrenCount = $childrenCountDbResult->Fetch();
			$childrenCount = $fetchedChildrenCount ? $fetchedChildrenCount['CNT'] : 0;

			if ($childrenCount == 1)
			{
				$scheduler = Scheduler::getInstance($this->userId);
				$shiftResult = $scheduler->processEntity(
					0,
					$fields,
					['MODE' => 'BEFORE_ATTACH']
				);
			}
		}

		$shiftResult->save(['!ID' => 0]);
	}

	private function sendAddPush(array $fields): void
	{
		$fullTaskData = $this->getFullTaskData();
		if (!$fullTaskData)
		{
			return;
		}

		$mergedFields = array_merge($fullTaskData, $fields, $this->byPassParams);

		$pushRecipients = [
			$fullTaskData['CREATED_BY'],
			$fullTaskData['RESPONSIBLE_ID'],
		];
		$pushRecipients = array_unique(array_merge($pushRecipients, $fullTaskData['AUDITORS'],
			$fullTaskData['ACCOMPLICES']));

		try
		{
			$groupId = (int)$mergedFields['GROUP_ID'];
			if ($groupId > 0)
			{
				$pushRecipients = array_unique(
					array_merge(
						$pushRecipients,
						User::getUsersCanPerformOperation($groupId, 'view_all')
					)
				);
			}

			PushService::addEvent($pushRecipients, [
				'module_id' => 'tasks',
				'command' => PushCommand::TASK_ADDED,
				'params' => $this->prepareAddPullEventParameters($mergedFields),
			]);
		}
		catch (Exception $exception)
		{
			LogFacade::logThrowable($exception);
		}
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function prepareAddPullEventParameters(array $mergedFields): array
	{
		$lastResult = ResultManager::getLastResult($this->taskId);

		return [
			'TASK_ID' => $this->taskId,
			'AFTER' => $mergedFields,
			'TS' => time(),
			'event_GUID' => $this->eventGuid,
			'params' => [
				'addCommentExists' => $this->isAddedComment,
			],
			'taskRequireResult' => ResultManager::requireResult($this->taskId) ? "Y" : "N",
			'taskHasResult' => $lastResult ? "Y" : "N",
			'taskHasOpenResult' => ($lastResult && (int)$lastResult['STATUS'] === ResultTable::STATUS_OPENED) ? "Y"
				: "N",
		];
	}

	private function postAddComment(array $fields): void
	{
		$fullTaskData = $this->getFullTaskData();
		if (!$fullTaskData)
		{
			return;
		}

		$mergedFields = array_merge($fullTaskData, $fields);

		$commentPoster = CommentPoster::getInstance($this->taskId, $this->getOccurUserId());
		if (!$commentPoster)
		{
			return;
		}

		if (!($isDeferred = $commentPoster->getDeferredPostMode()))
		{
			$commentPoster->enableDeferredPostMode();
		}

		$commentPoster->postCommentsOnTaskAdd($mergedFields);
		$this->isAddedComment = (bool)$commentPoster->getCommentByType(Comment::TYPE_ADD);

		if (!$isDeferred)
		{
			$commentPoster->disableDeferredPostMode();
			$commentPoster->postComments();
			$commentPoster->clearComments();
		}
	}

	/**
	 * @throws TaskNotFoundException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws LoaderException
	 * @throws Exception
	 */
	private function updateLastActivity(): void
	{
		$task = $this->getTask();

		if (!$task->getGroupId())
		{
			return;
		}

		ProjectLastActivityTable::update(
			$task->getGroupId(),
			['ACTIVITY_DATE' => $task->getActivityDate()]
		);

		if (Loader::includeModule('socialnetwork'))
		{
			CSocNetGroup::SetLastActivity($task->getGroupId());
		}
	}

	private function resetCache(): void
	{
		TaskAccessController::dropItemCache($this->taskId);
		TaskMemberService::invalidate();

		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		$participants = $this->getParticipants($taskData);

		// clear cache
		$this->cacheManager->ClearByTag("tasks_" . $this->taskId);

		if ($taskData["GROUP_ID"])
		{
			$this->cacheManager->ClearByTag("tasks_group_" . $taskData["GROUP_ID"]);
		}
		foreach ($participants as $userId)
		{
			$this->cacheManager->ClearByTag("tasks_user_" . $userId);
		}
		$cache = Cache::createInstance();
		$cache->clean(CacheConfig::UNIQUE_CODE, CacheConfig::DIRECTORY);
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function setSearchIndex(): void
	{
		$task = $this->getTask();
		$task->fillMemberList();
		$task->fillTagList();

		$tagList = $task->getTagList();
		$tags = [];
		foreach ($tagList as $tag)
		{
			$tags[] = $tag->getName();
		}

		$memberList = $task->getMemberList();
		$accomplices = [];
		$auditors = [];
		foreach ($memberList as $member)
		{
			if ($member->getType() === MemberTable::MEMBER_TYPE_ACCOMPLICE)
			{
				$accomplices[] = $member->getUserId();
			}
			elseif ($member->getType() === MemberTable::MEMBER_TYPE_AUDITOR)
			{
				$auditors[] = $member->getUserId();
			}
		}

		$taskData = [
			'ID' => $task->getId(),
			'TITLE' => $task->getTitle(),
			'DESCRIPTION' => $task->getDescription(),
			'SE_TAG' => $tags,
			'GROUP_ID' => $task->getGroupId(),
			'SITE_ID' => $task->getSiteId(),
			'CREATED_BY' => $task->getCreatedBy(),
			'RESPONSIBLE_ID' => $task->getResponsibleId(),
			'ACCOMPLICES' => $accomplices,
			'AUDITORS' => $auditors,
		];
		if ($task->getChangedDate())
		{
			$taskData['CHANGED_DATE'] = $task->getChangedDate()->toString();
		}
		if ($task->getCreatedDate())
		{
			$taskData['CREATED_DATE'] = $task->getCreatedDate()->toString();
		}

		\Bitrix\Tasks\Integration\Search\Task::index($taskData);
		SearchIndex::setTaskSearchIndex($this->taskId);
	}

	private function onTaskAdd(array $fields): array
	{
		$parameters = [
			'USER_ID' => $this->userId,
		];

		try
		{
			foreach (GetModuleEvents('tasks', 'OnTaskAdd', true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, [$this->taskId, &$fields, $parameters]);
			}
		}
		catch (Exception $exception)
		{
			LogFacade::logThrowable($exception);
			Util::log($exception);
		}

		return $fields;
	}

	private function addLog(): void
	{
		$arLogFields = [
			"TASK_ID" => $this->taskId,
			"USER_ID" => $this->getOccurUserId(),
			"CREATED_DATE" => UI::formatDateTime(Util\User::getTime()),
			"FIELD" => "NEW",
		];
		$log = new CTaskLog();
		$log->Add($arLogFields);
	}

	private function sendAddNotifications(array $fields): void
	{
		if ($fields['IS_REGULAR'])
		{
			return;
		}

		$fields = array_merge($fields, $this->byPassParams);

		CTaskNotifications::SendAddMessage(
			array_merge(
				$fields,
				[
					'CHANGED_BY' => $this->getOccurUserId(),
					'ID' => $this->taskId,
				]
			),
			[
				'SPAWNED_BY_AGENT' => $this->fromAgent,
				'SPAWNED_BY_WORKFLOW' => $this->fromWorkFlow,
			]
		);
	}

	/**
	 * @throws Exception
	 */
	private function sendRegularTaskReplicatedNotifications(array $fields): void
	{
		if (!$fields['IS_REGULAR'])
		{
			return;
		}

		$task = TaskRegistry::getInstance()->getObject($this->taskId, true);
		if (!$task)
		{
			return;
		}
		$controller = new Controller();
		$controller->onRegularTaskReplicated($task, ['SPAWNED_BY_AGENT' => $this->fromAgent]);
		$controller->push();
	}

	/**
	 * @throws TaskNotFoundException
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function addWebdavFiles(array $fields): void
	{
		if (
			!isset($fields['UF_TASK_WEBDAV_FILES'])
			|| !is_array($fields['UF_TASK_WEBDAV_FILES'])
		)
		{
			return;
		}

		$filesIds = array_filter($fields['UF_TASK_WEBDAV_FILES']);

		if (empty($filesIds))
		{
			return;
		}

		$this->addFilesRights($filesIds);
	}

	/**
	 * @throws TaskNotFoundException
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function addFilesRights($filesIds): void
	{
		$filesIds = array_unique(array_filter($filesIds));

		// Nothing to do?
		if (empty($filesIds))
		{
			return;
		}

		if (
			!Loader::includeModule('webdav')
			|| !Loader::includeModule('iblock')
		)
		{
			return;
		}

		$arRightsTasks = CWebDavIblock::GetTasks();

		$task = $this->getTask();
		$task->fillMemberList();

		$memberList = $task->getMemberList();

		$members = [
			$task->getCreatedBy(),
			$task->getResponsibleId(),
		];
		foreach ($memberList as $member)
		{
			$members[] = $member->getUserId();
		}
		$members = array_unique($members);

		$ibe = new CIBlockElement();
		$dbWDFile = $ibe->GetList(
			[],
			[
				'ID' => $filesIds,
				'SHOW_NEW' => 'Y',
			],
			false,
			false,
			['ID', 'NAME', 'SECTION_ID', 'IBLOCK_ID', 'WF_NEW']
		);

		if (!$dbWDFile)
		{
			return;
		}

		$i = 0;
		$arRightsForTaskMembers = [];
		foreach ($members as $userId)
		{
			// For intranet users and their managers
			$arRightsForTaskMembers['n' . $i++] = [
				'GROUP_CODE' => 'IU' . $userId,
				'TASK_ID' => $arRightsTasks['R'],        // rights for reading
			];

			// For extranet users
			$arRightsForTaskMembers['n' . $i++] = [
				'GROUP_CODE' => 'U' . $userId,
				'TASK_ID' => $arRightsTasks['R'],        // rights for reading
			];
		}
		$iNext = $i;

		while ($arWDFile = $dbWDFile->Fetch())
		{
			if (!$arWDFile['IBLOCK_ID'])
			{
				continue;
			}

			$fileId = $arWDFile['ID'];

			if (!CIBlock::GetArrayByID($arWDFile['IBLOCK_ID'], "RIGHTS_MODE") === "E")
			{
				continue;
			}
			$ibRights = new CIBlockElementRights($arWDFile['IBLOCK_ID'], $fileId);
			$arCurRightsRaw = $ibRights->getRights();

			// Preserve existing rights
			$i = $iNext;
			$arRights = $arRightsForTaskMembers;
			foreach ($arCurRightsRaw as $arRightsData)
			{
				$arRights['n' . $i++] = [
					'GROUP_CODE' => $arRightsData['GROUP_CODE'],
					'TASK_ID' => $arRightsData['TASK_ID'],
				];
			}

			$ibRights->setRights($arRights);
		}
	}

	private function setUserFields(array $fields): void
	{
		$systemUserFields = ['UF_CRM_TASK', 'UF_TASK_WEBDAV_FILES'];
		$userFields = $this->ufManager->GetUserFields(Util\UserField\Task::getEntityCode(), $this->taskId, false,
			$this->userId);

		foreach ($fields as $key => $value)
		{
			if (
				!array_key_exists($key, $userFields)
				|| array_key_exists($key, $systemUserFields)
				|| $userFields[$key]['USER_TYPE_ID'] !== 'boolean'
			)
			{
				continue;
			}

			if (
				$value
				&& mb_strtolower($value) !== 'n'
			)
			{
				$value = true;
			}
			else
			{
				$value = false;
			}

			$fields[$key] = $value;
		}

		$this->ufManager->Update(Util\UserField\Task::getEntityCode(), $this->taskId, $fields, $this->userId);
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function setTags(array $fields): void
	{
		$parsedTags = $this->parseTags($fields);
		if (
			empty($parsedTags)
			&& !array_key_exists('TAGS', $fields)
		)
		{
			return;
		}
		$oldGroupId = 0;
		$newGroupId = 0;
		if ($this->changes && array_key_exists('GROUP_ID', $this->changes))
		{
			$oldGroupId = (int)$this->changes['GROUP_ID']['FROM_VALUE'];
			$newGroupId = (int)$this->changes['GROUP_ID']['TO_VALUE'];
		}
		$tag = new Tag($this->userId);
		$tag->set($this->taskId, $parsedTags, $oldGroupId, $newGroupId);
	}

	private function parseTags(array $fields): array
	{
		$tags = [];
		$searchFields = ['TITLE', 'DESCRIPTION'];

		foreach ($searchFields as $code)
		{
			if (!array_key_exists($code, $fields))
			{
				continue;
			}
			if (preg_match_all(self::REGEX_TAG, ' ' . $fields[$code], $matches))
			{
				$tags[] = $matches[1];
			}
		}

		$tags = array_merge([], ...$tags);
		if (
			array_key_exists('TAGS', $fields)
			&& !empty($fields['TAGS'])
		)
		{
			$tags = array_merge($fields['TAGS'], $tags);
		}

		return array_unique($tags);
	}

	/**
	 * @throws Exception
	 */
	private function addFiles(array $fields): void
	{
		if (
			!isset($fields['FILES'])
			|| !is_array($fields['FILES'])
		)
		{
			return;
		}

		$fileIds = array_map(function ($el) {
			return (int)$el;
		}, $fields['FILES']);

		if (empty($fileIds))
		{
			return;
		}

		CTaskFiles::AddMultiple(
			$this->taskId,
			$fileIds,
			[
				'USER_ID' => $this->userId,
				'CHECK_RIGHTS_ON_FILES' => $this->checkFileRights,
			]
		);
	}

	/**
	 * @throws TaskNotFoundException
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function setMembers(array $fields, array $changes = []): void
	{
		$members = new Member($this->userId, $this->taskId);
		$members->set($fields, $changes);
	}

	private function addParameters(array $fields): void
	{
		$parameter = new Parameter($this->userId, $this->taskId);
		$parameter->add($fields);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function updateParameters(array $fields): void
	{
		$parameter = new Parameter($this->userId, $this->taskId);
		$parameter->update($fields);
	}

	/**
	 * @throws Exception
	 */
	private function addToFavorite(array $fields): void
	{
		if (!array_key_exists('PARENT_ID', $fields))
		{
			return;
		}

		$favorite = new Favorite($this->userId);

		if (!$favorite->isInFavorite($fields['PARENT_ID']))
		{
			return;
		}

		$favorite->add($this->taskId);
	}

	/**
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function insert(array $data): TaskObject
	{
		$handler = new TaskFieldHandler($this->userId, $data);
		$data = $handler->skipTimeZoneFields(...$this->skipTimeZoneFields)->getFieldsToDb();

		$task = new TaskObject($data);
		$result = $task->save();

		if (!$result->isSuccess())
		{
			$messages = $result->getErrorMessages();
			$message = Loc::getMessage('TASKS_UNKNOWN_ADD_ERROR');
			if (!empty($messages))
			{
				$message = array_shift($messages);
			}

			throw new TaskAddException($message);
		}

		return $task;
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	private function save(array $data): ?TaskObject
	{
		$handler = new TaskFieldHandler($this->userId, $data);
		$data = $handler->getFieldsToDb();

		$result = TaskTable::update($this->taskId, $data);

		if (!$result->isSuccess())
		{
			$messages = $result->getErrorMessages();
			$message = Loc::getMessage('TASKS_UNKNOWN_ADD_ERROR');
			if (!empty($messages))
			{
				$message = array_shift($messages);
			}

			throw new TaskUpdateException($message);
		}

		return $this->fetchTaskObjectById($this->taskId);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function fetchTaskObjectById(int $taskId): ?TaskObject
	{
		$memberList = MemberTable::getList([
			'select' => [
				'*',
			],
			'filter' => [
				'=TASK_ID' => $taskId,
			],
		])->fetchCollection();

		$select = ['*', 'UTS_DATA', 'FLOW_TASK'];
		if (!$memberList->isEmpty())
		{
			$select[] = 'MEMBER_LIST';
		}

		return TaskTable::getByPrimary($taskId, ['select' => $select])->fetchObject()?->cacheCrmFields();
	}

	/**
	 * @throws TaskAddException
	 */
	private function onBeforeAdd(array $fields): array
	{
		$fields = array_merge($fields, $this->byPassParams);

		foreach (GetModuleEvents('tasks', 'OnBeforeTaskAdd', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, [&$fields]) !== false)
			{
				continue;
			}

			$e = $this->application->GetException();
			if (!$e)
			{
				throw new TaskAddException(Loc::getMessage('TASKS_UNKNOWN_ADD_ERROR'));
			}

			if (
				$e instanceof CAdminException
				&& is_array($e->messages)
			)
			{
				$message = array_shift($e->messages);
				$message = $message['txt'];
			}
			else
			{
				$message = $this->getApplicationError(Loc::getMessage('TASKS_UNKNOWN_ADD_ERROR'));
			}
			throw new TaskAddException($message);
		}

		return $fields;
	}

	/**
	 * @throws ArgumentException
	 * @throws CTaskAssertException
	 */
	private function updateDatePlan(array $fields): array
	{
		if (!$this->needCorrectDatePlan)
		{
			return $fields;
		}

		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return $fields;
		}

		$fieldHandler = new TaskFieldHandler($this->userId, $fields, $taskData);

		$parentChanged = $fieldHandler->isParentChanged();
		$datesChanged = $fieldHandler->isDatesChanged();
		$followDatesChanged = $fieldHandler->isFollowDates();

		if ($parentChanged)
		{
			// task was attached previously, and now it is being unattached or reattached to smth else
			// then we need to recalculate its previous parent...
			$scheduler = Scheduler::getInstance($this->userId);
			$shiftResultPrev = $scheduler->processEntity(
				$this->taskId,
				$taskData,
				[
					'MODE' => 'BEFORE_DETACH',
				]
			);
			if ($shiftResultPrev->isSuccess())
			{
				$shiftResultPrev->save(['!ID' => $this->taskId]);
			}
		}
		else
		{
			if (array_key_exists('PARENT_ID', $fields))
			{
				unset($fields['PARENT_ID']);
			}
		}

		// when updating end or start date plan, we need to be sure the time is correct
		if (
			$parentChanged
			|| $datesChanged
			|| $followDatesChanged
		)
		{
			$scheduler = Scheduler::getInstance($this->userId);
			$this->shiftResult = $scheduler->processEntity(
				$this->taskId,
				$fields,
				[
					'MODE' => $parentChanged ? 'BEFORE_ATTACH' : '',
				]
			);
			if (!$this->shiftResult->isSuccess())
			{
				return $fields;
			}

			$shiftData = $this->shiftResult->getImpactById($this->taskId);
			if ($shiftData)
			{
				$fields['START_DATE_PLAN'] = $shiftData['START_DATE_PLAN'];
				if (
					isset($fields['START_DATE_PLAN'])
					&& $shiftData['START_DATE_PLAN'] === null
				)
				{
					$fields['START_DATE_PLAN'] = false;
				}

				$fields['END_DATE_PLAN'] = $shiftData['END_DATE_PLAN'];
				if (
					isset($fields['END_DATE_PLAN'])
					&& $shiftData['END_DATE_PLAN'] === null
				)
				{
					$fields['END_DATE_PLAN'] = false;
				}

				$fields['DURATION_PLAN_SECONDS'] = $shiftData['DURATION_PLAN_SECONDS'];
				$this->legacyOperationResultData['SHIFT_RESULT'] = $this->shiftResult->getData();
			}
		}

		if (
			isset($fields['END_DATE_PLAN'])
			&& (string)$fields['END_DATE_PLAN'] === ''
		)
		{
			$fields['DURATION_PLAN'] = 0;
		}

		$taskData = $this->getFullTaskData() ?? [];
		return (new TaskFieldHandler($this->userId, $fields, $taskData))
			->prepareDurationPlanFields()
			->getFields();
	}

	/**
	 * @throws ArgumentException
	 */
	private function correctDatePlan(array $fields): array
	{
		if (!$this->needCorrectDatePlan)
		{
			return $fields;
		}

		if (
			(
				!isset($fields['START_DATE_PLAN'])
				|| (string)$fields['START_DATE_PLAN'] === ''
			)
			&& (
				!isset($fields['END_DATE_PLAN'])
				|| (string)$fields['END_DATE_PLAN'] === ''
			)
		)
		{
			return $fields;
		}

		$scheduler = Scheduler::getInstance($this->userId);
		$this->shiftResult = $scheduler->processEntity(
			0,
			$fields,
			[
				'MODE' => 'BEFORE_ATTACH',
			]
		);
		if ($this->shiftResult->isSuccess())
		{
			$shiftData = $this->shiftResult->getImpactById(0);
			if ($shiftData)
			{
				$fields['START_DATE_PLAN'] = $shiftData['START_DATE_PLAN'];
				$fields['END_DATE_PLAN'] = $shiftData['END_DATE_PLAN'];
				$fields['DURATION_PLAN_SECONDS'] = $shiftData['DURATION_PLAN_SECONDS'];
			}
		}

		$taskData = $this->getFullTaskData() ?? [];
		return (new TaskFieldHandler($this->userId, $fields, $taskData))
			->prepareDurationPlanFields()
			->getFields();
	}

	/**
	 * @throws NotImplementedException
	 * @throws LoaderException
	 */
	private function cloneDiskAttachments(array $fields): array
	{
		if (
			!$this->cloneAttachments
			|| !Loader::includeModule('disk')
		)
		{
			return $fields;
		}

		if (
			array_key_exists('UF_TASK_WEBDAV_FILES', $fields)
			&& is_array($fields['UF_TASK_WEBDAV_FILES'])
			&& !empty($fields['UF_TASK_WEBDAV_FILES'])
		)
		{
			$source = $fields['UF_TASK_WEBDAV_FILES'];
			$fields['UF_TASK_WEBDAV_FILES'] = Disk::cloneFileAttachment($fields['UF_TASK_WEBDAV_FILES'], $this->userId);

			if (count($source) !== count($fields['UF_TASK_WEBDAV_FILES']))
			{
				return $fields;
			}

			$relations = array_combine($source, $fields['UF_TASK_WEBDAV_FILES']);
			$fields = $this->updateInlineFiles($fields, $relations);
		}

		return $fields;
	}

	/**
	 * @throws NotImplementedException
	 */
	private function updateInlineFiles(array $fields, array $relations): array
	{
		if (empty($relations))
		{
			return $fields;
		}

		$searchTpl = '[DISK FILE ID=%s]';

		$search = [];
		$replace = [];

		foreach ($relations as $source => $destination)
		{
			$search[] = sprintf($searchTpl, $source);
			$replace[] = sprintf($searchTpl, $destination);

			if (!preg_match('/^' . FileUserType::NEW_FILE_PREFIX . '/', $source))
			{
				$attachedObject = AttachedObject::loadById($source);
				if ($attachedObject)
				{
					$search[] = sprintf($searchTpl, FileUserType::NEW_FILE_PREFIX . $attachedObject->getObjectId());
					$replace[] = sprintf($searchTpl, $destination);
				}
			}
		}

		$fields['DESCRIPTION'] = str_replace($search, $replace, $fields['DESCRIPTION']);

		return $fields;
	}

	/**
	 * @throws TaskFieldValidateException
	 * @throws LoaderException
	 */
	private function prepareFields(array $fields): ?array
	{
		$taskData = $this->getFullTaskData() ?? [];
		$handler = new TaskFieldHandler($this->userId, $fields, $taskData);

		$handler
			->prepareFlow()
			->prepareGuid()
			->prepareSiteId()
			->prepareGroupId()
			->prepareCreatedBy()
			->prepareTitle()
			->prepareDescription()
			->prepareStatus()
			->preparePriority()
			->prepareMark()
			->prepareFlags()
			->prepareParents()
			->prepareMembers()
			->prepareDependencies()
			->prepareOutlook()
			->prepareTags()
			->prepareChangedBy()
			->prepareRegularParams()
			->prepareDates()
			->prepareId()
			->prepareIntegration();

		$handler = new TariffFieldHandler($handler->getFields());

		return $handler->getFields();
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws TaskNotFoundException
	 */
	private function getTask(bool $refresh = false): TaskObject
	{
		if (
			$this->task
			&& !$refresh
		)
		{
			return $this->task;
		}

		$this->task = TaskTable::getByPrimary($this->taskId)->fetchObject();
		if (!$this->task)
		{
			throw new TaskNotFoundException();
		}

		$this->task->fillMemberList();
		$this->task->fillTagList();
		$this->task->fillScenario();

		return $this->task;
	}

	private function getFullTaskData(bool $refresh = false): ?array
	{
		if (!$this->taskId)
		{
			return null;
		}

		if (
			$this->fullTaskData
			&& !$refresh
		)
		{
			return $this->fullTaskData;
		}

		$taskDbResult = CTasks::GetByID($this->taskId, false);
		$fullTaskData = $taskDbResult->Fetch();

		if (!$fullTaskData)
		{
			return null;
		}

		$this->fullTaskData = $fullTaskData;

		return $this->fullTaskData;
	}

	private function getOccurUserId(): int
	{
		if ($this->occurUserId)
		{
			return $this->occurUserId;
		}

		$this->occurUserId = Util\User::getOccurAsId();
		if (!$this->occurUserId)
		{
			$this->occurUserId = $this->userId;
		}

		return $this->occurUserId;
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function setScenario(array $fields): void
	{
		if (empty($fields[self::FIELD_SCENARIO]))
		{
			// set default scenario if none specified
			ScenarioTable::insertIgnore($this->taskId, [ScenarioTable::SCENARIO_DEFAULT]);
			return;
		}

		$scenarios = is_array($fields[self::FIELD_SCENARIO]) ? $fields[self::FIELD_SCENARIO]
			: [$fields[self::FIELD_SCENARIO]];
		ScenarioTable::insertIgnore($this->taskId, $scenarios);
	}

	/**
	 * @throws TaskAddException
	 */
	private function setRegularParameters(array $fields): void
	{
		try
		{
			(new RegularityService(new TaskRepository($this->taskId)))
				->setRegularity($fields[static::FIELD_REGULAR_PARAMETERS] ?? []);
		}
		catch (RegularityException $exception)
		{
			throw new TaskAddException($exception->getMessage());
		}
	}

	private function updateUserFields(array $fields): void
	{
		if (Util\UserField::checkContainsUFKeys($fields))
		{
			$this->ufManager->Update(Util\UserField\Task::getEntityCode(), $this->taskId, $fields, $this->userId);
		}
	}

	/**
	 * @throws TaskAddException
	 */
	private function checkUserFields(array $fields): void
	{
		if (!$this->ufManager->CheckFields(Util\UserField\Task::getEntityCode(), 0, $fields, $this->userId))
		{
			$message = $this->getApplicationError(Loc::getMessage('TASKS_UNKNOWN_ADD_ERROR'));
			throw new TaskAddException($message);
		}
	}

	private function replicate(): void
	{
		(new RegularTaskReplicator($this->userId))->replicate($this->taskId);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function setStageId(TaskObject $task): void
	{
		if (!$task->isInGroup() || $task->isInGroupStage())
		{
			return;
		}

		$systemStage = StagesTable::getSystemStage($task->getGroupId());
		if (!is_null($systemStage))
		{
			$task->setStageId($systemStage->getId())->save();
		}
	}

	private function handleAnalytics(array $fields, bool $status = true): void
	{
		if (!empty($fields['TASKS_ANALYTICS_SECTION']))
		{
			$parentId = (int)($fields['PARENT_ID'] ?? null);
			$event = $parentId ? Analytics::EVENT['subtask_add'] : Analytics::EVENT['task_create'];

			Analytics::getInstance($this->userId)->onTaskCreate(
				$fields['TASKS_ANALYTICS_CATEGORY'] ?: Analytics::TASK_CATEGORY,
				$event,
				$fields['TASKS_ANALYTICS_SECTION'],
				$fields['TASKS_ANALYTICS_ELEMENT'] ?? null,
				$fields['TASKS_ANALYTICS_SUB_SECTION'] ?? null,
				$status,
				$fields['TASKS_ANALYTICS_PARAMS'] ?? [],
			);
		}
	}
}
