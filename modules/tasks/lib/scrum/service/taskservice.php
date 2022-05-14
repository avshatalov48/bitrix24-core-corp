<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\Type\DateTime;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Internals\Counter\Template\CounterStyle;
use Bitrix\Tasks\Internals\Counter\Template\TaskCounter;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Helper\Common;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Internals\Task\CheckListTreeTable;
use Bitrix\Tasks\Scrum\Form\ItemForm;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util;

class TaskService implements Errorable
{
	const ERROR_COULD_NOT_ADD_TASK = 'TASKS_TS_01';
	const ERROR_COULD_NOT_UPDATE_TASK = 'TASKS_TS_02';
	const ERROR_COULD_NOT_READ_TASK = 'TASKS_TS_03';
	const ERROR_COULD_NOT_REMOVE_TASK = 'TASKS_TS_04';
	const ERROR_COULD_NOT_UPDATE_TAGS = 'TASKS_TS_06';
	const ERROR_COULD_NOT_READ_TAGS = 'TASKS_TS_07';
	const ERROR_COULD_NOT_COMPLETE_TASK = 'TASKS_TS_08';
	const ERROR_COULD_NOT_ADD_FILES_TASK = 'TASKS_TS_09';
	const ERROR_COULD_NOT_COUNT_CHECKLIST_FILES = 'TASKS_TS_11';
	const ERROR_COULD_NOT_COUNT_COMMENTS_TASK = 'TASKS_TS_12';
	const ERROR_COULD_NOT_CHECK_COMPLETED_TASK = 'TASKS_TS_13';
	const ERROR_COULD_NOT_CONVERT_DESCRIPTION_TASK = 'TASKS_TS_14';
	const ERROR_COULD_NOT_READ_LIST_TASK = 'TASKS_TS_15';
	const ERROR_COULD_NOT_REMOVE_TAGS = 'TASKS_TS_16';
	const ERROR_COULD_NOT_CHECK_IS_SUB_TASK = 'TASKS_TS_17';
	const ERROR_COULD_NOT_CHECK_IS_LINKED_TASK = 'TASKS_TS_18';
	const ERROR_COULD_NOT_CHECK_GET_SUB_TASK_IDS = 'TASKS_TS_19';
	const ERROR_COULD_NOT_CHECK_GET_SUB_TASK_INFO = 'TASKS_TS_20';

	private $executiveUserId;
	private $application;

	private $errorCollection;

	private static $taskItemObject = [];

	private $tasksTags = [];

	private $ownerId = 0;

	private static $isSubordinate = null;
	private static $isSuper = null;

