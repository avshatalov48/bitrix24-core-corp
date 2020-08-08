<?php

namespace Bitrix\Tasks\Rest\Controllers;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\AnalyticLogger;
use Bitrix\Tasks\Comments;
use Bitrix\Tasks\Exception;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Tasks\Util\Type\DateTime;
use TasksException;

final class Task extends Base
{
	public function configureActions()
	{
		return [
			'search' => [
				'class' => Action\SearchAction::class,
				'+prefilters' => [new CloseSession()]
			]
		];
	}

	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(
			\CTaskItem::class, 'task', function ($className, $id) {
			$userId = CurrentUser::get()->getId();

			return new $className($id, $userId);
		}
		);
	}

	/**
	 * Return all DB and UF_ fields of task
	 *
	 * @return array
	 */
	public function getFieldsAction()
	{
		return ['fields' => \CTasks::getFieldsInfo()];
	}

	/**
	 * Create new task
	 *
	 * @param array $fields See in tasks.api.task.fields
	 * @param array $params
	 *
	 * @return array
	 * @throws TasksException
	 * @throws \CTaskAssertException
	 * @throws \Exception
	 */
	public function addAction(array $fields, array $params = []): array
	{
		$fields = $this->formatDateFieldsForInput($fields);
		$task = \CTaskItem::add($fields, $this->getCurrentUser()->getId(), $params);

        if ($params['PLATFORM'] === 'mobile')
		{
			AnalyticLogger::logToFile('addTask');
		}

		return $this->getAction($task);
	}

	private function formatUserInfo(&$row)
	{
		if (array_key_exists('CREATED_BY', $row))
		{
			try
			{
				$row['CREATOR'] = self::getUserInfo($row['CREATED_BY']);
			}
			catch (\Exception $e)
			{
				$row['CREATOR']['ID'] = $row['CREATED_BY'];
			}
		}

		if (array_key_exists('RESPONSIBLE_ID', $row))
		{
			try
			{
				$row['RESPONSIBLE'] = self::getUserInfo($row['RESPONSIBLE_ID']);
			}
			catch (\Exception $e)
			{
				$row['RESPONSIBLE']['ID'] = $row['RESPONSIBLE_ID'];
			}
		}
	}

	private function formatGroupInfo(&$row)
	{
		if (array_key_exists('GROUP_ID', $row))
		{
			try
			{
				$row['GROUP'] = self::getGroupInfo($row['GROUP_ID']);
			}
			catch (\Exception $e)
			{
				$row['GROUP']['ID'] = $row['GROUP_ID'];
			}
		}
	}

	/**
	 * Get task item data
	 *
	 * @param \CTaskItem $task
	 * @param array $select
	 * @param array $params
	 *
	 * @return array|null
	 */
	public function getAction(\CTaskItem $task, array $select = [], array $params = [])
	{
	    if (!empty($select))
        {
            $select[] = 'FAVORITE';
        }

		$params['select'] = $this->prepareSelect($select);
		$row = $task->getData(false, $params);

		if (array_key_exists('STATUS', $row))
		{
			$row['STATUS'] = $row['REAL_STATUS'];
			unset($row['REAL_STATUS']);
		}

		$this->formatGroupInfo($row);
		$this->formatUserInfo($row);
		$this->formatDateFieldsForOutput($row);

		if (in_array('NEW_COMMENTS_COUNT', $params['select'], true))
		{
			$taskId = $task->getId();
			$userId = $this->getCurrentUser()->getId();

			$newComments = Comments\Task::getNewCommentsCountForTasks([$taskId], $userId);
			$row['NEW_COMMENTS_COUNT'] = $newComments[$taskId];
		}

		$action = $this->getAccessAction($task);
		$row['action'] = $action['allowedActions'][$this->getCurrentUser()->getId()];

		if (isset($params['GET_TASK_LIMIT_EXCEEDED']) && $params['GET_TASK_LIMIT_EXCEEDED'])
		{
			$row['TASK_LIMIT_EXCEEDED'] = TaskLimit::isLimitExceeded();
		}

		return ['task' => $this->convertKeysToCamelCase($row)];
	}

	private function prepareSelect(array $select)
	{
		$select = !empty($select) && !in_array('*', $select) ? $select : array_keys(\CTasks::getFieldsInfo());
		$select = array_intersect($select, array_keys(\CTasks::getFieldsInfo()));

		if (in_array('STATUS', $select)) // [1]
		{
			$select[] = 'REAL_STATUS';
		}

		return $select;
	}

	/**
	 * Returns fields of type datetime for task entity
	 *
	 * @return array
	 */
	private function getDateFields(): array
	{
		return array_filter(
			\CTasks::getFieldsInfo(),
			static function ($item)
			{
				if ($item['type'] === 'datetime')
				{
					return $item;
				}

				return null;
			}
		);
	}

	/**
	 * Prepares date fields of ISO-8601 format for base suitable format
	 *
	 * @param $fields
	 * @return array
	 */
	private function formatDateFieldsForInput(array $fields): array
	{
		foreach ($this->getDateFields() as $fieldName => $fieldData)
		{
			$date = $fields[$fieldName];
			if ($date)
			{
				$timestamp = strtotime($date);
				if ($timestamp !== false)
				{
					$timestamp += \CTimeZone::GetOffset() - DateTime::createFromTimestamp($timestamp)->getSecondGmt();
					$fields[$fieldName] = ConvertTimeStamp($timestamp, 'FULL');
				}
			}
		}

		return $fields;
	}

	/**
	 * Prepares date fields for output in ISO-8610 format
	 *
	 * @param $row
	 * @throws \Exception
	 */
	private function formatDateFieldsForOutput(&$row): void
	{
		static $dateFields;

		if (!$dateFields)
		{
			$dateFields = $this->getDateFields();
		}

		$localOffset = (new \DateTime())->getOffset();
		$userOffset =  \CTimeZone::GetOffset(null, true);
		$offset = $localOffset + $userOffset;

		foreach ($dateFields as $fieldName => $fieldData)
		{
			if ($row[$fieldName])
			{
				$date = new DateTime($row[$fieldName]);
				if ($date)
				{
					$newOffset = ($offset > 0 ? '+' : '').UI::formatTimeAmount($offset, 'HH:MI');
					$row[$fieldName] = mb_substr($date->format('c'), 0, -6).$newOffset;
				}
			}
		}
	}

	/**
	 * Return access data to task for current user
	 *
	 * @param \CTaskItem $task
	 * @param array $users
	 * @param array $params
	 *
	 * @return array
	 */
	public function getAccessAction(\CTaskItem $task, array $users = array(), array $params = array())
	{
		if (empty($users))
		{
			$users[] = $this->getCurrentUser()->getId();
		}

		$returnAsString = !array_key_exists('AS_STRING', $params) ||
                            (array_key_exists('AS_STRING', $params) && $params['AS_STRING'] != 'N');

		$list = [];
		foreach ($users as $userId)
		{
			$list[$userId] = static::translateAllowedActionNames(
				\CTaskItem::getAllowedActionsArray($userId, $task->getData(false), $returnAsString)
			);
		}

		return ['allowedActions' => $list];
	}

	private static function translateAllowedActionNames($can)
	{
		$newCan = array();
		if (is_array($can))
		{
			foreach ($can as $act => $flag)
			{
				$newCan[str_replace('ACTION_', '', $act)] = $flag;
			}

			static::replaceKey($newCan, 'CHANGE_DIRECTOR', 'EDIT.ORIGINATOR');
			static::replaceKey($newCan, 'CHECKLIST_REORDER_ITEMS', 'CHECKLIST.REORDER');
			static::replaceKey($newCan, 'ELAPSED_TIME_ADD', 'ELAPSEDTIME.ADD');
			static::replaceKey($newCan, 'START_TIME_TRACKING', 'DAYPLAN.TIMER.TOGGLE');

			// todo: when mobile stops using this fields, remove the third argument here
			static::replaceKey($newCan, 'CHANGE_DEADLINE', 'EDIT.PLAN', false); // used in mobile already
			static::replaceKey($newCan, 'CHECKLIST_ADD_ITEMS', 'CHECKLIST.ADD', false); // used in mobile already
			static::replaceKey($newCan, 'ADD_FAVORITE', 'FAVORITE.ADD', false); // used in mobile already
			static::replaceKey($newCan, 'DELETE_FAVORITE', 'FAVORITE.DELETE', false); // used in mobile already
		}

		return $newCan;
	}

	private static function replaceKey(array &$data, $from, $to, $dropFrom = true)
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
	 * Update existing task
	 *
	 * @param \CTaskItem $task
	 * @param array $fields See in tasks.api.task.fields
	 * @param array $params
	 *
	 * @return array
	 */
	public function updateAction(\CTaskItem $task, array $fields, array $params = array())
	{
		$fields = $this->formatDateFieldsForInput($fields);

		$task->update($fields, $params);
		\Bitrix\Pull\MobileCounter::send($this->getCurrentUser()->getId());

		return $this->getAction($task);
	}

	/**
	 * Remove existing task
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 *
	 * @return array
	 * @throws TasksException
	 */
	public function deleteAction(\CTaskItem $task, array $params = array())
	{
		$task->delete($params);

		return ['task' => true];
	}

	/**
	 * Get list all task
	 *
	 * @param PageNavigation $pageNavigation
	 * @param array $filter
	 * @param array $select
	 * @param array $group
	 * @param array $order
	 * @param array $params
	 * @return Response\DataType\Page
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Exception
	 */
	public function listAction(
		PageNavigation $pageNavigation,
		array $filter = [],
		array $select = [],
		array $group = [],
		array $order = [],
		array $params = []
	): ?Response\DataType\Page
	{
		if (!$this->checkOrderKeys($order))
        {
            $this->addError(new Error(GetMessage('TASKS_FAILED_WRONG_ORDER_FIELD')));
            return null;
        }

		$filter = $this->getFilter($filter);
		$dateFields = $this->getDateFields();

		foreach ($filter as $fieldName => $fieldData)
		{
			preg_match('#(\w+)#', $fieldName, $m);

			if (array_key_exists($m[1], $dateFields) && $filter[$fieldName])
			{
				$filter[$fieldName] = DateTime::createFromTimestamp(strtotime($filter[$fieldName]));
			}
		}

		if (isset($params['SIFT_THROUGH_FILTER']))
		{
			/** @var Filter $filterInstance */
			$filterInstance = Filter::getInstance(
				$params['SIFT_THROUGH_FILTER']['userId'],
				$params['SIFT_THROUGH_FILTER']['groupId']
			);
			$filter = array_merge($filter, $filterInstance->process());
			unset($filter['ONLY_ROOT_TASKS']);
		}

		$getListParams = [
			'limit' => $pageNavigation->getLimit(),
			'offset' => $pageNavigation->getOffset(),
			'page' => $pageNavigation->getCurrentPage(),
			'select' => $this->prepareSelect($select),
			'legacyFilter' => ($filter ?: []),
			'order' => ($order ?: []),
			'group' => ($group ?: []),
		];

		$params['PUBLIC_MODE'] = 'Y'; // VERY VERY BAD HACK! DONT REPEAT IT !
		$params['USE_MINIMAL_SELECT_LEGACY'] = 'N'; // VERY VERY BAD HACK! DONT REPEAT IT !
		$params['RETURN_ACCESS'] = ($params['RETURN_ACCESS'] ?? 'N'); // VERY VERY BAD HACK! DONT REPEAT IT !

		$result = Manager\Task::getList($this->getCurrentUser()->getId(), $getListParams, $params);
		$list = array_values($result['DATA']);

		foreach ($list as &$row)
		{
			if (array_key_exists('STATUS', $row))
			{
				$row['SUB_STATUS'] = $row['STATUS'];
				$row['STATUS'] = $row['REAL_STATUS'];
				unset($row['REAL_STATUS']);
			}

			$this->formatGroupInfo($row);
			$this->formatUserInfo($row);
			$this->formatDateFieldsForOutput($row);

			$row = $this->convertKeysToCamelCase($row);

			if (isset($params['SEND_PULL']) && $params['SEND_PULL'] !== 'N' && Loader::includeModule('pull'))
			{
				$users = array_unique(array_merge([$row['CREATED_BY']], [$row['RESPONSIBLE_ID']]));
				foreach ($users as $userId)
				{
					\CPullWatch::Add($userId, 'TASK_'.$row['ID']);
				}
			}
		}
		unset($row);

		return new Response\DataType\Page(
			'tasks',
			$list,
			function() use ($getListParams, $params, $result)
			{
				return $result['AUX']['OBJ_RES']->nSelectedCount;
			}
		);

	}

	/**
	 * @param $order
	 * @return bool
	 */
	private function checkOrderKeys($order): bool
    {
        $orderKeys = array_keys(array_change_key_case($order, CASE_UPPER));
        $availableKeys = \CTasks::getAvailableOrderFields();

        return empty(array_diff($orderKeys, $availableKeys));
    }

	/**
	 * Parses source filter.
	 *
	 * @param array $filter
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getFilter($filter)
	{
		$filter = (is_array($filter) && !empty($filter) ? $filter : []);
		$userId = ($filter['MEMBER'] ?? $this->getCurrentUser()->getId());
		$roleId = (array_key_exists('ROLE', $filter) ? $filter['ROLE'] : '');

		if (!empty($filter))
		{
			if (array_key_exists('SEARCH_INDEX', $filter))
			{
				$operator = (($isFullTextIndexEnabled = SearchIndexTable::isFullTextIndexEnabled())? '*' : '*%');
				$searchValue = SearchIndex::prepareStringToSearch($filter['SEARCH_INDEX'], $isFullTextIndexEnabled);

				$filter['::SUBFILTER-FULL_SEARCH_INDEX'][$operator . 'FULL_SEARCH_INDEX'] = $searchValue;
			}

			if (array_key_exists('WO_DEADLINE', $filter) && $filter['WO_DEADLINE'] === 'Y')
			{
				switch ($roleId)
				{
					case 'R':
						$filter['!CREATED_BY'] = $userId;
						break;

					case 'O':
						$filter['!RESPONSIBLE_ID'] = $userId;
						break;

					default:
						if (array_key_exists('GROUP_ID', $filter))
						{
							$filter['!REFERENCE:RESPONSIBLE_ID'] = 'CREATED_BY';
						}
						else
						{
							$filter['::SUBFILTER-OR'] = [
								'::LOGIC' => 'OR',
								'::SUBFILTER-R' => [
									'!CREATED_BY' => $userId,
									'RESPONSIBLE_ID' => $userId,
								],
								'::SUBFILTER-O' => [
									'CREATED_BY' => $userId,
									'!RESPONSIBLE_ID' => $userId,
								],
							];
						}
						break;
				}

				$filter['DEADLINE'] = '';
			}

			if (array_key_exists('NOT_VIEWED', $filter) && $filter['NOT_VIEWED'] === 'Y')
			{
				$filter['VIEWED'] = 0;
				$filter['VIEWED_BY'] = $userId;
				$filter['!CREATED_BY'] = $userId;

				switch ($roleId)
				{
					default:
					case '': // view all
						$filter['::SUBFILTER-OR-NW'] = [
							'::LOGIC' => 'OR',
							'::SUBFILTER-R' => [
								'RESPONSIBLE_ID' => $userId,
							],
							'::SUBFILTER-A' => [
								'=ACCOMPLICE' => $userId,
							],
						];
						break;

					case 'A':
						$filter['::SUBFILTER-R'] = [
							'=ACCOMPLICE' => $userId,
						];
						break;

					case 'U':
						$filter['::SUBFILTER-R'] = [
							'=AUDITOR' => $userId,
						];
						break;
				}
			}

			if (array_key_exists('STATUS', $filter))
			{
				$filter['REAL_STATUS'] = $filter['STATUS']; // hack for darkness times
				unset($filter['STATUS']);
			}
		}

		if ($roleId)
		{
			switch ($roleId)
			{
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
							'REAL_STATUS' => \CTasks::STATE_SUPPOSEDLY_COMPLETED,
						],
					];
					unset($filter['REAL_STATUS']);
					break;

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
			}

			unset($filter['ROLE']);
		}

		return $filter;
	}

	/**
	 * @param \CTaskItem $task
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function muteAction(\CTaskItem $task): ?array
	{
		UserOption::add($task->getId(), CurrentUser::get()->getId(), UserOption\Option::MUTED);
		return $this->getAction($task);
	}

	/**
	 * @param \CTaskItem $task
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function unmuteAction(\CTaskItem $task): ?array
	{
		UserOption::delete($task->getId(), CurrentUser::get()->getId(), UserOption\Option::MUTED);
		return $this->getAction($task);
	}

	/**
	 * @param \CTaskItem $task
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function pinAction(\CTaskItem $task): ?array
	{
		UserOption::add($task->getId(), CurrentUser::get()->getId(), UserOption\Option::PINNED);
		return $this->getAction($task);
	}

	/**
	 * @param \CTaskItem $task
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function unpinAction(\CTaskItem $task): ?array
	{
		UserOption::delete($task->getId(), CurrentUser::get()->getId(), UserOption\Option::PINNED);
		return $this->getAction($task);
	}

	/**
	 * Delegate task to another user
	 *
	 * @param \CTaskItem $task
	 * @param $userId
	 * @param array $params
	 *
	 * @return array
	 * @throws TasksException
	 */
	public function delegateAction(\CTaskItem $task, $userId, array $params = array())
	{
		$task->delegate($userId, $params);

		if ($params['PLATFORM'] === 'mobile')
		{
			AnalyticLogger::logToFile('delegateTask');
		}

		return $this->getAction($task);
	}

	/**
	 * Start execute task
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 *
	 * @return array
	 * @throws TasksException
	 */
	public function startAction(\CTaskItem $task, array $params = array())
	{
		$row = $task->getData(true);
		if ($row['ALLOW_TIME_TRACKING'] === 'Y')
		{
			if (!$this->startTimer($task, true))
			{
				return null;
			}
		}

		$task->startExecution($params);

		return $this->getAction($task);
	}

	/**
	 * Start an execution timer for a specified task
	 *
	 * @param \CTaskItem $task
	 * @param bool $stopPrevious
	 *
	 * @return bool|null
	 * @throws TasksException
	 */
	private function startTimer(\CTaskItem $task, $stopPrevious = false)
	{
		$timer = \CTaskTimerManager::getInstance($this->getCurrentUser()->getId());
		$lastTimer = $timer->getLastTimer();
		if (!$stopPrevious &&
			$lastTimer['TASK_ID'] &&
			$lastTimer['TIMER_STARTED_AT'] > 0 &&
			intval($lastTimer['TASK_ID']) &&
			$lastTimer['TASK_ID'] != $task->getId())
		{
			$additional = array();

			// use direct query here, avoiding cached CTaskItem::getData(), because $lastTimer['TASK_ID'] unlikely will be in cache
			[$tasks, $res] = \CTaskItem::fetchList(
				$this->getCurrentUser()->getId(),
				array(),
				array('ID' => intval($lastTimer['TASK_ID'])),
				array(),
				array('ID', 'TITLE')
			);
			if (is_array($tasks))
			{
				$_task = array_shift($tasks);
				if ($_task)
				{
					$data = $_task->getData(false);
					if (intval($data['ID']))
					{
						$additional['TASK'] = array(
							'ID'    => $data['ID'],
							'TITLE' => $data['TITLE']
						);
					}
				}
			}

			$this->addError(
				new Error(GetMessage('TASKS_OTHER_TASK_ON_TIMER', ['ID' => $data['ID'], 'TITLE' => $data['TITLE']]))
			);

			return null;
		}
		else
		{
			if ($timer->start($task->getId()) === false)
			{
				$this->addError(new Error(GetMessage('TASKS_FAILED_START_TASK_TIMER')));
			}
		}

		return true;
	}

	/**
	 * Stop execute task
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 *
	 * @return array
	 * @throws TasksException
	 */
	public function pauseAction(\CTaskItem $task, array $params = array())
	{
		$row = $task->getData(true);
		if ($row['ALLOW_TIME_TRACKING'] === 'Y')
		{
			if (!$this->stopTimer($task))
			{
				return null;
			}
		}

		$task->pauseExecution($params);

		return $this->getAction($task);
	}

	/**
	 * Stop an execution timer for a specified task
	 *
	 * @param \CTaskItem $task
	 *
	 * @return bool|null
	 */
	private function stopTimer(\CTaskItem $task)
	{
		$timer = \CTaskTimerManager::getInstance($this->getCurrentUser()->getId());
		if ($timer->stop($task->getId()) === false)
		{
			$this->addError(new Error(GetMessage('TASKS_FAILED_STOP_TASK_TIMER')));

			return null;
		}

		return true;
	}


	// internal functions

	/**
	 * Complete task
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 *
	 * @return array
	 * @throws TasksException
	 */
	public function completeAction(\CTaskItem $task, array $params = array())
	{
		$task->complete($params);

		return $this->getAction($task);
	}

	/**
	 * Defer task
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 *
	 * @return array
	 * @throws TasksException
	 */
	public function deferAction(\CTaskItem $task, array $params = array())
	{
		$task->defer($params);

		return $this->getAction($task);
	}

	/**
	 * Renew task after complete
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 *
	 * @return array
	 * @throws TasksException
	 */
	public function renewAction(\CTaskItem $task, array $params = array())
	{
		$task->renew($params);

		return $this->getAction($task);
	}

	/**
	 * Approve task
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 *
	 * @return array
	 * @throws TasksException
	 */
	public function approveAction(\CTaskItem $task, array $params = array())
	{
		$task->approve($params);

		return $this->getAction($task);
	}

	/**
	 * Disapprove task
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 *
	 * @return array
	 * @throws TasksException
	 */
	public function disapproveAction(\CTaskItem $task, array $params = array())
	{
		$task->disapprove($params);

		return $this->getAction($task);
	}

	/**
	 * @param \CTaskItem $task
	 * @param array $auditorsIds
	 * @return array
	 */
	public function addAuditorsAction(\CTaskItem $task, array $auditorsIds = [])
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

	/**
	 * @param \CTaskItem $task
	 * @param array $accomplicesIds
	 * @return array
	 */
	public function addAccomplicesAction(\CTaskItem $task, array $accomplicesIds = [])
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

	/**
	 * Become an auditor of a specified task
	 *
	 * @param \CTaskItem $task
	 *
	 * @return array
	 * @throws TasksException
	 */
	public function startWatchAction(\CTaskItem $task)
	{
		$task->startWatch();

		return $this->getAction($task);
	}

	/**
	 * Stop being an auditor of a specified task
	 *
	 * @param \CTaskItem $task
	 *
	 * @return array
	 * @throws TasksException
	 */
	public function stopWatchAction(\CTaskItem $task)
	{
		$task->stopWatch();

		return $this->getAction($task);
	}

	private static function getGroupInfo($groupId)
    {
        static $groups = [];

        if($groupId) {
            if (!$groups[$groupId]) {
                $group = SocialNetwork\Group::getData([$groupId]);
                $group = $group[$groupId];

                $groups[$groupId] = [
                    'ID' => $groupId,
                    'NAME' => $group['NAME']
                ];
            }
        }
        else
        {
            $groups[$groupId] = [];
        }

        return $groups[$groupId];
    }

	/**
	 * @param $userId
	 *
	 * @return mixed|null
	 */
	private static function getUserInfo($userId)
	{
		static $users = array();

		if (!$userId)
		{
			return null;
		}

		if (!$users[$userId])
		{
			// prepare link to profile
			$replaceList = array('user_id' => $userId);
			$link = \CComponentEngine::makePathFromTemplate('/company/personal/user/#user_id#/', $replaceList);

			$userFields = \Bitrix\Main\UserTable::getRowById($userId);
			if (!$userFields)
			{
				return null;
			}

			// format name
			$userName = \CUser::FormatName(
				'#NOBR##LAST_NAME# #NAME##/NOBR#',
				array(
					'LOGIN'       => $userFields['LOGIN'],
					'NAME'        => $userFields['NAME'],
					'LAST_NAME'   => $userFields['LAST_NAME'],
					'SECOND_NAME' => $userFields['SECOND_NAME']
				),
				true,
				false
			);


			$users[$userId] = array(
				'ID'   => $userId,
				'NAME' => $userName,
				'LINK' => $link,
				'ICON' => UI\Avatar::getPerson($userFields['PERSONAL_PHOTO'])
			);
		}

		return $users[$userId];
	}


	protected function buildErrorFromException(\Exception $exception)
	{
		if (!($exception instanceof Exception))
		{
			return parent::buildErrorFromException($exception);
		}

		if (Util::is_serialized($exception->getMessage()))
		{
			$message = unserialize($exception->getMessage());

			return new Error($message[0]['text'], $exception->getCode(), [$message[0]]);
		}

		return new Error($exception->getMessage(), $exception->getCode());
	}
}

/**
 * [1] for compatible with future php api
 * [2] for compatible with rest standarts
 */