<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Disk\AttachedObject;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\Control\Exception\TaskAddException;
use Bitrix\Tasks\Control\Exception\TaskNotFoundException;
use Bitrix\Tasks\Control\Exception\TaskUpdateException;
use Bitrix\Tasks\Control\Handler\TaskFieldHandler;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\Integration\Bizproc\Listener;
use Bitrix\Tasks\Integration\CRM\Timeline;
use Bitrix\Tasks\Integration\CRM\Timeline\Exception\TimelineException;
use Bitrix\Tasks\Integration\CRM\TimeLineManager;
use Bitrix\Tasks\Integration\Disk;
use Bitrix\Tasks\Integration\Forum\Task\Topic;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Integration\SocialNetwork\User;
use Bitrix\Tasks\Internals\Counter\CounterService;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\EO_Scenario;
use Bitrix\Tasks\Internals\Task\FavoriteTable;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\Internals\Task\ProjectLastActivityTable;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Internals\Task\SortingTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Kanban\TaskStageTable;
use Bitrix\Tasks\Scrum\Internal\ItemTable;
use Bitrix\Tasks\Util;
use Bitrix\Main\Localization\Loc;

class Task
{
	private const REGEX_TAG = '/\s#([^\s,\[\]<>]+)/is';

	private const PUSH_COMMAND_ADD = 'task_add';
	private const FIELD_SCENARIO = 'SCENARIO_NAME';

	private $userId;
	private $taskId = 0;

	private $ufManager;
	private $cacheManager;
	private $application;

	private $needCorrectDatePlan = false;
	private $correctDatePlanDependent = false;
	private $fromAgent = false;
	private $checkFileRights = false;
	private $cloneAttachments = false;
	private $skipExchangeSync = false;
	private $byPassParams = [];
	private $needAutoclose = false;
	private $skipNotifications = false;
	private $skipRecount = false;
	private $skipComments = false;
	private $skipPush = false;

	private $eventGuid;
	private $task;
	private $isAddedComment = false;
	private $shiftResult;
	private $fullTaskData;
	private $eventTaskData;
	private $sourceTaskData;
	private $legacyOperationResultData;
	private $changes;

	private $occurUserId;

	/**
	 * @param int $userId
	 */
	public function __construct(int $userId)
	{
		global $USER_FIELD_MANAGER;
		global $CACHE_MANAGER;
		global $APPLICATION;

		$this->ufManager = $USER_FIELD_MANAGER;
		$this->cacheManager = $CACHE_MANAGER;
		$this->application = $APPLICATION;

		$this->userId = $userId;

		$this->eventGuid = sha1(uniqid('AUTOGUID', true));
	}

	/**
	 * @return array|null
	 */
	public function getLegacyOperationResultData(): ?array
	{
		return $this->legacyOperationResultData;
	}

	/**
	 * @param array $fields
	 * @return TaskObject
	 * @throws TaskAddException
	 * @throws TaskNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \CTaskAssertException
	 */
	public function add(array $fields): TaskObject
	{
		try
		{
			$fields = $this->prepareFields($fields);
		}
		catch (TaskFieldValidateException $e)
		{
			$message = $e->getMessage();
			$this->application->ThrowException(new \CAdminException([
				['text' => $message]
			]));

			throw new TaskAddException($e->getMessage());
		}


		if (!$fields)
		{
			throw new TaskAddException(Loc::getMessage('TASKS_UNKNOWN_ADD_ERROR'));
		}

		$fields = $this->cloneDiskAttachments($fields);

		if (!$this->ufManager->CheckFields(Util\UserField\Task::getEntityCode(), 0, $fields, $this->userId))
		{
			$message = $this->getApplicationError(Loc::getMessage('TASKS_UNKNOWN_ADD_ERROR'));
			throw new TaskAddException($message);
		}

		$fields = $this->correctDatePlan($fields);

		$fields = $this->onBeforeAdd($fields);

		try
		{
			$task = $this->insert($fields);
			$this->taskId = $task->getId();
			$fields['ID'] = $this->taskId;
		}
		catch (\Exception $e)
		{
			throw new TaskAddException($e->getMessage());
		}

		$this->setScenario($fields);
		$this->addToFavorite($fields);
		$this->setMembers($fields);
		$this->addParameters($fields);
		$this->addFiles($fields);
		$this->setTags($fields);
		$this->setUserFields($fields);
		$this->addWebdavFiles($fields);
		$this->sendAddNotifications($fields);

		\Bitrix\Tasks\Internals\UserOption\Task::onTaskAdd($fields);

		CounterService::addEvent(
			EventDictionary::EVENT_AFTER_TASK_ADD,
			$fields
		);

		\CTaskSync::AddItem($fields);

		$this->addLog();
		$fields = $this->onTaskAdd($fields);
		$this->setSearchIndex();
		$this->resetCache();
		$this->updateLastActivity();
		$this->postAddComment($fields);
		$this->sendAddPush($fields);
		$this->saveDependencies($fields);

		$this->sendAddIntegrationEvent($fields);

		return $task;
	}

