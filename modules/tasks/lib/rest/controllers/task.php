<?php
namespace Bitrix\Tasks\Rest\Controllers;

use Bitrix\Crm\Integration\UI\EntitySelector\DynamicMultipleProvider;
use Bitrix\Crm\Service\Display;
use Bitrix\Crm\Service\Display\Field;
use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UserTable;
use Bitrix\Pull\MobileCounter;
use Bitrix\Socialnetwork\Helper\Workgroup;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\FileUploader\TaskController;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Integration\CRM;
use Bitrix\Tasks\Integration\Disk;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Integration\TasksMobile;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\Template\TaskCounter;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Helper\Analytics;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Scrum\Service\TaskService;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\UI\FileUploader\Uploader;
use CBitrixComponent;
use CComponentEngine;
use CCrmOwnerTypeAbbr;
use CFile;
use CSite;
use CTaskDependence;
use CTaskItem;
use CTaskNotifications;
use CTasks;
use CTaskTags;
use CTaskTimerManager;
use CTimeZone;
use CUser;
use Exception;
use TasksException;
use TasksTaskListComponent;

/**
 * Class Task
 * @package Bitrix\Tasks\Rest\Controllers
 * @restController tasks.task
 */
final class Task extends Base
{
	public function configureActions(): array
	{
		return [
			'search' => [
				'class' => Action\SearchAction::class,
				'+prefilters' => [new Engine\ActionFilter\CloseSession()],
			],
		];
	}

	/**
	 * @throws BinderArgumentException
	 */
	public function getPrimaryAutoWiredParameter(): Engine\AutoWire\ExactParameter
	{
		return new Engine\AutoWire\ExactParameter(
			CTaskItem::class,
			'task',
			function ($className, $id) {
				if (($id = (int)$id) <= 0)
				{
					$this->addError(new Error('wrong task id'));
					return null;
				}
				return new $className($id, Engine\CurrentUser::get()->getId());
			}
		);
	}

	/**
	 * Return all DB and UF_ fields of task
	 *
	 * @restMethod tasks.task.getFields
	 */
	public function getFieldsAction(): array
	{
		return ['fields' => CTasks::getFieldsInfo()];
	}

	/**
	 * Return access data to task for current user
	 *
	 * @restMethod tasks.task.getAccess
	 */
	public function getAccessAction(CTaskItem $task, array $users = [], array $params = []): array
	{
		if (empty($users))
		{
			$users[] = $this->getCurrentUser()->getId();
		}

		$returnAsString = !array_key_exists('AS_STRING', $params) || $params['AS_STRING'] !== 'N';

		$list = [];
		foreach ($users as $userId)
		{
			try
			{
				$list[$userId] = $this->translateAllowedActionNames(
					CTaskItem::getAllowedActionsArray($userId, $task->getData(false), $returnAsString)
				);
			}
			catch (TasksException)
			{
			}
		}

		return ['allowedActions' => $list];
	}

	/**
	 * Get task item data
	 *
	 * @restMethod tasks.task.get
	 */
	public function getAction(CTaskItem $task, array $select = [], array $params = []): array
	{
		if (!empty($select))
		{
			$select[] = 'FAVORITE';
		}

		$params['select'] = $this->prepareSelect($select);
		try
		{
			$row = $task->getData(false, $params);
			if (array_key_exists('STATUS', $row))
			{
				$row['STATUS'] = $row['REAL_STATUS'];
				unset($row['REAL_STATUS']);
			}
			if (array_key_exists('DESCRIPTION', $row))
			{
				$row['DESCRIPTION'] = htmlspecialchars_decode($row['DESCRIPTION'] ?? '', ENT_QUOTES);
			}

			$row = $this->fillGroupInfo([$row], $params)[0];
			$row = $this->fillUserInfo([$row])[0];
			if (array_key_exists('WITH_RESULT_INFO', $params))
			{
				$row = $this->fillResultInfo([$row])[0];
			}
			if (array_key_exists('WITH_TIMER_INFO', $params))
			{
				$row = $this->fillWithTimerInfo([$row])[0];
			}
			if (in_array('COUNTERS', $select, true))
			{
				$row = $this->fillCounterInfo([$row])[0];
			}
			if (in_array('RELATED_TASKS', $select, true))
			{
				$row = $this->fillWithRelatedTasks([$row])[0];
			}
			if (in_array('SUB_TASKS', $select, true))
			{
				$row = $this->fillWithSubTasks([$row])[0];
			}
			if (in_array('TAGS', $select, true))
			{
				$row = $this->fillWithTags([$row])[0];
			}
			if (
				array_key_exists('WITH_FILES_INFO', $params)
				&& in_array(Disk\UserField::getMainSysUFCode(), $select, true)
			)
			{
				$row = $this->fillWithFilesInfo([$row])[0];
			}
			if (
				array_key_exists('WITH_CRM_INFO', $params)
				&& in_array(CRM\UserField::getMainSysUFCode(), $select, true)
			)
			{
				$row = $this->fillWithCrmInfo([$row])[0];
			}
			if (
				array_key_exists('WITH_PARENT_TASK_INFO', $params)
				&& in_array('PARENT_ID', $select, true)
			)
			{
				$row = $this->fillWithParentTaskInfo([$row])[0];
			}
			if (array_key_exists('WITH_PARSED_DESCRIPTION', $params))
			{
				$row = $this->fillWithParsedDescription([$row])[0];
			}

			$this->formatDateFieldsForOutput($row);

			if (in_array('NEW_COMMENTS_COUNT', $params['select'], true))
			{
				$taskId = $task->getId();
				$userId = $this->getCurrentUser()->getId();

				$newComments = Counter::getInstance((int)$userId)->getCommentsCount([$taskId]);
				$row['NEW_COMMENTS_COUNT'] = $newComments[$taskId];
			}

			$action = $this->getAccessAction($task);
			$row['action'] = $action['allowedActions'][$this->getCurrentUser()->getId()];

			if (isset($params['GET_TASK_LIMIT_EXCEEDED']) && $params['GET_TASK_LIMIT_EXCEEDED'])
			{
				$row['TASK_LIMIT_EXCEEDED'] = TaskLimit::isLimitExceeded();
			}

			if (isset($row['CHECKLIST']))
			{
				$canAdd = TaskCheckListFacade::isActionAllowed(
					$task->getId(),
					null,
					$this->getCurrentUser()->getId(),
					TaskCheckListFacade::ACTION_ADD
				);

				$row['CHECKLIST'] = $this->fillActionsForCheckListItems(
					$task->getId(),
					$row['CHECKLIST'],
					$canAdd
				);

				$objectTreeStructure = $this->buildTreeStructure($row['CHECKLIST']);
				$objectTreeStructure = $this->fillTreeInfo($objectTreeStructure);

				$row['CHECK_LIST_TREE'] = $objectTreeStructure->toTreeArray();
				$row['CHECK_LIST_CAN_ADD'] = $canAdd;
			}

			return ['task' => $this->convertKeysToCamelCase($row)];
		}
		catch (Exception $exception)
		{
			// special case for mobile
			return [];
		}
	}

