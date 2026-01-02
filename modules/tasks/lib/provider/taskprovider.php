<?php

namespace Bitrix\Tasks\Provider;

use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\DI\Container;
use Bitrix\Tasks\Internals\Task\TimeUnitType;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Provider\Exception\InvalidGroupByException;
use CDBResult;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Util\User;
use CTaskAssertException;
use CTasks;
use Exception;
use TasksException;

class TaskProvider
{
	use UserProviderTrait;

	private mixed $params;
	private array $fields;
	private mixed $nPageTop = false;

	/**
	 * @throws InvalidGroupByException
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function getList(
		mixed $arOrder = [],
		mixed $arFilter = [],
		mixed $arSelect = [],
		mixed $arParams = [],
		array $arGroup = []
	): CDBResult
	{
		$this->configure(filter: $arFilter, params: $arParams);

		return $this->getListOrm($arOrder, $arFilter, $arSelect, $arParams, $arGroup);
	}

	/**
	 * @throws InvalidGroupByException
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function getCount(mixed $arFilter = [], mixed $arParams = [], mixed $arGroup = []): CDBResult
	{
		$this->configure(filter: $arFilter, params: $arParams);

		return $this->getCountOrm($arFilter, $arParams, $arGroup);
	}

	/**
	 * @throws InvalidGroupByException
	 * @throws TasksException
	 */
	private function getListOrm(
		mixed $order = [],
		mixed $filter = [],
		mixed $select = [],
		mixed $arParams = [],
		array $group = []
	): CDBResult
	{
		$userFields = [];
		if (
			in_array('UF_*', $select, true)
			|| in_array('*', $select, true)
			|| !empty(preg_grep('/^UF_/', $select))

		)
		{
			$userFields = TasksUFManager::getInstance()->getFields();
		}
		if (
			empty($select)
			|| in_array('*', $select, true)
		)
		{
			$this->makeFields();
			$select = array_diff(array_keys($this->fields), ['MESSAGE_ID']);
		}
		if (!in_array('ID', $select, true))
		{
			$select[] = 'ID';
		}

		$select = array_merge($select, $userFields);

		$query = new TaskQuery($this->executorId);
		$query
			->setBehalfUser($this->userId)
			->setSelect($select)
			->setOrder($order)
			->setGroupBy($group)
			->setWhere($filter)
		;

		if ($this->params['MAKE_ACCESS_FILTER'] ?? null)
		{
			$query->needMakeAccessFilter();
		}

		if (
			(isset($arParams['CHECK_PERMISSIONS']) && $arParams['CHECK_PERMISSIONS'] === 'N')
			|| (isset($filter['CHECK_PERMISSIONS']) && $filter['CHECK_PERMISSIONS'] === 'N')
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

		// backward compatability starts, I am so sorry
		// this is a query for subtasks on the task view page
		if ($this->isLimitQuery())
		{
			global $NavNum;
			$navNum = (int)$NavNum;
			$pageName = 'PAGEN_' . ($navNum + 1);
			global ${$pageName};
			$page = ${$pageName};
			$page = $page > 0 ? (int)$page : (int)($this->params['NAV_PARAMS']['iNumPage'] ?? null);
			$page = $page > 0 ? $page : 1;
			$cnt = $this->getCountOrm($filter, $this->params, $group)->Fetch()['CNT'];
			$pageSize = $this->params['NAV_PARAMS']['nPageSize'] ?? 0;
		}

		// this is a query with limit which used in \Bitrix\Tasks\Integration\UI\EntitySelector\TaskProvider etc.
		$nTopCount = (int)($this->params['NAV_PARAMS']['nTopCount'] ?? 0);
		if ($nTopCount > 0)
		{
			$query->setLimit($nTopCount + $nPlusOne);
		}
		else
		{
			$query->setLimit($pageSize + $nPlusOne);
			$query->setOffset(($page - 1) * $pageSize);
		}

		if (
			!is_null($cnt)
			&& (int)$cnt === 0
		)
		{
			$tasks = [];
			$dbResult = null;
		}
		else
		{
			try
			{
				$list = Container::getInstance()->getTaskProvider();
				$tasks = $list->getList($query);
				$dbResult = $list->getLastDbResult();
			}
			catch (Exception $e)
			{
				throw new TasksException($e->getMessage(), TasksException::TE_SQL_ERROR);
			}
		}

		$tasks = $this->prepareOrmData($tasks);

		$result = new CDBResult($dbResult);
		$result->InitFromArray($tasks);
		$result->InitNavStartVars($pageSize, false, $page);
		$result->NavPageNomer = $page;
		$result->PAGEN = $page;
		$result->NavRecordCount = $cnt;
		$result->NavPageSize = $pageSize;
		$result->NavPageCount = ($pageSize > 0 && !is_null($cnt)) ? ceil($cnt / $pageSize) : null;
		$result->NavNum = is_null($navNum) ? null : $navNum + 1;

		// InitFromArray increased global $NavNum, which is responsible FOR ALL page navigation.
		// let's decrease it back
		global $NavNum;
		$NavNum--;

		return $result;
	}

	/**
	 * @throws InvalidGroupByException
	 * @throws TasksException
	 */
	private function getCountOrm(mixed $filter = [], mixed $params = [], mixed $group = []): CDBResult
	{
		$query = new TaskQuery($this->executorId);
		$query
			->setBehalfUser($this->userId)
			->setGroupBy($group)
			->setWhere($filter);

		if (
			isset($params['FILTER_PARAMS']['SEARCH_TASK_ONLY'])
			&& $params['FILTER_PARAMS']['SEARCH_TASK_ONLY'] = 'Y'
		)
		{
			$query->setParam('SEARCH_TASK_ONLY', true);
		}

		try
		{
			$list = Container::getInstance()->getTaskProvider();
			$count = $list->getCount($query);
			$dbResult = $list->getLastDbResult();
		}
		catch (Exception $e)
		{
			throw new TasksException($e->getMessage(), TasksException::TE_SQL_ERROR);
		}

		$result = new CDBResult($dbResult);
		$result->InitFromArray([
			['CNT' => $count],
		]);

		return $result;
	}

	private function makeFields(): self
	{
		global $DB;
		
		$helper = Application::getConnection()->getSqlHelper();
		$this->fields = [
			"ID" => "T.ID",
			"TITLE" => "T.TITLE",
			"DESCRIPTION" => "T.DESCRIPTION",
			"DESCRIPTION_IN_BBCODE" => "T.DESCRIPTION_IN_BBCODE",
			"DECLINE_REASON" => "T.DECLINE_REASON",
			"PRIORITY" => "T.PRIORITY",
			// 1) deadline in the past, real status is not STATE_SUPPOSEDLY_COMPLETED and not STATE_COMPLETED and
			// (not STATE_DECLINED or responsible is not me (user))
			// 2) viewed by no one(?) and created not by me (user) and (STATE_NEW or STATE_PENDING)
			"STATUS" => "
				CASE
					WHEN
						T.DEADLINE < {$helper->addSecondsToDateTime($DB->CurrentTimeFunction(), Counter\Deadline::getDeadlineTimeLimit())}
						AND T.DEADLINE >= ". $DB->CurrentTimeFunction() ."
						AND T.STATUS != '4'
						AND T.STATUS != '5'
						AND (
							T.STATUS != '7'
							OR T.RESPONSIBLE_ID != ". $this->userId ."
						)
					THEN
						'-3'
					WHEN
						T.DEADLINE < ". $DB->CurrentTimeFunction() ." AND T.STATUS != '4' AND T.STATUS != '5' AND (T.STATUS != '7' OR T.RESPONSIBLE_ID != ". $this->userId .")
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
			"DATE_START" => $DB->DateToCharFunction("T.DATE_START"),
			"DURATION_FACT" => "(SELECT SUM(TE.MINUTES) FROM b_tasks_elapsed_time TE WHERE TE.TASK_ID = T.ID GROUP BY TE.TASK_ID)",
			"TIME_ESTIMATE" => "T.TIME_ESTIMATE",
			"TIME_SPENT_IN_LOGS" => "(SELECT SUM(TE.SECONDS) FROM b_tasks_elapsed_time TE WHERE TE.TASK_ID = T.ID GROUP BY TE.TASK_ID)",
			"REPLICATE" => "T.REPLICATE",
			"DEADLINE" => $DB->DateToCharFunction("T.DEADLINE"),
			"DEADLINE_ORIG" => "T.DEADLINE",
			"START_DATE_PLAN" => $DB->DateToCharFunction("T.START_DATE_PLAN"),
			"END_DATE_PLAN" => $DB->DateToCharFunction("T.END_DATE_PLAN"),
			"CREATED_BY" => "T.CREATED_BY",
			"CREATED_BY_NAME" => "CU.NAME",
			"CREATED_BY_LAST_NAME" => "CU.LAST_NAME",
			"CREATED_BY_SECOND_NAME" => "CU.SECOND_NAME",
			"CREATED_BY_LOGIN" => "CU.LOGIN",
			"CREATED_BY_WORK_POSITION" => "CU.WORK_POSITION",
			"CREATED_BY_PHOTO" => "CU.PERSONAL_PHOTO",
			"CREATED_DATE" => $DB->DateToCharFunction("T.CREATED_DATE"),
			"CHANGED_BY" => "T.CHANGED_BY",
			"CHANGED_DATE" => $DB->DateToCharFunction("T.CHANGED_DATE"),
			"STATUS_CHANGED_BY" => "T.CHANGED_BY",
			"STATUS_CHANGED_DATE" =>
				'CASE WHEN T.STATUS_CHANGED_DATE IS NULL THEN '
				. $DB->DateToCharFunction("T.CHANGED_DATE")
				. ' ELSE '
				. $DB->DateToCharFunction("T.STATUS_CHANGED_DATE")
				. ' END ',
			"CLOSED_BY" => "T.CLOSED_BY",
			"CLOSED_DATE" => $DB->DateToCharFunction("T.CLOSED_DATE"),
			"ACTIVITY_DATE" => $DB->DateToCharFunction("T.ACTIVITY_DATE"),
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
			"SUBORDINATE" => ($strSql = CTasks::GetSubordinateSql('', $this->params)) ? "CASE WHEN EXISTS(".$strSql.") THEN 'Y' ELSE 'N' END" : "'N'",
			"EXCHANGE_MODIFIED" => "T.EXCHANGE_MODIFIED",
			"EXCHANGE_ID" => "T.EXCHANGE_ID",
			"OUTLOOK_VERSION" => "T.OUTLOOK_VERSION",
			"VIEWED_DATE" => $DB->DateToCharFunction("TV.VIEWED_DATE"),
			"DEADLINE_COUNTED" => "T.DEADLINE_COUNTED",
			"FORKED_BY_TEMPLATE_ID" => "T.FORKED_BY_TEMPLATE_ID",

			"FAVORITE" => "CASE WHEN FVT.TASK_ID IS NULL THEN 'N' ELSE 'Y' END",
			"SORTING" => "SRT.SORT",

			"DURATION_PLAN_SECONDS" => "T.DURATION_PLAN",
			"DURATION_TYPE_ALL" => "T.DURATION_TYPE",

			"DURATION_PLAN" => "
				case
					when
						T.DURATION_TYPE = '".TimeUnitType::MINUTE."' or T.DURATION_TYPE = '".TimeUnitType::HOUR."'
					then
						ROUND(T.DURATION_PLAN / 3600, 0)
					when
						T.DURATION_TYPE = '".TimeUnitType::DAY."' or T.DURATION_TYPE = '' or T.DURATION_TYPE is null
					then
						ROUND(T.DURATION_PLAN / 86400, 0)
					else
						T.DURATION_PLAN
				end
			",
			"DURATION_TYPE" => "
				case
					when
						T.DURATION_TYPE = '".TimeUnitType::MINUTE."'
					then
						'".TimeUnitType::HOUR."'
					else
						T.DURATION_TYPE
				end
			",
			"SCENARIO_NAME" => "SCR.SCENARIO",
			'IS_REGULAR' => 'IS_REGULAR',
			'FLOW_ID' => 'FLOW_ID',
		];

		if ($this->userId)
		{
			$this->fields['IS_MUTED'] = UserOption::getSelectSql($this->userId, UserOption\Option::MUTED);
			$this->fields['IS_PINNED'] = UserOption::getSelectSql($this->userId, UserOption\Option::PINNED);
			$this->fields['IS_PINNED_IN_GROUP'] = UserOption::getSelectSql($this->userId, UserOption\Option::PINNED_IN_GROUP);
		}

		return $this;
	}

	/**
	 * @throws CTaskAssertException
	 */
	private function configure(
		mixed $filter = [],
		mixed $params = [],
	): void
	{
		$this->params = $params;

		if (!is_array($this->params))
		{
			$this->nPageTop = $this->params;
			$this->params = false;
		}
		elseif (isset($this->params['nPageTop']))
		{
			$this->nPageTop = $this->params['nPageTop'];
		}

		// First level logic MUST be 'AND', because of backward compatibility
		if (isset($filter['::LOGIC']) && $filter['::::LOGIC'] !== 'AND')
		{
			throw new CTaskAssertException();
		}

		$this->setUserId();
	}

	private function setUserId(): void
	{
		$this->executorId = User::getId();
		if (
			is_array($this->params)
			&& array_key_exists('USER_ID', $this->params)
			&& ($this->params['USER_ID'] > 0)
		)
		{
			$this->executorId = (int)$this->params['USER_ID'];
		}

		$this->userId = $this->executorId;
		if (
			is_array($this->params)
			&& array_key_exists('TARGET_USER_ID', $this->params)
			&& ($this->params['TARGET_USER_ID'] > 0)
		)
		{
			$this->userId = (int)$this->params['TARGET_USER_ID'];
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

	private function isLimitQuery(): bool
	{
		if (
			is_array($this->params)
			&& array_key_exists('NAV_PARAMS', $this->params)
			&& is_array($this->params['NAV_PARAMS'])
		)
		{
			if (
				array_key_exists('__calculateTotalCount', $this->params['NAV_PARAMS'])
				&& $this->params['NAV_PARAMS']['__calculateTotalCount'] === false
			)
			{
				return false;
			}

			if ((int)($this->params['NAV_PARAMS']['nTopCount'] ?? 0)> 0)
			{
				return false;
			}

			if (is_numeric($this->nPageTop))
			{
				return false;
			}

			if (
				array_key_exists('nPageSize', $this->params['NAV_PARAMS'])
				&& array_key_exists('iNumPage', $this->params['NAV_PARAMS'])
				&& !array_key_exists('getTotalCount', $this->params['NAV_PARAMS'])
			)
			{
				return false;
			}

			return true;
		}

		return false;
	}

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
}
