<?php

namespace Bitrix\Tasks\Provider;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Internals\UserOption;
use \CDBResult;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Integration;

class TaskProvider
{
	use UserProviderTrait;

	private const USE_ORM_KEY = 'tasks_use_orm_list';

	private $db;
	private $userFieldManager;
	private $obUserFieldsSql;

	private
		$arOrder,
		$arFilter,
		$arSelect,
		$arParams,
		$arGroup,
		$arFields,
		$arSqlOrder 			= [],
		$arSqlSelect 			= [],
		$arOptimizedFilter,
		$arJoins				= [],
		$relatedJoins			= [],
		$accessSql 				= '',
		$arSqlSearch,
		$userFieldsJoin 		= false,
		$strGroupBy,
		$strSqlOrder 			= '',
		$bIgnoreErrors 			= false,
		$nPageTop 				= false,
		$getPlusOne				= false,
		$deleteMessageId 		= false,
		$useAccessAsWhere,
		$distinct 				= 'DISTINCT',
		$bIgnoreDbErrors 		= false,
		$bSkipUserFields 		= false,
		$bSkipExtraTables 		= false,
		$bSkipJoinTblViewed 	= false,
		$bNeedJoinMembersTable 	= false,
		$disableOptimization	= false,
		$canUseOptimization		= false,
		$countMode				= false;

	public function __construct(\CDatabase $db, \CUserTypeManager $userFieldManager)
	{
		$this->db = $db;
		$this->userFieldManager = $userFieldManager;
	}

	/**
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param array $arSelect
	 * @param array $arParams
	 * @param array $arGroup
	 *
	 * @return CDBResult
	 */
	public function getList($arOrder = [], $arFilter = [], $arSelect = [], $arParams = [], array $arGroup = []): CDBResult
	{
		$this->configure($arOrder, $arFilter, $arSelect, $arParams, $arGroup);

		if ($this->useOrm())
		{
			return $this->getListOrm($arOrder, $arFilter, $arSelect, $arParams, $arGroup);
		}

		$this
			->makeArFields()
			->makeArSelect()
			->makeArSqlOrder()
			->makeArSqlSelect()
			->makeArJoins()
			->makeRelatedJoins()
			->makeFilter()
			->makeAccessSql()
			->makeGroupBy()
			->makeOrderBy();

		return $this->executeQuery();
	}

	/**
	 * @param $arFilter
	 * @param $arParams
	 * @param $arGroup
	 * @return CDBResult
	 * @throws \TasksException
	 */
	public function getCount($arFilter = [], $arParams = [], $arGroup = []): CDBResult
	{
		$this->countMode = true;
		$this->configure([], $arFilter, ['*'], $arParams, $arGroup);

		if ($this->useOrm())
		{
			return $this->getCountOrm($arFilter, $arParams, $arGroup);
		}

		$this
			->makeArFields()
			->makeArSelect()
			->makeArSqlOrder()
			->makeArSqlSelect()
			->makeArJoins()
			->makeRelatedJoins()
			->makeFilter()
			->makeAccessSql()
			->makeGroupBy();

		$res = $this->db->Query($this->buildCountQuery(), $this->bIgnoreDbErrors, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res === false)
		{
			throw new \TasksException('', \TasksException::TE_SQL_ERROR);
		}

		return $res;
	}