	/**
	 * Create new task
	 *
	 * @restMethod tasks.task.add
	 */
	public function addAction(array $fields, array $params = []): ?array
	{
		$fields = $this->filterFields($fields);
		$fields = $this->formatDateFieldsForInput($fields);
		$fields = $this->processFiles(0, $fields);
		$fields = $this->processCrmElements($fields);
		$fields = $this->processScenario($fields, $params);

		try
		{
			$task = CTaskItem::add($fields, $this->getCurrentUser()->getId(), $params);
			if (isset($params['PLATFORM']) && $params['PLATFORM'] === 'mobile')
			{
				Analytics::getInstance()->logToFile('addTask');
			}

			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			if (
				$exception instanceof TasksException
				&& $exception->isSerialized()
			)
			{
				$errors = unserialize($exception->getMessage(), ['allowed_classes' => false]);
				$error = $errors[0];
				$this->addError(new Error($error['text'], $error['id']));
			}
			else
			{
				$this->addError(Error::createFromThrowable($exception));
			}

			return null;
		}
	}

	/**
	 * Update existing task
	 *
	 * @restMethod tasks.task.update
	 */
	public function updateAction(CTaskItem $task, array $fields, array $params = []): ?array
	{
		$fields = $this->filterFields($fields);
		$fields = $this->formatDateFieldsForInput($fields);
		$fields = $this->processFiles($task->getId(), $fields);
		$fields = $this->processCrmElements($fields);

		try
		{
			$fields = $this->removeReferences($fields);
			$task->update($fields, $params);
			if (Loader::includeModule('pull'))
			{
				MobileCounter::send($this->getCurrentUser()->getId());
			}

			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			if (
				$exception instanceof TasksException
				&& $exception->isSerialized()
			)
			{
				$errors = unserialize($exception->getMessage(), ['allowed_classes' => false]);
				$error = $errors[0];
				$this->addError(new Error($error['text'], $error['id']));
			}
			else
			{
				$this->addError(new Error($exception->getMessage()));
			}

			return null;
		}
	}

	/**
	 * Remove existing task
	 *
	 * @restMethod tasks.task.delete
	 */
	public function deleteAction(CTaskItem $task, array $params = []): ?array
	{
		try
		{
			$task->delete($params);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}

		return ['task' => true];
	}