	/**
	 * @param int $taskId
	 * @param array $fields
	 * @return TaskObject|false
	 * @throws TaskNotFoundException
	 * @throws TaskUpdateException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Tasks\Internals\Task\Result\Exception\ResultNotFoundException
	 * @throws \CTaskAssertException
	 */
	public function update(int $taskId, array $fields)
	{
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
		catch (TaskFieldValidateException $e)
		{
			$message = $e->getMessage();
			$this->application->ThrowException(new \CAdminException([
				['text' => $message]
			]));

			throw new TaskUpdateException($e->getMessage());
		}

		if (
			Util\UserField::checkContainsUFKeys($fields)
			&& !$this->ufManager->CheckFields(Util\UserField\Task::getEntityCode(), $this->taskId, $fields, $this->userId)
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
		catch (\Exception $e)
		{
			throw new TaskUpdateException();
		}

		$this->changes = $this->getChanges($fields);

		$this->setMembers($fields);
		$this->updateParameters($fields);
		$this->saveFiles($fields);
		$this->setTags($fields);
		$this->updateDepends($fields);

		if (Util\UserField::checkContainsUFKeys($fields))
		{
			$this->ufManager->Update(Util\UserField\Task::getEntityCode(), $this->taskId, $fields, $this->userId);
		}

		$fields = $this->reloadTaskData($fields);

		$this->stopTimer();
		$this->saveUpdateLog();
		$this->autocloseTasks($fields);
		$this->sendUpdateNotifications($fields);
		$this->updateSearchIndex($fields);

		\CTaskSync::UpdateItem($fields, $this->sourceTaskData);
		$fields = $this->onUpdate($fields);

		$this->resetCache();
		$this->updateViewDate($fields);
		UserOption\Task::onTaskUpdate($this->sourceTaskData, $fields);
		$this->updateCounters();
		$this->closeResult();
		$this->updatePins();
		$this->updateTopicTitle();
		(new TimeLineManager($taskId, $this->userId))->onTaskUpdated($taskBeforeUpdate)->save();

		$updateComment = $this->postUpdateComments($fields);
		$this->sendUpdatePush($updateComment);

		Listener::onTaskUpdate($this->taskId, $fields, $this->eventTaskData);

		$this->sendUpdateIntegrationEvent($fields);

		return $task;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \CTaskAssertException
	 */
	public function delete(int $taskId)
	{
		if ($taskId < 1)
		{
			return false;
		}
		$this->taskId = $taskId;

		$safeDelete = $this->proceedSafeDelete();

		CounterService::getInstance()->collectData($this->taskId);

		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return false;
		}

		if (!$this->onBeforeDelete())
		{
			return false;
		}

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
			\CTaskSync::DeleteItem($taskData);
		}

		$this->sendDeletePush();
		$this->onTaskDelete();

		if (!$safeDelete)
		{
			TaskTable::delete($taskId);
		}
		else
		{
			$sql = "DELETE FROM `b_tasks` WHERE ID = ".$taskId;
			Application::getConnection()->query($sql);
		}

		ScenarioTable::delete($taskId);

		$tagService = new Tag($this->userId);
		$tagService->unlinkTags($taskId);

		\CTaskNotifications::SendDeleteMessage($taskData, $safeDelete);

		CounterService::addEvent(
			EventDictionary::EVENT_AFTER_TASK_DELETE,
			$taskData
		);

		$timeLineManager->save();
		$this->sendDeleteIntegrationEvent($safeDelete);

		return true;
	}

	/**
	 * @param array $params
	 * @return $this
	 */
	public function setByPassParams(array $params): self
	{
		$this->byPassParams = $params;
		return $this;
	}