	/**
	 * @return bool
	 */
	private function useOrm(): bool
	{
		$request = \Bitrix\Main\Context::getCurrent()->getRequest()->toArray();
		if (array_key_exists(self::USE_ORM_KEY, $request))
		{
			\CUserOptions::setOption('tasks', self::USE_ORM_KEY, (int) $request[self::USE_ORM_KEY], false, $this->userId);
			return $request[self::USE_ORM_KEY] > 0 ? true : false;
		}

		if ((int) \CUserOptions::getOption('tasks', self::USE_ORM_KEY, 0, $this->userId) > 0)
		{
			return true;
		}

		if (Option::get('tasks', self::USE_ORM_KEY, 'null', '-') !== 'null')
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $arOrder
	 * @param $arFilter
	 * @param $arSelect
	 * @param $arParams
	 * @param array $arGroup
	 * @return CDBResult
	 * @throws Exception\InvalidSelectException
	 * @throws Exception\UnexpectedTableException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getListOrm($arOrder = [], $arFilter = [], $arSelect = [], $arParams = [], array $arGroup = []): CDBResult
	{
		$userFields = [];
		if (
			in_array('UF_*', $arSelect, true)
			|| in_array('*', $arSelect, true)
			|| !empty(preg_grep('/^UF_/', $arSelect))

		)
		{
			$userFields = TasksUFManager::getInstance()->getFields();
		}
		if (
			empty($arSelect)
			|| in_array('*', $arSelect)
		)
		{
			$this->makeArFields();
			$arSelect = array_diff(array_keys($this->arFields), ['MESSAGE_ID']);
		}
		if (!in_array('ID', $arSelect, true))
		{
			$arSelect[] = 'ID';
		}

		$arSelect = array_merge($arSelect, $userFields);

		$query = new TaskQuery($this->executorId);
		$query
			->setBehalfUser($this->userId)
			->setSelect($arSelect)
			->setOrder($arOrder)
			->setGroupBy($arGroup)
			->setWhere($arFilter)
		;

		if (
			(isset($arParams['CHECK_PERMISSIONS']) && $arParams['CHECK_PERMISSIONS'] === 'N')
			|| (isset($arFilter['CHECK_PERMISSIONS']) && $arFilter['CHECK_PERMISSIONS'] === 'N')
		)
		{
			$query->skipAccessCheck();
		}

		if (
			isset($arParams['FILTER_PARAMS']['SEARCH_TASK_ONLY'])
			&& $arParams['FILTER_PARAMS']['SEARCH_TASK_ONLY'] = 'Y'
		)
		{
			$query->setParam('SEARCH_TASK_ONLY', true);
		}

		$nPlusOne = isset($arParams['NAV_PARAMS']['getPlusOne']) ? 1 : 0;
		$pageSize = isset($arParams['NAV_PARAMS']['nPageSize']) ? (int) $arParams['NAV_PARAMS']['nPageSize'] : 0;
		$page =
			(isset($arParams['NAV_PARAMS']['iNumPage']) && $arParams['NAV_PARAMS']['iNumPage'] > 0)
			? $arParams['NAV_PARAMS']['iNumPage']
			: 1;

		$navNum = null;
		$cnt = null;
		// this is a query for subtasks on the task view page
		if ($this->isLimitQuery())
		{
			global $NavNum;
			$navNum = (int)$NavNum;
			$pageName = 'PAGEN_' . ($navNum + 1);
			global ${$pageName};
			$page = ${$pageName};
			$page = $page > 0 ? (int)$page : (int)$this->arParams['NAV_PARAMS']['iNumPage'];
			$page = $page > 0 ? $page : 1;
			$cnt = $this->getCountOrm($this->arFilter, $this->arParams, $this->arGroup)->Fetch()['CNT'];
			$pageSize = $this->arParams['NAV_PARAMS']['nPageSize'] ?? 10;
		}

		// this is a query with limit which used in \Bitrix\Tasks\Integration\UI\EntitySelector\TaskProvider etc
		$nTopCount = (int)($this->arParams['NAV_PARAMS']['nTopCount'] ?? 0);
		if ($nTopCount > 0)
		{
			$query->setLimit($nTopCount + $nPlusOne);
		}
		else
		{
			$query->setLimit($pageSize + $nPlusOne);
			$query->setOffset(($page - 1) * $pageSize);
		}

		try
		{
			$list = new TaskList();
			$tasks = $list->getList($query);
			$dbResult = $list->getLastDbResult();
		}
		catch (\Exception $e)
		{
			throw new \TasksException($e->getMessage(), \TasksException::TE_SQL_ERROR);
		}

		$tasks = $this->prepareOrmData($tasks);

		$result = new CDBResult($dbResult);
		$result->InitFromArray($tasks);
		$result->NavPageNomer = $page;
		$result->PAGEN = $page;
		$result->NavRecordCount = $cnt;
		$result->NavPageSize = $pageSize;
		$result->NavPageCount = ($pageSize > 0 && !is_null($cnt)) ? ceil($cnt / $pageSize) : null;
		$result->NavNum = is_null($navNum) ? null : $navNum + 1;

		return $result;
	}

	/**
	 * @param array $rows
	 * @return array
	 */
	private function prepareOrmData(array $rows): array
	{
		if (empty($rows))
		{
			return [];
		}

		$res = [];
		foreach ($rows as $k => $row)
		{
			if (!is_array($row))
			{
				$res[$k] = $row;
				continue;
			}

			foreach ($row as $key => $value)
			{
				if (is_array($value))
				{
					foreach ($value as $subValue)
					{
						if (is_a($subValue, DateTime::class))
						{
							$subValue = $subValue->toString();
						}

						$res[$k][$key][] = $subValue;
					}
				}
				else
				{
					if (is_a($value, DateTime::class))
					{
						$value = $value->toString();
					}

					$res[$k][$key] = $value;
				}
			}
		}

		return $res;
	}

	/**
	 * @param $arFilter
	 * @param $arParams
	 * @param $arGroup
	 * @return CDBResult
	 * @throws \TasksException
	 */
	private function getCountOrm($arFilter = [], $arParams = [], $arGroup = []): CDBResult
	{
		$query = new TaskQuery($this->executorId);
		$query
			->setBehalfUser($this->userId)
			->setGroupBy($arGroup)
			->setWhere($arFilter);

		if (
			isset($arParams['FILTER_PARAMS']['SEARCH_TASK_ONLY'])
			&& $arParams['FILTER_PARAMS']['SEARCH_TASK_ONLY'] = 'Y'
		)
		{
			$query->setParam('SEARCH_TASK_ONLY', true);
		}

		try
		{
			$list = new TaskList();
			$count = $list->getCount($query);
			$dbResult = $list->getLastDbResult();
		}
		catch (\Exception $e)
		{
			throw new \TasksException($e->getMessage(), \TasksException::TE_SQL_ERROR);
		}

		$result = new CDBResult($dbResult);
		$result->InitFromArray([
			['CNT' => $count],
		]);

		return $result;
	}

	private function executeQuery(): \CDBResult
	{
		if (
			is_array($this->arParams)
			&& array_key_exists('NAV_PARAMS', $this->arParams)
			&& is_array($this->arParams['NAV_PARAMS'])
		)
		{
			$nTopCount = (int)($this->arParams['NAV_PARAMS']['nTopCount'] ?? 0);
			if ($nTopCount > 0)
			{
				$res = $this->executeTopQuery($nTopCount);
			}
			elseif (is_numeric($this->nPageTop))
			{
				$res = $this->executeTopQuery($this->nPageTop);
			}
			elseif (
				array_key_exists('nPageSize', $this->arParams['NAV_PARAMS'])
				&& array_key_exists('iNumPage', $this->arParams['NAV_PARAMS'])
				&& !array_key_exists('getTotalCount', $this->arParams['NAV_PARAMS'])
			)
			{
				$res = $this->executeLimitOffsetQuery();
			}
			else
			{
				$res = $this->executeLimitQuery();
			}
		}
		else
		{
			$res = $this->executeNonLimitQuery();
		}

		return $res;
	}

	private function executeLimitOffsetQuery(): \CDBResult
	{
		$sql = $this->buildQuery();

		$pageSize = (int) $this->arParams['NAV_PARAMS']['nPageSize'];
		$page = (int) $this->arParams['NAV_PARAMS']['iNumPage'];
		$page = ($page > 0) ? $page : 1;

		$sql .= "
			LIMIT " . ($pageSize + $this->getPlusOne) . "
			OFFSET " . ($page - 1) * $pageSize . "
		";

		$res = $this->db->Query($sql, $this->bIgnoreErrors, "File: " . __FILE__ . "<br>Line: " . __LINE__);
		if ($res === false)
		{
			throw new \TasksException('', \TasksException::TE_SQL_ERROR);
		}

		$res->NavPageNomer = $page;
		$res->PAGEN = $page;
		$res->SetUserFields($this->userFieldManager->GetUserFields("TASKS_TASK"));

		return $res;
	}

	private function executeLimitQuery(): \CDBResult
	{
		$res_cnt = $this->db->Query($this->buildCountQuery());
		$res_cnt = $res_cnt->Fetch();
		$totalTasksCount = (int) $res_cnt["CNT"];	// unknown by default

		$strSql = $this->buildQuery();

		// Sync counters in case of mistiming
//				CTaskCountersProcessorHomeostasis::onTaskGetList($arFilter, $totalTasksCount);

		$res = new \CDBResult();
		$res->SetUserFields($this->userFieldManager->GetUserFields("TASKS_TASK"));
		$rc = $res->NavQuery($strSql, $totalTasksCount, $this->arParams["NAV_PARAMS"], $this->bIgnoreErrors);

		if ($this->bIgnoreErrors && ($rc === false))
		{
			throw new \TasksException('', \TasksException::TE_SQL_ERROR);
		}
		return $res;
	}

	private function executeNonLimitQuery(): \CDBResult
	{
		$res = $this->db->Query($this->buildQuery(), $this->bIgnoreErrors, "File: " . __FILE__ . "<br>Line: " . __LINE__);

		if ($res === false)
		{
			throw new \TasksException('', \TasksException::TE_SQL_ERROR);
		}
		$res->SetUserFields($this->userFieldManager->GetUserFields("TASKS_TASK"));

		return $res;
	}

	private function executeTopQuery(int $nTopCount): \CDBResult
	{
		$nTopCount += $this->getPlusOne;
		$strSql = $this->db->TopSql($this->buildQuery(), $nTopCount);
		$res = $this->db->Query($strSql, $this->bIgnoreErrors, "File: " . __FILE__ . "<br>Line: " . __LINE__);
		if ($res === false)
		{
			throw new \TasksException('', \TasksException::TE_SQL_ERROR);
		}
		$res->SetUserFields($this->userFieldManager->GetUserFields("TASKS_TASK"));
		return $res;
	}

	private function buildQuery(): string
	{
		$strSql = "
			SELECT " . $this->distinct . "
			" . implode(",\n", $this->arSqlSelect) . "
			" . $this->obUserFieldsSql->GetSelect() . "
			FROM b_tasks T
			" . implode("\n", $this->arJoins) . "
			" . implode("\n", $this->relatedJoins) . "
			" . $this->obUserFieldsSql->GetJoin("T.ID") . "
			" . (count($this->arSqlSearch)? "WHERE " . implode(" AND ", $this->arSqlSearch) : "") . "
			" . $this->strGroupBy . "
			" . $this->strSqlOrder;

		return $strSql;
	}

	private function buildCountQuery(): string
	{
		$select[] = "COUNT(".($this->canUseOptimization ? "DISTINCT " : "")."T.ID) AS CNT";
		foreach ($this->arGroup as $key)
		{
			if (array_key_exists($key, $this->arSqlSelect))
			{
				$select[] = $this->arSqlSelect[$key];
			}
		}

		$strSql = "
			SELECT
				". implode(",\n", $select) ."
			FROM b_tasks T
			" . implode("\n", $this->arJoins) . "
			" . implode("\n", $this->relatedJoins) . "
			" . $this->obUserFieldsSql->GetJoin("T.ID") . "
			" . (count($this->arSqlSearch) ? "WHERE " . implode(" AND ", $this->arSqlSearch) : "") . "
			" . $this->strGroupBy;

		return $strSql;
	}

	private function makeOrderBy(): self
	{
		DelDuplicateSort($this->arSqlOrder);
		for ($i = 0, $arSqlOrderCnt = count($this->arSqlOrder); $i < $arSqlOrderCnt; $i++)
		{
			if ($i == 0)
			{
				$this->strSqlOrder = " ORDER BY ";
			}
			else
			{
				$this->strSqlOrder .= ",";
			}

			$this->strSqlOrder .= $this->arSqlOrder[$i];
		}

		return $this;
	}

	private function makeGroupBy(): self
	{
		$this->strGroupBy = (!empty($this->arGroup)? 'GROUP BY ' . implode(',', $this->arGroup) : "");

		return $this;
	}

	private function makeFilter(): self
	{
		$this->arParams['ENABLE_LEGACY_ACCESS'] = false;
		$this->arSqlSearch = \CTasks::GetFilter($this->arOptimizedFilter, '', $this->arParams);

		if ($this->accessSql !== '')
		{
			$this->arSqlSearch[] = $this->accessSql;
		}

		$r = $this->obUserFieldsSql->GetFilter();
		if ($r <> '')
		{
			$this->userFieldsJoin = true;
			$this->arSqlSearch[] = "(".$r.")";
		}

		return $this;
	}

	/**
	 * @return bool
	 */
	private function isJoinMembers(): bool
	{
		foreach ($this->arJoins as $row)
		{
			if (preg_match('/^INNER JOIN (`)?b_tasks_member/i', $row))
			{
				return true;
			}
		}

		return false;
	}

	private function joinTaskMembers()
	{
		$this->arJoins[] = "INNER JOIN b_tasks_member TMACCESS ON T.ID = TMACCESS.TASK_ID";
	}

	private function makeAccessSql(): self
	{
		if (!$this->useAccessAsWhere)
		{
			return $this;
		}

		if ($this->userId !== $this->executorId)
		{
			$this->buildAccessSql();
			return $this;
		}

		if (\CTasks::needAccessRestriction($this->arOptimizedFilter, $this->arParams))
		{
			$buildAccessSql = true;
			$this->arParams['APPLY_FILTER'] = \CTasks::makePossibleForwardedFilter($this->arOptimizedFilter);

			if ($this->arParams['MAKE_ACCESS_FILTER'] ?? null)
			{
				$viewedUserId = \CTasks::getViewedUserId($this->arFilter, $this->userId);

				$runtimeOptions = \CTasks::makeAccessFilterRuntimeOptions($this->arFilter, [
					'USER_ID' => $this->userId,
					'VIEWED_USER_ID' => $viewedUserId
				]);

				if (!is_array($this->arParams['ACCESS_FILTER_RUNTIME_OPTIONS'] ?? null))
				{
					$this->arParams['ACCESS_FILTER_RUNTIME_OPTIONS'] = $runtimeOptions;
				}
				else
				{
					foreach ($runtimeOptions as $key => $value)
					{
						$this->arParams['ACCESS_FILTER_RUNTIME_OPTIONS'][$key] += $value;
					}
				}

				if ($viewedUserId == $this->userId)
				{
					$buildAccessSql = \CTasks::checkAccessSqlBuilding($runtimeOptions);
				}
			}

			if ($buildAccessSql)
			{
				$this->buildAccessSql();
			}
		}

		return $this;
	}

	private function buildAccessSql(): self
	{
		$userModel = UserModel::createFromId($this->executorId);

		// admin can see all tasks
		if ($userModel->isAdmin())
		{
			return $this;
		}

		$this->joinTaskMembers();

		$query = [];
		$permissions = $this->getPermissions();

		// user in tasks
		$query[] = 'TMACCESS.USER_ID = '. $this->executorId;

		// user can view subordinate tasks
		$subordinate = $userModel->getAllSubordinates();
		if (!empty($subordinate))
		{
			$query[] = 'TMACCESS.user_id IN ('. implode(',', $subordinate) .')';
		}

		// user can view all department tasks
		if (in_array(PermissionDictionary::TASK_DEPARTMENT_VIEW, $permissions))
		{
			$departmentMembers = $this->getDepartmentMembers();
			if (!empty($departmentMembers))
			{
				$query[] = '
					TMACCESS.type IN ("'. RoleDictionary::ROLE_RESPONSIBLE .'", "'. RoleDictionary::ROLE_DIRECTOR .'", "'. RoleDictionary::ROLE_ACCOMPLICE .'")
					AND TMACCESS.user_id IN ('. implode(',', $departmentMembers) .')
				';
			}
		}

		// user can view all non department tasks
		if (in_array(PermissionDictionary::TASK_NON_DEPARTMENT_VIEW, $permissions))
		{
			$departmentMembers = $this->getDepartmentMembers();
			$query[] = '
				TMACCESS.type IN ("'. RoleDictionary::ROLE_RESPONSIBLE .'", "'. RoleDictionary::ROLE_DIRECTOR .'", "'. RoleDictionary::ROLE_ACCOMPLICE .'")
				AND TMACCESS.user_id NOT IN ('. (!empty($departmentMembers) ? implode(',', $departmentMembers) : 0) .')
			';
		}

		// user can view group tasks
		$userGroups = Integration\SocialNetwork\Group::getIdsByAllowedAction('view_all', true, $this->executorId);
		if (!empty($userGroups))
		{
			$query[] = '
				T.GROUP_ID IN ('. implode(',', $userGroups) .')
			';
		}

		if (!empty($query))
		{
			$this->arSqlSearch[] = '((' . implode(') OR (', $query) . '))';
		}

		return $this;
	}

	private function makeRelatedJoins(): self
	{
		$params = [
			'USER_ID' => $this->userId,
			'VIEWED_BY' => \CTasks::getViewedBy($this->arFilter, $this->userId),
			'SORTING_GROUP_ID' => (isset($this->arParams['SORTING_GROUP_ID']) && $this->arParams['SORTING_GROUP_ID'] > 0? $this->arParams['SORTING_GROUP_ID'] : false)
		];
		$this->relatedJoins = \CTasks::getRelatedJoins($this->arSelect, $this->arOptimizedFilter, $this->arOrder, $params);
		return $this;
	}

	private function makeArJoins(): self
	{
		$optimized = \CTasks::tryOptimizeFilter($this->arFilter);
		$this->arOptimizedFilter = $optimized['FILTER'];
		$this->arJoins = $optimized['JOINS'];

		if (!empty($optimized['JOINS']))
		{
			$this->distinct = 'DISTINCT';
			$this->arParams['SOURCE_FILTER'] = $this->arFilter;
		}

		return $this;
	}

	private function makeArSqlSelect(): self
	{
		foreach ($this->arSelect as $field)
		{
			$field = strtoupper($field);
			if (array_key_exists($field, $this->arFields))
				$this->arSqlSelect[$field] = $this->arFields[$field]." AS ".$field;
		}

		if (!sizeof($this->arSqlSelect))
		{
			$this->arSqlSelect = "T.ID AS ID";
		}

		return $this;
	}

	private function makeArSqlOrder(): self
	{
		foreach ($this->arOrder as $by => $order)
		{
			$needle = null;
			$by = strtolower($by);
			$order = strtolower($order);

			if ($by === 'deadline')
			{
				if ( ! in_array($order, array('asc', 'desc', 'asc,nulls', 'desc,nulls'), true) )
					$order = 'asc,nulls';
			}
			else
			{
				if ($order !== 'asc')
					$order = 'desc';
			}

			switch ($by)
			{
				case 'id':
					$this->arSqlOrder[] = " ID ".$order." ";
					break;

				case 'title':
					$this->arSqlOrder[] = " TITLE ".$order." ";
					$needle = 'TITLE';
					break;

				case 'time_spent_in_logs':
					$this->arSqlOrder[] = " TIME_SPENT_IN_LOGS ".$order." ";
					$needle = 'TIME_SPENT_IN_LOGS';
					break;

				case 'date_start':
					$this->arSqlOrder[] = " T.DATE_START ".$order." ";
					$needle = 'DATE_START';
					break;

				case 'created_date':
					$this->arSqlOrder[] = " T.CREATED_DATE ".$order." ";
					$needle = 'CREATED_DATE';
					break;

				case 'changed_date':
					$this->arSqlOrder[] = " T.CHANGED_DATE ".$order." ";
					$needle = 'CHANGED_DATE';
					break;

				case 'closed_date':
					$this->arSqlOrder[] = " T.CLOSED_DATE ".$order." ";
					$needle = 'CLOSED_DATE';
					break;

				case 'activity_date':
					$this->arSqlOrder[] = " T.ACTIVITY_DATE ".$order." ";
					$needle = 'ACTIVITY_DATE';
					break;

				case 'start_date_plan':
					$this->arSqlOrder[] = " T.START_DATE_PLAN ".$order." ";
					$needle = 'START_DATE_PLAN';
					break;

				case 'end_date_plan':
					$this->arSqlOrder[] = " T.END_DATE_PLAN ".$order." ";
					$needle = 'END_DATE_PLAN';
					break;

				case 'deadline':
					$orderClause = $this->getOrderSql(
						'T.DEADLINE',
						$order,
						$default_order = 'asc,nulls',
						$nullable = true
					);
					$needle = 'DEADLINE_ORIG';

					if ( !is_array($orderClause) )
						$this->arSqlOrder[] = $orderClause;
					else   // we have to add select field in order to correctly sort
					{
						//         COLUMN ALIAS      COLUMN EXPRESSION
						$this->arFields[$orderClause[1]] = $orderClause[0];

						if ( ! in_array($orderClause[1], $this->arSelect) )
							$this->arSelect[] = $orderClause[1];

						$this->arSqlOrder[] = $orderClause[2];	// order expression
					}
					break;

				case 'status':
//					$arSqlOrder[] = " STATUS ".$order." ";
//					$needle = 'STATUS';
					break;
				case 'real_status':
					$this->arSqlOrder[] = " REAL_STATUS ".$order." ";
					$needle = 'REAL_STATUS';
					break;

				case 'status_complete':
					$this->arSqlOrder[] = " STATUS_COMPLETE ".$order." ";
					$needle = 'STATUS_COMPLETE';
					break;

				case 'priority':
					$this->arSqlOrder[] = " PRIORITY ".$order." ";
					$needle = 'PRIORITY';
					break;

				case 'mark':
					$this->arSqlOrder[] = " MARK ".$order." ";
					$needle = 'MARK';
					break;

				case 'originator_name':
				case 'created_by':
				case 'created_by_last_name':
					$this->arSqlOrder[] = " CREATED_BY_LAST_NAME ".$order." ";
					$needle = 'CREATED_BY_LAST_NAME';
					break;

				case 'responsible_name':
				case 'responsible_id':
				case 'responsible_last_name':
					$this->arSqlOrder[] = " RESPONSIBLE_LAST_NAME ".$order." ";
					$needle = 'RESPONSIBLE_LAST_NAME';
					break;

				case 'group_id':
					$this->arSqlOrder[] = " GROUP_ID ".$order." ";
					$needle = 'GROUP_ID';
					break;

				case 'time_estimate':
					$this->arSqlOrder[] = " TIME_ESTIMATE ".$order." ";
					$needle = 'TIME_ESTIMATE';
					break;

				case 'allow_change_deadline':
					$this->arSqlOrder[] = " ALLOW_CHANGE_DEADLINE ".$order." ";
					$needle = 'ALLOW_CHANGE_DEADLINE';
					break;

				case 'allow_time_tracking':
					$this->arSqlOrder[] = " ALLOW_TIME_TRACKING ".$order." ";
					$needle = 'ALLOW_TIME_TRACKING';
					break;

				case 'match_work_time':
					$this->arSqlOrder[] = " MATCH_WORK_TIME ".$order." ";
					$needle = 'MATCH_WORK_TIME';
					break;

				case 'favorite':
					$this->arSqlOrder[] = " FAVORITE ".$order." ";
					$needle = 'FAVORITE';
					break;

				case 'sorting':
					$asc = stripos($order, "desc") === false;
					$this->arSqlOrder = array_merge($this->arSqlOrder, $this->getSortingOrderBy($asc));
					$needle = "SORTING";
					break;

				case 'message_id':
					$this->arSqlOrder[] = " MESSAGE_ID " . $order . " ";
					$needle = 'MESSAGE_ID';
					break;

				case 'is_pinned':
					$this->arSqlOrder[] = " IS_PINNED " . $order . " ";
					$needle = 'IS_PINNED';
					break;

				case 'is_pinned_in_group':
					$this->arSqlOrder[] = " IS_PINNED_IN_GROUP " . $order . " ";
					$needle = 'IS_PINNED_IN_GROUP';
					break;

				case 'scrum_items_sort':
					$this->arSqlOrder[] = " BTSI.SORT " . $order . " ";
					break;

				default:
					if (substr($by, 0, 3) === 'uf_')
					{
						if ($s = $this->obUserFieldsSql->GetOrder($by))
							$this->arSqlOrder[$by] = " ".$s." ".$order." ";
					}
					else
						\CTaskAssert::logWarning('[0x9a92cf7d] invalid sort by field requested: ' . $by);
					break;
			}

			if (
				($needle !== null)
				&& ( ! in_array($needle, $this->arSelect) )
			)
			{
				$this->arSelect[] = $needle;
			}
		}

		return $this;
	}

	private function makeArSelect(): self
	{
		if (count($this->arSelect) <= 0 || in_array("*", $this->arSelect))
		{
			$this->arSelect = array_keys($this->arFields);
		}
		elseif (!in_array("ID", $this->arSelect))
		{
			$this->arSelect[] = "ID";
		}

		// add fields that are NOT selected by default
		//$this->arFields["FAVORITE"] = "CASE WHEN FVT.TASK_ID IS NULL THEN 'N' ELSE 'Y' END";

		// If DESCRIPTION selected, then BBCODE flag must be selected too
		if (
			in_array('DESCRIPTION', $this->arSelect)
			&& ( ! in_array('DESCRIPTION_IN_BBCODE', $this->arSelect) )
		)
		{
			$this->arSelect[] = 'DESCRIPTION_IN_BBCODE';
		}

		if (!Integration\Forum::isInstalled())
		{
			$this->arSelect = array_diff($this->arSelect, ['COMMENTS_COUNT', 'FORUM_ID', 'SERVICE_COMMENTS_COUNT']);
		}

		if ($this->deleteMessageId)
		{
			$this->arSelect = array_diff($this->arSelect, ['MESSAGE_ID']);
		}

		if (!array_key_exists('IM_CHAT_CHAT_ID', $this->arFilter))
		{
			$this->arSelect = array_diff($this->arSelect, ['IM_CHAT_ID', 'IM_CHAT_MESSAGE_ID', 'IM_CHAT_CHAT_ID', 'IM_CHAT_AUTHOR_ID']);
		}

		return $this;
	}

	private function makeArFields(bool $isCount = false): self
	{
		$this->arFields = [
			"ID" => "T.ID",
			"TITLE" => "T.TITLE",
			"DESCRIPTION" => "T.DESCRIPTION",
			"DESCRIPTION_IN_BBCODE" => "T.DESCRIPTION_IN_BBCODE",
			"DECLINE_REASON" => "T.DECLINE_REASON",
			"PRIORITY" => "T.PRIORITY",
			// 1) deadline in past, real status is not STATE_SUPPOSEDLY_COMPLETED and not STATE_COMPLETED and (not STATE_DECLINED or responsible is not me (user))
			// 2) viewed by noone(?) and created not by me (user) and (STATE_NEW or STATE_PENDING)
			"STATUS" => "
				CASE
					WHEN
						T.DEADLINE < DATE_ADD(". $this->db->CurrentTimeFunction() .", INTERVAL ".
				Counter\Deadline::getDeadlineTimeLimit()." SECOND)
						AND T.DEADLINE >= ". $this->db->CurrentTimeFunction() ."
						AND T.STATUS != '4'
						AND T.STATUS != '5'
						AND (
							T.STATUS != '7'
							OR T.RESPONSIBLE_ID != ". $this->userId ."
						)
					THEN
						'-3'
					WHEN
						T.DEADLINE < ". $this->db->CurrentTimeFunction() ." AND T.STATUS != '4' AND T.STATUS != '5' AND (T.STATUS != '7' OR T.RESPONSIBLE_ID != ". $this->userId .")
					THEN
						'-1'
					WHEN
						TV.USER_ID IS NULL
						AND
						T.CREATED_BY != ". $this->userId ."
						AND
						(T.STATUS = 1 OR T.STATUS = 2)
					THEN
						'-2'
					ELSE
						T.STATUS
				END
			",
			"NOT_VIEWED" => "
				CASE
					WHEN
						TV.USER_ID IS NULL
						AND
						T.CREATED_BY != ". $this->userId ."
						AND
						(T.STATUS = 1 OR T.STATUS = 2)
					THEN
						'Y'
					ELSE
						'N'
				END
			",
			// used in ORDER BY to make completed tasks go after (or before) all other tasks
			"STATUS_COMPLETE" => "
				CASE
					WHEN
						T.STATUS = '5'
					THEN
						'2'
					ELSE
						'1'
					END
			",
			"REAL_STATUS" => "T.STATUS",
			"MULTITASK" => "T.MULTITASK",
			"STAGE_ID" => "T.STAGE_ID",
			"RESPONSIBLE_ID" => "T.RESPONSIBLE_ID",
			"RESPONSIBLE_NAME" => "RU.NAME",
			"RESPONSIBLE_LAST_NAME" => "RU.LAST_NAME",
			"RESPONSIBLE_SECOND_NAME" => "RU.SECOND_NAME",
			"RESPONSIBLE_LOGIN" => "RU.LOGIN",
			"RESPONSIBLE_WORK_POSITION" => "RU.WORK_POSITION",
			"RESPONSIBLE_PHOTO" => "RU.PERSONAL_PHOTO",
			"DATE_START" => $this->db->DateToCharFunction("T.DATE_START", "FULL"),
			"DURATION_FACT" => "(SELECT SUM(TE.MINUTES) FROM b_tasks_elapsed_time TE WHERE TE.TASK_ID = T.ID GROUP BY TE.TASK_ID)",
			"TIME_ESTIMATE" => "T.TIME_ESTIMATE",
			"TIME_SPENT_IN_LOGS" => "(SELECT SUM(TE.SECONDS) FROM b_tasks_elapsed_time TE WHERE TE.TASK_ID = T.ID GROUP BY TE.TASK_ID)",
			"REPLICATE" => "T.REPLICATE",
			"DEADLINE" => $this->db->DateToCharFunction("T.DEADLINE", "FULL"),
			"DEADLINE_ORIG" => "T.DEADLINE",
			"START_DATE_PLAN" => $this->db->DateToCharFunction("T.START_DATE_PLAN", "FULL"),
			"END_DATE_PLAN" => $this->db->DateToCharFunction("T.END_DATE_PLAN", "FULL"),
			"CREATED_BY" => "T.CREATED_BY",
			"CREATED_BY_NAME" => "CU.NAME",
			"CREATED_BY_LAST_NAME" => "CU.LAST_NAME",
			"CREATED_BY_SECOND_NAME" => "CU.SECOND_NAME",
			"CREATED_BY_LOGIN" => "CU.LOGIN",
			"CREATED_BY_WORK_POSITION" => "CU.WORK_POSITION",
			"CREATED_BY_PHOTO" => "CU.PERSONAL_PHOTO",
			"CREATED_DATE" => $this->db->DateToCharFunction("T.CREATED_DATE", "FULL"),
			"CHANGED_BY" => "T.CHANGED_BY",
			"CHANGED_DATE" => $this->db->DateToCharFunction("T.CHANGED_DATE", "FULL"),
			"STATUS_CHANGED_BY" => "T.CHANGED_BY",
			"STATUS_CHANGED_DATE" =>
				'CASE WHEN T.STATUS_CHANGED_DATE IS NULL THEN '
				. $this->db->DateToCharFunction("T.CHANGED_DATE", "FULL")
				. ' ELSE '
				. $this->db->DateToCharFunction("T.STATUS_CHANGED_DATE", "FULL")
				. ' END ',
			"CLOSED_BY" => "T.CLOSED_BY",
			"CLOSED_DATE" => $this->db->DateToCharFunction("T.CLOSED_DATE", "FULL"),
			"ACTIVITY_DATE" => $this->db->DateToCharFunction("T.ACTIVITY_DATE", "FULL"),
			'GUID' => 'T.GUID',
			"XML_ID" => "T.XML_ID",
			"MARK" => "T.MARK",
			"ALLOW_CHANGE_DEADLINE" => "T.ALLOW_CHANGE_DEADLINE",
			"ALLOW_TIME_TRACKING" => 'T.ALLOW_TIME_TRACKING',
			"MATCH_WORK_TIME" => "T.MATCH_WORK_TIME",
			"TASK_CONTROL" => "T.TASK_CONTROL",
			"ADD_IN_REPORT" => "T.ADD_IN_REPORT",
			"GROUP_ID" => "CASE WHEN T.GROUP_ID IS NULL THEN 0 ELSE T.GROUP_ID END",
			"FORUM_TOPIC_ID" => "T.FORUM_TOPIC_ID",
			"PARENT_ID" => "T.PARENT_ID",
			"COMMENTS_COUNT" => "FT.POSTS",
			"SERVICE_COMMENTS_COUNT" => "FT.POSTS_SERVICE",
			"FORUM_ID" => "FT.FORUM_ID",
			"MESSAGE_ID" => "MIN(TSIF.MESSAGE_ID)",
			"SITE_ID" => "T.SITE_ID",
			"SUBORDINATE" => ($strSql = \CTasks::GetSubordinateSql('', $this->arParams)) ? "CASE WHEN EXISTS(".$strSql.") THEN 'Y' ELSE 'N' END" : "'N'",
			"EXCHANGE_MODIFIED" => "T.EXCHANGE_MODIFIED",
			"EXCHANGE_ID" => "T.EXCHANGE_ID",
			"OUTLOOK_VERSION" => "T.OUTLOOK_VERSION",
			"VIEWED_DATE" => $this->db->DateToCharFunction("TV.VIEWED_DATE", "FULL"),
			"DEADLINE_COUNTED" => "T.DEADLINE_COUNTED",
			"FORKED_BY_TEMPLATE_ID" => "T.FORKED_BY_TEMPLATE_ID",

			"FAVORITE" => "CASE WHEN FVT.TASK_ID IS NULL THEN 'N' ELSE 'Y' END",
			"SORTING" => "SRT.SORT",

			"DURATION_PLAN_SECONDS" => "T.DURATION_PLAN",
			"DURATION_TYPE_ALL" => "T.DURATION_TYPE",

			"DURATION_PLAN" => "
				case
					when
						T.DURATION_TYPE = '".\CTasks::TIME_UNIT_TYPE_MINUTE."' or T.DURATION_TYPE = '".\CTasks::TIME_UNIT_TYPE_HOUR."'
					then
						ROUND(T.DURATION_PLAN / 3600, 0)
					when
						T.DURATION_TYPE = '".\CTasks::TIME_UNIT_TYPE_DAY."' or T.DURATION_TYPE = '' or T.DURATION_TYPE is null
					then
						ROUND(T.DURATION_PLAN / 86400, 0)
					else
						T.DURATION_PLAN
				end
			",
			"DURATION_TYPE" => "
				case
					when
						T.DURATION_TYPE = '".\CTasks::TIME_UNIT_TYPE_MINUTE."'
					then
						'".\CTasks::TIME_UNIT_TYPE_HOUR."'
					else
						T.DURATION_TYPE
				end
			",
			"SCENARIO_NAME" => "SCR.SCENARIO",
		];

		if ($this->userId)
		{
			$this->arFields['IS_MUTED'] = UserOption::getSelectSql($this->userId, UserOption\Option::MUTED);
			$this->arFields['IS_PINNED'] = UserOption::getSelectSql($this->userId, UserOption\Option::PINNED);
			$this->arFields['IS_PINNED_IN_GROUP'] = UserOption::getSelectSql($this->userId, UserOption\Option::PINNED_IN_GROUP);
		}

		return $this;
	}

	/**
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param array $arSelect
	 * @param array $arParams
	 * @param array $arGroup
	 */
	private function configure($arOrder = [], $arFilter = [], $arSelect = [], $arParams = [], array $arGroup = []): void
	{
		$this->arOrder 		= is_array($arOrder) ? $arOrder: [];
		$this->arFilter 	= $arFilter;
		$this->arSelect 	= $arSelect;
		$this->arParams 	= $arParams;
		$this->arGroup 		= $arGroup;

		if ( !is_array($this->arParams) )
		{
			$this->nPageTop = $this->arParams;
			$this->arParams = false;
		}
		else
		{
			if (isset($this->arParams['nPageTop']))
			{
				$this->nPageTop = $this->arParams['nPageTop'];
			}

			if (isset($this->arParams['bIgnoreErrors']))
			{
				// $this->bIgnoreErrors = (bool) $this->arParams['bIgnoreErrors'];
			}

			if (isset($this->arParams['bIgnoreDbErrors']))
			{
				$this->bIgnoreDbErrors = (bool) $this->arParams['bIgnoreDbErrors'];
			}

			if (isset($this->arParams['bSkipUserFields']))
			{
				$this->bSkipUserFields = (bool) $this->arParams['bSkipUserFields'];
			}

			if (isset($this->arParams['bSkipExtraTables']))
			{
				$this->bSkipExtraTables = (bool) $this->arParams['bSkipExtraTables'];
			}

			if (isset($this->arParams['bSkipJoinTblViewed']))
			{
				$this->bSkipJoinTblViewed = (bool) $this->arParams['bSkipJoinTblViewed'];
			}

			if (isset($this->arParams['bNeedJoinMembersTable']))
			{
				$this->bNeedJoinMembersTable = (bool) $this->arParams['bNeedJoinMembersTable'];
			}
		}

		if (!in_array('MESSAGE_ID', $this->arSelect))
		{
			$this->deleteMessageId = true;
		}

		$this->disableOptimization = (is_array($this->arParams) && array_key_exists('DISABLE_OPTIMIZATION', $this->arParams) && $this->arParams['DISABLE_OPTIMIZATION'] === true);
		$this->useAccessAsWhere = !(is_array($this->arParams) && array_key_exists('DISABLE_ACCESS_OPTIMIZATION', $this->arParams) && $this->arParams['DISABLE_ACCESS_OPTIMIZATION'] === true);
		$this->canUseOptimization = !$this->disableOptimization && !$this->bNeedJoinMembersTable;

		// First level logic MUST be 'AND', because of backward compatibility
		// and some requests for checking rights, attached at first level of filter.
		// Situtation when there is OR-logic at first level cannot be resolved
		// in general case.
		// So if there is OR-logic, it is FATAL error caused by programmer.
		// But, if you want to use OR-logic at the first level of filter, you
		// can do this by putting all your filter conditions to the ::SUBFILTER-xxx,
		// except CHECK_PERMISSIONS, SUBORDINATE_TASKS (if you don't know exactly,
		// what are consequences of this fields in OR-logic of subfilters).
		if (isset($this->arFilter['::LOGIC']))
		{
			\CTaskAssert::assert($this->arFilter['::LOGIC'] === 'AND');
		}
		$this->bIgnoreErrors = false;
		$this->invokeUserTypeSql();
		$this->setUserId();

		if (
			is_array($this->arParams)
			&& array_key_exists('NAV_PARAMS', $this->arParams)
			&& is_array($this->arParams['NAV_PARAMS'])
			&& array_key_exists('getPlusOne', $this->arParams['NAV_PARAMS'])
		)
		{
			$this->getPlusOne = $this->arParams['NAV_PARAMS']['getPlusOne'];
		}
	}

	private function setUserId(): void
	{
		$this->executorId = (int) User::getId();
		if (
			is_array($this->arParams)
			&& array_key_exists('USER_ID', $this->arParams)
			&& ($this->arParams['USER_ID'] > 0)
		)
		{
			$this->executorId = (int) $this->arParams['USER_ID'];
		}

		$this->userId = $this->executorId;
		if (
			is_array($this->arParams)
			&& array_key_exists('TARGET_USER_ID', $this->arParams)
			&& ($this->arParams['TARGET_USER_ID'] > 0)
		)
		{
			$this->userId = (int) $this->arParams['TARGET_USER_ID'];
		}

		if ($this->userId && !$this->executorId)
		{
			$this->executorId = $this->userId;
		}

		if ($this->executorId && !$this->userId)
		{
			$this->userId = $this->executorId;
		}
	}

	private function invokeUserTypeSql(): void
	{
		$this->obUserFieldsSql = new \CUserTypeSQL();
		$this->obUserFieldsSql->SetEntity("TASKS_TASK", "T.ID");
		$this->obUserFieldsSql->SetSelect($this->arSelect);
		$this->obUserFieldsSql->SetFilter($this->arFilter);
		$this->obUserFieldsSql->SetOrder($this->arOrder);
	}

	private function getOrderSql($by, $order, $default_order, $nullable = true)
	{
		$o = $this->parseOrder($order, $default_order, $nullable);
		//$o[0] - bNullsFirst
		//$o[1] - asc|desc
		if($o[0])
		{
			if($o[1] == "asc")
				return $by." asc";
			else
				return "length(".$by.")>0 asc, ".$by." desc";
		}
		else
		{
			if($o[1] == "asc")
				return "length(".$by.")>0 desc, ".$by." asc";
			else
				return $by." desc";
		}
	}

	private function parseOrder($order, $default_order, $nullable = true)
	{
		static $arOrder = array(
			"nulls,asc"  => array(true,  "asc" ),
			"asc,nulls"  => array(false, "asc" ),
			"nulls,desc" => array(true,  "desc"),
			"desc,nulls" => array(false, "desc"),
			"asc"        => array(true,  "asc" ),
			"desc"       => array(false, "desc"),
		);
		$order = strtolower(trim($order));
		if(array_key_exists($order, $arOrder))
			$o = $arOrder[$order];
		elseif(array_key_exists($default_order, $arOrder))
			$o = $arOrder[$default_order];
		else
			$o = $arOrder["desc,nulls"];

		//There is no need to "reverse" nulls order when
		//column can not contain nulls
		if(!$nullable)
		{
			if($o[1] == "asc")
				$o[0] = true;
			else
				$o[0] = false;
		}

		return $o;
	}

	private function getSortingOrderBy($asc = true)
	{
		$order = array();
		$direction = $asc ? "ASC" : "DESC";

		$order[] = " ISNULL(SORTING) ".$direction." ";
		$order[] = " SORTING ".$direction." ";

		return $order;
	}

	private function isLimitQuery(): bool
	{
		if (
			is_array($this->arParams)
			&& array_key_exists('NAV_PARAMS', $this->arParams)
			&& is_array($this->arParams['NAV_PARAMS'])
		)
		{
			if ((int)($this->arParams['NAV_PARAMS']['nTopCount'] ?? 0)> 0)
			{
				return false;
			}

			if (is_numeric($this->nPageTop))
			{
				return false;
			}

			if (
				array_key_exists('nPageSize', $this->arParams['NAV_PARAMS'])
				&& array_key_exists('iNumPage', $this->arParams['NAV_PARAMS'])
				&& !array_key_exists('getTotalCount', $this->arParams['NAV_PARAMS'])
			)
			{
				return false;
			}

			return true;
		}

		return false;
	}
}