	/**
	 * Get list all task
	 *
	 * @restMethod tasks.task.list
	 */
	public function listAction(
		array $filter = [],
		array $select = [],
		array $group = [],
		array $order = [],
		array $params = [],
		PageNavigation $pageNavigation = null,
		bool $__calculateTotalCount = true
	): ?Engine\Response\DataType\Page
	{
		if (!$this->checkOrderKeys($order))
		{
			$this->addError(new Error(Main\Localization\Loc::getMessage('TASKS_FAILED_WRONG_ORDER_FIELD')));

			return null;
		}

		if (!$this->checkSelect($select))
		{
			$this->addError(new Error('Invalid select data'));

			return null;
		}

		$preparedSelect = $this->prepareSelect($select);
		$preparedFilter = $this->prepareFilter($filter, $select, $params);

		$navParams = $this->prepareNavParams($pageNavigation, $params);
		$navParams['__calculateTotalCount'] = $__calculateTotalCount;

		$getListParams = [
			'select' => $preparedSelect,
			'legacyFilter' => ($preparedFilter ?: []),
			'order' => ($order ?: []),
			'group' => ($group ?: []),
			'NAV_PARAMS' => $navParams,
		];

		$params['PUBLIC_MODE'] = 'Y'; // VERY VERY BAD HACK! DONT REPEAT IT !
		$params['USE_MINIMAL_SELECT_LEGACY'] = 'N'; // VERY VERY BAD HACK! DONT REPEAT IT !
		$params['RETURN_ACCESS'] = ($params['RETURN_ACCESS'] ?? 'N'); // VERY VERY BAD HACK! DONT REPEAT IT !.. too late
		$params['DISTINCT'] = true;

		try
		{
			$result = Manager\Task::getList($this->getCurrentUser()->getId(), $getListParams, $params);
			$tasks = array_values($result['DATA']);
			$tasks = $this->fillGroupInfo($tasks, $params);
			$tasks = $this->fillUserInfo($tasks);

			if (array_key_exists('WITH_RESULT_INFO', $params))
			{
				$tasks = $this->fillResultInfo($tasks);
			}
			if (array_key_exists('WITH_TIMER_INFO', $params))
			{
				$tasks = $this->fillWithTimerInfo($tasks);
			}
			if (array_key_exists('WITH_PARSED_DESCRIPTION', $params))
			{
				$tasks = $this->fillWithParsedDescription($tasks);
			}
			if (in_array('COUNTERS', $select, true))
			{
				$tasks = $this->fillCounterInfo($tasks);
			}
			if (in_array('TAGS', $select, true))
			{
				$tasks = $this->fillWithTags($tasks);
			}

			foreach ($tasks as &$task)
			{
				if (array_key_exists('STATUS', $task))
				{
					$task['SUB_STATUS'] = $task['STATUS'];
					$task['STATUS'] = $task['REAL_STATUS'];
					unset($task['REAL_STATUS']);
				}
				if (isset($task['DESCRIPTION']))
				{
					$task['DESCRIPTION'] = htmlspecialchars_decode($task['DESCRIPTION'], ENT_QUOTES);
				}

				$this->formatDateFieldsForOutput($task);
				$task = $this->convertKeysToCamelCase($task);
			}
			unset($task);

			return new Engine\Response\DataType\Page(
				'tasks',
				$tasks,
				static function() use ($result) {
					return $result['AUX']['OBJ_RES']->NavRecordCount;
				}
			);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * @restMethod tasks.task.mute
	 */
	public function muteAction(CTaskItem $task): ?array
	{
		try
		{
			UserOption::add($task->getId(), $this->getCurrentUser()->getId(), UserOption\Option::MUTED);
			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * @restMethod tasks.task.unmute
	 */
	public function unmuteAction(CTaskItem $task): ?array
	{
		try
		{
			UserOption::delete($task->getId(), $this->getCurrentUser()->getId(), UserOption\Option::MUTED);
			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * @restMethod tasks.task.pin
	 */
	public function pinAction(CTaskItem $task): ?array
	{
		try
		{
			UserOption::add($task->getId(), $this->getCurrentUser()->getId(), UserOption\Option::PINNED);
			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * @restMethod tasks.task.unpin
	 */
	public function unpinAction(CTaskItem $task): ?array
	{
		try
		{
			UserOption::delete($task->getId(), $this->getCurrentUser()->getId(), UserOption\Option::PINNED);
			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * @restMethod tasks.task.ping
	 */
	public function pingAction(CTaskItem $task): ?bool
	{
		try
		{
			if ($taskData = $task->getData(false))
			{
				return $this->pingStatus($taskData);
			}
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}

		return false;
	}

	/**
	 * Delegate task to another user
	 *
	 * @restMethod tasks.task.delegate
	 */
	public function delegateAction(CTaskItem $task, $userId, array $params = []): ?array
	{
		try
		{
			$task->delegate((int)$userId, $params);
			if ($params['PLATFORM'] === 'mobile')
			{
				Analytics::getInstance()->logToFile('delegateTask');
			}

			return $this->getAction($task);
		}
		catch (Exception $e)
		{
			$this->errorCollection->add([new Error($e->getMessage())]);
			return null;
		}
	}

	/**
	 * Start execute task
	 *
	 * @restMethod tasks.task.start
	 */
	public function startAction(CTaskItem $task, array $params = []): ?array
	{
		try
		{
			$task->startExecution($params);
			return $this->getAction($task);

		}
		catch (Exception $e)
		{
			$this->errorCollection->add([new Error($e->getMessage())]);
			return null;
		}
	}

	/**
	 * Take task from himself distribution flow
	 *
	 * @restMethod tasks.task.take
	 */
	public function takeAction(CTaskItem $task, array $params = []): ?array
	{
		try
		{
			$task->takeExecution($params);
			return $this->getAction($task);

		}
		catch (Exception $e)
		{
			$this->errorCollection->add([new Error($e->getMessage())]);
			return null;
		}
	}

	/**
	 * @restMethod tasks.task.startTimer
	 */
	public function startTimerAction(CTaskItem $task, array $params = []): ?array
	{
		try
		{
			$row = $task->getData(false);
			if ($row['ALLOW_TIME_TRACKING'] === 'N')
			{
				return null;
			}

			$params['STOP_PREVIOUS'] = (array_key_exists('STOP_PREVIOUS', $params) ? $params['STOP_PREVIOUS'] : 'N');

			if (!$this->startTimer($task, ($params['STOP_PREVIOUS'] === 'Y')))
			{
				return null;
			}

			return $this->getAction($task, [], ['WITH_TIMER_INFO' => 'Y']);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * Stop execute task
	 *
	 * @restMethod tasks.task.pause
	 */
	public function pauseAction(CTaskItem $task, array $params = []): ?array
	{
		try
		{
			$task->pauseExecution($params);
			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * @restMethod tasks.task.pauseTimer
	 */
	public function pauseTimerAction(CTaskItem $task, array $params = []): ?array
	{
		try
		{
			$row = $task->getData(false);
			if ($row['ALLOW_TIME_TRACKING'] === 'N')
			{
				return null;
			}

			if (!$this->stopTimer($task))
			{
				return null;
			}

			return $this->getAction($task, [], ['WITH_TIMER_INFO' => 'Y']);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * Complete task
	 *
	 * @restMethod tasks.task.complete
	 */
	public function completeAction(CTaskItem $task, array $params = []): ?array
	{
		try
		{
			$taskId = $task->getId();
			$lastResult = ResultManager::getLastResult($taskId);

			if (
				array_key_exists('PLATFORM', $params)
				&& in_array($params['PLATFORM'], ['web', 'mobile'])
				&& ResultManager::requireResult($taskId)
				&& (
					!$lastResult
					|| (int) $lastResult['STATUS'] !== ResultTable::STATUS_OPENED
				)
			)
			{
				$this->errorCollection->add([new Error(GetMessage('TASKS_FAILED_RESULT_REQUIRED'))]);
				return null;
			}

			$task->complete($params);

			return $this->getAction($task);
		}
		catch (Exception $e)
		{
			$this->errorCollection->add([new Error($e->getMessage())]);
			return null;
		}
	}

	/**
	 * Defer task
	 *
	 * @restMethod tasks.task.defer
	 */
	public function deferAction(CTaskItem $task, array $params = []): ?array
	{
		try
		{
			$task->defer($params);
			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * Renew task after complete
	 *
	 * @restMethod tasks.task.renew
	 */
	public function renewAction(CTaskItem $task, array $params = []): ?array
	{
		try
		{
			$task->renew($params);
			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}

	}

	/**
	 * Approve task
	 *
	 * @restMethod tasks.task.approve
	 */
	public function approveAction(CTaskItem $task, array $params = []): ?array
	{
		try
		{
			$task->approve($params);
			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * Disapprove task
	 *
	 * @restMethod tasks.task.disapprove
	 */
	public function disapproveAction(CTaskItem $task, array $params = []): ?array
	{
		try
		{
			$task->disapprove($params);
			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * Become an auditor of a specified task
	 *
	 * @restMethod tasks.task.startWatch
	 */
	public function startWatchAction(CTaskItem $task): ?array
	{
		try
		{
			$task->startWatch();
			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * Stop being an auditor of a specified task
	 *
	 * @restMethod tasks.task.stopWatch
	 */
	public function stopWatchAction(CTaskItem $task): ?array
	{
		try
		{
			$task->stopWatch();
			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * @restMethod tasks.task.addAuditors
	 */
	public function addAuditorsAction(CTaskItem $task, array $auditorsIds = []): ?array
	{
		try
		{
			if (empty($auditorsIds))
			{
				return $this->getAction($task);
			}
			$taskData = $task->getData(false);
			$auditors = array_merge($taskData['AUDITORS'], $auditorsIds);
			$task->update(['AUDITORS' => $auditors]);

			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * @restMethod tasks.task.addAccomplices
	 */
	public function addAccomplicesAction(CTaskItem $task, array $accomplicesIds = []): ?array
	{
		try
		{
			if (empty($accomplicesIds))
			{
				return $this->getAction($task);
			}

			$taskData = $task->getData(false);
			$accomplices = array_merge($taskData['ACCOMPLICES'], $accomplicesIds);
			$task->update(['ACCOMPLICES' => $accomplices]);

			return $this->getAction($task);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * @restMethod tasks.task.sendAnalyticsLabel
	 */
	public function sendAnalyticsLabelAction(): void
	{
	}

	/**
	 * @throws Main\NotImplementedException
	 */
	private function buildTreeStructure($checkListItems): CheckList
	{
		$nodeId = 0;

		$result = new CheckList(
			$nodeId,
			$this->getCurrentUser()->getId(),
			TaskCheckListFacade::class
		);

		$sortIndex = 0;
		$keyToSort = $this->getKeyToSort($checkListItems);

		$arrayTreeStructure = TaskCheckListFacade::getArrayStructuredRoots($checkListItems, $keyToSort);

		foreach ($arrayTreeStructure as $root)
		{
			$nodeId++;

			$result->add($this->makeTree($nodeId, $root, $sortIndex, false));

			$sortIndex++;
		}

		return $result;
	}

	private function getKeyToSort($items): string
	{
		$keyToSort = 'PARENT_ID';

		foreach ($items as $item)
		{
			if (array_key_exists('PARENT_NODE_ID', $item))
			{
				$keyToSort = 'PARENT_NODE_ID';
				break;
			}
		}

		return $keyToSort;
	}

	/**
	 * @throws Main\NotImplementedException
	 */
	private function makeTree($nodeId, $root, $sortIndex, $displaySortIndex): CheckList
	{
		$root['SORT_INDEX'] = $sortIndex;
		$root['DISPLAY_SORT_INDEX'] = htmlspecialcharsbx($displaySortIndex);

		$tree = new CheckList(
			$nodeId,
			$this->getCurrentUser()->getId(),
			TaskCheckListFacade::class,
			$root
		);

		$localSortIndex = 0;
		foreach ($root['SUB_TREE'] as $item)
		{
			++$localSortIndex;

			$nextDisplaySortIndex = (
				$displaySortIndex === false
					? $localSortIndex
					: "$displaySortIndex.$localSortIndex"
			);

			$tree->add($this->makeTree(++$nodeId, $item, $localSortIndex - 1, $nextDisplaySortIndex));
		}

		return $tree;
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function removeReferences(array $receivedFields): array
	{
		$allowableFields = [];
		$taskEntityFields = TaskTable::getEntity()->getFields();

		foreach ($receivedFields as $field => $data)
		{
			if (($taskEntityFields[$field] ?? null) instanceof Main\ORM\Fields\Relations\Relation)
			{
				continue;
			}

			$allowableFields[$field] = $data;
		}

		return $allowableFields;
	}

	/**
	 * @restMethod tasks.task.getGridRows
	 */
	public function getGridRowsAction(array $taskIds = [], array $navigation = [], array $arParams = []): ?array
	{
		try
		{
			/** @var TasksTaskListComponent $componentClassName */
			$componentClassName = CBitrixComponent::includeComponentClass('bitrix:tasks.task.list');
			return $componentClassName::getGridRows($taskIds, $arParams);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	/**
	 * @restMethod tasks.task.getNearTasks
	 */
	public function getNearTasksAction(array $taskIds, array $navigation, array $arParams = []): ?array
	{
		try
		{
			/** @var TasksTaskListComponent $componentClassName */
			$componentClassName = CBitrixComponent::includeComponentClass('bitrix:tasks.task.list');
			return $componentClassName::getNearTasks($taskIds, $navigation, $arParams);
		}
		catch (Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));
			return null;
		}
	}

	private function processScenario(array $fields, array $params): array
	{
		$isMobile = isset($params['PLATFORM']) && $params['PLATFORM'] === 'mobile';

		if ($isMobile)
		{
			$fields['SCENARIO_NAME'][] = ScenarioTable::SCENARIO_MOBILE;
		}

		if (!empty($fields[CRM\UserField::getMainSysUFCode()]))
		{
			$fields['SCENARIO_NAME'][] = ScenarioTable::SCENARIO_CRM;
		}

		if (isset($fields['SCENARIO_NAME']) && is_array($fields['SCENARIO_NAME']))
		{
			$fields['SCENARIO_NAME'] = ScenarioTable::filterByValidScenarios($fields['SCENARIO_NAME']);
		}

		return $fields;
	}

	private function processFilterRoleId(array $filter, int $userId, string $roleId): array
	{
		if (!$roleId)
		{
			return $filter;
		}

		switch ($roleId)
		{
			case 'R':
				$filter['=RESPONSIBLE_ID'] = $userId;
				break;

			case 'A':
				$filter['=ACCOMPLICE'] = $userId;
				break;

			case 'U':
				$filter['=AUDITOR'] = $userId;
				break;

			case 'O':
				$filter['=CREATED_BY'] = $userId;
				$filter['!REFERENCE:RESPONSIBLE_ID'] = 'CREATED_BY';
				break;

			default:
				if (array_key_exists('GROUP_ID', $filter))
				{
					$filter['MEMBER'] = $userId;
				}
				$filter['::SUBFILTER-OR-ORIGIN'] = [
					'::LOGIC' => 'OR',
					'::SUBFILTER-1' => [
						'REAL_STATUS' => $filter['REAL_STATUS'],
					],
					'::SUBFILTER-2' => [
						'=CREATED_BY' => $userId,
						'REAL_STATUS' => Status::SUPPOSEDLY_COMPLETED,
					],
				];
				unset($filter['REAL_STATUS']);
				break;
		}
		unset($filter['ROLE']);

		return $filter;
	}

	private function processFilterNotViewed(array $filter, int $userId, string $roleId): array
	{
		if (!array_key_exists('NOT_VIEWED', $filter) || $filter['NOT_VIEWED'] !== 'Y')
		{
			return $filter;
		}

		$filter['VIEWED'] = 0;
		$filter['VIEWED_BY'] = $userId;
		$filter['!CREATED_BY'] = $userId;

		switch ($roleId)
		{
			case 'A':
				$filter['::SUBFILTER-R'] = ['=ACCOMPLICE' => $userId];
				break;

			case 'U':
				$filter['::SUBFILTER-R'] = ['=AUDITOR' => $userId];
				break;

			case '': // view all
			default:
				$filter['::SUBFILTER-OR-NW'] = [
					'::LOGIC' => 'OR',
					'::SUBFILTER-R' => ['RESPONSIBLE_ID' => $userId],
					'::SUBFILTER-A' => ['=ACCOMPLICE' => $userId],
				];
				break;
		}

		return $filter;
	}

	private function processFilterSearchIndex(array $filter): array
	{
		if (!array_key_exists('SEARCH_INDEX', $filter))
		{
			return $filter;
		}

		$searchValue = SearchIndex::prepareStringToSearch($filter['SEARCH_INDEX']);
		if ($searchValue !== '')
		{
			$filter['::SUBFILTER-FULL_SEARCH_INDEX']['*FULL_SEARCH_INDEX'] = $searchValue;
		}

		return $filter;
	}

	private function processFilterWithoutDeadline(array $filter): array
	{
		if (array_key_exists('WO_DEADLINE', $filter) && $filter['WO_DEADLINE'] === 'Y')
		{
			$filter['DEADLINE'] = '';
		}

		return $filter;
	}

	private function processCrmElements(array $fields): array
	{
		if (
			!array_key_exists('CRM', $fields)
			|| !Loader::includeModule('crm')
		)
		{
			return $fields;
		}

		$crmUfCode = CRM\UserField::getMainSysUFCode();
		if (!is_array($fields[$crmUfCode] ?? null))
		{
			$fields[$crmUfCode] = [];
		}

		if (!is_array($fields['CRM']) || empty($fields['CRM']))
		{
			return $fields;
		}

		foreach ($fields['CRM'] as $item)
		{
			$entityTypeName = $item['type'];
			$entityId = $item['id'];

			if ($entityTypeName === DynamicMultipleProvider::DYNAMIC_MULTIPLE_ID)
			{
				[$entityTypeId, $entityId] = DynamicMultipleProvider::parseId($entityId);
				$entityTypeAbbr = CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);
			}
			else
			{
				$entityTypeAbbr = CCrmOwnerTypeAbbr::ResolveByTypeName($entityTypeName);
			}

			if ($entityTypeAbbr)
			{
				$fields[$crmUfCode][] = "{$entityTypeAbbr}_{$entityId}";
			}
		}

		return $fields;
	}

	private function processFiles(int $taskId = 0, array $fields = []): array
	{
		$filesUfCode = Disk\UserField::getMainSysUFCode();
		if (array_key_exists($filesUfCode, $fields) && $fields[$filesUfCode] === '')
		{
			$fields[$filesUfCode] = [''];
		}

		if (
			!array_key_exists('UPLOADED_FILES', $fields)
			|| !is_array($fields['UPLOADED_FILES'])
			|| empty($fields['UPLOADED_FILES'])
		)
		{
			return $fields;
		}

		if (!is_array($fields[$filesUfCode]))
		{
			$fields[$filesUfCode] = [];
		}

		$controller = new TaskController(['taskId' => $taskId]);
		$uploader = new Uploader($controller);
		$pendingFiles = $uploader->getPendingFiles($fields['UPLOADED_FILES']);

		foreach ($pendingFiles->getFileIds() as $fileId)
		{
			$addingResult = Disk::addFile($fileId);
			if ($addingResult->isSuccess())
			{
				$fields[$filesUfCode][] = $addingResult->getData()['ATTACHMENT_ID'];
			}
		}
		$pendingFiles->makePersistent();

		return $fields;
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function fillUserInfo(array $rows): array
	{
		static $users = [];

		$userIds = [];
		foreach ($rows as $row)
		{
			if (
				array_key_exists('CREATED_BY', $row)
				&& !array_key_exists($row['CREATED_BY'], $users)
			)
			{
				$userIds[] = (int)$row['CREATED_BY'];
			}
			if (
				array_key_exists('RESPONSIBLE_ID', $row)
				&& !array_key_exists($row['RESPONSIBLE_ID'], $users)
			)
			{
				$userIds[] = (int)$row['RESPONSIBLE_ID'];
			}
			if (array_key_exists('ACCOMPLICES', $row) && is_array($row['ACCOMPLICES']))
			{
				foreach ($row['ACCOMPLICES'] as $userId)
				{
					$userId = (int)$userId;
					if (!array_key_exists($userId, $users))
					{
						$userIds[] = $userId;
					}
				}
			}
			if (array_key_exists('AUDITORS', $row) && is_array($row['AUDITORS']))
			{
				foreach ($row['AUDITORS'] as $userId)
				{
					$userId = (int)$userId;
					if (!array_key_exists($userId, $users))
					{
						$userIds[] = $userId;
					}
				}
			}
		}
		$userIds = array_unique($userIds);

		if (!empty($userIds))
		{
			$userResult = UserTable::getList([
				'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO', 'WORK_POSITION'],
				'filter' => ['ID' => $userIds],
			]);
			while ($user = $userResult->fetch())
			{
				$userId = $user['ID'];
				$userName = CUser::FormatName(
					CSite::GetNameFormat(),
					[
						'LOGIN' => $user['LOGIN'],
						'NAME' => $user['NAME'],
						'LAST_NAME' => $user['LAST_NAME'],
						'SECOND_NAME' => $user['SECOND_NAME'],
					],
					true,
					false
				);
				$replaceList = ['user_id' => $userId];
				$link = CComponentEngine::makePathFromTemplate('/company/personal/user/#user_id#/', $replaceList);

				$users[$userId] = [
					'ID' => $userId,
					'NAME' => $userName,
					'LINK' => $link,
					'ICON' => UI\Avatar::getPerson($user['PERSONAL_PHOTO']),
					'WORK_POSITION' => $user['WORK_POSITION'],
				];
			}
		}

		foreach ($rows as $id => $row)
		{
			if (array_key_exists('CREATED_BY', $row) && array_key_exists($row['CREATED_BY'], $users))
			{
				$rows[$id]['CREATOR'] = $users[$row['CREATED_BY']];
			}
			if (array_key_exists('RESPONSIBLE_ID', $row) && array_key_exists($row['RESPONSIBLE_ID'], $users))
			{
				$rows[$id]['RESPONSIBLE'] = $users[$row['RESPONSIBLE_ID']];
			}
			if (array_key_exists('ACCOMPLICES', $row) && is_array($row['ACCOMPLICES']))
			{
				$accomplicesData = [];
				foreach ($row['ACCOMPLICES'] as $userId)
				{
					if (array_key_exists($userId, $users))
					{
						$accomplicesData[$userId] = $users[$userId];
					}
				}
				$rows[$id]['ACCOMPLICES_DATA'] = $accomplicesData;
			}
			if (array_key_exists('AUDITORS', $row) && is_array($row['AUDITORS']))
			{
				$auditorsData = [];
				foreach ($row['AUDITORS'] as $userId)
				{
					if (array_key_exists($userId, $users))
					{
						$auditorsData[$userId] = $users[$userId];
					}
				}
				$rows[$id]['AUDITORS_DATA'] = $auditorsData;
			}
		}

		return $rows;
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws LoaderException
	 */
	private function fillGroupInfo(array $rows, array $params = []): array
	{
		static $groups = [];

		$groupIds = [];
		foreach ($rows as $id => $row)
		{
			if (
				array_key_exists('GROUP_ID', $row)
				&& !array_key_exists($row['GROUP_ID'], $groups)
			)
			{
				$groupIds[] = (int)$row['GROUP_ID'];
			}
			$rows[$id]['GROUP'] = [];
		}
		$groupIds = array_unique($groupIds);

		$params['CURRENT_USER_ID'] = (int)$this->getCurrentUser()->getId();

		$select = [
			'IMAGE_ID',
			'OPENED',
			'NUMBER_OF_MEMBERS',
			'AVATAR_TYPE',
		];
		$groupsData = SocialNetwork\Group::getData($groupIds, $select, $params);

		$avatarTypes = (Loader::includeModule('socialnetwork') ? Workgroup::getAvatarTypes() : []);

		$groupsData = array_map(
			static function ($group) use ($avatarTypes) {

				$imageUrl = '';
				if (
					(int)$group['IMAGE_ID'] > 0
					&& is_array($file = CFile::GetFileArray($group['IMAGE_ID']))
				)
				{
					$imageUrl = $file['SRC'];
				}
				elseif (
					!empty($group['AVATAR_TYPE'])
					&& isset($avatarTypes[$group['AVATAR_TYPE']])
				)
				{
					$imageUrl = $avatarTypes[$group['AVATAR_TYPE']]['mobileUrl'];
				}

				return [
					'ID' => $group['ID'],
					'NAME' => $group['NAME'],
					'OPENED' => ($group['OPENED'] === 'Y'),
					'MEMBERS_COUNT' => (int)$group['NUMBER_OF_MEMBERS'],
					'IMAGE' => $imageUrl,
					'ADDITIONAL_DATA' => ($group['ADDITIONAL_DATA'] ?? []),
				];
			},
			$groupsData
		);
		foreach ($groupsData as $id => $data)
		{
			$groups[$id] = $data;
		}

		foreach ($rows as $id => $row)
		{
			if (array_key_exists('GROUP_ID', $row) && array_key_exists($row['GROUP_ID'], $groups))
			{
				$rows[$id]['GROUP'] = $groups[$row['GROUP_ID']];
			}
		}

		return $rows;
	}

	private function fillCounterInfo(array $tasks): array
	{
		$counter = new TaskCounter($this->getCurrentUser()->getId());

		foreach ($tasks as $id => $task)
		{
			$tasks[$id]['COUNTER'] = $counter->getMobileRowCounter($task['ID']);
		}

		return $tasks;
	}

	private function fillWithTimerInfo(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		$timerManager = CTaskTimerManager::getInstance($this->getCurrentUser()->getId());
		$runningTaskData = $timerManager->getRunningTask(false);
		foreach ($tasks as $id => $task)
		{
			$tasks[$id]['TIME_ELAPSED'] = $task['TIME_SPENT_IN_LOGS'];
			$tasks[$id]['TIMER_IS_RUNNING_FOR_CURRENT_USER'] = 'N';

			if (
				is_array($runningTaskData)
				&& (int)$task['ID'] === (int)$runningTaskData['TASK_ID']
				&& $task['ALLOW_TIME_TRACKING'] === 'Y'
			)
			{
				// elapsed time is a sum of times in task log plus time of the current timer
				$tasks[$id]['TIME_ELAPSED'] += (time() - $runningTaskData['TIMER_STARTED_AT']);
				$tasks[$id]['TIME_ELAPSED'] = (string)$tasks[$id]['TIME_ELAPSED'];
				$tasks[$id]['TIMER_IS_RUNNING_FOR_CURRENT_USER'] = 'Y';
			}
		}

		return $tasks;
	}

	/**
	 * @throws TasksException
	 */
	private function fillWithRelatedTasks(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		foreach ($tasks as $id => $task)
		{
			$tasks[$id]['RELATED_TASKS'] = [];

			$relatedTaskIds = [];
			$relatedTaskIdsResult = CTaskDependence::getList([], ['TASK_ID' => $task['ID']]);
			while ($task = $relatedTaskIdsResult->fetch())
			{
				$relatedTaskIds[] = (int)$task['DEPENDS_ON_ID'];
			}
			if (!empty($relatedTaskIds))
			{
				$relatedTasks = CTasks::GetList([], ['ID' => $relatedTaskIds], ['TITLE']);
				while ($task = $relatedTasks->Fetch())
				{
					$tasks[$id]['RELATED_TASKS'][$task['ID']] = $task['TITLE'];
				}
			}
		}

		return $tasks;
	}

	/**
	 * @throws TasksException
	 */
	private function fillWithSubTasks(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		foreach ($tasks as $id => $task)
		{
			$tasks[$id]['SUB_TASKS'] = [];

			$subTasks = CTasks::GetList([], ['PARENT_ID' => $task['ID']], ['TITLE']);
			while ($task = $subTasks->Fetch())
			{
				$tasks[$id]['SUB_TASKS'][$task['ID']] = $task['TITLE'];
			}
		}

		return $tasks;
	}

	private function fillWithParentTaskInfo(array $tasks): array
	{
		// todo: load all parent tasks with TaskRegistry::getInstance()->load first in case of calling this method from listAction

		foreach ($tasks as $id => $task)
		{
			$tasks[$id]['PARENT_TASK'] = [
				'ID' => 0,
				'TITLE' => '',
			];

			if ($task['PARENT_ID'] > 0)
			{
				$tasks[$id]['PARENT_TASK'] = [
					'ID' => $task['PARENT_ID'],
					'TITLE' => TaskRegistry::getInstance()->get($task['PARENT_ID'])['TITLE'],
				];
			}
		}

		return $tasks;
	}

	private function fillWithTags(array $tasks): array
	{
		$taskIds = [];
		foreach ($tasks as $id => $task)
		{
			$tasks[$id]['TAGS'] = [];
			$taskIds[] = (int)$task['ID'];
		}

		if (empty($taskIds))
		{
			return $tasks;
		}

		$tags = [];
		$tagsResult = CTaskTags::GetList([], ['TASK_ID' => $taskIds]);
		while ($tag = $tagsResult->fetch())
		{
			$tags[$tag['TASK_ID']][$tag['ID']] = [
				'ID' => $tag['ID'],
				'TITLE' => $tag['NAME'],
			];
		}

		foreach ($tasks as $id => $task)
		{
			if (array_key_exists($task['ID'], $tags))
			{
				$tasks[$id]['TAGS'] = $tags[$task['ID']];
			}
		}

		return $tasks;
	}

	private function fillWithFilesInfo(array $tasks): array
	{
		$fileIds = [];
		foreach ($tasks as $id => $task)
		{
			$tasks[$id]['FILES'] = [];
			$fileId = $task[Disk\UserField::getMainSysUFCode()] ?? [];
			if ($fileId !== false)
			{
				$fileIds[] = $fileId;
			}
		}
		$fileIds = array_merge(...$fileIds);
		$fileIds = array_unique($fileIds);

		if (empty($fileIds))
		{
			return $tasks;
		}

		$attachmentsData = Disk::getAttachmentData($fileIds);
		foreach ($tasks as $id => $task)
		{
			foreach ($task[Disk\UserField::getMainSysUFCode()] as $fileId)
			{
				if ($attachmentsData[$fileId])
				{
					$tasks[$id]['FILES'][] = $attachmentsData[$fileId];
				}
			}
		}

		return $tasks;
	}

	/**
	 * @throws LoaderException
	 */
	private function fillWithCrmInfo(array $tasks): array
	{
		if (!Loader::includeModule('crm'))
		{
			return $tasks;
		}

		$ufCrmTaskCode = CRM\UserField::getMainSysUFCode();
		$ufCrmTask = CRM\UserField::getSysUFScheme()[$ufCrmTaskCode];
		$displayField = Field::createByType('crm', $ufCrmTaskCode)
			->setIsMultiple($ufCrmTask['MULTIPLE'] === 'Y')
			->setIsUserField(true)
			->setUserFieldParams($ufCrmTask)
			->setContext(Field::MOBILE_CONTEXT)
		;
		$display = new Display(0, [$ufCrmTaskCode => $displayField]);

		foreach ($tasks as $id => $task)
		{
			$tasks[$id]['CRM'] = [];

			if (
				empty($task[$ufCrmTaskCode])
				|| !is_array($task[$ufCrmTaskCode])
			)
			{
				continue;
			}

			$items = CRM\Fields\Collection::createFromArray($task[$ufCrmTaskCode])->filter();
			$res = $display
				->setItems([[$ufCrmTaskCode => $items->toArray()]])
				->getValues(0)
			;

			if (
				!is_array($res[$ufCrmTaskCode]['config']['entityList'])
				|| count($res[$ufCrmTaskCode]['config']['entityList']) !== $items->count()
			)
			{
				continue;
			}

			$tasks[$id]['CRM'] = array_combine($items->toArray(), $res[$ufCrmTaskCode]['config']['entityList']);
		}

		return $tasks;
	}

	private function fillWithParsedDescription(array $tasks): array
	{
		if ($textFragmentParserClass = TasksMobile\TextFragmentParser::getTextFragmentParserClass())
		{
			$textFragmentParser = new $textFragmentParserClass();

			foreach ($tasks as $id => $task)
			{
				if (!$task['DESCRIPTION'])
				{
					$tasks[$id]['PARSED_DESCRIPTION'] = '';
					continue;
				}

				$textFragmentParser->setText($task['DESCRIPTION']);
				$textFragmentParser->setFiles(isset($task['FILES']) && $task['FILES'] ? $task['FILES'] : []);

				$tasks[$id]['PARSED_DESCRIPTION'] = htmlspecialchars_decode($textFragmentParser->getParsedText(), ENT_QUOTES);
			}
		}

		return $tasks;
	}

	private function fillTreeInfo(CheckList $tree): CheckList
	{
		$completedCount = 0;

		foreach ($tree->getDescendants() as $descendant)
		{
			/** @var CheckList $descendant */
			$fields = $descendant->getFields();

			if ($fields['IS_COMPLETE'])
			{
				$completedCount++;
			}

			$this->fillTreeInfo($descendant);
		}

		$tree->setFields(['COMPLETED_COUNT' => $completedCount]);

		return $tree;
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function fillResultInfo(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		$taskIds = [];
		foreach ($tasks as $key => $task)
		{
			$taskIds[(int)$task['ID']] = $key;

			$tasks[$key]['TASK_REQUIRE_RESULT'] = 'N';
			$tasks[$key]['TASK_HAS_OPEN_RESULT'] = 'N';
			$tasks[$key]['TASK_HAS_RESULT'] = 'N';
		}

		$query = (new Main\ORM\Query\Query(ResultTable::getEntity()))
			->addSelect('TASK_ID')
			->addSelect(new Main\Entity\ExpressionField(
				'RES_ID',
				'MAX(%s)',
				'ID'
			))
			->whereIn('TASK_ID', array_keys($taskIds))
			->addGroup('TASK_ID');

		$lastResults = $query->fetchAll();

		if (!empty($lastResults))
		{
			$lastResults = array_column($lastResults, 'RES_ID');
			$results = ResultTable::GetList([
				'select' => ['TASK_ID', 'STATUS'],
				'filter' => [
					'@ID' => $lastResults,
				],
			])->fetchAll();

			foreach ($results as $row)
			{
				$taskId = $row['TASK_ID'];
				$tasks[$taskIds[$taskId]]['TASK_HAS_RESULT'] = 'Y';

				if ((int)$row['STATUS'] === ResultTable::STATUS_OPENED)
				{
					$tasks[$taskIds[$taskId]]['TASK_HAS_OPEN_RESULT'] = 'Y';
				}
			}
		}

		$requireResults = ParameterTable::getList([
			'select' => ['TASK_ID'],
			'filter' => [
				'@TASK_ID' => array_keys($taskIds),
				'=CODE' => ParameterTable::PARAM_RESULT_REQUIRED,
				'=VALUE' => 'Y',
			],
		])->fetchAll();

		foreach ($requireResults as $row)
		{
			$taskId = $row['TASK_ID'];
			$tasks[$taskIds[$taskId]]['TASK_REQUIRE_RESULT'] = 'Y';
		}

		return $tasks;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function fillActionsForCheckListItems($taskId, array $checkListItems, bool $canAdd): array
	{
		$taskObserversParticipantsEnabled = Bitrix24::checkFeatureEnabled(
			Bitrix24\FeatureDictionary::TASK_OBSERVERS_PARTICIPANTS
		);

		$canAddAccomplice = (
			TaskAccessController::can(
				$this->getCurrentUser()->getId(),
				ActionDictionary::ACTION_TASK_EDIT,
				$taskId
			)
			&& $taskObserversParticipantsEnabled
		);

		$checkListItems = TaskCheckListFacade::fillActionsForItems(
			$taskId,
			$this->getCurrentUser()->getId(),
			$checkListItems
		);

		foreach ($checkListItems as $id => $item)
		{
			if (array_key_exists('ACTION', $item))
			{
				$checkListItems[$id]['ACTION']['ADD'] = $canAdd;
				$checkListItems[$id]['ACTION']['ADD_ACCOMPLICE'] = $canAddAccomplice;
			}
		}

		return $checkListItems;
	}

	private function prepareSelect(array $select): array
	{
		$validKeys = array_keys(CTasks::getFieldsInfo($this->isUfExist($select)));

		$select = (!empty($select) && !in_array('*', $select, true) ? $select : $validKeys);
		$select = array_intersect($select, $validKeys);

		if (in_array('STATUS', $select, true))
		{
			$select[] = 'REAL_STATUS';
		}

		return $select;
	}

	/**
	 * Parses source filter.
	 */
	private function prepareFilter(array $filter, array $select, array $params): array
	{
		$filter = (!empty($filter) ? $filter : []);

		$userId = ($filter['MEMBER'] ?? $this->getCurrentUser()->getId());
		$roleId = (array_key_exists('ROLE', $filter) ? $filter['ROLE'] : '');

		$filter = $this->processFilterSearchIndex($filter);
		$filter = $this->processFilterWithoutDeadline($filter);
		$filter = $this->processFilterNotViewed($filter, $userId, $roleId);
		$filter = $this->processFilterRoleId($filter, $userId, $roleId);

		$getUf = ($this->isUfExist($select) || $this->isUfExist(array_keys($filter)));
		$dateFields = $this->getDateFields($getUf);

		foreach ($filter as $fieldName => $fieldData)
		{
			preg_match('#(\w+)#', $fieldName, $m);
			if (array_key_exists($m[1], $dateFields) && $fieldData)
			{
				//compatibility..
				$date = is_string($fieldData) ? strtotime($fieldData) : false;
				$filter[$fieldName] = DateTime::createFromTimestamp($date);
			}
		}

		if (isset($params['SIFT_THROUGH_FILTER']))
		{
			/** @var Filter $filterInstance */
			$isSprintKanban = (($params['SIFT_THROUGH_FILTER']['sprintKanban'] ?? null) === 'Y');
			if ($isSprintKanban)
			{
				$taskService = new TaskService($params['SIFT_THROUGH_FILTER']['userId']);
				$filterInstance = $taskService->getFilterInstance(
					$params['SIFT_THROUGH_FILTER']['groupId'],
					($params['SIFT_THROUGH_FILTER']['isCompletedSprint'] === 'Y' ? 'complete' : 'active')
				);
			}
			else
			{
				$filterInstance = Filter::getInstance(
					$params['SIFT_THROUGH_FILTER']['userId'],
					$params['SIFT_THROUGH_FILTER']['groupId']
				);
				if ($presetId = ($params['SIFT_THROUGH_FILTER']['presetId'] ?? ''))
				{
					$filterValues = [];
					if (array_key_exists($presetId, $filterInstance->getAllPresets()))
					{
						$filterOptions = $filterInstance->getOptions();
						$filterSettings = (
							$filterOptions->getFilterSettings($presetId)
							?? $filterOptions->getDefaultPresets()[$presetId]
						);
						$sourceFields = $filterInstance->getFilters();
						$filterValues = Options::fetchFieldValuesFromFilterSettings($filterSettings, [], $sourceFields);
					}
					$filterInstance->setFilterData($filterValues);
				}
			}

			$filter = array_merge($filter, $filterInstance->process());
			unset($filter['ONLY_ROOT_TASKS']);
		}

		return $filter;
	}

	private function prepareNavParams(PageNavigation $pageNavigation, array $params): array
	{
		$navParams = [
			'nPageSize' => $pageNavigation->getLimit(),
			'iNumPageSize' => $pageNavigation->getOffset(),
			'iNumPage' => $pageNavigation->getCurrentPage(),
			'getTotalCount' => true,
		];

		if (
			($getPlusOne = (isset($params['GET_PLUS_ONE']) && $params['GET_PLUS_ONE'] === 'Y'))
			|| (int)$this->getRequest()->get('start') === -1
		)
		{
			if ($getPlusOne)
			{
				$navParams['getPlusOne'] = true;
			}
			unset($navParams['getTotalCount']);
		}

		return $navParams;
	}

	/**
	 * Prepares date fields of ISO-8601 format for base suitable format
	 */
	private function formatDateFieldsForInput(array $fields): array
	{
		$getUf = $this->isUfExist(array_keys($fields));

		foreach ($this->getDateFields($getUf) as $fieldName => $fieldData)
		{
			if (
				isset($fields[$fieldName])
				&& ($date = $fields[$fieldName])
			)
			{
				//compatibility..
				$timestamp = is_string($date) ? strtotime($date) : false;
				if ($timestamp !== false)
				{
					$timestamp += CTimeZone::GetOffset() - DateTime::createFromTimestamp($timestamp)->getSecondGmt();
					$fields[$fieldName] = ConvertTimeStamp($timestamp, 'FULL');
				}
			}
		}

		return $fields;
	}

	/**
	 * Prepares date fields for output in ISO-8610 format
	 *
	 * @throws Main\ObjectException
	 */
	private function formatDateFieldsForOutput(&$row): void
	{
		static $dateFields;

		if (!$dateFields)
		{
			$dateFields = $this->getDateFields($this->isUfExist(array_keys($row)));
		}

		$localOffset = (new \DateTime())->getOffset();
		$userOffset =  CTimeZone::GetOffset(null, true);
		$offset = $localOffset + $userOffset;
		$newOffset = ($offset >= 0 ? '+' : '').UI::formatTimeAmount($offset, 'HH:MI');

		foreach ($dateFields as $fieldName => $fieldData)
		{
			if (
				isset($row[$fieldName])
				&& ($field = $row[$fieldName])
			)
			{
				if (is_array($field))
				{
					foreach ($field as $key => $value)
					{
						if ($date = new DateTime($value))
						{
							$row[$fieldName][$key] = mb_substr($date->format('c'), 0, -6).$newOffset;
						}
					}
				}
				else if ($date = new DateTime($field))
				{
					$row[$fieldName] = mb_substr($date->format('c'), 0, -6).$newOffset;
				}
			}
		}
	}

	private function translateAllowedActionNames($can): array
	{
		$newCan = [];

		if (is_array($can))
		{
			foreach ($can as $act => $flag)
			{
				$newCan[str_replace('ACTION_', '', $act)] = $flag;
			}

			$withDropFrom = [
				'CHANGE_DIRECTOR' => 'EDIT.ORIGINATOR',
				'CHECKLIST_REORDER_ITEMS' => 'CHECKLIST.REORDER',
				'ELAPSED_TIME_ADD' => 'ELAPSEDTIME.ADD',
				'START_TIME_TRACKING' => 'DAYPLAN.TIMER.TOGGLE',
			];
			foreach ($withDropFrom as $from => $to)
			{
				$this->replaceKey($newCan, $from, $to);
			}

			$withoutDropFrom = [
				'CHANGE_DEADLINE' => 'EDIT.PLAN',
				'CHECKLIST_ADD_ITEMS' => 'CHECKLIST.ADD',
				'ADD_FAVORITE' => 'FAVORITE.ADD',
				'DELETE_FAVORITE' => 'FAVORITE.DELETE',
			];
			foreach ($withoutDropFrom as $from => $to)
			{
				// todo: when mobile stops using this fields, remove the last argument here
				$this->replaceKey($newCan, $from, $to, false);
			}
		}

		return $newCan;
	}

	private function replaceKey(array &$data, string $from, string $to, bool $dropFrom = true): void
	{
		if (array_key_exists($from, $data))
		{
			$data[$to] = $data[$from];
			if ($dropFrom)
			{
				unset($data[$from]);
			}
		}
	}

	/**
	 * Returns fields of type datetime for task entity
	 */
	private function getDateFields($getUf = true): array
	{
		return array_filter(
			CTasks::getFieldsInfo($getUf),
			static function ($item) {
				return ($item['type'] === 'datetime' ? $item : null);
			}
		);
	}

	private function checkOrderKeys($order): bool
	{
		$orderKeys = array_keys(array_change_key_case($order, CASE_UPPER));
		$availableKeys = CTasks::getAvailableOrderFields();

		return empty(array_diff($orderKeys, $availableKeys));
	}

	private function checkSelect(array $select): bool
	{
		foreach ($select as $key)
		{
			if (!is_string($key))
			{
				return false;
			}
		}

		return true;
	}

	private function isUfExist(array $fields): bool
	{
		foreach ($fields as $field)
		{
			if (mb_strpos($field, 'UF_') === 0)
			{
				return true;
			}
		}
		return false;
	}

	private function pingStatus(array $taskData): bool
	{
		$taskId = (int)$taskData['ID'];
		$userId = $this->getCurrentUser()->getId();

		$commentPoster = CommentPoster::getInstance($taskId, $userId);
		$commentPoster && $commentPoster->postCommentsOnTaskStatusPinged($taskData);

		CTaskNotifications::sendPingStatusMessage($taskData, $userId);

		return true;
	}

	/**
	 * Start an execution timer for a specified task
	 *
	 * @throws TasksException
	 */
	private function startTimer(CTaskItem $task, bool $stopPrevious = false): ?bool
	{
		$userId = $this->getCurrentUser()->getId();

		$timer = CTaskTimerManager::getInstance($userId);
		$lastTimer = $timer->getLastTimer();
		$lastTimerTaskId = (int)$lastTimer['TASK_ID'];

		if (
			!$stopPrevious
			&& $lastTimerTaskId
			&& $lastTimer['TIMER_STARTED_AT'] > 0
			&& $lastTimerTaskId !== $task->getId()
		)
		{
			// use direct query here, avoiding cached CTaskItem::getData(), because $lastTimerTaskId unlikely will be in cache
			[$tasks,] = CTaskItem::fetchList($userId, [], ['ID' => $lastTimerTaskId], [], ['ID', 'TITLE']);
			if (is_array($tasks) && !empty($tasks))
			{
				$task = array_shift($tasks);
				if ($task)
				{
					$data = $task->getData(false);
					$replace = ['ID' => $data['ID'], 'TITLE' => $data['TITLE']];
					$this->addError(new Error(Main\Localization\Loc::getMessage('TASKS_OTHER_TASK_ON_TIMER', $replace)));
				}
			}

			return null;
		}

		if ($timer->start($task->getId()) === false)
		{
			$this->addError(new Error(Main\Localization\Loc::getMessage('TASKS_FAILED_START_TASK_TIMER')));
			return null;
		}

		return true;
	}

	/**
	 * Stop an execution timer for a specified task
	 */
	private function stopTimer(CTaskItem $task): ?bool
	{
		$timer = CTaskTimerManager::getInstance($this->getCurrentUser()->getId());
		if ($timer->stop($task->getId()) === false)
		{
			$this->addError(new Error(Main\Localization\Loc::getMessage('TASKS_FAILED_STOP_TASK_TIMER')));
			return null;
		}

		return true;
	}
}