	public function __construct(int $executiveUserId, \CMain $application = null)
	{
		$this->executiveUserId = $executiveUserId;
		$this->application = $application;

		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * If your application is displaying another user's view, pass the id of the view owner to verify rights.
	 *
	 * @param int $ownerId
	 */
	public function setOwnerId(int $ownerId): void
	{
		$this->ownerId = (int) $ownerId;
	}

	public function getOwnerId(): int
	{
		return ($this->ownerId === 0 ? $this->executiveUserId : $this->ownerId);
	}

	public function getUserId(): int
	{
		return $this->executiveUserId;
	}

	public function getFilterInstance(int $groupId, bool $isCompletedSprint = false): Common
	{
		if ($isCompletedSprint)
		{
			$filterId = 'TASKS_GRID_ROLE_ID_4096_'.$groupId.'_COMPLETED_N';
		}
		else
		{
			$filterId = 'TASKS_GRID_ROLE_ID_4096_'.$groupId.'_ADVANCED_N';
		}

		$filterInstance = Filter::getInstance($this->executiveUserId, $groupId, $filterId);

		$presets = Filter::getPresets($filterInstance);
		if ($isCompletedSprint)
		{
			unset($presets['filter_tasks_scrum']);
		}

		$savedOptions = \CUserOptions::getOption('main.ui.filter', $filterId, [], $this->executiveUserId);
		if (!$savedOptions)
		{
			// todo remove after fix main filter
			$filterOptions = new Options($filterId, $presets);
			$filterOptions->save();
		}

		return $filterInstance;
	}

	public function getFilter(Common $filterInstance): array
	{
		return $filterInstance->process();
	}

	public function createTask(array $taskFields): int
	{
		try
		{
			$taskItemObject = \CTaskItem::add($taskFields, $this->executiveUserId);

			$taskId = $taskItemObject->getId();

			if (!$taskId)
			{
				if ($exception = $this->application->getException())
				{
					$this->errorCollection->setError(
						new Error(
							$exception->getString(),
							self::ERROR_COULD_NOT_ADD_TASK
						)
					);
				}
				else
				{
					$this->errorCollection->setError(
						new Error(
							'Error creating task',
							self::ERROR_COULD_NOT_ADD_TASK
						)
					);
				}
			}

			return $taskId;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(
				new Error(
					$message,
					self::ERROR_COULD_NOT_ADD_TASK
				)
			);

			return 0;
		}
	}

	public function updateTagsList(int $taskId, array $inputTags): bool
	{
		try
		{
			$tags = $this->getTagsByTaskIds([$taskId]);
			$this->addTags($taskId, array_merge($tags, $inputTags));

			return true;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(
				new Error(
					$message,
					self::ERROR_COULD_NOT_UPDATE_TAGS
				)
			);

			return false;
		}
	}

	public function removeTags(int $taskId, string $inputTag): bool
	{
		try
		{
			$taskTags = new \CTaskTags();
			$taskTags->delete(['TASK_ID' => $taskId, 'NAME' => $inputTag]);

			return true;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(
				new Error(
					$message,
					self::ERROR_COULD_NOT_REMOVE_TAGS
				)
			);

			return false;
		}
	}

	public function updateTaskLinks(int $parentTaskId, int $childTaskId): void
	{
		$taskItem = $this->getTaskItemObject($parentTaskId);

		$parentTask = $taskItem->getData(false, [
			'select' => ['DEPENDS_ON'],
		]);
		$parentTask['DEPENDS_ON'][] = $childTaskId;
		$parentTask['DEPENDS_ON'] = array_unique(
			array_map('intval', $parentTask['DEPENDS_ON'])
		);

		$taskDependence = new \CTaskDependence();

		if ($parentTask['DEPENDS_ON'])
		{
			$taskDependence->deleteByTaskID($parentTaskId);

			foreach ($parentTask['DEPENDS_ON'] as $taskId)
			{
				$taskDependence->add([
					'TASK_ID' => $parentTaskId,
					'DEPENDS_ON_ID' => $taskId,
				]);
			}
		}
	}

	/**
	 * Returns the ids of the group's not completed tasks.
	 *
	 * @param int $groupId
	 * @return array
	 */
	public function getTaskIds(int $groupId)
	{
		$taskIds = [];

		try
		{
			$queryObject = \CTasks::getList(
				['ID' => 'ASC'],
				[
					'GROUP_ID' => $groupId,
					'!=STATUS' => \CTasks::STATE_COMPLETED,
					'CHECK_PERMISSIONS' => 'N',
				],
				['ID']
			);
			while ($taskData = $queryObject->fetch())
			{
				$taskIds[] = $taskData['ID'];
			}

			return $taskIds;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(
				new Error(
					$message,
					self::ERROR_COULD_NOT_READ_TASK
				)
			);

			return $taskIds;
		}
	}

	public function getTaskIdsByFilter(array $filter): array
	{
		$taskIds = [];

		try
		{
			$filter['ONLY_ROOT_TASKS'] = ($filter['ONLY_ROOT_TASKS'] === 'N' ? 'N' : 'Y');

			[$rows, $queryObject] = $this->getList([
				'select' => ['ID'],
				'filter' => $filter,
			]);

			if (count($rows) <= 0)
			{
				return $taskIds;
			}

			/**
			 * @var \CTaskItem[] $rows
			 */
			foreach ($rows as $row)
			{
				$taskData = $row->getData();
				$taskIds[] = $taskData['ID'];
			}

			return $taskIds;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(
				new Error(
					$message,
					self::ERROR_COULD_NOT_READ_TASK
				)
			);

			return $taskIds;
		}
	}

	public function getTagsByTaskIds(array $taskIds): array
	{
		try
		{
			$tags = [];
			$queryObject = \CTaskTags::getList([], ['TASK_ID' => $taskIds]);
			while ($tag = $queryObject->fetch())
			{
				if (in_array($tag['TASK_ID'], $taskIds))
				{
					$tags[] = $tag['NAME'];
				}
			}

			return array_unique($tags);
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(
				new Error(
					$message,
					self::ERROR_COULD_NOT_READ_TAGS
				)
			);

			return [];
		}
	}

	public function getTagsByUserIds(array $userIds): array
	{
		try
		{
			$tags = [];
			$queryObject = \CTaskTags::getList([], ['USER_ID' => $userIds]);
			while ($tag = $queryObject->fetch())
			{
				$tags[] = $tag['NAME'];
			}
			return array_unique($tags);
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(
				new Error($message, self::ERROR_COULD_NOT_READ_TAGS)
			);

			return [];
		}
	}

	public function getTags($taskIds): array
	{
		try
		{
			$tags = [];

			$queryObject = \CTaskTags::getList([], ['TASK_ID' => $taskIds]);
			while ($tag = $queryObject->fetch())
			{
				if (in_array($tag['TASK_ID'], $taskIds))
				{
					if (!is_array($tags[$tag['TASK_ID']]))
					{
						$tags[$tag['TASK_ID']] = [];
					}
					$tags[$tag['TASK_ID']][] = $tag['NAME'];
				}
			}

			return $tags;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(
				new Error(
					$message,
					self::ERROR_COULD_NOT_READ_TAGS
				)
			);

			return [];
		}
	}

	public function changeTask(int $taskId, array $taskFields): bool
	{
		try
		{
			$task = $this->getTaskItemObject($taskId);
			$task->update($taskFields);

			return true;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(
				new Error($message, self::ERROR_COULD_NOT_UPDATE_TASK)
			);

			return false;
		}
	}

	public function setTaskTags(array $taskTags): void
	{
		foreach ($taskTags as $taskTag)
		{
			$this->tasksTags[$taskTag] = $taskTag;
		}
	}

	public function getTasksTags(): array
	{
		return $this->tasksTags;
	}

	public function hasAccessToCounters(): bool
	{
		if (!self::$isSubordinate)
		{
			self::$isSubordinate = \CTasks::isSubordinate($this->getOwnerId(), $this->executiveUserId);
		}

		if (!self::$isSuper)
		{
			self::$isSuper = Util\User::isSuper($this->executiveUserId);
		}

		return (
			$this->executiveUserId === $this->getOwnerId()
			|| self::$isSuper
			|| self::$isSubordinate
		);
	}

	public function getAllowedTaskActions(int $taskId): array
	{
		$accessController = new TaskAccessController($this->executiveUserId);

		$taskModel = TaskModel::createFromId($taskId);

		$accessRequest = [
			ActionDictionary::ACTION_TASK_EDIT => null,
			ActionDictionary::ACTION_TASK_REMOVE => null,
		];

		return $accessController->batchCheck($accessRequest, $taskModel);
	}

	/**
	 * @param $taskId
	 * @return DateTime|null
	 */
	public function getTaskClosedDate($taskId)
	{
		try
		{
			$taskItemObject = $this->getTaskItemObject($taskId);

			$taskData = $taskItemObject->getData(false, [
				'select' => [
					'CLOSED_DATE'
				]
			]);

			if ($taskData['CLOSED_DATE'])
			{
				return Type\DateTime::createFrom($taskData['CLOSED_DATE']);
			}
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(
				new Error($message, self::ERROR_COULD_NOT_READ_TASK)
			);
		}

		return null;
	}

	public function removeTask(int $taskId): bool
	{
		try
		{
			$task = \CTaskItem::getInstance($taskId, $this->executiveUserId);

			$task->delete();

			return true;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage().$exception->getTraceAsString(),
					self::ERROR_COULD_NOT_REMOVE_TASK
				)
			);

			return false;
		}
	}

	public function completeTasks(array $taskIds): bool
	{
		try
		{
			foreach ($taskIds as $taskId)
			{
				$task = \CTaskItem::getInstance($taskId, $this->executiveUserId);
				$task->complete();
			}

			return true;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(
				new Error($message, self::ERROR_COULD_NOT_COMPLETE_TASK)
			);

			return false;
		}
	}

	public function attachFilesToTask(\CUserTypeManager $manager, int $taskId, array $attachedIds): array
	{
		try
		{
			$ufValue = $manager->getUserFieldValue('TASKS_TASK', 'UF_TASK_WEBDAV_FILES', $taskId);

			if (is_array($ufValue))
			{
				$ufValue = array_merge($ufValue, $attachedIds);
			}
			else
			{
				$ufValue = $attachedIds;
			}

			$userFields = ['UF_TASK_WEBDAV_FILES' => $ufValue];

			if ($manager->checkFields('TASKS_TASK', $taskId, $userFields, $this->executiveUserId))
			{
				$manager->update('TASKS_TASK', $taskId, $userFields);
			}

			return $ufValue;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(
				new Error(
					$message,
					self::ERROR_COULD_NOT_ADD_FILES_TASK
				)
			);

			return [];
		}
	}

	public function isCompletedTask(int $taskId): bool
	{
		try
		{
			$queryObject = \CTasks::getList(
				[],
				[
					'ID' => $taskId,
					'=STATUS' => \CTasks::STATE_COMPLETED,
					'CHECK_PERMISSIONS' => 'N',
				],
				['ID']
			);
			return ($queryObject->fetch() ? true : false);
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_CHECK_COMPLETED_TASK));

			return false;
		}
	}

	public function getUncompletedTaskIds(array $taskIds): array
	{
		if (empty($taskIds))
		{
			return [];
		}

		try
		{
			$unCompletedTaskIds = [];

			$queryObject = \CTasks::getList(
				[],
				[
					'ID' => $taskIds,
					'!=STATUS' => \CTasks::STATE_COMPLETED,
					'CHECK_PERMISSIONS' => 'N',
				],
				['ID']
			);
			while ($data = $queryObject->fetch())
			{
				$unCompletedTaskIds[] = $data['ID'];
			}

			return $unCompletedTaskIds;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_CHECK_COMPLETED_TASK));

			return [];
		}
	}

	public function getSubTaskIds(int $groupId, int $taskId, bool $notCompleted = true): array
	{
		$taskIds = [];

		try
		{
			$filter = [
				'PARENT_ID' => $taskId,
				'GROUP_ID' => $groupId,
			];
			if ($notCompleted)
			{
				$filter['!=STATUS'] = \CTasks::STATE_COMPLETED;
			}

			$queryObject = \CTasks::getList(
				['ID' => 'ASC'],
				$filter,
				['ID']
			);
			while ($taskData = $queryObject->fetch())
			{
				$taskIds[] = $taskData['ID'];
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_CHECK_GET_SUB_TASK_IDS)
			);
		}

		return $taskIds;
	}

	public function getParentTaskId(int $taskId, int $groupId): int
	{
		try
		{
			$parentId = \CTasks::getParentOfTask($taskId);

			if ($parentId === false)
			{
				return 0;
			}
			else
			{
				[$rows, $queryObject] = $this->getList([
					'select' => ['ID'],
					'filter' => [
						'ID' => $parentId,
						'GROUP_ID' => $groupId,
						'CHECK_PERMISSIONS' => 'Y',
						'!=STATUS' => \CTasks::STATE_COMPLETED,
					],
				]);

				return (count($rows) > 0 ? $parentId : 0);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_CHECK_IS_SUB_TASK)
			);

			return 0;
		}
	}

	public function getLinkedTasks(int $taskId): array
	{
		try
		{
			$taskItem = $this->getTaskItemObject($taskId);

			$taskData = $taskItem->getData(false, [
				'select' => ['DEPENDS_ON'],
			]);

			return $taskData['DEPENDS_ON'];
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_CHECK_IS_LINKED_TASK)
			);

			return [];
		}
	}

	public function convertDescription(string $text, $ufFields): string
	{
		try
		{
			return UI::convertBBCodeToHtml(
				$text,
				[
					'maxStringLen' => 0,
					'USER_FIELDS' => $ufFields
				]
			);
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(
				new Error(
					$message,
					self::ERROR_COULD_NOT_CONVERT_DESCRIPTION_TASK
				)
			);

			return '';
		}
	}

	/**
	 * The method returns an array of data in the required format for the client app.
	 *
	 * @param array $taskIds Task ids.
	 * @return array
	 */
	public function getItemsData(array $taskIds): array
	{
		if (empty($taskIds))
		{
			return [];
		}

		$itemsData = [];

		$tasksInfo = $this->getTasksInfo($taskIds);

		$groupId = 0;
		$parentIdsToCheck = [];

		foreach ($tasksInfo as $taskId => $taskInfo)
		{
			$groupId = (int) $taskInfo['GROUP_ID'];

			$attachedFilesCount = (
				is_array($taskInfo['UF_TASK_WEBDAV_FILES'])
				? count($taskInfo['UF_TASK_WEBDAV_FILES'])
				: 0
			);

			$itemsData[$taskId] = [
				'name' => $taskInfo['TITLE'],
				'responsibleId' => ($taskInfo['RESPONSIBLE_ID'] ?? 0),
				'completed' => ($taskInfo['STATUS'] == \CTasks::STATE_COMPLETED) ? 'Y' : 'N',
				'attachedFilesCount' => $attachedFilesCount,
			];

			$parentId = (int) $taskInfo['PARENT_ID'];
			if ($parentId)
			{
				$parentIdsToCheck[$taskId] = $parentId;
			}
			else
			{
				$itemsData[$taskId]['parentTaskId'] = 0;
				$itemsData[$taskId]['isSubTask'] = 'N';
			}
		}

		$actualParentIds = $this->getActualParentIds($parentIdsToCheck, $groupId);
		foreach ($actualParentIds as $taskId => $parentId)
		{
			$itemsData[$taskId]['parentTaskId'] = $parentId;
			$itemsData[$taskId]['isSubTask'] = $parentId ? 'Y' : 'N';
		}

		return $itemsData;
	}

	public function getItemsDynamicData(array $taskIds, $itemsData): array
	{
		foreach ($taskIds as $taskId)
		{
			$itemsData[$taskId]['tags'] = [];
		}

		$tags = [];
		$queryObject = \CTaskTags::getList([], ['TASK_ID' => $taskIds]);
		while ($tag = $queryObject->fetch())
		{
			if (in_array($tag['TASK_ID'], $taskIds))
			{
				if (!is_array($tags[$tag['TASK_ID']]))
				{
					$tags[$tag['TASK_ID']] = [];
				}
				$tags[$tag['TASK_ID']][] = $tag['NAME'];
			}
		}
		foreach ($tags as $taskId => $tagList)
		{
			$itemsData[$taskId]['tags'] = $tagList;

			$this->setTaskTags($tagList);
		}

		foreach ($taskIds as $taskId)
		{
			$itemsData[$taskId]['allowedActions'] = $this->getAllowedTaskActions($taskId);
			$itemsData[$taskId]['isLinkedTask'] = $this->isLinkedTask($taskId) ? 'Y' : 'N';
		}

		// todo
		foreach ($this->getSubTasksInfo($taskIds) as $taskId => $subTasksInfo)
		{
			$subTasks = [];
			$completedSubTasks = [];
			foreach ($subTasksInfo as $subTaskInfo)
			{
				if ($subTaskInfo['completed'] === 'Y')
				{
					$completedSubTasks[$subTaskInfo['sourceId']] = $subTaskInfo;
				}
				else
				{
					$subTasks[$subTaskInfo['sourceId']] = $subTaskInfo;
				}
			}

			$itemsData[$taskId]['isParentTask'] = ($subTasks ? 'Y' : 'N');
			$itemsData[$taskId]['subTasksCount'] = count($subTasks);
			$itemsData[$taskId]['subTasksInfo'] = $subTasks;
			$itemsData[$taskId]['completedSubTasksInfo'] = $completedSubTasks;
		}

		$checkListCounts = $this->getChecklistCounts($taskIds);
		foreach ($checkListCounts as $taskId => $checkListCount)
		{
			$itemsData[$taskId]['checkListComplete'] = (int) $checkListCount['complete'];
			$itemsData[$taskId]['checkListAll'] = (int) ($checkListCount['complete'] + $checkListCount['progress']);
		}

		$tasksCounters = $this->getTasksCounters($taskIds);
		foreach ($tasksCounters as $taskId => $taskCounter)
		{
			$itemsData[$taskId]['taskCounter'] = $taskCounter;
		}

		return $itemsData;
	}

	public function mandatoryExists(): bool
	{
		$queryObject = \CUserTypeEntity::getList(
			[],
			[
				'ENTITY_ID' => 'TASKS_TASK',
				'MANDATORY' => 'Y'
			]
		);

		return (bool) $queryObject->fetch();
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public static function onAfterTaskAdd(int $taskId, array &$fields)
	{
		try
		{
			if ($fields['GROUP_ID'] && Loader::includeModule('socialnetwork'))
			{
				$group = Workgroup::getById($fields['GROUP_ID']);
				if ($group && $group->isScrumProject())
				{
					self::createScrumItem($taskId, $fields);

					$parentTaskId = $fields['PARENT_ID'];
					if ($parentTaskId)
					{
						$itemService = new ItemService();
						$parentItem = $itemService->getItemBySourceId($parentTaskId);
						if (!$parentItem->isEmpty())
						{
							(new CacheService($parentItem->getSourceId(), CacheService::ITEM_TASKS))->clean();

							$pushService = (Loader::includeModule('pull') ? new PushService() : null);
							$itemService->changeItem($parentItem, $pushService);
						}
					}

					$hasLinks = (isset($fields['DEPENDS_ON']) && is_array($fields['DEPENDS_ON']));
					if ($hasLinks)
					{
						$taskService = new TaskService(Util\User::getId());

						foreach ($fields['DEPENDS_ON'] as $linkedTaskId)
						{
							$taskService->updateTaskLinks($linkedTaskId, $taskId);
						}
					}
				}
			}
		}
		catch (\Exception $exception) {}
	}

	public static function onAfterTaskUpdate(int $taskId, array &$fields, array &$previousFields)
	{
		try
		{
			$currentGroupId = (int)$previousFields['GROUP_ID'];

			$isScrumTaskUpdated = false;
			if (Loader::includeModule('socialnetwork'))
			{
				$currentGroupId = (int)($fields['GROUP_ID'] > 0 ? $fields['GROUP_ID'] : $previousFields['GROUP_ID']);
				$group = Workgroup::getById($currentGroupId);
				if ($group && $group->isScrumProject())
				{
					$isScrumTaskUpdated = true;
				}
			}
			if (!$isScrumTaskUpdated)
			{
				return;
			}

			(new CacheService($taskId, CacheService::ITEM_TASKS))->clean();

			$isGroupUpdateAction = isset($fields['GROUP_ID']);
			if ($isGroupUpdateAction)
			{
				if ($fields['GROUP_ID'] > 0)
				{
					$oldGroupId = (int)$previousFields['GROUP_ID'];
					$oldParentId = (int)$previousFields['PARENT_ID'];
					if ($oldGroupId && $fields['GROUP_ID'] != $oldGroupId)
					{
						$previousFieldsPart = ['GROUP_ID' => $fields['GROUP_ID']];
						if ($oldParentId && $fields['PARENT_ID'] && $fields['PARENT_ID'] != $oldParentId)
						{
							$previousFieldsPart['PARENT_ID'] = $fields['PARENT_ID'];
						}

						self::updateScrumItem($taskId, array_merge($previousFields, $previousFieldsPart));
					}
					if (!$oldGroupId)
					{
						self::createScrumItem($taskId, $fields, $previousFields);
					}
				}
				else
				{
					self::deleteScrumItem($taskId);
				}
			}

			$hasLinks = (isset($fields['DEPENDS_ON']) && is_array($fields['DEPENDS_ON']));
			$hasPrevLinks = (isset($previousFields['DEPENDS_ON']) && is_array($previousFields['DEPENDS_ON']));
			if ($hasLinks)
			{
				$taskService = new TaskService(Util\User::getId());

				foreach ($fields['DEPENDS_ON'] as $linkedTaskId)
				{
					$taskService->updateTaskLinks($linkedTaskId, $taskId);
				}

				if ($hasPrevLinks)
				{
					foreach (array_diff($previousFields['DEPENDS_ON'], $fields['DEPENDS_ON']) as $linkedTaskId)
					{
						$taskDependence = new \CTaskDependence();

						$taskDependence->delete($linkedTaskId, $taskId);
					}
				}
			}

			$isScrumFieldsUpdated = (
				(isset($fields['TITLE']) && $fields['TITLE'] !== $previousFields['TITLE'])
				|| (isset($fields['TAGS']))
				|| (isset($fields['RESPONSIBLE_ID']) && $fields['RESPONSIBLE_ID'] != $previousFields['RESPONSIBLE_ID'])
				|| (
					isset($fields['UF_TASK_WEBDAV_FILES'])
					&& (array_filter($fields['UF_TASK_WEBDAV_FILES']) != $previousFields['UF_TASK_WEBDAV_FILES'])
				)
			);
			$isCompleteAction = (
				isset($fields['STATUS']) && $fields['STATUS'] == \CTasks::STATE_COMPLETED
				&& (isset($previousFields['STATUS']) && $previousFields['STATUS'] != \CTasks::STATE_COMPLETED)
			);

			$isRenewAction = (
				(isset($fields['STATUS']) && $fields['STATUS'] == \CTasks::STATE_PENDING)
				&& (
					isset($previousFields['STATUS'])
					&& (
						$previousFields['STATUS'] == \CTasks::STATE_COMPLETED
						|| $previousFields['STATUS'] == \CTasks::STATE_SUPPOSEDLY_COMPLETED
					)
				)
			);

			$isParentChangeAction =
				isset($fields['PARENT_ID'])
				&& $fields['PARENT_ID'] != $previousFields['PARENT_ID']
			;
			if ($isParentChangeAction)
			{
				$itemService = new ItemService();
				$pushService = (Loader::includeModule('pull') ? new PushService() : null);

				$parentId = (int) $fields['PARENT_ID'];
				$oldParentId = (int) $previousFields['PARENT_ID'];
				if ($oldParentId)
				{
					$parentItem = $itemService->getItemBySourceId($oldParentId);
					if (!$parentItem->isEmpty())
					{
						$itemService->changeItem($parentItem, $pushService);

						(new CacheService($parentItem->getSourceId(), CacheService::ITEM_TASKS))->clean();
					}
				}
				if ($parentId)
				{
					$parentItem = $itemService->getItemBySourceId($parentId);
					if (!$parentItem->isEmpty())
					{
						$itemService->changeItem($parentItem, $pushService);

						(new CacheService($parentItem->getSourceId(), CacheService::ITEM_TASKS))->clean();
					}
				}

				$itemService->changeItem($itemService->getItemBySourceId($taskId), $pushService);
			}

			if (($isScrumFieldsUpdated || $isCompleteAction || $isRenewAction) && !$isParentChangeAction)
			{
				$itemService = new ItemService();
				$pushService = (Loader::includeModule('pull') ? new PushService() : null);
				$itemService->changeItem($itemService->getItemBySourceId($taskId), $pushService);
			}
			if ($isRenewAction)
			{
				$parentId = (int) $previousFields['PARENT_ID'];
				if ($parentId && self::isTaskInActiveSprint($parentId, $currentGroupId))
				{
					if (!self::isTaskInActiveSprint($taskId, $currentGroupId))
					{
						self::moveTaskToActiveSprint($taskId, $currentGroupId);
					}
				}
				else
				{
					self::moveTaskToBacklog($taskId, $currentGroupId);
				}
			}
			if ($isCompleteAction)
			{
				self::moveTaskToFinishStatus($taskId, $currentGroupId);
			}
		}
		catch (\Exception $exception) {}
	}

	public function getTasksInfo(array $taskIds): array
	{
		try
		{
			$tasksInfo = [];

			$queryObject = \CTasks::getList(
				[],
				[
					'ID' => $taskIds,
					'CHECK_PERMISSIONS' => 'N',
				],
				[
					'TITLE',
					'RESPONSIBLE_ID',
					'CREATED_BY',
					'GROUP_ID',
					'PARENT_ID',
					'STATUS',
					'UF_TASK_WEBDAV_FILES',
				]
			);
			while ($data = $queryObject->fetch())
			{
				$data['TITLE'] = \Bitrix\Main\Text\Emoji::decode($data['TITLE']);
				$tasksInfo[$data['ID']] = $data;
			}

			return $tasksInfo;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_READ_TASK));
		}

		return [];
	}

	public function getActualParentIds(array $parentIds, int $groupId): array
	{
		[$rows, $queryObject] = $this->getList([
			'select' => ['ID'],
			'filter' => [
				'ID' => $parentIds,
				'GROUP_ID' => $groupId,
				'CHECK_PERMISSIONS' => 'Y',
			],
		]);

		$receivedIds = [];
		foreach ($rows as $row)
		{
			$receivedIds[] = $row->getId();
		}

		foreach ($parentIds as $taskId => $parentId)
		{
			if (!in_array($parentId, $receivedIds))
			{
				$parentIds[$taskId] = 0;
			}
		}

		return $parentIds;
	}

	private function getTaskItemObject($taskId)
	{
		if (empty(self::$taskItemObject[$taskId]))
		{
			self::$taskItemObject[$taskId] = \CTaskItem::getInstance($taskId, $this->executiveUserId);
		}
		return self::$taskItemObject[$taskId];
	}

	private function getChecklistCounts(array $taskIds): array
	{
		try
		{
			$checkList = [];

			$query = new Query(CheckListTable::getEntity());
			$query->setSelect(['TASK_ID', 'IS_COMPLETE', new ExpressionField('CNT', 'COUNT(TASK_ID)')]);
			$query->setFilter(['TASK_ID' => $taskIds]);
			$query->setGroup(['TASK_ID', 'IS_COMPLETE']);
			$query->registerRuntimeField('', new ReferenceField(
				'IT',
				CheckListTreeTable::class,
				Join::on('this.ID', 'ref.CHILD_ID')->where('ref.LEVEL', 1),
				['join_type' => 'INNER']
			));

			$result = $query->exec();
			while ($row = $result->fetch())
			{
				$checkList[$row['TASK_ID']][$row['IS_COMPLETE'] == 'Y' ? 'complete' : 'progress'] = $row['CNT'];
			}

			return $checkList;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();
			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_COUNT_CHECKLIST_FILES));
			return [];
		}
	}

	private function getTasksCounters(array $taskIds): array
	{
		$taskCounters = [];

		foreach ($taskIds as $taskId)
		{
			$taskCounters[$taskId] = [
				'color' => 'ui-counter-gray',
				'value' => 0,
			];
		}

		if (!$this->hasAccessToCounters())
		{
			return $taskCounters;
		}

		try
		{
			$colorMap = [
				CounterStyle::STYLE_GRAY => 'ui-counter-gray',
				CounterStyle::STYLE_GREEN => 'ui-counter-success',
			];

			$taskCounter = new TaskCounter($this->executiveUserId);

			foreach ($taskIds as $taskId)
			{
				$rowCounter = $taskCounter->getRowCounter($taskId);

				$taskCounters[$taskId] = [
					'color' => $colorMap[$rowCounter['COLOR']] ?? 'ui-counter-gray',
					'value' => $rowCounter['VALUE'],
				];
			}

			return $taskCounters;
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(new Error($message, self::ERROR_COULD_NOT_COUNT_COMMENTS_TASK));

			return [];
		}
	}

	private function getSubTasksInfo(array $taskIds): array
	{
		$subTasksInfo = [];

		foreach ($taskIds as $taskId)
		{
			$subTasksInfo[$taskId] = [];
		}

		try
		{
			$queryObject = \CTasks::getList(
				['ID' => 'ASC'],
				['PARENT_ID' => $taskIds],
				['ID', 'STATUS', 'PARENT_ID']
			);
			while ($taskData = $queryObject->fetch())
			{
				$subTasksInfo[$taskData['PARENT_ID']][$taskData['ID']] = [
					'sourceId' => (int) $taskData['ID'],
					'completed' => ($taskData['STATUS'] == \CTasks::STATE_COMPLETED ? 'Y' : 'N'),
				];
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_CHECK_GET_SUB_TASK_INFO)
			);
		}

		return $subTasksInfo;
	}

	private function isLinkedTask(int $taskId): bool
	{
		try
		{
			$taskItem = $this->getTaskItemObject($taskId);

			$taskData = $taskItem->getData(false, [
				'select' => ['DEPENDS_ON'],
			]);

			return !empty($taskData['DEPENDS_ON']);
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_CHECK_IS_LINKED_TASK)
			);

			return false;
		}
	}

	private function getList(array $params): array
	{
		try
		{
			[$rows, $queryObject] = \CTaskItem::fetchList(
				$this->executiveUserId,
				isset($params['order']) ? $params['order'] : [],
				isset($params['filter']) ? $params['filter'] : [],
				isset($params['navigate']) ? $params['navigate'] : [],
				isset($params['select']) ? $params['select'] : []
			);

			return [$rows, $queryObject];
		}
		catch (\Exception $exception)
		{
			$message = $exception->getMessage().$exception->getTraceAsString();

			$this->errorCollection->setError(
				new Error(
					$message,
					self::ERROR_COULD_NOT_READ_LIST_TASK
				)
			);

			return [];
		}
	}

	private function cleanTagsInTaskName(string $name): array
	{
		$tags = [];
		if (isset($name) && preg_match_all('/\s#([^\s,\[\]<>]+)/is', ' '.$name, $matches))
		{
			$name = trim(str_replace($matches[0], '', $name));
			$tags = $matches[1];
		}
		return [$name, $tags];
	}

	private function addTags(int $taskId, array $tags): void
	{
		$tasksObject = new \CTasks();
		$tasksObject->addTags($taskId, $this->executiveUserId, $tags, $this->executiveUserId);
	}

	private function setErrors(Result $result, string $code): void
	{
		$this->errorCollection->setError(new Error(implode('; ', $result->getErrorMessages()), $code));
	}

	private static function createScrumItem(int $taskId, array $fields, $previousFields = []): void
	{
		$isBacklogTarget = true;

		$parentTaskId = $fields['PARENT_ID'] ? $fields['PARENT_ID'] : $previousFields['PARENT_ID'];

		if ($parentTaskId)
		{
			$itemService = new ItemService();

			$parentScrumItem = $itemService->getItemBySourceId($parentTaskId);
			if (!$parentScrumItem->isEmpty())
			{
				$taskService = new TaskService(Util\User::getId());
				$entityService = new EntityService();

				$entity = $entityService->getEntityById($parentScrumItem->getEntityId());
				if (!$entity->isEmpty())
				{
					$parentTaskId = $parentScrumItem->getSourceId();

					$sort = count($taskService->getSubTasksInfo([$parentTaskId])[$parentTaskId]);
					$sort = ($sort === 0 ? 1 : $sort);

					self::createItem(
						$entity,
						$taskId,
						$fields,
						$previousFields,
						$parentScrumItem->getEpicId(),
						$sort
					);

					$isBacklogTarget = false;
				}
			}
		}

		if ($isBacklogTarget)
		{
			$backlogService = new BacklogService();

			$backlog = $backlogService->getBacklogByGroupId($fields['GROUP_ID']);
			if (!$backlog->isEmpty())
			{
				self::createItem($backlog, $taskId, $fields, $previousFields);
			}
		}
	}

	private static function createItem(
		EntityForm $entity,
		int $taskId,
		array $fields,
		array $previousFields = [],
		int $epicId = 0,
		int $sort = 1
	): void
	{
		$itemService = new ItemService();

		$item = $itemService->getItemBySourceId($taskId);
		if (!$itemService->getErrors() && $item->isEmpty())
		{
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			$scrumItem = new ItemForm();

			$createdBy = ($fields['CREATED_BY'] ? $fields['CREATED_BY'] : $previousFields['CREATED_BY']);
			$scrumItem->setCreatedBy($createdBy);
			$scrumItem->setEntityId($entity->getId());
			$scrumItem->setSourceId($taskId);
			$scrumItem->setSort($sort);
			$scrumItem->setEpicId($epicId);

			$itemService->createTaskItem($scrumItem, $pushService);
			if (!$itemService->getErrors() && $entity->isActiveSprint())
			{
				$kanbanService = new KanbanService();
				if (!$kanbanService->isTaskInKanban($entity->getId(), $scrumItem->getSourceId()))
				{
					$kanbanService->addTasksToKanban($entity->getId(), [$scrumItem->getSourceId()]);
				}
			}
		}
	}

	private static function updateScrumItem(int $taskId, $fields): void
	{
		$isActiveSprintItem = false;

		$parentTaskId = $fields['PARENT_ID'];

		if ($parentTaskId)
		{
			$sprintService = new SprintService();
			$itemService = new ItemService();
			$scrumItem = $itemService->getItemBySourceId($parentTaskId);
			$sprint = $sprintService->getActiveSprintByGroupId($fields['GROUP_ID']);
			$isActiveSprintItem = ($sprint->getId() === $scrumItem->getEntityId());
			if ($sprint->isEmpty() || $scrumItem->isEmpty())
			{
				$isActiveSprintItem = false;
			}
			if ($isActiveSprintItem)
			{
				self::updateItem($sprint, $taskId, $fields);
			}
		}

		if (!$isActiveSprintItem)
		{
			$backlogService = new BacklogService();
			$backlog = $backlogService->getBacklogByGroupId($fields['GROUP_ID']);
			if (!$backlogService->getErrors() && !$backlog->isEmpty())
			{
				self::updateItem($backlog, $taskId, $fields);
			}
		}
	}

	private static function updateItem(EntityForm $entity, int $taskId, $fields): void
	{
		$itemService = new ItemService();
		$scrumItem = $itemService->getItemBySourceId($taskId);
		if (!$itemService->getErrors() && !$scrumItem->isEmpty())
		{
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			$scrumItem->setEntityId($entity->getId());
			$scrumItem->setSort(1);
			$scrumItem->setEpicId(0);
			$scrumItem->setModifiedBy($fields['CHANGED_BY']);
			$itemService->changeItem($scrumItem, $pushService);
			if (!$itemService->getErrors() && $entity->isActiveSprint())
			{
				$kanbanService = new KanbanService();
				$kanbanService->addTasksToKanban($entity->getId(), [$scrumItem->getSourceId()]);
			}
		}
	}

	private static function deleteScrumItem(int $taskId): void
	{
		$itemService = new ItemService();
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);
		$scrumItem = $itemService->getItemBySourceId($taskId);
		if (!$itemService->getErrors() && !$scrumItem->isEmpty())
		{
			$itemService->removeItem($scrumItem, $pushService);
		}
	}

	private static function isTaskInActiveSprint(int $taskId, int $groupId): bool
	{
		$itemService = new ItemService();
		$sprintService = new SprintService();
		$scrumItem = $itemService->getItemBySourceId($taskId);
		if (!$itemService->getErrors() && !$scrumItem->isEmpty())
		{
			$sprint = $sprintService->getActiveSprintByGroupId($groupId);

			return ($sprint->getId() === $scrumItem->getEntityId());
		}

		return false;
	}

	private static function moveTaskToActiveSprint(int $taskId, int $groupId): void
	{
		$itemService = new ItemService();
		$scrumItem = $itemService->getItemBySourceId($taskId);
		if (!$itemService->getErrors() && !$scrumItem->isEmpty())
		{
			$sprintService = new SprintService();
			$kanbanService = new KanbanService();

			$sprint = $sprintService->getActiveSprintByGroupId($groupId);

			$scrumItem->setEntityId($sprint->getId());

			$pushService = (Loader::includeModule('pull') ? new PushService() : null);
			$itemService->changeItem($scrumItem, $pushService);

			$kanbanService->addTaskToNewStatus($sprint->getId(), $scrumItem->getSourceId());
		}
	}

	private static function moveTaskToBacklog(int $taskId, int $groupId): void
	{
		$itemService = new ItemService();
		$scrumItem = $itemService->getItemBySourceId($taskId);
		if (!$itemService->getErrors() && !$scrumItem->isEmpty())
		{
			$backlogService = new BacklogService();
			$backlog = $backlogService->getBacklogByGroupId($groupId);
			if (!$backlogService->getErrors() && !$backlog->isEmpty())
			{
				if ($backlog->getId() !== $scrumItem->getEntityId())
				{
					$sprintService = new SprintService();
					$sprint = $sprintService->getSprintById($scrumItem->getEntityId());
					if ($sprint->isActiveSprint())
					{
						$kanbanService = new KanbanService();
						if ($kanbanService->isTaskInFinishStatus($sprint->getId(), $scrumItem->getSourceId()))
						{
							$kanbanService->addTaskToNewStatus($sprint->getId(), $scrumItem->getSourceId());
						}
					}
					if ($sprint->isCompletedSprint())
					{
						$scrumItem->setEntityId($backlog->getId());
						$scrumItem->setSort(1);

						$pushService = (Loader::includeModule('pull') ? new PushService() : null);
						$itemService->changeItem($scrumItem, $pushService);
					}
				}
			}
		}
	}

	private static function moveTaskToFinishStatus(int $taskId, int $currentGroupId): void
	{
		$sprintService = new SprintService();
		$itemService = new ItemService();
		$scrumItem = $itemService->getItemBySourceId($taskId);
		if ($itemService->getErrors() || $scrumItem->isEmpty())
		{
			return;
		}

		$sprint = $sprintService->getActiveSprintByGroupId($currentGroupId);
		$isActiveSprintItem = ($sprint->getId() === $scrumItem->getEntityId());
		if ($isActiveSprintItem)
		{
			$kanbanService = new KanbanService();
			if (!$kanbanService->isTaskInFinishStatus($sprint->getId(), $scrumItem->getSourceId()))
			{
				$kanbanService->addTaskToFinishStatus($sprint->getId(), $scrumItem->getSourceId());
			}
		}
	}
}
