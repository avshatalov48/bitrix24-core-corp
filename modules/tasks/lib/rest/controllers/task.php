<?php

namespace Bitrix\Tasks\Rest\Controllers;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\Error;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Exception;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\LogTable;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Ui\Avatar;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Type\DateTime;
use TasksException;
use Bitrix\Tasks\Integration\SocialNetwork;

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
	public function addAction(array $fields, array $params = array())
	{
        if( $fields['DEADLINE'] )
        {
            $fields['DEADLINE'] = date('d.m.Y H:i:s', strtotime($fields['DEADLINE']));
        }

		$task = \CTaskItem::add($fields, $this->getCurrentUser()->getId(), $params);

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
	public function getAction(\CTaskItem $task, array $select = array(), array $params = array())
	{
	    if(!empty($select))
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
		$this->formatDateFields($row);

		$action = $this->getAccessAction($task);
		$row['action'] = $action['allowedActions'][$this->getCurrentUser()->getId()];

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

	private function formatDateFields(&$row)
	{
		static $dateFields;

		if (!$dateFields)
		{
			$dateFields = array_filter(
				\CTasks::getFieldsInfo(),
				static function ($item)
				{
					if ($item['type'] == 'datetime')
					{
						return $item;
					}

					return null;
				}
			);
		}

		foreach ($dateFields as $fieldName => $fieldData)
		{
			if (array_key_exists($fieldName, $row))
			{
				if ($row[$fieldName])
				{
				    $date = new \Bitrix\Main\Type\DateTime($row[$fieldName]);
				    if($date)
                    {
                        $row[$fieldName] = $date->format('c');
                    }
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
		global $DB;

		$dateFields = array_filter(
			\CTasks::getFieldsInfo(),
			function ($item) {
				if ($item['type'] == 'datetime')
				{
					return $item;
				}

				return null;
			}
		);

		foreach ($dateFields as $fieldName => $fieldData)
		{
			if (array_key_exists($fieldName, $fields))
			{
				if ($fields[$fieldName])
				{
					$fields[$fieldName] = date(
						$DB->DateFormatToPhp(\CSite::GetDateFormat('FULL')),
						strtotime($fields[$fieldName])
					);
				}
			}
		}

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
	 * @param array $filter
	 * @param array $select
	 * @param array $group
	 * @param array $order
	 * @param array $params
	 * @param PageNavigation $pageNavigation
	 *
	 * @return Response\DataType\Page
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function listAction(PageNavigation $pageNavigation, array $filter = array(), array $select = array(),
							   array $group = array(), array $order = array(), array $params = array())
	{
		$filter = $this->getFilter($filter);

		if(!$this->checkOrderKeys($order))
        {
            $this->addError(new Error(GetMessage('TASKS_FAILED_WRONG_ORDER_FIELD')));
            return null;
        }

		$params['USE_MINIMAL_SELECT_LEGACY'] = 'N'; // VERY VERY BAD HACK! DONT REPEAT IT !

		if (!isset($params['RETURN_ACCESS']))
		{
			$params['RETURN_ACCESS'] = 'N'; // VERY VERY BAD HACK! DONT REPEAT IT !
		}

		$dateFields = array_filter(
			\CTasks::getFieldsInfo(),
			function ($item) {// [2]
				if ($item['type'] == 'datetime')
				{
					return $item;
				}

				return null;
			}
		);

		foreach ($filter as $fieldName => $fieldData)
		{
			preg_match('#(\w+)#', $fieldName, $m);

			if (array_key_exists($m[1], $dateFields))
			{
				if ($filter[$fieldName])
				{
					$filter[$fieldName] = DateTime::createFromTimestamp(strtotime($filter[$fieldName]));
				}
			}
		}

		$getListParams = [
			'limit'  => $pageNavigation->getLimit(),
			'offset' => $pageNavigation->getOffset(),
			'page'   => $pageNavigation->getCurrentPage(),

			'select'       => $this->prepareSelect($select),
			'legacyFilter' => !empty($filter) ? $filter : [],
			'order'        => !empty($order) ? $order : [],
			'group'        => !empty($group) ? $group : [],
		];

		$params['PUBLIC_MODE'] = 'Y'; // VERY VERY BAD HACK! DONT REPEAT IT !
		$result = Manager\Task::getList($this->getCurrentUser()->getId(), $getListParams, $params);

		$list = array_values($result['DATA']);

		$needReturnUserInfo = array_key_exists('RETURN_USER_INFO', $params) && $params['RETURN_USER_INFO'] != 'N';

		foreach ($list as &
				 $row)
		{
			if (array_key_exists('STATUS', $row))
			{
				$row['SUB_STATUS'] = $row['STATUS'];
				$row['STATUS'] = $row['REAL_STATUS'];
				unset($row['REAL_STATUS']);
			}

			$this->formatGroupInfo($row);
			$this->formatUserInfo($row);
			$this->formatDateFields($row);

			$row = $this->convertKeysToCamelCase($row);

			if (\Bitrix\Main\Loader::includeModule('pull') && isset($params['SEND_PULL']) && $params['SEND_PULL'] !='N')
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
			'tasks', $list, function () use ($getListParams, $params, $result) {
			$obj = $result['AUX']['OBJ_RES'];

			return $obj->nSelectedCount;
		}
		);

	}

	private function checkOrderKeys($order)
    {
        $orderKeys = array_keys(array_change_key_case($order, CASE_UPPER));
        $availableKeys = \CTasks::getAvailableOrderFields();

        if(array_diff($orderKeys, $availableKeys))
        {
            return false;
        }

        return true;
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
			list($tasks, $res) = \CTaskItem::fetchList(
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
				'ICON' => Avatar::getPerson($userFields['PERSONAL_PHOTO'])
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