	/**
	 * @param string $guid
	 * @return $this
	 */
	public function setEventGuid(string $guid): self
	{
		$this->eventGuid = $guid;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function withSkipExchangeSync(): self
	{
		$this->skipExchangeSync = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function withCorrectDatePlan(): self
	{
		$this->needCorrectDatePlan = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function fromAgent(): self
	{
		$this->fromAgent = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function withFilesRights(): self
	{
		$this->checkFileRights = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function withCloneAttachments(): self
	{
		$this->cloneAttachments = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function withSkipNotifications(): self
	{
		$this->skipNotifications = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function withAutoclose(): self
	{
		$this->needAutoclose = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function withSkipRecount(): self
	{
		$this->skipRecount = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function withSkipComments(): self
	{
		$this->skipComments = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function withSkipPush(): self
	{
		$this->skipPush = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function withCorrectDatePlanDependent(): self
	{
		$this->correctDatePlanDependent = true;
		return $this;
	}

	/**
	 * @param array $fields
	 * @return void
	 * @throws TaskNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function sendAddIntegrationEvent(array $fields): void
	{
		$application = Application::getInstance();

		if (
			array_key_exists('IM_CHAT_ID', $fields)
			&& $fields['IM_CHAT_ID'] > 0
			&& Loader::includeModule('im')
			&& method_exists(\Bitrix\Im\V2\Service\Messenger::class, 'registerTask')
		)
		{
			$task = $this->getTask();
			$application && $application->addBackgroundJob(
				function () use ($fields, $task)
				{
					$messageId = 0;

					if (isset($fields['IM_MESSAGE_ID']) && $fields['IM_MESSAGE_ID'] > 0)
					{
						$messageId = $fields['IM_MESSAGE_ID'];
					}

					\Bitrix\Im\V2\Service\Locator::getMessenger()->registerTask($fields['IM_CHAT_ID'], $messageId, $task);
				}
			);
		}
	}

	/**
	 * @param array $fields
	 * @return void
	 * @throws TaskNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function sendUpdateIntegrationEvent(array $fields): void
	{
		$application = Application::getInstance();

		if (
			Loader::includeModule('im')
			&& method_exists(\Bitrix\Im\V2\Service\Messenger::class, 'updateTask')
		)
		{
			$task = $this->getTask();
			$application && $application->addBackgroundJob(
				function () use ($task)
				{
					\Bitrix\Im\V2\Service\Locator::getMessenger()->updateTask($task);
				}
			);
		}
	}

	/**
	 * @return void
	 * @throws TaskNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function sendDeleteIntegrationEvent(bool $saveDelete): void
	{
		$application = Application::getInstance();

		if (
			Loader::includeModule('im')
			&& method_exists(\Bitrix\Im\V2\Service\Messenger::class, 'unregisterTask')
		)
		{
			$application && $application->addBackgroundJob(
				function () use ($saveDelete)
				{
					\Bitrix\Im\V2\Service\Locator::getMessenger()->unregisterTask($this->getFullTaskData(), $saveDelete);
				}
			);
		}
	}

	/**
	 * @return string
	 */
	private function getApplicationError(string $default = ''): string
	{
		$e = $this->application->GetException();

		if (is_a($e, \CApplicationException::class))
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

	/**
	 * @return void
	 */
	private function updateTopicTitle()
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

	/**
	 * @return void
	 */
	private function updatePins()
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		if (
			!$taskData['GROUP_ID']
			|| (int) $taskData['GROUP_ID'] === (int) $this->sourceTaskData['GROUP_ID'])
		{
			return;
		}

		\Bitrix\Tasks\Kanban\StagesTable::pinInStage(
			$this->taskId,
			array(
				'CREATED_BY' => $this->sourceTaskData['CREATED_BY']
			),
			true
		);
	}

	/**
	 * @param $updateComment
	 * @return bool|void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function sendUpdatePush($updateComment)
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

		$before['GROUP_ID'] = (int) $this->sourceTaskData['GROUP_ID'];
		$after['GROUP_ID'] = (int) $taskData['GROUP_ID'];

		$lastResult = ResultManager::getLastResult($this->taskId);

		$params = [
			'TASK_ID' => $this->taskId,
			'USER_ID' => $this->userId,
			'BEFORE' => $before,
			'AFTER' => $after,
			'TS' => time(),
			'event_GUID' => $this->eventGuid,
			'params' => [
				'HIDE' => (array_key_exists('HIDE', $this->byPassParams) ? (bool)$this->byPassParams['HIDE'] : true),
				'updateCommentExists' => $updateComment,
				'removedParticipants' => array_values($removedParticipants),
			],
			'taskRequireResult' => ResultManager::requireResult($this->taskId) ? "Y" : "N",
			'taskHasResult' => $lastResult ? "Y" : "N",
			'taskHasOpenResult' => ($lastResult && (int) $lastResult['STATUS'] === ResultTable::STATUS_OPENED) ? "Y" : "N",
		];

		try
		{
			PushService::addEvent($participants, [
				'module_id' => 'tasks',
				'command' => 'task_update',
				'params' => $params,
			]);
		}
		catch (\Exception $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * @param array $fields
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function postUpdateComments(array $fields)
	{
		$updateComment = false;
		if ($this->skipComments)
		{
			return $updateComment;
		}

		$fieldsForComments = ['STATUS', 'CREATED_BY', 'RESPONSIBLE_ID', 'ACCOMPLICES', 'AUDITORS', 'DEADLINE', 'GROUP_ID'];
		$changesForUpdate = array_intersect_key($this->changes, array_flip($fieldsForComments));

		if (empty($changesForUpdate))
		{
			return $updateComment;
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
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Tasks\Internals\Task\Result\Exception\ResultNotFoundException
	 */
	private function closeResult()
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		if (in_array((int)$taskData['STATUS'], [\CTasks::STATE_COMPLETED, \CTasks::STATE_SUPPOSEDLY_COMPLETED]))
		{
			(new ResultManager($this->getOccurUserId()))->close($this->taskId);
		}
	}

	/**
	 * @return void
	 */
	private function updateCounters()
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
	 * @param array $fields
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function updateViewDate(array $fields)
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

	/**
	 * @param array $taskData
	 * @return array
	 */
	private function getParticipants(array $taskData): array
	{
		return array_unique(
			array_merge(
				[
					$taskData["CREATED_BY"],
					$taskData["RESPONSIBLE_ID"]
				],
				$taskData["ACCOMPLICES"],
				$taskData["AUDITORS"]
			)
		);
	}

	/**
	 * @param array $fields
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function updateSearchIndex(array $fields)
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		$mergedFields = array_merge($taskData, $fields);

		\CTasks::Index($mergedFields, $fields["TAGS"]);
		SearchIndex::setTaskSearchIndex($this->taskId, $mergedFields);
	}

	/**
	 * @param array $fields
	 * @return array
	 * @throws TaskUpdateException
	 */
	private function onUpdate(array $fields): array
	{
		$fields['META:PREV_FIELDS'] = $this->sourceTaskData;

		try
		{
			foreach (GetModuleEvents('tasks', 'OnTaskUpdate', true) as $event)
			{
				ExecuteModuleEventEx($event, array($this->taskId, &$fields, &$this->eventTaskData));
			}
		}
		catch (\Exception $e)
		{
			throw new TaskUpdateException(
				$this->getApplicationError(Loc::getMessage('TASKS_UNKNOWN_UPDATE_ERROR'))
			);
		}

		unset($fields['META:PREV_FIELDS']);

		return $fields;
	}

	/**
	 * @param array $fields
	 * @return void
	 */
	private function sendUpdateNotifications(array $fields)
	{
		if ($this->skipNotifications)
		{
			return;
		}

		if (!$this->sourceTaskData)
		{
			return;
		}

		$notificationFields = array_merge($fields, ['CHANGED_BY' => $this->getOccurUserId()]);

		$statusChanged =
			($status = (int)($this->fullTaskData['STATUS'] ?? null))
			&& $status >= \CTasks::STATE_NEW
			&& $status <= \CTasks::STATE_DECLINED
			&& $status !== (int)$this->sourceTaskData['STATUS']
		;

		if ($statusChanged)
		{
			\CTaskNotifications::SendStatusMessage($this->sourceTaskData, $status, $notificationFields);
		}

		\CTaskNotifications::SendUpdateMessage(
			$notificationFields,
			$this->sourceTaskData,
			false,
			$this->byPassParams
		);
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	private function reloadTaskData(array $fields): array
	{
		$this->sourceTaskData = $this->getFullTaskData();
		$this->getFullTaskData(true);

		if (!$this->fullTaskData)
		{
			return $fields;
		}

		$statusChanged =
			($status = (int)($fields['STATUS'] ?? null))
			&& $status >= \CTasks::STATE_NEW
			&& $status <= \CTasks::STATE_DECLINED
			&& $status !== (int)$this->fullTaskData['STATUS']
		;

		if ($statusChanged && $status === \CTasks::STATE_DECLINED)
		{
			$this->fullTaskData['DECLINE_REASON'] = $fields['DECLINE_REASON'];
		}

		$fields['ID'] = $this->taskId;

		$this->getTask(true);
		/** @var EO_Scenario $scenarioObject */
		$scenarioObject = $this->task->getScenario();
		$fields['SCENARIO'] = is_null($scenarioObject) ? ScenarioTable::SCENARIO_DEFAULT : $scenarioObject->getScenario();

		return $fields;
	}

	/**
	 * @param array $fields
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private function autocloseTasks(array $fields)
	{
		if (
			!array_key_exists('STATUS', $fields)
			|| (int) $fields['STATUS'] !== \CTasks::STATE_COMPLETED
		)
		{
			return;
		}
		if (!$this->needAutoclose)
		{
			return;
		}

		$closer = \Bitrix\Tasks\Processor\Task\AutoCloser::getInstance($this->userId);
		$closeResult = $closer->processEntity($this->taskId, $fields);
		if ($closeResult->isSuccess())
		{
			$closeResult->save(array('!ID' => $this->taskId));
		}
	}

	/**
	 * @param array $fields
	 * @return void
	 */
	private function updateDepends(array $fields)
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

		if (
			$this->shiftResult
			&& $this->correctDatePlanDependent
		)
		{
			$saveResult = $this->shiftResult->save(array('!ID' => $this->taskId));
			if ($saveResult->isSuccess())
			{
				$this->legacyOperationResultData['SHIFT_RESULT'] = $this->shiftResult->exportData();
			}
		}
	}

	/**
	 * @param array $fields
	 * @return void
	 */
	private function saveFiles(array $fields)
	{
		if (
			isset($fields["FILES"])
			&& (isset($this->changes["NEW_FILES"]) || isset($this->changes["DELETED_FILES"]))
		)
		{
			$arNotDeleteFiles = $fields["FILES"];
			\CTaskFiles::DeleteByTaskID($this->taskId, $arNotDeleteFiles);
			$this->addFiles($fields);
		}
	}

	/**
	 * @return void
	 */
	private function saveUpdateLog()
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		foreach ($this->changes as $key => $value)
		{
			$arLogFields = array(
				"TASK_ID"      => $this->taskId,
				"USER_ID"      => $this->getOccurUserId(),
				"CREATED_DATE" => $taskData["CHANGED_DATE"],
				"FIELD"        => $key,
				"FROM_VALUE"   => $value["FROM_VALUE"],
				"TO_VALUE"     => $value["TO_VALUE"]
			);

			$log = new \CTaskLog();
			$log->Add($arLogFields);
		}
	}

	/**
	 * @param array $fields
	 * @return array
	 */
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

		return \CTaskLog::GetChanges($taskData, $fields);
	}

	/**
	 * @param array $fields
	 * @return array
	 * @throws TaskUpdateException
	 * @throws \CTaskAssertException
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
			if (ExecuteModuleEventEx($arEvent, array($this->taskId, &$fields, &$this->eventTaskData)) === false)
			{
				$message = $this->getApplicationError(Loc::getMessage('TASKS_UNKNOWN_UPDATE_ERROR'));
				throw new TaskUpdateException($message);
			}
		}

		return $fields;
	}

	/**
	 * @param array $fields
	 * @return array
	 * @throws \CTaskAssertException
	 */
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
	 * @return void
	 */
	private function onTaskDelete()
	{
		foreach (GetModuleEvents('tasks', 'OnTaskDelete', true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, [$this->taskId, $this->byPassParams]);
		}
		Listener::onTaskDelete($this->taskId);
		ItemTable::deactivateBySourceId($this->taskId);
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \CTaskAssertException
	 */
	private function sendDeletePush()
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
					$taskData["RESPONSIBLE_ID"]
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

		PushService::addEvent($pushRecipients, [
			'module_id' => 'tasks',
			'command' => 'task_remove',
			'params' => [
				'TASK_ID' => $this->taskId,
				'TS' => time(),
				'event_GUID' => $this->eventGuid,
				'BEFORE' => [
					'GROUP_ID' => $groupId,
				],
			],
		]);
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \CTaskAssertException
	 */
	private function updateAfterDelete()
	{
		$connection = Application::getConnection();

		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		SortingTable::fixSiblingsEx($this->taskId);

		$parentId = $taskData["PARENT_ID"] ? $taskData["PARENT_ID"] : "NULL";

		$sql = "
			UPDATE b_tasks_template 
			SET TASK_ID = NULL 
			WHERE TASK_ID = ". $this->taskId;
		$connection->queryExecute($sql);

		$sql = "
			UPDATE b_tasks_template 
			SET PARENT_ID = ". $parentId ." 
			WHERE PARENT_ID = ". $this->taskId;
		$connection->queryExecute($sql);

		$sql = "
			UPDATE b_tasks 
			SET PARENT_ID = ". $parentId ." 
			WHERE PARENT_ID = ". $this->taskId;
		$connection->queryExecute($sql);
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function unsafeDeleteRelations()
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		\CTaskFiles::DeleteByTaskID($this->taskId);
		\CTaskTags::DeleteByTaskID($this->taskId);
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
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \CTaskAssertException
	 */
	private function deleteRelations()
	{
		$taskData = $this->getFullTaskData();
		if (!$taskData)
		{
			return;
		}

		\CTaskMembers::DeleteAllByTaskID($this->taskId);
		\CTaskDependence::DeleteByTaskID($this->taskId);
		\CTaskDependence::DeleteByDependsOnID($this->taskId);
		\CTaskReminders::DeleteByTaskID($this->taskId);

		$tableResult = ProjectDependenceTable::getList([
			"select" => ['TASK_ID'],
			"filter" => [
				"=TASK_ID" => $this->taskId,
				"DEPENDS_ON_ID" => $this->taskId
			]
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
			$scheduler = \Bitrix\Tasks\Processor\Task\Scheduler::getInstance($this->userId);
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
			\CSearch::DeleteIndex("tasks", $this->taskId);
		}
	}

	/**
	 * @param bool $force
	 * @return void
	 */
	private function stopTimer(bool $force = false)
	{
		$taskData = $this->getFullTaskData();

		if (!$taskData)
		{
			return;
		}

		if (
			!$force
			&& !in_array($taskData['STATUS'], [\CTasks::STATE_COMPLETED, \CTasks::STATE_SUPPOSEDLY_COMPLETED])
		)
		{
			return;
		}

		$timer = \CTaskTimerManager::getInstance($taskData['RESPONSIBLE_ID']);
		$timer->stop($this->taskId);

		$accomplices = $taskData['ACCOMPLICES'];
		if (isset($accomplices) && !empty($accomplices))
		{
			foreach ($accomplices as $accompliceId)
			{
				$accompliceTimer = \CTaskTimerManager::getInstance($accompliceId);
				$accompliceTimer->stop($this->taskId);
			}
		}
	}

	/**
	 * @return bool
	 * @throws \CTaskAssertException
	 */
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

	/**
	 * @return bool
	 */
	private function proceedSafeDelete(): bool
	{
		try
		{
			if (!\Bitrix\Main\Loader::includeModule('recyclebin'))
			{
				return false;
			}
			$taskData = $this->getFullTaskData();
			if (!$taskData)
			{
				return false;
			}
			return \Bitrix\Tasks\Integration\Recyclebin\Task::OnBeforeTaskDelete($this->taskId, $taskData);
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	/**
	 * @param array $fields
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private function saveDependencies(array $fields)
	{
		if (array_key_exists('DEPENDS_ON', $fields))
		{
			$dependence = new Dependence($this->userId, $this->taskId);
			$dependence->setPrevious($fields['DEPENDS_ON']);
		}

		$parentId = 0;
		if (array_key_exists('PARENT_ID', $fields))
		{
			$parentId = (int) $fields['PARENT_ID'];
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
			$childrenCountDbResult = \CTasks::GetChildrenCount([], $parentId);
			$fetchedChildrenCount = $childrenCountDbResult->Fetch();
			$childrenCount = $fetchedChildrenCount['CNT'];

			if ($childrenCount == 1)
			{
				$scheduler = \Bitrix\Tasks\Processor\Task\Scheduler::getInstance($this->userId);
				$shiftResult = $scheduler->processEntity(
					0,
					$fields,
					array('MODE' => 'BEFORE_ATTACH')
				);
			}
		}

		$shiftResult->save(['!ID' => 0]);
	}

	/**
	 * @param array $fields
	 * @return void
	 * @throws \CTaskAssertException
	 */
	private function sendAddPush(array $fields)
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
		$pushRecipients = array_unique(array_merge($pushRecipients, $fullTaskData['AUDITORS'], $fullTaskData['ACCOMPLICES']));

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
				'command' => self::PUSH_COMMAND_ADD,
				'params' => $this->prepareAddPullEventParameters($mergedFields),
			]);
		}
		catch (\Exception $e)
		{

		}
	}

	/**
	 * @param array $mergedFields
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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
			'taskHasOpenResult' => ($lastResult && (int) $lastResult['STATUS'] === ResultTable::STATUS_OPENED) ? "Y" : "N",
		];
	}

	/**
	 * @param array $fields
	 * @return void
	 * @throws \CTaskAssertException
	 */
	private function postAddComment(array $fields)
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
		$this->isAddedComment = $commentPoster->getCommentByType(Comment::TYPE_ADD) ? true : false;

		if (!$isDeferred)
		{
			$commentPoster->disableDeferredPostMode();
			$commentPoster->postComments();
			$commentPoster->clearComments();
		}
	}

	/**
	 * @return void
	 * @throws TaskNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function updateLastActivity()
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
			\CSocNetGroup::SetLastActivity($task->getGroupId());
		}
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function resetCache()
	{
		\Bitrix\Tasks\Access\TaskAccessController::dropItemCache($this->taskId);

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
			$this->cacheManager->ClearByTag("tasks_group_".$taskData["GROUP_ID"]);
		}
		foreach ($participants as $userId)
		{
			$this->cacheManager->ClearByTag("tasks_user_".$userId);
		}
		$cache = Cache::createInstance();
		$cache->clean(\CTasks::CACHE_TASKS_COUNT, \CTasks::CACHE_TASKS_COUNT_DIR_NAME);
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function setSearchIndex()
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

	/**
	 * @param array $fields
	 * @return array
	 */
	private function onTaskAdd(array $fields): array
	{
		(new TimeLineManager($this->taskId, $this->userId))->onTaskCreated()->save();

		try
		{
			foreach (GetModuleEvents('tasks', 'OnTaskAdd', true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, [$this->taskId, &$fields]);
			}
		}
		catch (\Exception $e)
		{
			\Bitrix\Tasks\Util::log($e);
		}

		return $fields;
	}

	/**
	 * @return void
	 */
	private function addLog()
	{
		$arLogFields = array(
			"TASK_ID"      => $this->taskId,
			"USER_ID"      => $this->getOccurUserId(),
			"CREATED_DATE" => \Bitrix\Tasks\UI::formatDateTime(Util\User::getTime()),
			"FIELD"        => "NEW"
		);
		$log = new \CTaskLog();
		$log->Add($arLogFields);
	}

	/**
	 * @param array $fields
	 * @return void
	 */
	private function sendAddNotifications(array $fields)
	{
		$fields = array_merge($fields, $this->byPassParams);

		\CTaskNotifications::SendAddMessage(
			array_merge(
				$fields,
				[
					'CHANGED_BY' => $this->getOccurUserId(),
					'ID' => $this->taskId,
				]
			),
			['SPAWNED_BY_AGENT' => $this->fromAgent]
		);
	}

	/**
	 * @param array $fields
	 * @return void
	 */
	private function addWebdavFiles(array $fields)
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
	 * @param $filesIds
	 * @return void
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function addFilesRights($filesIds)
	{
		$filesIds = array_unique(array_filter($filesIds));

		// Nothing to do?
		if (empty($filesIds))
			return;

		if(
			!Loader::includeModule('webdav')
			|| !Loader::includeModule('iblock')
		)
		{
			return;
		}

		$arRightsTasks = \CWebDavIblock::GetTasks();

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

		$ibe = new \CIBlockElement();
		$dbWDFile = $ibe->GetList(
			[],
			[
				'ID' => $filesIds,
				'SHOW_NEW' => 'Y'
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
		$arRightsForTaskMembers = array();
		foreach ($members as $userId)
		{
			// For intranet users and their managers
			$arRightsForTaskMembers['n' . $i++] = [
				'GROUP_CODE' => 'IU' . $userId,
				'TASK_ID'    => $arRightsTasks['R']		// rights for reading
			];

			// For extranet users
			$arRightsForTaskMembers['n' . $i++] = [
				'GROUP_CODE' => 'U' . $userId,
				'TASK_ID'    => $arRightsTasks['R']		// rights for reading
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

			if (!\CIBlock::GetArrayByID($arWDFile['IBLOCK_ID'], "RIGHTS_MODE") === "E")
			{
				continue;
			}
			$ibRights = new \CIBlockElementRights($arWDFile['IBLOCK_ID'], $fileId);
			$arCurRightsRaw = $ibRights->getRights();

			// Preserve existing rights
			$i = $iNext;
			$arRights = $arRightsForTaskMembers;
			foreach ($arCurRightsRaw as $arRightsData)
			{
				$arRights['n' . $i++] = [
					'GROUP_CODE' => $arRightsData['GROUP_CODE'],
					'TASK_ID'    => $arRightsData['TASK_ID']
				];
			}

			$ibRights->setRights($arRights);
		}
	}

	/**
	 * @param array $fields
	 * @return void
	 */
	private function setUserFields(array $fields)
	{
		$systemUserFields = array('UF_CRM_TASK', 'UF_TASK_WEBDAV_FILES');
		$userFields = $this->ufManager->GetUserFields(Util\UserField\Task::getEntityCode(), $this->taskId, false, $this->userId);

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
	 * @param array $fields
	 * @return void
	 * @throws Exception\TaskNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function setTags(array $fields)
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

	/**
	 * @param array $fields
	 * @return array
	 */
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
			if (preg_match_all(self::REGEX_TAG, ' '.$fields[$code], $matches))
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
	 * @param array $fields
	 * @return void
	 */
	private function addFiles(array $fields)
	{
		if (
			!isset($fields['FILES'])
			|| !is_array($fields['FILES'])
		)
		{
			return;
		}

		$fileIds = array_map(function($el) {
			return (int) $el;
		}, $fields['FILES']);

		if (empty($fileIds))
		{
			return;
		}

		\CTaskFiles::AddMultiple(
			$this->taskId,
			$fileIds,
			[
				'USER_ID'               => $this->userId,
				'CHECK_RIGHTS_ON_FILES' => $this->checkFileRights,
			]
		);
	}

	/**
	 * @param array $fields
	 * @return void
	 * @throws Exception\TaskNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function setMembers(array $fields)
	{
		$members = new Member($this->userId, $this->taskId);
		$members->set($fields);
	}

	/**
	 * @param array $fields
	 * @return void
	 */
	private function addParameters(array $fields)
	{
		$parametes = new Parameter($this->userId, $this->taskId);
		$parametes->add($fields);
	}

	/**
	 * @param array $fields
	 * @return void
	 */
	private function updateParameters(array $fields)
	{
		$parametes = new Parameter($this->userId, $this->taskId);
		$parametes->update($fields);
	}

	/**
	 * @param array $fields
	 * @return void
	 * @throws \Exception
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
	 * @param array $data
	 * @return TaskObject
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function insert(array $data): TaskObject
	{
		$handler = new TaskFieldHandler($this->userId, $data);
		$data = $handler->getFieldsToDb();

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
	 * @param array $data
	 * @return TaskObject
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function save(array $data): TaskObject
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

	private function fetchTaskObjectById(int $taskId): ?TaskObject
	{
		$memberList = MemberTable::getList([
			'select' => [
				'*'
			],
			'filter' => [
				'=TASK_ID' => $taskId
			],
		])->fetchCollection();
		if ($memberList->count() === 0)
		{
			$select = ['select' => ['*', 'UTS_DATA']];
		}
		else
		{
			$select = ['select' => ['*', 'UTS_DATA', 'MEMBER_LIST']];
		}

		return TaskTable::getByPrimary($taskId, $select)->fetchObject();
	}

	/**
	 * @param array $fields
	 * @return array
	 * @throws TaskAddException
	 */
	private function onBeforeAdd(array $fields): array
	{
		$fields = array_merge($fields, $this->byPassParams);

		foreach (GetModuleEvents('tasks', 'OnBeforeTaskAdd', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$fields)) !== false)
			{
				continue;
			}

			$e = $this->application->GetException();
			if (!$e)
			{
				throw new TaskAddException(Loc::getMessage('TASKS_UNKNOWN_ADD_ERROR'));
			}

			if (
				$e instanceof \CAdminException
				&& is_array($e->messages)
			)
			{
				$message = array_shift($e->messages);
				$message = $message['txt'];
				throw new TaskAddException($message);
			}
			else
			{
				$message = $this->getApplicationError(Loc::getMessage('TASKS_UNKNOWN_ADD_ERROR'));
				$this->_errors[] = array('text' => $message, 'id' => 'unknown');
				throw new TaskAddException($message);
			}
		}

		return $fields;
	}

	/**
	 * @param array $fields
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \CTaskAssertException
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
			$scheduler = \Bitrix\Tasks\Processor\Task\Scheduler::getInstance($this->userId);
			$shiftResultPrev = $scheduler->processEntity(
				$this->taskId,
				$taskData,
				array(
					'MODE' => 'BEFORE_DETACH',
				)
			);
			if ($shiftResultPrev->isSuccess())
			{
				$shiftResultPrev->save(array('!ID' => $this->taskId));
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
			$scheduler = \Bitrix\Tasks\Processor\Task\Scheduler::getInstance($this->userId);
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
			&& (string) $fields['END_DATE_PLAN'] === ''
		)
		{
			$fields['DURATION_PLAN'] = 0;
		}

		$taskData = $this->getFullTaskData() ?? [];
		$fields = (new TaskFieldHandler($this->userId, $fields, $taskData))
			->prepareDurationPlanFields()
			->getFields();

		return $fields;
	}

	/**
	 * @param array $fields
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
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
				|| (string) $fields['START_DATE_PLAN'] === ''
			)
			&&
			(
				!isset($fields['END_DATE_PLAN'])
				|| (string) $fields['END_DATE_PLAN'] === ''
			)
		)
		{
			return $fields;
		}

		$scheduler = \Bitrix\Tasks\Processor\Task\Scheduler::getInstance($this->userId);
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
		$fields = (new TaskFieldHandler($this->userId, $fields, $taskData))
			->prepareDurationPlanFields()
			->getFields();

		return $fields;
	}

	/**
	 * @param array $fields
	 * @return array
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
	 * @param array $fields
	 * @param array $relations
	 * @return array
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

			if (!preg_match('/^'.\Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX.'/', $source))
			{
				$attachedObject = AttachedObject::loadById($source);
				if($attachedObject)
				{
					$search[] = sprintf($searchTpl, \Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX.$attachedObject->getObjectId());
					$replace[] = sprintf($searchTpl, $destination);
				}
			}
		}

		$fields['DESCRIPTION'] = str_replace($search, $replace, $fields['DESCRIPTION']);

		return $fields;
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	private function prepareFields(array $fields): ?array
	{
		$taskData = $this->getFullTaskData() ?? [];
		$handler = new TaskFieldHandler($this->userId, $fields, $taskData);

		$handler
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
			->prepareDates()
			->prepareId()
			->prepareIntegration();

		return $handler->getFields();
	}

	/**
	 * @return TaskObject
	 * @throws TaskNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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

	/**
	 * @param bool $refresh
	 * @return array|null
	 */
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

		$taskDbResult = \CTasks::GetByID($this->taskId, false);
		$fullTaskData = $taskDbResult->Fetch();

		if (!$fullTaskData)
		{
			return null;
		}

		$this->fullTaskData = $fullTaskData;

		return $this->fullTaskData;
	}

	/**
	 * @return int
	 */
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
	 * @param array $fields
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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
}