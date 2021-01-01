<?php
namespace Bitrix\Tasks\Rest\Controllers;

use Bitrix\Main;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UserTable;
use Bitrix\Pull\MobileCounter;
use Bitrix\Tasks\AnalyticLogger;
use Bitrix\Tasks\Exception;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Tasks\Util\Type\DateTime;
use TasksException;

/**
 * Class Task
 *
 * @package Bitrix\Tasks\Rest\Controllers
 */
final class Task extends Base
{
	/**
	 * @return array[]
	 */
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
	 * @return Engine\AutoWire\ExactParameter|Engine\AutoWire\Parameter|null
	 */
	public function getPrimaryAutoWiredParameter()
	{
		return new Engine\AutoWire\ExactParameter(
			\CTaskItem::class,
			'task',
			static function ($className, $id) {
				return new $className($id, Engine\CurrentUser::get()->getId());
			}
		);
	}

	/**
	 * Return all DB and UF_ fields of task
	 *
	 * @return array
	 */
	public function getFieldsAction(): array
	{
		return ['fields' => \CTasks::getFieldsInfo()];
	}

	/**
	 * Return access data to task for current user
	 *
	 * @param \CTaskItem $task
	 * @param array $users
	 * @param array $params
	 * @return array[]
	 */
	public function getAccessAction(\CTaskItem $task, array $users = [], array $params = []): array
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
					\CTaskItem::getAllowedActionsArray($userId, $task->getData(false), $returnAsString)
				);
			}
			catch (TasksException $e)
			{

			}
		}

		return ['allowedActions' => $list];
	}

	/**
	 * @param $can
	 * @return array
	 */
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

	/**
	 * @param array $data
	 * @param string $from
	 * @param string $to
	 * @param bool $dropFrom
	 */
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
	 * Get task item data
	 *
	 * @param \CTaskItem $task
	 * @param array $select
	 * @param array $params
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getAction(\CTaskItem $task, array $select = [], array $params = []): array
	{
		if (!empty($select))
		{
			$select[] = 'FAVORITE';
		}

		$params['select'] = $this->prepareSelect($select);
		try
		{
			$row = $task->getData(false, $params);
		}
		catch (TasksException $e)
		{
			return [];
		}

		if (array_key_exists('STATUS', $row))
		{
			$row['STATUS'] = $row['REAL_STATUS'];
			unset($row['REAL_STATUS']);
		}

		$row = $this->fillGroupInfo([$row])[0];
		$row = $this->fillUserInfo([$row])[0];
		$this->formatDateFieldsForOutput($row);

		$action = $this->getAccessAction($task);
		$row['action'] = $action['allowedActions'][$this->getCurrentUser()->getId()];

		if (isset($params['GET_TASK_LIMIT_EXCEEDED']) && $params['GET_TASK_LIMIT_EXCEEDED'])
		{
			$row['TASK_LIMIT_EXCEEDED'] = TaskLimit::isLimitExceeded();
		}

		return ['task' => $this->convertKeysToCamelCase($row)];
	}

	/**
	 * @param array $select
	 * @return array
	 */
	private function prepareSelect(array $select): array
	{
		$validKeys = array_keys(\CTasks::getFieldsInfo($this->isUfExist($select)));

		$select = (!empty($select) && !in_array('*', $select, true) ? $select : $validKeys);
		$select = array_intersect($select, $validKeys);

		if (in_array('STATUS', $select, true))
		{
			$select[] = 'REAL_STATUS';
		}

		return $select;
	}

	/**
	 * @param array $rows
	 *
	 * @return array
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
			if (array_key_exists('CREATED_BY', $row) && !$users[$row['CREATED_BY']])
			{
				$userIds[] = (int)$row['CREATED_BY'];
			}
			if (array_key_exists('RESPONSIBLE_ID', $row) && !$users[$row['RESPONSIBLE_ID']])
			{
				$userIds[] = (int)$row['RESPONSIBLE_ID'];
			}
		}
		$userIds = array_unique($userIds);

		$userResult = UserTable::getList([
			'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO'],
			'filter' => ['ID' => $userIds],
		]);
		while ($user = $userResult->fetch())
		{
			$userId = $user['ID'];
			$userName = \CUser::FormatName(
				'#NOBR##LAST_NAME# #NAME##/NOBR#',
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
			$link = \CComponentEngine::makePathFromTemplate('/company/personal/user/#user_id#/', $replaceList);

			$users[$userId] = [
				'ID' => $userId,
				'NAME' => $userName,
				'LINK' => $link,
				'ICON' => UI\Avatar::getPerson($user['PERSONAL_PHOTO']),
			];
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
		}

		return $rows;
	}

	/**
	 * @param array $rows
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function fillGroupInfo(array $rows): array
	{
		static $groups = [];

		$groupIds = [];
		foreach ($rows as $id => $row)
		{
			if (array_key_exists('GROUP_ID', $row) && !$groups[$row['GROUP_ID']])
			{
				$groupIds[] = (int)$row['GROUP_ID'];
			}
			$rows[$id]['GROUP'] = [];
		}
		$groupIds = array_unique($groupIds);

		$groupsData = SocialNetwork\Group::getData($groupIds, ['IMAGE_ID']);
		$groupsData = array_map(
			static function ($group) {
				return [
					'ID' => $group['ID'],
					'NAME' => $group['NAME'],
					'IMAGE' => (is_array($file = \CFile::GetFileArray($group['IMAGE_ID'])) ? $file['SRC'] : ''),
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

	/**
	 * Returns fields of type datetime for task entity
	 *
	 * @param bool $getUf
	 * @return array
	 */
	private function getDateFields($getUf = true): array
	{
		return array_filter(
			\CTasks::getFieldsInfo($getUf),
			static function ($item) {
				return ($item['type'] === 'datetime' ? $item : null);
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
		$getUf = $this->isUfExist(array_keys($fields));

		foreach ($this->getDateFields($getUf) as $fieldName => $fieldData)
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
		$userOffset =  \CTimeZone::GetOffset(null, true);
		$offset = $localOffset + $userOffset;
		$newOffset = ($offset > 0 ? '+' : '').UI::formatTimeAmount($offset, 'HH:MI');

		foreach ($dateFields as $fieldName => $fieldData)
		{
			if ($field = $row[$fieldName])
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

	/**
	 * Create new task
	 *
	 * @param array $fields See in tasks.api.task.fields
	 * @param array $params
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws TasksException
	 * @throws \CTaskAssertException
	 */
	public function addAction(array $fields, array $params = []): array
	{
		$fields = $this->filterFields($fields);
		$fields = $this->formatDateFieldsForInput($fields);

		$task = \CTaskItem::add($fields, $this->getCurrentUser()->getId(), $params);

		if ($params['PLATFORM'] === 'mobile')
		{
			AnalyticLogger::logToFile('addTask');
		}

		return $this->getAction($task);
	}

	/**
	 * Update existing task
	 *
	 * @param \CTaskItem $task
	 * @param array $fields See in tasks.api.task.fields
	 * @param array $params
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function updateAction(\CTaskItem $task, array $fields, array $params = []): array
	{
		$fields = $this->filterFields($fields);
		$fields = $this->formatDateFieldsForInput($fields);

		$task->update($fields, $params);

		if (Loader::includeModule('pull'))
		{
			MobileCounter::send($this->getCurrentUser()->getId());
		}

		return $this->getAction($task);
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	private function filterFields(array $fields): array
	{
		$fieldNames = array_keys($fields);
		foreach ($fieldNames as $field)
		{
			if (mb_strpos($field, '~') === 0)
			{
				$fields[str_replace('~', '', $field)] = $fields[$field];
				unset($fields[$field]);
			}
		}

		return $fields;
	}

	/**
	 * Remove existing task
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 * @return array
	 * @throws TasksException
	 */
	public function deleteAction(\CTaskItem $task, array $params = []): array
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
	 * @return Engine\Response\DataType\Page
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\SystemException
	 */
	public function listAction(
		PageNavigation $pageNavigation,
		array $filter = [],
		array $select = [],
		array $group = [],
		array $order = [],
		array $params = []
	): ?Engine\Response\DataType\Page
	{
		if (!$this->checkOrderKeys($order))
        {
            $this->addError(new Error(GetMessage('TASKS_FAILED_WRONG_ORDER_FIELD')));
            return null;
        }

		$filter = $this->getFilter($filter);
		$getUf = $this->isUfExist($select) || $this->isUfExist(array_keys($filter));
		$dateFields = $this->getDateFields($getUf);

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
		$navParams = [
			'nPageSize' => $pageNavigation->getLimit(),
			'iNumPageSize' => $pageNavigation->getOffset(),
			'iNumPage' => $pageNavigation->getCurrentPage(),
		];
		$key = (isset($params['COUNT_TOTAL']) && $params['COUNT_TOTAL'] === 'N' ? 'getPlusOne' : 'getTotalCount');
		$navParams[$key] = true;

		$getListParams = [
			'select' => $this->prepareSelect($select),
			'legacyFilter' => ($filter ?: []),
			'order' => ($order ?: []),
			'group' => ($group ?: []),
			'NAV_PARAMS' => $navParams,
		];

		$params['PUBLIC_MODE'] = 'Y'; // VERY VERY BAD HACK! DONT REPEAT IT !
		$params['USE_MINIMAL_SELECT_LEGACY'] = 'N'; // VERY VERY BAD HACK! DONT REPEAT IT !
		$params['RETURN_ACCESS'] = ($params['RETURN_ACCESS'] ?? 'N'); // VERY VERY BAD HACK! DONT REPEAT IT !

		$result = Manager\Task::getList($this->getCurrentUser()->getId(), $getListParams, $params);
		$tasks = array_values($result['DATA']);
		$tasks = $this->fillGroupInfo($tasks);
		$tasks = $this->fillUserInfo($tasks);

		foreach ($tasks as &$task)
		{
			if (array_key_exists('STATUS', $task))
			{
				$task['SUB_STATUS'] = $task['STATUS'];
				$task['STATUS'] = $task['REAL_STATUS'];
				unset($task['REAL_STATUS']);
			}

			$this->formatDateFieldsForOutput($task);
			$task = $this->convertKeysToCamelCase($task);

			if (
				isset($params['SEND_PULL'])
				&& $params['SEND_PULL'] !== 'N'
				&& Loader::includeModule('pull')
			)
			{
				$users = array_unique(array_merge([$task['CREATED_BY']], [$task['RESPONSIBLE_ID']]));
				foreach ($users as $userId)
				{
					\CPullWatch::Add($userId, 'TASK_'.$task['ID']);
				}
			}
		}
		unset($task);

		return new Engine\Response\DataType\Page(
			'tasks',
			$tasks,
			static function() use ($result) {
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
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\SystemException
	 */
	private function getFilter(array $filter): array
	{
		$filter = (is_array($filter) && !empty($filter) ? $filter : []);
		$userId = ($filter['MEMBER'] ?? $this->getCurrentUser()->getId());
		$roleId = (array_key_exists('ROLE', $filter) ? $filter['ROLE'] : '');

		return $this->processFilter($filter, $userId, $roleId);
	}

	/**
	 * @param array $filter
	 * @param int $userId
	 * @param string $roleId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\SystemException
	 */
	private function processFilter(array $filter, int $userId, string $roleId): array
	{
		$filter = $this->processFilterSearchIndex($filter);
		$filter = $this->processFilterStatus($filter);
		$filter = $this->processFilterWithoutDeadline($filter, $userId, $roleId);
		$filter = $this->processFilterNotViewed($filter, $userId, $roleId);
		$filter = $this->processFilterRoleId($filter, $userId, $roleId);

		return $filter;
	}

	/**
	 * @param array $filter
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\SystemException
	 */
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

	/**
	 * @param array $filter
	 * @return array
	 */
	private function processFilterStatus(array $filter): array
	{
		if (!array_key_exists('STATUS', $filter))
		{
			return $filter;
		}

		$filter['REAL_STATUS'] = $filter['STATUS'];
		unset($filter['STATUS']);

		return $filter;
	}

	/**
	 * @param array $filter
	 * @param int $userId
	 * @param string $roleId
	 * @return array
	 */
	private function processFilterWithoutDeadline(array $filter, int $userId, string $roleId): array
	{
		if (!array_key_exists('WO_DEADLINE', $filter) || $filter['WO_DEADLINE'] !== 'Y')
		{
			return $filter;
		}

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

		return $filter;
	}

	/**
	 * @param array $filter
	 * @param int $userId
	 * @param string $roleId
	 * @return array
	 */
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

			default:
			case '': // view all
				$filter['::SUBFILTER-OR-NW'] = [
					'::LOGIC' => 'OR',
					'::SUBFILTER-R' => ['RESPONSIBLE_ID' => $userId],
					'::SUBFILTER-A' => ['=ACCOMPLICE' => $userId],
				];
				break;
		}

		return $filter;
	}

	/**
	 * @param array $filter
	 * @param int $userId
	 * @param string $roleId
	 * @return array
	 */
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
						'REAL_STATUS' => \CTasks::STATE_SUPPOSEDLY_COMPLETED,
					],
				];
				unset($filter['REAL_STATUS']);
				break;
		}
		unset($filter['ROLE']);

		return $filter;
	}

	/**
	 * @param array $fields
	 * @return bool
	 */
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

	/**
	 * @param \CTaskItem $task
	 * @return array|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function muteAction(\CTaskItem $task): ?array
	{
		UserOption::add($task->getId(), $this->getCurrentUser()->getId(), UserOption\Option::MUTED);
		return $this->getAction($task);
	}

	/**
	 * @param \CTaskItem $task
	 * @return array|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function unmuteAction(\CTaskItem $task): ?array
	{
		UserOption::delete($task->getId(), $this->getCurrentUser()->getId(), UserOption\Option::MUTED);
		return $this->getAction($task);
	}

	/**
	 * @param \CTaskItem $task
	 * @return array|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function pinAction(\CTaskItem $task): ?array
	{
		UserOption::add($task->getId(), $this->getCurrentUser()->getId(), UserOption\Option::PINNED);
		return $this->getAction($task);
	}

	/**
	 * @param \CTaskItem $task
	 * @return array|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function unpinAction(\CTaskItem $task): ?array
	{
		UserOption::delete($task->getId(), $this->getCurrentUser()->getId(), UserOption\Option::PINNED);
		return $this->getAction($task);
	}

	/**
	 * Delegate task to another user
	 *
	 * @param \CTaskItem $task
	 * @param $userId
	 * @param array $params
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws TasksException
	 */
	public function delegateAction(\CTaskItem $task, $userId, array $params = []): array
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
	 * @return array|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws TasksException
	 */
	public function startAction(\CTaskItem $task, array $params = []): ?array
	{
		try
		{
			$row = $task->getData(true);
		}
		catch (TasksException $e)
		{
			return null;
		}

		if ($row['ALLOW_TIME_TRACKING'] === 'Y' && !$this->startTimer($task, true))
		{
			return null;
		}

		$task->startExecution($params);

		return $this->getAction($task);
	}

	/**
	 * Start an execution timer for a specified task
	 *
	 * @param \CTaskItem $task
	 * @param bool $stopPrevious
	 * @return bool|null
	 * @throws TasksException
	 */
	private function startTimer(\CTaskItem $task, $stopPrevious = false): ?bool
	{
		$userId = $this->getCurrentUser()->getId();

		$timer = \CTaskTimerManager::getInstance($userId);
		$lastTimer = $timer->getLastTimer();
		$lastTimerTaskId = (int)$lastTimer['TASK_ID'];

		if (
			!$stopPrevious
			&& $lastTimerTaskId
			&& $lastTimer['TIMER_STARTED_AT'] > 0
			&& $lastTimerTaskId !== (int)$task->getId()
		)
		{
			// use direct query here, avoiding cached CTaskItem::getData(), because $lastTimerTaskId unlikely will be in cache
			[$tasks,] = \CTaskItem::fetchList($userId, [], ['ID' => $lastTimerTaskId], [], ['ID', 'TITLE']);
			if (is_array($tasks) && !empty($tasks))
			{
				$task = array_shift($tasks);
				if ($task)
				{
					$data = $task->getData(false);
					$replace = ['ID' => $data['ID'], 'TITLE' => $data['TITLE']];

					$this->addError(new Error(GetMessage('TASKS_OTHER_TASK_ON_TIMER', $replace)));
				}
			}

			return null;
		}

		if ($timer->start($task->getId()) === false)
		{
			$this->addError(new Error(GetMessage('TASKS_FAILED_START_TASK_TIMER')));
		}

		return true;
	}

	/**
	 * Stop execute task
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 * @return array|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws TasksException
	 */
	public function pauseAction(\CTaskItem $task, array $params = []): ?array
	{
		try
		{
			$row = $task->getData(true);
		}
		catch (TasksException $e)
		{
			return null;
		}

		if ($row['ALLOW_TIME_TRACKING'] === 'Y' && !$this->stopTimer($task))
		{
			return null;
		}

		$task->pauseExecution($params);

		return $this->getAction($task);
	}

	/**
	 * Stop an execution timer for a specified task
	 *
	 * @param \CTaskItem $task
	 * @return bool|null
	 */
	private function stopTimer(\CTaskItem $task): ?bool
	{
		$timer = \CTaskTimerManager::getInstance($this->getCurrentUser()->getId());
		if ($timer->stop($task->getId()) === false)
		{
			$this->addError(new Error(GetMessage('TASKS_FAILED_STOP_TASK_TIMER')));
			return null;
		}
		return true;
	}

	/**
	 * Complete task
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws TasksException
	 */
	public function completeAction(\CTaskItem $task, array $params = []): array
	{
		$task->complete($params);
		return $this->getAction($task);
	}

	/**
	 * Defer task
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws TasksException
	 */
	public function deferAction(\CTaskItem $task, array $params = []): array
	{
		$task->defer($params);
		return $this->getAction($task);
	}

	/**
	 * Renew task after complete
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws TasksException
	 */
	public function renewAction(\CTaskItem $task, array $params = []): array
	{
		$task->renew($params);
		return $this->getAction($task);
	}

	/**
	 * Approve task
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws TasksException
	 */
	public function approveAction(\CTaskItem $task, array $params = []): array
	{
		$task->approve($params);
		return $this->getAction($task);
	}

	/**
	 * Disapprove task
	 *
	 * @param \CTaskItem $task
	 * @param array $params
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws TasksException
	 */
	public function disapproveAction(\CTaskItem $task, array $params = []): array
	{
		$task->disapprove($params);
		return $this->getAction($task);
	}

	/**
	 * Become an auditor of a specified task
	 *
	 * @param \CTaskItem $task
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws TasksException
	 */
	public function startWatchAction(\CTaskItem $task): array
	{
		$task->startWatch();
		return $this->getAction($task);
	}

	/**
	 * Stop being an auditor of a specified task
	 *
	 * @param \CTaskItem $task
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws TasksException
	 */
	public function stopWatchAction(\CTaskItem $task): array
	{
		$task->stopWatch();
		return $this->getAction($task);
	}

	/**
	 * @param \CTaskItem $task
	 * @param array $auditorsIds
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function addAuditorsAction(\CTaskItem $task, array $auditorsIds = []): array
	{
		if (empty($auditorsIds))
		{
			return $this->getAction($task);
		}

		try
		{
			$taskData = $task->getData(false);
		}
		catch (TasksException $e)
		{
			return $this->getAction($task);
		}

		$auditors = array_merge($taskData['AUDITORS'], $auditorsIds);
		$task->update(['AUDITORS' => $auditors]);

		return $this->getAction($task);
	}

	/**
	 * @param \CTaskItem $task
	 * @param array $accomplicesIds
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function addAccomplicesAction(\CTaskItem $task, array $accomplicesIds = []): array
	{
		if (empty($accomplicesIds))
		{
			return $this->getAction($task);
		}

		try
		{
			$taskData = $task->getData(false);
		}
		catch (TasksException $e)
		{
			return $this->getAction($task);
		}

		$accomplices = array_merge($taskData['ACCOMPLICES'], $accomplicesIds);
		$task->update(['ACCOMPLICES' => $accomplices]);

		return $this->getAction($task);
	}

	/**
	 * @param \Exception $exception
	 * @return Error
	 */
	protected function buildErrorFromException(\Exception $exception): Error
	{
		if (!($exception instanceof Exception))
		{
			return parent::buildErrorFromException($exception);
		}

		if (Util::is_serialized($exception->getMessage()))
		{
			$message = unserialize($exception->getMessage(), ['allowed_classes' => false]);
			return new Error($message[0]['text'], $exception->getCode(), [$message[0]]);
		}

		return new Error($exception->getMessage(), $exception->getCode());
	}
}