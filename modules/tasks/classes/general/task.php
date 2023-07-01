<?php
/**
 * Bitrix Framework
 *
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @global $USER_FIELD_MANAGER CUserTypeManager
 * @global $APPLICATION CMain
 *
 * @deprecated
 */
global $USER_FIELD_MANAGER;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Control\Tag;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Task\FavoriteTable;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Internals\Task\SortingTable;
use Bitrix\Tasks\Internals\Task\UserOptionTable;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Kanban\TaskStageTable;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Internal\ItemTable;
use Bitrix\Tasks\Util\Replicator;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Util\Db;

class CTasks
{
	//Task statuses: 1 - New, 2 - Pending, 3 - In Progress, 4 - Supposedly completed, 5 - Completed, 6 - Deferred, 7 - Declined
	// todo: using statuses in the way "-2, -1" is a bad idea. its better to have separate (probably runtime) fields called "viewed" and "expired"
	// todo: and then, if you want to know if the task is "virgin new", just apply filter array('=VIEWED' => false, '=STATUS' => 2/*or 1*/)
	const METASTATE_VIRGIN_NEW = -2; // unseen
	const METASTATE_EXPIRED = -1;
	const METASTATE_EXPIRED_SOON = -3;
	const STATE_NEW = 1;
	const STATE_PENDING = 2;    // Pending === Accepted
	const STATE_IN_PROGRESS = 3;
	const STATE_SUPPOSEDLY_COMPLETED = 4;
	const STATE_COMPLETED = 5;
	const STATE_DEFERRED = 6;
	const STATE_DECLINED = 7;

	const PRIORITY_LOW = 0;
	const PRIORITY_AVERAGE = 1;
	const PRIORITY_HIGH = 2;

	const MARK_POSITIVE = 'P';
	const MARK_NEGATIVE = 'N';

	const TIME_UNIT_TYPE_SECOND = 'secs';
	const TIME_UNIT_TYPE_MINUTE = 'mins';
	const TIME_UNIT_TYPE_HOUR = 'hours';
	const TIME_UNIT_TYPE_DAY = 'days';
	const TIME_UNIT_TYPE_WEEK = 'weeks';
	const TIME_UNIT_TYPE_MONTH = 'monts'; // 5 chars max :)
	const TIME_UNIT_TYPE_YEAR = 'years';

	const MAX_INT = 2147483647;

	const CACHE_TASKS_COUNT = 'CACHE_TASKS_COUNT_KEY';
	const CACHE_TASKS_COUNT_DIR_NAME = '/bx_tasks_count';

	private $_errors = [];
	private $lastOperationResultData = [];

	private static $cacheIds = [];
	private static $cacheClearEnabled = true;

	function GetErrors()
	{
		return $this->_errors;
	}

	public function getLastOperationResultData()
	{
		return $this->lastOperationResultData;
	}

	/**
	 * @param $arFields
	 * @param $arParams
	 * @return false|int
	 *
	 * @deprecated since tasks 22.700.0
	 * Use (new Tasks\Control\Task($userId))->add($fields) instead
	 */
	public function Add($arFields, $arParams = [])
	{
		$userId = null;
		if (
			isset($arParams['USER_ID'])
			&& (int)$arParams['USER_ID'] > 0
		)
		{
			$userId = (int)$arParams['USER_ID'];
		}

		if ($userId === null)
		{
			$userId = User::getId();
			if (!$userId)
			{
				$userId = 1; // nasty, but for compatibility :(
			}
		}

		$handler = new \Bitrix\Tasks\Control\Task($userId);

		$correctDatePlan = ($arParams['CORRECT_DATE_PLAN'] ?? true);
		if ($correctDatePlan !== 'N' && $correctDatePlan !== false)
		{
			$handler->withCorrectDatePlan();
		}

		$spawnedByAgent = ($arParams['SPAWNED_BY_AGENT'] ?? false);
		if ($spawnedByAgent === 'Y' || $spawnedByAgent === true)
		{
			$handler->fromAgent();
		}

		$checkRightsOnFiles = ($arParams['CHECK_RIGHTS_ON_FILES'] ?? false);
		if ($checkRightsOnFiles === 'Y' || $checkRightsOnFiles === true)
		{
			$handler->withFilesRights();
		}

		$cloneDiskFileAttachment = ($arParams['CLONE_DISK_FILE_ATTACHMENT'] ?? false);
		if ($cloneDiskFileAttachment === 'Y' || $cloneDiskFileAttachment === true)
		{
			$handler->withCloneAttachments();
		}

		if (isset($arFields['META::EVENT_GUID']))
		{
			$handler->setEventGuid($arFields['META::EVENT_GUID']);
			unset($arFields['META::EVENT_GUID']);
		}

		try
		{
			$task = $handler->add($arFields);
		}
		catch (\Bitrix\Tasks\Control\Exception\TaskAddException $e)
		{
			$msg = $e->getMessage();
			if (!$msg)
			{
				$msg = GetMessage("TASKS_UNKNOWN_ADD_ERROR");
			}
			$this->_errors[] = [
				"text" => $msg,
				"id" => "ERROR_UNKNOWN_ADD_TASK_ERROR",
			];
			return false;
		}
		catch (\Exception $e)
		{
			$this->_errors[] = [
				"text" => $e->getMessage(),
				"id" => "ERROR_UNKNOWN_ADD_TASK_ERROR",
			];
			return false;
		}

		return $task->getId();
	}

	public function Update($taskId, $arFields, $arParams = [
		'CORRECT_DATE_PLAN_DEPENDENT_TASKS' => true,
		'CORRECT_DATE_PLAN' => true,
		'THROTTLE_MESSAGES' => false,
	])
	{
		$taskId = (int)$taskId;
		if ($taskId < 1)
		{
			return false;
		}

		$userId = null;
		if (
			isset($arParams['USER_ID'])
			&& ($arParams['USER_ID'] > 0)
		)
		{
			$userId = (int)$arParams['USER_ID'];
		}
		if ($userId === null)
		{
			$userId = User::getId();
			if (!$userId)
			{
				$userId = 1;
			}
		}

		if (!isset($arParams['THROTTLE_MESSAGES']))
		{
			$arParams['THROTTLE_MESSAGES'] = false;
		}

		$handler = new \Bitrix\Tasks\Control\Task($userId);
		$handler->setByPassParams($arParams);

		if (isset($arFields['META::EVENT_GUID']))
		{
			$handler->setEventGuid($arFields['META::EVENT_GUID']);
			unset($arFields['META::EVENT_GUID']);
		}

		if (
			!isset($arParams['CORRECT_DATE_PLAN'])
			|| (
				$arParams['CORRECT_DATE_PLAN'] !== false
				&& $arParams['CORRECT_DATE_PLAN'] !== 'N'
			)
		)
		{
			$handler->withCorrectDatePlan();
		}

		if (
			!isset($arParams['CORRECT_DATE_PLAN_DEPENDENT_TASKS'])
			|| (
				$arParams['CORRECT_DATE_PLAN_DEPENDENT_TASKS'] !== false
				&& $arParams['CORRECT_DATE_PLAN_DEPENDENT_TASKS'] !== 'N'
			)
		)
		{
			$handler->withCorrectDatePlanDependent();
		}
		if (
			!array_key_exists('AUTO_CLOSE', $arParams)
			|| $arParams['AUTO_CLOSE'] !== false
		)
		{
			$handler->withAutoclose();
		}
		if (
			isset($arParams['SKIP_NOTIFICATION'])
			&& $arParams['SKIP_NOTIFICATION']
		)
		{
			$handler->withSkipNotifications();
		}
		if (array_key_exists('FIELDS_FOR_COMMENTS', $arParams))
		{
			$handler->withSkipComments();
		}
		if (
			array_key_exists('SEND_UPDATE_PULL_EVENT', $arParams)
			&& !$arParams['SEND_UPDATE_PULL_EVENT']
		)
		{
			$handler->withSkipPush();
		}

		try
		{
			$handler->update($taskId, $arFields);
			$this->lastOperationResultData = $handler->getLegacyOperationResultData();
		}
		catch (\Bitrix\Tasks\Control\Exception\TaskUpdateException $e)
		{
			$this->_errors[] = [
				"text" => $e->getMessage(),
				"id" => "ERROR_UNKNOWN_UPDATE_TASK_ERROR",
			];

			return false;
		}
		catch (\Exception $e)
		{
			$this->_errors[] = [
				"text" => GetMessage("TASKS_UNKNOWN_UPDATE_ERROR"),
				"id" => "ERROR_UNKNOWN_UPDATE_TASK_ERROR",
			];

			return false;
		}

		return true;
	}

	public static function checkCacheAutoClearEnabled()
	{
		return static::$cacheClearEnabled;
	}

	public static function disableCacheAutoClear()
	{
		if (!static::$cacheClearEnabled)
		{
			return false;
		}

		static::$cacheClearEnabled = false;

		return true;
	}

	public static function enableCacheAutoClear($clearNow = true)
	{
		static::$cacheClearEnabled = true;

		if ($clearNow)
		{
			static::clearCache();
		}
	}

	private static function clearCache()
	{
		if (!static::$cacheClearEnabled)
		{
			return;
		}

		global $CACHE_MANAGER;

		if (!empty(static::$cacheIds))
		{
			foreach (static::$cacheIds as $id => $void)
			{
				$CACHE_MANAGER->ClearByTag($id);
			}

			static::$cacheIds = [];
		}
	}

	/**
	 * @param $taskId
	 * @param $parameters
	 * @return bool
	 * @throws CTaskAssertException
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 *
	 * @deprecated since tasks 22.700.0
	 * use (new \Bitrix\Tasks\Control\Task($userId))->delete($taskId) instead
	 */
	public static function Delete($taskId, $parameters = [])
	{
		$userId = User::getId();
		if (!$userId)
		{
			$userId = User::getAdminId();
		}

		$handler = new \Bitrix\Tasks\Control\Task($userId);

		if (isset($parameters['META::EVENT_GUID']))
		{
			$eventGuid = $parameters['META::EVENT_GUID'];
			unset($parameters['META::EVENT_GUID']);
		}
		else
		{
			$eventGuid = sha1(uniqid('AUTOGUID', true));
		}
		$handler->setEventGuid($eventGuid);

		if (
			isset($parameters['skipExchangeSync'])
			&& (
				$parameters['skipExchangeSync'] === 'Y'
				|| $parameters['skipExchangeSync'] === true
			)
		)
		{
			$handler->withSkipExchangeSync();
		}

		return $handler->delete((int)$taskId);
	}

	protected static function GetSqlByFilter($arFilter, $userID, $sAliasPrefix, $bGetZombie,
		$bMembersTableJoined = false, $params = [])
	{
		global $DB;

		$bFullJoin = null;

		if (!is_array($arFilter))
		{
			throw new TasksException(
				'GetSqlByFilter: expected array, but something other given: ' . var_export($arFilter, true)
			);
		}

		if (
			array_key_exists('ONLY_ROOT_TASKS', $arFilter)
			&& $arFilter['ONLY_ROOT_TASKS'] === 'Y'
			&& (
				array_key_exists('FULL_SEARCH_INDEX', $arFilter)
				|| array_key_exists('COMMENT_SEARCH_INDEX', $arFilter)
			)
		)
		{
			unset($arFilter['ONLY_ROOT_TASKS']);
		}

		$logicStr = ' AND ';

		if (isset($arFilter['::LOGIC']))
		{
			switch ($arFilter['::LOGIC'])
			{
				case 'AND':
					$logicStr = ' AND ';
					break;

				case 'OR':
					$logicStr = ' OR ';
					break;

				default:
					throw new TasksException('Unknown logic in filter');
			}
		}

		$arSqlSearch = [];

		$targetUserId = isset($params['TARGET_USER_ID']) ? $params['TARGET_USER_ID'] : $userID;

		foreach ($arFilter as $key => $val)
		{
			// Skip meta-key
			if ($key === '::LOGIC')
			{
				continue;
			}

			// Skip markers
			if ($key === '::MARKERS')
			{
				continue;
			}

			// Subfilter?
			if (static::isSubFilterKey($key))
			{
				$arSqlSearch[] = self::GetSqlByFilter($val, $userID, $sAliasPrefix, $bGetZombie, $bMembersTableJoined,
					$params);
				continue;
			}

			$key = ltrim($key);

			// This type of operations should be processed in special way
			// Fields like "META:DEADLINE_TS" will be replaced to "DEADLINE"
			if (mb_substr($key, -3) === '_TS')
			{
				$arSqlSearch = array_merge(
					$arSqlSearch,
					self::getSqlForTimestamps($key, $val, $userID, $sAliasPrefix, $bGetZombie)
				);

				continue;
			}

			$res = CTasks::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = mb_strtoupper($key);

			switch ($key)
			{
				case 'META::ID_OR_NAME':
					$arSqlSearch[] = " (" .
						$sAliasPrefix .
						"T.ID = '" .
						intval($val) .
						"' OR (UPPER(" .
						$sAliasPrefix .
						"T.TITLE) LIKE UPPER('%" .
						$DB->ForSqlLike($val) .
						"%')) ) ";
					break;

				//case "DURATION_PLAN": // temporal
				case "PARENT_ID":
				case "GROUP_ID":
				case "STATUS_CHANGED_BY":
				case "FORUM_TOPIC_ID":
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix . "T." . $key,
						$val,
						"number",
						$bFullJoin,
						$cOperationType
					);
					break;

				case "ID":
				case "PRIORITY":
				case "CREATED_BY":
				case "RESPONSIBLE_ID":
				case "STAGE_ID":
				case 'TIME_ESTIMATE':
				case 'FORKED_BY_TEMPLATE_ID':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix . "T." . $key,
						$val,
						"number_wo_nulls",
						$bFullJoin,
						$cOperationType
					);
					break;

				case "REFERENCE:RESPONSIBLE_ID":
					$arSqlSearch[] = CTasks::FilterCreate(
						"{$sAliasPrefix}T.RESPONSIBLE_ID",
						"{$sAliasPrefix}T.{$val}",
						'reference',
						$bFullJoin,
						$cOperationType
					);
					break;

				case "REFERENCE:START_DATE_PLAN":
					$key = 'START_DATE_PLAN';
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix . "T." . $key,
						$val,
						'reference',
						$bFullJoin,
						$cOperationType
					);
					break;

				case 'META:GROUP_ID_IS_NULL_OR_ZERO':
					$key = 'GROUP_ID';
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix . "T." . $key,
						$val,
						"null_or_zero",
						$bFullJoin,
						$cOperationType,
						false
					);
					break;

				case 'META:PARENT_ID_OR_NULL':
					if ((array)$val)
					{
						$arSqlSearch[] = '(T.PARENT_ID IN ('
							. join(', ', array_map('intval', (array)$val))
							. ') OR T.PARENT_ID IS NULL)';
					}
					break;

				case "CHANGED_BY":
					$arSqlSearch[] = CTasks::FilterCreate(
						"CASE WHEN " .
						$sAliasPrefix .
						"T." .
						$key .
						" IS NULL THEN " .
						$sAliasPrefix .
						"T.CREATED_BY ELSE " .
						$sAliasPrefix .
						"T." .
						$key .
						" END",
						$val,
						"number",
						$bFullJoin,
						$cOperationType
					);
					break;

				case 'GUID':
				case 'TITLE':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix . "T." . $key,
						$val,
						"string",
						$bFullJoin,
						$cOperationType
					);
					break;

				case 'FULL_SEARCH_INDEX':
				case 'COMMENT_SEARCH_INDEX':
					$isComment = $key === 'COMMENT_SEARCH_INDEX';
					$tableName = SearchIndexTable::getTableName();
					$tableAlias = $sAliasPrefix . ($isComment ? 'TSIC' : 'TSIF');
					$columnName = "{$tableAlias}.SEARCH_INDEX";
					$where = self::FilterCreate($columnName, $val, 'fulltext', $bFullJoin, $cOperationType);

					$filterParams = ($params['FILTER_PARAMS'] ?? null);
					$searchTaskOnly =
						isset($filterParams['SEARCH_TASK_ONLY'])
						&& $filterParams['SEARCH_TASK_ONLY'] === 'Y'
					;
					$searchCommentOnly =
						isset($filterParams['SEARCH_COMMENT_ONLY'])
						&& $filterParams['SEARCH_COMMENT_ONLY'] === 'Y'
					;

					$join = "";
					if ($searchTaskOnly)
					{
						$join = "AND {$tableAlias}.MESSAGE_ID = 0";
					}
					elseif ($isComment || $searchCommentOnly)
					{
						$join = "AND {$tableAlias}.MESSAGE_ID != 0";
					}

					$innerQuery = "
						"
						. "SELECT {$sAliasPrefix}ST.ID"
						. "
						"
						. "FROM b_tasks {$sAliasPrefix}ST"
						. "
						"
						. "INNER JOIN {$tableName} {$tableAlias} ON {$tableAlias}.TASK_ID = {$sAliasPrefix}ST.ID {$join}"
						. "
						"
						. "WHERE {$where}";
					$arSqlSearch[] = "({$sAliasPrefix}T.ID IN ({$innerQuery}))";
					break;

				case 'TAG':
					if (!is_array($val))
					{
						$val = [$val];
					}
					$tags = array_filter(
						array_map(
							static function ($tag) use ($DB) {
								return ($tag ? $DB->ForSql($tag) : false);
							},
							$val
						)
					);
					$tagsCount = count($tags);
					if ($tagsCount)
					{
						$tags = "('" . implode("','", $tags) . "')";
						$arSqlSearch[] = trim("
							{$sAliasPrefix}T.ID IN (
								SELECT TT.TASK_ID
								FROM (
									SELECT TASK_ID, COUNT(TASK_ID) AS CNT
									FROM b_tasks_label
									INNER JOIN b_tasks_task_tag bttt on bttt.TAG_ID = b_tasks_label.ID
									WHERE NAME IN {$tags}
									GROUP BY TASK_ID
									HAVING CNT = {$tagsCount}
								) TT
							)
						");
					}
					break;
				case 'TAG_ID':
					if (!is_array($val))
					{
						$val = [$val];
					}
					$tags = array_filter(
						array_map(
							static function ($tag) use ($DB) {
								return ($tag ? $DB->ForSql($tag) : false);
							},
							$val
						)
					);
					$tagsCount = count($tags);
					if ($tagsCount)
					{
						$tags = "('" . implode("','", $tags) . "')";
						$arSqlSearch[] = "
						{$sAliasPrefix}T.ID IN (
								SELECT TASK_TAGS.ID FROM b_tasks AS TASK_TAGS
								INNER JOIN b_tasks_task_tag BTT on BTT.TASK_ID = TASK_TAGS.ID
								WHERE BTT.TAG_ID IN $tags
						)
					";
					}
					break;

				case 'REAL_STATUS':
					$val = self::removeStatusValueForActiveSprint($val);
					$realStatusFilter = CTasks::FilterCreate(
						$sAliasPrefix . "T.STATUS",
						$val,
						"number",
						$bFullJoin,
						$cOperationType
					);
					if (self::containCompletedInActiveSprintStatus($arFilter))
					{
						$realStatusFilter = $realStatusFilter
							? $realStatusFilter . " OR ({$sAliasPrefix}TSI.ID IS NOT NULL AND (T.STATUS = '5'))"
							: "({$sAliasPrefix}TSI.ID IS NOT NULL AND (T.STATUS = '5'))";
					}
					$arSqlSearch[] = $realStatusFilter;
					break;

				case 'DEADLINE_COUNTED':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix . "T.DEADLINE_COUNTED",
						$val,
						"number_wo_nulls",
						$bFullJoin,
						$cOperationType
					);
					break;

				case 'VIEWED':
					$arSqlSearch[] = CTasks::FilterCreate(
						"
						CASE
							WHEN
								" . $sAliasPrefix . "TV.USER_ID IS NULL
								AND
								(" . $sAliasPrefix . "T.STATUS = 1 OR " . $sAliasPrefix . "T.STATUS = 2)
							THEN
								'0'
							ELSE
								'1'
						END
					",
						$val,
						"number",
						$bFullJoin,
						$cOperationType
					);
					break;

				case "STATUS_EXPIRED": // expired: deadline in past and

					$arSqlSearch[] = ($cOperationType == 'N' ? 'not' : '') .
						"(" .
						$sAliasPrefix .
						"T.DEADLINE < " .
						$DB->CurrentTimeFunction() .
						" AND " .
						$sAliasPrefix .
						"T.STATUS != '4' AND " .
						$sAliasPrefix .
						"T.STATUS != '5' AND (" .
						$sAliasPrefix .
						"T.STATUS != '7' OR " .
						$sAliasPrefix .
						"T.RESPONSIBLE_ID != " .
						$userID .
						"))";

					break;

				case "STATUS_NEW": // viewed by a specified user + status is either new or pending

					$arSqlSearch[] = ($cOperationType == 'N' ? 'not' : '') . "(

						" . $sAliasPrefix . "TV.USER_ID IS NULL
						AND
						" . $sAliasPrefix . "T.CREATED_BY != " . $userID . "
						AND
						(" . $sAliasPrefix . "T.STATUS = 1 OR " . $sAliasPrefix . "T.STATUS = 2)

					)";
					$bFullJoin = true; // join TV

					break;

				case "STATUS":
					$arSqlSearch[] = CTasks::FilterCreate(
						"
						CASE
							WHEN
								" .
						$sAliasPrefix .
						"T.DEADLINE < DATE_ADD(" .
						$DB->CurrentTimeFunction() .
						", INTERVAL " .
						Counter\Deadline::getDeadlineTimeLimit() .
						" SECOND)
								AND " .
						$sAliasPrefix .
						"T.DEADLINE >= " .
						$DB->CurrentTimeFunction() .
						"
								AND " .
						$sAliasPrefix .
						"T.STATUS != '4'
								AND " .
						$sAliasPrefix .
						"T.STATUS != '5'
								AND (
									" .
						$sAliasPrefix .
						"T.STATUS != '7'
									OR " .
						$sAliasPrefix .
						"T.RESPONSIBLE_ID != " .
						intval($userID) .
						"
								)
							THEN
								'-3'
							WHEN
								" .
						$sAliasPrefix .
						"T.DEADLINE < " .
						$DB->CurrentTimeFunction() .
						" AND " .
						$sAliasPrefix .
						"T.STATUS != '4' AND " .
						$sAliasPrefix .
						"T.STATUS != '5' AND (" .
						$sAliasPrefix .
						"T.STATUS != '7' OR " .
						$sAliasPrefix .
						"T.RESPONSIBLE_ID != " .
						$userID .
						")
							THEN
								'-1'
							WHEN
								" .
						$sAliasPrefix .
						"TV.USER_ID IS NULL
								AND
								" .
						$sAliasPrefix .
						"T.CREATED_BY != " .
						$userID .
						"
								AND
								(" .
						$sAliasPrefix .
						"T.STATUS = 1 OR " .
						$sAliasPrefix .
						"T.STATUS = 2)
							THEN
								'-2'
							ELSE
								" .
						$sAliasPrefix .
						"T.STATUS
						END
					",
						$val,
						"number",
						$bFullJoin,
						$cOperationType
					);

					break;

				case 'MARK':
				case 'XML_ID':
				case 'SITE_ID':
				case 'ADD_IN_REPORT':
				case 'ALLOW_TIME_TRACKING':
				case 'ALLOW_CHANGE_DEADLINE':
				case 'MATCH_WORK_TIME':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix . "T." . $key,
						$val,
						"string_equal",
						$bFullJoin,
						$cOperationType
					);
					break;

				case "END_DATE_PLAN":
				case "START_DATE_PLAN":
				case "DATE_START":
				case "DEADLINE":
				case "CREATED_DATE":
				case "CLOSED_DATE":
					if (($val === false) || ($val === ''))
					{
						$arSqlSearch[] = CTasks::FilterCreate(
							$sAliasPrefix . "T." . $key,
							$val,
							"date",
							$bFullJoin,
							$cOperationType,
							$bSkipEmpty = false
						);
					}
					else
					{
						$arSqlSearch[] = CTasks::FilterCreate(
							$sAliasPrefix . "T." . $key,
							Db::charToDateFunction($val),
							"date",
							$bFullJoin,
							$cOperationType
						);
					}
					break;

				case "CHANGED_DATE":
				case "ACTIVITY_DATE":
					$fname = "CASE WHEN {$sAliasPrefix}T.{$key} IS NULL"
						. " THEN {$sAliasPrefix}T.CREATED_DATE"
						. " ELSE {$sAliasPrefix}T.{$key} END";
					$arSqlSearch[] = CTasks::FilterCreate(
						$fname,
						Db::charToDateFunction($val),
						"date",
						$bFullJoin,
						$cOperationType
					);
					break;

				case "ACCOMPLICE":
					if (!is_array($val))
					{
						$val = [$val];
					}

					$val = array_filter($val);

					$arConds = [];

					if ($bMembersTableJoined)
					{
						if ($cOperationType !== 'N')
						{
							foreach ($val as $id)
							{
								$arConds[] = "(" . $sAliasPrefix . "TM.USER_ID = '" . intval($id) . "')";
							}

							if (!empty($arConds))
							{
								$arSqlSearch[] = '('
									. $sAliasPrefix
									. "TM.TYPE = 'A' AND ("
									. implode(" OR ", $arConds)
									. '))';
							}
						}
						else
						{
							foreach ($val as $id)
							{
								$arConds[] = "(" . $sAliasPrefix . "TM.USER_ID != '" . intval($id) . "')";
							}

							if (!empty($arConds))
							{
								$arSqlSearch[] = '(' .
									$sAliasPrefix .
									"TM.TYPE = 'A' AND (" .
									implode(" AND ", $arConds) .
									'))';
							}
						}
					}
					else
					{
						foreach ($val as $id)
						{
							$arConds[] = "(" . $sAliasPrefix . "TM.USER_ID = '" . intval($id) . "')";
						}

						if (!empty($arConds))
						{
							$arSqlSearch[] = ($cOperationType !== 'N' ? 'EXISTS' : 'NOT EXISTS') . "(
								SELECT
									'x'
								FROM
									b_tasks_member " . $sAliasPrefix . "TM
								WHERE
									(" . implode(" OR ", $arConds) . ")
								AND
									" . $sAliasPrefix . "TM.TASK_ID = " . $sAliasPrefix . "T.ID
								AND
									" . $sAliasPrefix . "TM.TYPE = 'A'
							)";
						}
					}
					break;

				case "PERIOD":
				case "ACTIVE":
					if ($val["START"] || $val["END"])
					{
						$strDateStart = $strDateEnd = false;

						if (MakeTimeStamp($val['START']) > 0)
						{
							$strDateStart = Db::charToDateFunction(
								$DB->ForSql(
									CDatabase::FormatDate(
										$val['START'],
										FORMAT_DATETIME
									)
								)
							);
						}

						if (MakeTimeStamp($val['END']))
						{
							$strDateEnd = Db::charToDateFunction(
								$DB->ForSql(
									CDatabase::FormatDate(
										$val['END'],
										FORMAT_DATETIME
									)
								)
							);
						}

						if (($strDateStart !== false) && ($strDateEnd !== false))
						{
							$arSqlSearch[] = "(
									(T.CREATED_DATE >= $strDateStart AND T.CLOSED_DATE <= $strDateEnd)
								OR
									(T.CHANGED_DATE >= $strDateStart AND T.CHANGED_DATE <= $strDateEnd)
								OR
									(T.CREATED_DATE <= $strDateStart AND T.CLOSED_DATE IS NULL)
								)";
						}
						elseif (($strDateStart !== false) && ($strDateEnd === false))
						{
							$arSqlSearch[] = "(
									(T.CREATED_DATE >= $strDateStart)
								OR
									(T.CHANGED_DATE >= $strDateStart)
								)";
						}
						elseif (($strDateStart === false) && ($strDateEnd !== false))
						{
							$arSqlSearch[] = "(
									(T.CLOSED_DATE <= $strDateEnd)
									(T.CHANGED_DATE <= $strDateEnd)
								)";
						}
					}
					break;

				case "AUDITOR":
					if (!is_array($val))
					{
						$val = [$val];
					}

					$val = array_filter($val);

					$arConds = [];

					if ($bMembersTableJoined)
					{
						if ($cOperationType !== 'N')
						{
							foreach ($val as $id)
							{
								$arConds[] = "(" . $sAliasPrefix . "TM.USER_ID = '" . intval($id) . "')";
							}

							if (!empty($arConds))
							{
								$arSqlSearch[] = '('
									. $sAliasPrefix
									. "TM.TYPE = 'U' AND ("
									. implode(" OR ", $arConds)
									. '))';
							}
						}
						else
						{
							foreach ($val as $id)
							{
								$arConds[] = "(" . $sAliasPrefix . "TM.USER_ID != '" . intval($id) . "')";
							}

							if (!empty($arConds))
							{
								$arSqlSearch[] = '(' .
									$sAliasPrefix .
									"TM.TYPE = 'U' AND (" .
									implode(" AND ", $arConds) .
									'))';
							}
						}
					}
					else
					{
						foreach ($val as $id)
						{
							$arConds[] = "(" . $sAliasPrefix . "TM.USER_ID = '" . intval($id) . "')";
						}

						if (!empty($arConds))
						{
							$arSqlSearch[] = ($cOperationType !== 'N' ? 'EXISTS' : 'NOT EXISTS') . "(
								SELECT
									'x'
								FROM
									b_tasks_member " . $sAliasPrefix . "TM
								WHERE
									(" . implode(" OR ", $arConds) . ")
								AND
									" . $sAliasPrefix . "TM.TASK_ID = " . $sAliasPrefix . "T.ID
								AND
									" . $sAliasPrefix . "TM.TYPE = 'U'
							)";
						}
					}

					break;

				case "DOER":
					$val = intval($val);
					$arSqlSearch[] = "(
						" . $sAliasPrefix . "T.RESPONSIBLE_ID = " . $val . "
						OR
						EXISTS(
							SELECT 'x'
							FROM
								b_tasks_member " . $sAliasPrefix . "TM
							WHERE
								" . $sAliasPrefix . "TM.TASK_ID = " . $sAliasPrefix . "T.ID
								AND
								" . $sAliasPrefix . "TM.USER_ID = '" . $val . "'
								AND
								" . $sAliasPrefix . "TM.TYPE = 'A'
							)
						)";
					break;

				case "MEMBER":
					$val = intval($val);
					$arSqlSearch[] = "(
						" . $sAliasPrefix . "T.CREATED_BY = " . intval($val) . "
						OR
						" . $sAliasPrefix . "T.RESPONSIBLE_ID = " . intval($val) . "
						OR
						EXISTS(
							SELECT 'x' FROM b_tasks_member " . $sAliasPrefix . "TM
							WHERE
								" . $sAliasPrefix . "TM.TASK_ID = " . $sAliasPrefix . "T.ID
								AND
								" . $sAliasPrefix . "TM.USER_ID = '" . $val . "'
						)
					)";
					break;

				case "DEPENDS_ON":
					if (!is_array($val))
					{
						$val = [$val];
					}
					$arConds = [];
					foreach ($val as $id)
					{
						if ($id)
						{
							$arConds[] = "(" . $sAliasPrefix . "TD.TASK_ID = '" . intval($id) . "')";
						}
					}
					if (sizeof($arConds))
					{
						$arSqlSearch[] = "EXISTS(
							SELECT
								'x'
							FROM
								b_tasks_dependence " . $sAliasPrefix . "TD
							WHERE
								(" . implode(" OR ", $arConds) . ")
							AND
								" . $sAliasPrefix . "TD.DEPENDS_ON_ID = " . $sAliasPrefix . "T.ID
						)";
					}
					break;

				case "ONLY_ROOT_TASKS":
					if ($val === 'Y')
					{
						$arSqlSearch[] = "("
							. "{$sAliasPrefix}T.PARENT_ID IS NULL OR "
							. "{$sAliasPrefix}T.PARENT_ID = '0' OR "
							. "{$sAliasPrefix}T.PARENT_ID NOT IN ("
							. CTasks::GetRootSubQuery($arFilter, $sAliasPrefix, $params)
							. "))";
					}
					break;

				case "SUBORDINATE_TASKS":
					if ($val == "Y")
					{
						$arSubSqlSearch = [
							$sAliasPrefix . "T.CREATED_BY = " . $targetUserId,
							$sAliasPrefix . "T.RESPONSIBLE_ID = " . $targetUserId,
							"EXISTS(
								SELECT 'x'
								FROM
									b_tasks_member " . $sAliasPrefix . "TM
								WHERE
									" . $sAliasPrefix . "TM.TASK_ID = " . $sAliasPrefix . "T.ID
									AND
									" . $sAliasPrefix . "TM.USER_ID = " . $targetUserId . "
							)",
						];
						// subordinate check
						if ($strSql = CTasks::GetSubordinateSql($sAliasPrefix, ['USER_ID' => $targetUserId]))
						{
							$arSubSqlSearch[] = "EXISTS(" . $strSql . ")";
						}

						$arSqlSearch[] = "(" . implode(" OR ", $arSubSqlSearch) . ")";
					}
					break;

				case "OVERDUED":
					if ($val == "Y")
					{
						$arSqlSearch[] = $sAliasPrefix .
							"T.CLOSED_DATE IS NOT NULL AND " .
							$sAliasPrefix .
							"T.DEADLINE IS NOT NULL AND " .
							$sAliasPrefix .
							"T.DEADLINE < CLOSED_DATE";
					}
					break;

				case "SAME_GROUP_PARENT":
					if ($val == "Y" && !array_key_exists("ONLY_ROOT_TASKS", $arFilter))
					{
						$arSqlSearch[] = "EXISTS(
							SELECT
								'x'
							FROM
								b_tasks " . $sAliasPrefix . "PT
							WHERE
								" . $sAliasPrefix . "T.PARENT_ID = " . $sAliasPrefix . "PT.ID
							AND
								(" . $sAliasPrefix . "PT.GROUP_ID = " . $sAliasPrefix . "T.GROUP_ID
								OR (" . $sAliasPrefix . "PT.GROUP_ID IS NULL AND " . $sAliasPrefix . "T.GROUP_ID IS NULL)
								OR (" . $sAliasPrefix . "PT.GROUP_ID = 0 AND " . $sAliasPrefix . "T.GROUP_ID IS NULL)
								OR (" . $sAliasPrefix . "PT.GROUP_ID IS NULL AND " . $sAliasPrefix . "T.GROUP_ID = 0)
								)
						)";
					}
					break;

				case "DEPARTMENT_ID":
					if ($strSql = CTasks::GetDeparmentSql($val, $sAliasPrefix))
					{
						$arSqlSearch[] = "EXISTS(" . $strSql . ")";
					}
					break;

				case 'CHECK_PERMISSIONS':
					break;

				case 'FAVORITE':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix . "FVT.TASK_ID",
						$val,
						"left_existence",
						$bFullJoin,
						$cOperationType,
						false
					);
					break;

				case 'SORTING':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix . "SRT.TASK_ID",
						$val,
						"left_existence",
						$bFullJoin,
						$cOperationType,
						false
					);
					break;

				case 'STAGES_ID':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix . "STG.STAGE_ID",
						$val,
						"number",
						$bFullJoin,
						$cOperationType,
						false
					);
					break;

				case 'PROJECT_EXPIRED':
					$typesIn = array_merge(
						[Counter\CounterDictionary::COUNTER_GROUP_EXPIRED],
						Counter\CounterDictionary::MAP_MUTED_EXPIRED
					);
					$typesIn = "('" . implode("', '", $typesIn) . "')";
					$typesEx = "('" . implode("', '", Counter\CounterDictionary::MAP_EXPIRED) . "')";

					$arSqlSearch[] = "
						{$sAliasPrefix}TSC.ID IS NOT NULL
						AND {$sAliasPrefix}TSC.TYPE IN {$typesIn}
						AND NOT EXISTS (
							SELECT 1
							FROM b_tasks_scorer
							WHERE
								GROUP_ID = {$sAliasPrefix}T.GROUP_ID
								AND TASK_ID = {$sAliasPrefix}T.ID
								AND USER_ID = {$userID}
								AND TYPE IN {$typesEx}
						)
					";
					break;

				case 'PROJECT_NEW_COMMENTS':
					$typesIn = array_merge(
						[Counter\CounterDictionary::COUNTER_GROUP_COMMENTS],
						Counter\CounterDictionary::MAP_MUTED_COMMENTS
					);
					$typesIn = "('" . implode("', '", $typesIn) . "')";
					$typesEx = "('" . implode("', '", Counter\CounterDictionary::MAP_COMMENTS) . "')";

					$arSqlSearch[] = "
						{$sAliasPrefix}TSC.ID IS NOT NULL
						AND {$sAliasPrefix}TSC.TYPE IN {$typesIn}
						AND NOT EXISTS (
							SELECT 1
							FROM b_tasks_scorer
							WHERE
								GROUP_ID = {$sAliasPrefix}T.GROUP_ID
								AND TASK_ID = {$sAliasPrefix}T.ID
								AND USER_ID = {$userID}
								AND TYPE IN {$typesEx}
						)
					";
					break;

				case 'WITH_COMMENT_COUNTERS':
					$types = array_merge(
						array_values(Counter\CounterDictionary::MAP_COMMENTS),
						array_values(Counter\CounterDictionary::MAP_MUTED_COMMENTS)
					);
					$types = "('" . implode("', '", $types) . "')";
					$arSqlSearch[] = "{$sAliasPrefix}TSC.ID IS NOT NULL AND {$sAliasPrefix}TSC.TYPE IN {$types}";
					break;

				case 'WITH_NEW_COMMENTS':
					$expiredCommentType = Comment::TYPE_EXPIRED;
					$expiredSoonCommentType = Comment::TYPE_EXPIRED_SOON;
					$qr = "
						(
							({$sAliasPrefix}TV.VIEWED_DATE IS NOT NULL AND {$sAliasPrefix}FM.POST_DATE > {$sAliasPrefix}TV.VIEWED_DATE)
							OR ({$sAliasPrefix}TV.VIEWED_DATE IS NULL AND {$sAliasPrefix}FM.POST_DATE >= {$sAliasPrefix}T.CREATED_DATE)
						)
						AND {$sAliasPrefix}FM.NEW_TOPIC = 'N'
						AND (
							(
								{$sAliasPrefix}FM.AUTHOR_ID != {$targetUserId}
								AND (
									{$sAliasPrefix}BUF_FM.UF_TASK_COMMENT_TYPE IS NULL
									OR {$sAliasPrefix}BUF_FM.UF_TASK_COMMENT_TYPE != {$expiredCommentType}
								)
							)
							OR {$sAliasPrefix}BUF_FM.UF_TASK_COMMENT_TYPE = {$expiredSoonCommentType}
						)
					";

					$startCounterDate = \COption::GetOptionString("tasks", "tasksDropCommentCounters", null);
					if ($startCounterDate)
					{
						$qr .= " AND {$sAliasPrefix}FM.POST_DATE > '{$startCounterDate}'";
					}

					$arSqlSearch[] = $qr;
					break;

				case 'IS_MUTED':
				case 'IS_PINNED':
				case 'IS_PINNED_IN_GROUP':
					$optionMap = [
						'IS_MUTED' => UserOption\Option::MUTED,
						'IS_PINNED' => UserOption\Option::PINNED,
						'IS_PINNED_IN_GROUP' => UserOption\Option::PINNED_IN_GROUP,
					];
					$arSqlSearch[] = " {$sAliasPrefix}T.ID " . ($val === 'N' ? 'NOT ' : '')
						. UserOption::getFilterSql($targetUserId, $optionMap[$key], $sAliasPrefix);
					break;

				case 'SCENARIO_NAME':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix . "SCR.SCENARIO",
						$val,
						"string_equal",
						$bFullJoin,
						$cOperationType,
						false
					);
					break;

				default:
					if ((mb_strlen($key) >= 3) && (mb_substr($key, 0, 3) === 'UF_'))
					{
						;    // It's OK, this fields will be processed by UserFieldManager
					}
					else
					{
						$extraData = '';

						if (isset($_POST['action']) && ($_POST['action'] === 'group_action'))
						{
							$extraData = '; Extra data: <data0>' .
								serialize([$_POST['arFilter'], $_POST['action'], $arFilter]) .
								'</data0>';
						}
						else
						{
							$extraData = '; Extra data: <data1>' . serialize($arFilter) . '</data1>';
						}

						//CTaskAssert::logError('[0x6024749e] unexpected field in filter: ' . $key . $extraData);

						//throw new TasksException('Bad filter argument: '.$key, TasksException::TE_WRONG_ARGUMENTS);
					}
					break;
			}
		}

		$sql = implode(
			$logicStr,
			array_filter(
				$arSqlSearch
			)
		);

		if ($sql == '')
		{
			$sql = '1=1';
		}

		return ('(' . $sql . ')');
	}

	private static function removeStatusValueForActiveSprint($values)
	{
		if (is_array($values))
		{
			foreach ($values as $key => $value)
			{
				if ($value == EntityForm::STATE_COMPLETED_IN_ACTIVE_SPRINT)
				{
					unset($values[$key]);
				}
			}
		}

		return $values;
	}

	private static function containCompletedInActiveSprintStatus($filter): bool
	{
		$filterValues = static::getFilteredValues($filter);
		foreach ($filterValues as $filterValue)
		{
			if (array_key_exists('REAL_STATUS', $filterValue))
			{
				if (!is_array($filterValue['REAL_STATUS']))
				{
					$filterValue['REAL_STATUS'] = [$filterValue['REAL_STATUS']];
				}
				foreach ($filterValue['REAL_STATUS'] as $realStatus)
				{
					if ($realStatus == EntityForm::STATE_COMPLETED_IN_ACTIVE_SPRINT)
					{
						return true;
					}
				}
			}
		}

		return false;
	}

	private static function getSqlForTimestamps($key, $val, $userID, $sAliasPrefix, $bGetZombie)
	{
		static $ts = null;        // some fixed timestamp of "now" (for consistency)

		if ($ts === null)
		{
			$ts = CTasksPerHitOption::getHitTimestamp();
		}

		$bTzWasDisabled = !CTimeZone::enabled();

		if ($bTzWasDisabled)
		{
			CTimeZone::enable();
		}

		// Adjust UNIX TS to "Bitrix timestamp"
		$tzOffset = CTimeZone::getOffset();
		$ts += $tzOffset;

		if ($bTzWasDisabled)
		{
			CTimeZone::disable();
		}

		$arSqlSearch = [];

		$arFilter = [
			'::LOGIC' => 'AND',
		];

		$key = ltrim($key);

		$res = CTasks::MkOperationFilter($key);
		$fieldName = mb_substr($res["FIELD"], 5, -3);    // Cutoff prefix "META:" and suffix "_TS"
		$cOperationType = $res["OPERATION"];

		$operationSymbol = mb_substr($key, 0, -1 * mb_strlen($res["FIELD"]));

		if (mb_substr($cOperationType, 0, 1) !== '#')
		{
			switch ($operationSymbol)
			{
				case '<':
					$operationCode = CTaskFilterCtrl::OP_STRICTLY_LESS;
					break;

				case '>':
					$operationCode = CTaskFilterCtrl::OP_STRICTLY_GREATER;
					break;

				case '<=':
					$operationCode = CTaskFilterCtrl::OP_LESS_OR_EQUAL;
					break;

				case '>=':
					$operationCode = CTaskFilterCtrl::OP_GREATER_OR_EQUAL;
					break;

				case '!=':
					$operationCode = CTaskFilterCtrl::OP_NOT_EQUAL;
					break;

				case '':
				case '=':
					$operationCode = CTaskFilterCtrl::OP_EQUAL;
					break;

				default:
					CTaskAssert::log(
						'Unknown operation code: ' .
						$operationSymbol .
						'; $key = ' .
						$key .
						'; it will be silently ignored, incorrect results expected',
						CTaskAssert::ELL_ERROR    // errors, incorrect results expected
					);

					return ($arSqlSearch);
					break;
			}
		}
		else
		{
			$operationCode = (int)mb_substr($cOperationType, 1);
		}

		$date1 = $date2 = $cOperationType1 = $cOperationType2 = null;

		// sometimes we can have DAYS in $val, not TIMESTAMP
		if (
			$operationCode != CTaskFilterCtrl::OP_DATE_NEXT_DAYS
			&& $operationCode
			!= CTaskFilterCtrl::OP_DATE_LAST_DAYS
		)
		{
			$val += $tzOffset;
		}

		// Convert cOperationType to format accepted by self::FilterCreate
		switch ($operationCode)
		{
			case CTaskFilterCtrl::OP_EQUAL:
			case CTaskFilterCtrl::OP_DATE_TODAY:
			case CTaskFilterCtrl::OP_DATE_YESTERDAY:
			case CTaskFilterCtrl::OP_DATE_TOMORROW:
			case CTaskFilterCtrl::OP_DATE_CUR_WEEK:
			case CTaskFilterCtrl::OP_DATE_PREV_WEEK:
			case CTaskFilterCtrl::OP_DATE_NEXT_WEEK:
			case CTaskFilterCtrl::OP_DATE_CUR_MONTH:
			case CTaskFilterCtrl::OP_DATE_PREV_MONTH:
			case CTaskFilterCtrl::OP_DATE_NEXT_MONTH:
			case CTaskFilterCtrl::OP_DATE_NEXT_DAYS:
			case CTaskFilterCtrl::OP_DATE_LAST_DAYS:
				$cOperationType1 = '>=';
				$cOperationType2 = '<=';
				break;

			case CTaskFilterCtrl::OP_LESS_OR_EQUAL:
				$cOperationType1 = '<=';
				break;

			case CTaskFilterCtrl::OP_GREATER_OR_EQUAL:
				$cOperationType1 = '>=';
				break;

			case CTaskFilterCtrl::OP_NOT_EQUAL:
				$cOperationType1 = '<';
				$cOperationType2 = '>';
				break;

			case CTaskFilterCtrl::OP_STRICTLY_LESS:
				$cOperationType1 = '<';
				break;

			case CTaskFilterCtrl::OP_STRICTLY_GREATER:
				$cOperationType1 = '>';
				break;

			default:
				CTaskAssert::log(
					'Unknown operation code: ' .
					$operationCode .
					'; $key = ' .
					$key .
					'; it will be silently ignored, incorrect results expected',
					CTaskAssert::ELL_ERROR    // errors, incorrect results expected
				);

				return ($arSqlSearch);
				break;
		}

		// Convert/generate dates
		$ts1 = $ts2 = null;
		switch ($operationCode)
		{
			case CTaskFilterCtrl::OP_DATE_TODAY:
				$ts1 = $ts2 = $ts;
				break;

			case CTaskFilterCtrl::OP_DATE_YESTERDAY:
				$ts1 = $ts2 = $ts - 86400;
				break;

			case CTaskFilterCtrl::OP_DATE_TOMORROW:
				$ts1 = $ts2 = $ts + 86400;
				break;

			case CTaskFilterCtrl::OP_DATE_CUR_WEEK:
				$weekDay = date('N');    // numeric representation of the day of the week (1 to 7)
				$ts1 = $ts - ($weekDay - 1) * 86400;
				$ts2 = $ts + (7 - $weekDay) * 86400;
				break;

			case CTaskFilterCtrl::OP_DATE_PREV_WEEK:
				$weekDay = date('N');    // numeric representation of the day of the week (1 to 7)
				$ts1 = $ts - ($weekDay - 1 + 7) * 86400;
				$ts2 = $ts - $weekDay * 86400;
				break;

			case CTaskFilterCtrl::OP_DATE_NEXT_WEEK:
				$weekDay = date('N');    // numeric representation of the day of the week (1 to 7)
				$ts1 = $ts + (7 - $weekDay + 1) * 86400;
				$ts2 = $ts + (7 - $weekDay + 7) * 86400;
				break;

			case CTaskFilterCtrl::OP_DATE_CUR_MONTH:
				$ts1 = mktime(0, 0, 0, date('n', $ts), 1, date('Y', $ts));
				$ts2 = mktime(23, 59, 59, date('n', $ts) + 1, 0, date('Y', $ts));
				break;

			case CTaskFilterCtrl::OP_DATE_PREV_MONTH:
				$ts1 = mktime(0, 0, 0, date('n', $ts) - 1, 1, date('Y', $ts));
				$ts2 = mktime(23, 59, 59, date('n', $ts), 0, date('Y', $ts));
				break;

			case CTaskFilterCtrl::OP_DATE_NEXT_MONTH:
				$ts1 = mktime(0, 0, 0, date('n', $ts) + 1, 1, date('Y', $ts));
				$ts2 = mktime(23, 59, 59, date('n', $ts) + 2, 0, date('Y', $ts));
				break;

			case CTaskFilterCtrl::OP_DATE_LAST_DAYS:
				$ts1 = $ts - ((int)$val) * 86400; // val in days
				$ts2 = $ts;
				break;

			case CTaskFilterCtrl::OP_DATE_NEXT_DAYS:
				$ts1 = $ts;
				$ts2 = $ts + ((int)$val) * 86400; // val in days
				break;

			case CTaskFilterCtrl::OP_GREATER_OR_EQUAL:
			case CTaskFilterCtrl::OP_LESS_OR_EQUAL:
			case CTaskFilterCtrl::OP_STRICTLY_LESS:
			case CTaskFilterCtrl::OP_STRICTLY_GREATER:
				$ts1 = $val;
				break;

			case CTaskFilterCtrl::OP_EQUAL:
				$ts1 = mktime(0, 0, 0, date('n', $val), date('j', $val), date('Y', $val));
				$ts2 = mktime(23, 59, 59, date('n', $val), date('j', $val), date('Y', $val));
				break;

			case CTaskFilterCtrl::OP_NOT_EQUAL:
				$ts1 = mktime(0, 0, 0, date('n', $val), date('j', $val), date('Y', $val));
				$ts2 = mktime(23, 59, 59, date('n', $val), date('j', $val), date('Y', $val));
				break;

			default:
				CTaskAssert::log(
					'Unknown operation code: ' .
					$operationCode .
					'; $key = ' .
					$key .
					'; it will be silently ignored, incorrect results expected',
					CTaskAssert::ELL_ERROR    // errors, incorrect results expected
				);

				return ($arSqlSearch);
				break;
		}

		if ($ts1)
		{
			$date1 = ConvertTimeStamp(mktime(0, 0, 0, date('n', $ts1), date('j', $ts1), date('Y', $ts1)), 'FULL');
		}

		if ($ts2)
		{
			$date2 = ConvertTimeStamp(mktime(23, 59, 59, date('n', $ts2), date('j', $ts2), date('Y', $ts2)), 'FULL');
		}

		if (($cOperationType1 !== null) && ($date1 !== null))
		{
			$arrayKey = $cOperationType1 . $fieldName;
			while (isset($arFilter[$arrayKey]))
			{
				$arrayKey = ' ' . $arrayKey;
			}

			$arFilter[$arrayKey] = $date1;
		}

		if (($cOperationType2 !== null) && ($date2 !== null))
		{
			$arrayKey = $cOperationType2 . $fieldName;
			while (isset($arFilter[$arrayKey]))
			{
				$arrayKey = ' ' . $arrayKey;
			}

			$arFilter[$arrayKey] = $date2;
		}

		$arSqlSearch[] = self::GetSqlByFilter($arFilter, $userID, $sAliasPrefix, $bGetZombie);

		return ($arSqlSearch);
	}

	public static function GetFilteredKeys($filter)
	{
		$filteredKeys = [];

		if (is_array($filter))
		{
			foreach ($filter as $key => $value)
			{
				if ($key === '::LOGIC' || $key === '::MARKERS')
				{
					continue;
				}

				if (static::isSubFilterKey($key))
				{
					$filteredKeys = array_merge($filteredKeys, self::GetFilteredKeys($value));
					continue;
				}

				$operationFilter = CTasks::MkOperationFilter($key);
				$operationField = $operationFilter['FIELD'];

				if ($operationField !== '')
				{
					$filteredKeys[] = mb_strtoupper($operationField);
				}
			}
		}

		return array_unique($filteredKeys);
	}

	private static function getFilteredValues($filter): array
	{
		$filteredValues = [];

		if (is_array($filter))
		{
			foreach ($filter as $key => $value)
			{
				if ($key === '::LOGIC' || $key === '::MARKERS')
				{
					continue;
				}

				if (static::isSubFilterKey($key))
				{
					$filteredValues = array_merge($filteredValues, self::getFilteredValues($value));
					continue;
				}

				$operationFilter = CTasks::MkOperationFilter($key);
				$operationField = $operationFilter['FIELD'];

				if ($operationField !== '')
				{
					$filteredValues[] = [mb_strtoupper($operationField) => $value];
				}
			}
		}

		return $filteredValues;
	}

	public static function isSubFilterKey($key)
	{
		return is_numeric($key) || (mb_substr((string)$key, 0, 12) === '::SUBFILTER-');
	}

	public static function GetFilter($arFilter, $sAliasPrefix = "", $arParams = false)
	{
		if (!is_array($arFilter))
		{
			$arFilter = [];
		}

		$arSqlSearch = [];

		if (is_array($arParams) && array_key_exists('USER_ID', $arParams) && ($arParams['USER_ID'] > 0))
		{
			$userID = (int)$arParams['USER_ID'];
		}
		else
		{
			$userID = User::getId();
		}

		// if TRUE will be generated constraint for members
		$bMembersTableJoined = false;
		if (isset($arParams['bMembersTableJoined']))
		{
			$bMembersTableJoined = (bool)$arParams['bMembersTableJoined'];
		}

		$bGetZombie = false;
		$sql = self::GetSqlByFilter($arFilter, $userID, $sAliasPrefix, $bGetZombie, $bMembersTableJoined, $arParams);
		if ($sql <> '')
		{
			$arSqlSearch[] = $sql;
		}

		// enable legacy access if no option passed (by default)
		// disable legacy access when ENABLE_LEGACY_ACCESS === true
		// we can not switch legacy access off by default, because getFilter() can be used separately
		$enableLegacyAccess = !is_array($arParams) || !array_key_exists('ENABLE_LEGACY_ACCESS', $arParams) || $arParams['ENABLE_LEGACY_ACCESS'] !== false;
		if ($enableLegacyAccess && static::needAccessRestriction($arFilter, $arParams))
		{
			[$arSubSqlSearch, $fields] = static::getPermissionFilterConditions(
				$arParams,
				['ALIAS' => $sAliasPrefix]
			);

			if (!empty($arSubSqlSearch))
			{
				$arSqlSearch[] = " \n/*access LEGACY BEGIN*/\n (" .
					implode(" OR ", $arSubSqlSearch) .
					") \n/*access LEGACY END*/\n";
			}
		}

		return $arSqlSearch;
	}

	private static function placeFieldSql($field, $behaviour, &$fields)
	{
		if (
			array_key_exists('USE_PLACEHOLDERS', $behaviour)
			&& $behaviour['USE_PLACEHOLDERS']
		)
		{
			$fields[] = $field;

			return '%s';
		}

		return $behaviour['ALIAS'] . 'T.' . $field;
	}

	/**
	 * @param $arParams
	 * @param array $behaviour
	 *
	 * @return array
	 * @deprecated
	 */
	public static function getPermissionFilterConditions($arParams,
		$behaviour = ['ALIAS' => '', 'USE_PLACEHOLDERS' => false])
	{
		if (!is_array($behaviour))
		{
			$behaviour = [];
		}
		if (!isset($behaviour['ALIAS']))
		{
			$behaviour['ALIAS'] = '';
		}
		if (!isset($behaviour['USE_PLACEHOLDERS']))
		{
			$behaviour['USE_PLACEHOLDERS'] = false;
		}

		$arSubSqlSearch = [];
		$fields = [];

		$a = $behaviour['ALIAS'];
		$b = $behaviour;
		$f =& $fields;

		if (!is_array($arParams))
		{
			$arParams = [];
		}

		if (array_key_exists('USER_ID', $arParams) && ($arParams['USER_ID'] > 0))
		{
			$userID = (int)$arParams['USER_ID'];
		}
		else
		{
			$userID = User::getId();
		}

		if (array_key_exists('TASK_MEMBER_JOINED', $arParams) && $arParams['TASK_MEMBER_JOINED'])
		{
			$taskMemberJoined = true;
		}
		else
		{
			$taskMemberJoined = false;
		}

		if (!User::isSuper($userID))
		{
			// subordinate check
			$arParams['FIELDS'] =& $fields;
			if ($strSql = CTasks::GetSubordinateSql($a, $arParams, $behaviour))
			{
				$arSubSqlSearch[] = "EXISTS(" . $strSql . ")";
			}

			// group permission check
			if (
				$arAllowedGroups = Integration\SocialNetwork\Group::getIdsByAllowedAction(
					'view_all',
					true,
					($arParams['USER_ID'] ?? null)
				)
			)
			{
				$arSubSqlSearch[] =
					'('
					. static::placeFieldSql('GROUP_ID', $b, $f)
					. ' IN ('
					. implode(',', $arAllowedGroups)
					. '))'
				;
			}

			if (!$taskMemberJoined || ($taskMemberJoined && !empty($arSubSqlSearch)))
			{
				$arSubSqlSearch[] = static::placeFieldSql('CREATED_BY', $b, $f) . " = '" . $userID . "'";
				$arSubSqlSearch[] = static::placeFieldSql('RESPONSIBLE_ID', $b, $f) . " = '" . $userID . "'";
				$arSubSqlSearch[] =
					"EXISTS(
					SELECT 'x'
					FROM b_tasks_member "
					. $a
					. "TM
					WHERE
						"
					. $a
					. "TM.TASK_ID = "
					. static::placeFieldSql('ID', $b, $f)
					. " AND "
					. $a
					. "TM.USER_ID = '"
					. $userID
					. "'
					)";
			}
		}

		return [$arSubSqlSearch, $fields];
	}

	public static function MkOperationFilter($key)
	{
		static $arOperationsMap = null;    // will be loaded on demand

		$key = ltrim($key);

		$firstSymbol = mb_substr($key, 0, 1);
		$twoSymbols = mb_substr($key, 0, 2);

		if ($firstSymbol == "=") //Identical
		{
			$key = mb_substr($key, 1);
			$cOperationType = "I";
		}
		elseif ($twoSymbols == "!=") //not Identical
		{
			$key = mb_substr($key, 2);
			$cOperationType = "NI";
		}
		elseif ($firstSymbol == "%") //substring
		{
			$key = mb_substr($key, 1);
			$cOperationType = "S";
		}
		elseif ($twoSymbols == "!%") //not substring
		{
			$key = mb_substr($key, 2);
			$cOperationType = "NS";
		}
		elseif ($firstSymbol == "?") //logical
		{
			$key = mb_substr($key, 1);
			$cOperationType = "?";
		}
		elseif ($twoSymbols == "><") //between
		{
			$key = mb_substr($key, 2);
			$cOperationType = "B";
		}
		elseif ($twoSymbols == "*=") // identical full text match
		{
			$key = mb_substr($key, 2);
			$cOperationType = "FTI";
		}
		elseif ($twoSymbols == "*%") // partial full text match based on LIKE
		{
			$key = mb_substr($key, 2);
			$cOperationType = "FTL";
		}
		elseif ($firstSymbol == "*") // partial full text match
		{
			$key = mb_substr($key, 1);
			$cOperationType = "FT";
		}
		elseif (mb_substr($key, 0, 3) == "!><") //not between
		{
			$key = mb_substr($key, 3);
			$cOperationType = "NB";
		}
		elseif ($twoSymbols == ">=") //greater or equal
		{
			$key = mb_substr($key, 2);
			$cOperationType = "GE";
		}
		elseif ($firstSymbol == ">")  //greater
		{
			$key = mb_substr($key, 1);
			$cOperationType = "G";
		}
		elseif ($twoSymbols == "<=")  //less or equal
		{
			$key = mb_substr($key, 2);
			$cOperationType = "LE";
		}
		elseif ($firstSymbol == "<")  //less
		{
			$key = mb_substr($key, 1);
			$cOperationType = "L";
		}
		elseif ($firstSymbol == "!") // not field LIKE val
		{
			$key = mb_substr($key, 1);
			$cOperationType = "N";
		}
		elseif ($firstSymbol === '#')
		{
			// Preload and cache in static variable
			if ($arOperationsMap === null)
			{
				$arManifest = CTaskFilterCtrl::getManifest();
				$arOperationsMap = $arManifest['Operations map'];
			}

			// Resolve operation code and cutoff operation prefix from item name
			$operation = null;
			foreach ($arOperationsMap as $operationCode => $operationPrefix)
			{
				$pattern = '/^' . preg_quote($operationPrefix) . '[A-Za-z]/';
				if (preg_match($pattern, $key))
				{
					$operation = $operationCode;
					$key = mb_substr($key, mb_strlen($operationPrefix));
					break;
				}
			}

			CTaskAssert::assert($operation !== null);

			$cOperationType = "#" . $operation;
		}
		else
		{
			$cOperationType = "E";
		} // field LIKE val

		return ["FIELD" => $key, "OPERATION" => $cOperationType];
	}

	public static function FilterCreate($fname, $vals, $type, &$bFullJoin, $cOperationType = false, $bSkipEmpty = true)
	{
		global $DB;
		if (!is_array($vals))
		{
			$vals = [$vals];
		}
		else
		{
			$vals = array_unique(array_values($vals));
		}

		if (count($vals) < 1)
		{
			return "";
		}

		if (is_bool($cOperationType))
		{
			if ($cOperationType === true)
			{
				$cOperationType = "N";
			}
			else
			{
				$cOperationType = "E";
			}
		}

		if ($cOperationType == "G")
		{
			$strOperation = ">";
		}
		elseif ($cOperationType == "GE")
		{
			$strOperation = ">=";
		}
		elseif ($cOperationType == "LE")
		{
			$strOperation = "<=";
		}
		elseif ($cOperationType == "L")
		{
			$strOperation = "<";
		}
		elseif ($cOperationType === "NI")
		{
			$strOperation = "!=";
		}
		else
		{
			$strOperation = "=";
		}

		$bFullJoin = false;
		$bWasLeftJoin = false;

		// special case for array of number
		if ($type === 'number' && is_array($vals) && count($vals) > 1 && count($vals) < 80)
		{
			$vals = implode(', ', array_unique(array_map('intval', $vals)));

			$res = $fname . ' ' . ($cOperationType == 'N' ? 'not' : '') . ' in (' . $vals . ')';

			// INNER JOIN in this case
			if ($cOperationType != "N")
			{
				$bFullJoin = true;
			}

			return $res;
		}

		$res = [];

		foreach ($vals as $key => $val)
		{
			if (($type === 'number') && !$val)
			{
				$val = 0;
			}

			if (!$bSkipEmpty || $val === 0 || $val <> '' || (is_bool($val) && $val === false))
			{
				switch ($type)
				{
					case "string_equal":
						if ($val == '')
						{
							$res[] = ($cOperationType == "N" ? "NOT" : "") .
								"(" .
								$fname .
								" IS NULL OR " .
								$DB->Length($fname) .
								"<=0)";
						}
						else
						{
							$res[] = "(" .
								($cOperationType == "N" ? " " . $fname . " IS NULL OR NOT (" : "") .
								$fname .
								$strOperation .
								"'" .
								$DB->ForSql($val) .
								"'" .
								($cOperationType == "N" ? ")" : "") .
								")";
						}
						break;

					case "string":
						if ($cOperationType == "?")
						{
							if ($val === 0 || $val <> '')
							{
								$res[] = GetFilterQuery($fname, $val, "Y", [], "N");
							}
						}
						elseif ($cOperationType == "S")
						{
							$res[] = "(UPPER(" . $fname . ") LIKE UPPER('%" . $DB->ForSqlLike($val) . "%'))";
						}
						elseif ($cOperationType == "NS")
						{
							$res[] = "(UPPER(" . $fname . ") NOT LIKE UPPER('%" . $DB->ForSqlLike($val) . "%'))";
						}
						elseif ($cOperationType == "FTL")
						{
							$sqlWhere = new CSQLWhere();
							$res[] = $sqlWhere->matchLike($fname, $val);
						}
						elseif ($val == '')
						{
							$res[] = ($cOperationType == "N" ? "NOT" : "") .
								"(" .
								$fname .
								" IS NULL OR " .
								$DB->Length($fname) .
								"<=0)";
						}
						else
						{
							if ($strOperation == "=")
							{
								$res[] = "(" .
									($cOperationType == "N" ? " " . $fname . " IS NULL OR NOT (" : "") .
									($fname .
										" " .
										($strOperation ==
										"="
											? "LIKE"
											: $strOperation) .
										" '" .
										$DB->ForSqlLike(
											$val
										) .
										"'") .
									($cOperationType == "N" ? ")" : "") .
									")";
							}
							else
							{
								$res[] = "(" .
									($cOperationType == "N" ? " " . $fname . " IS NULL OR NOT (" : "") .
									($fname .
										" " .
										$strOperation .
										" '" .
										$DB->ForSql($val) .
										"'") .
									($cOperationType == "N" ? ")" : "") .
									")";
							}
						}
						break;
					case "fulltext":
						echo '';
						if ($cOperationType == "FT" || $cOperationType == "FTI")
						{
							$sqlWhere = new CSQLWhere();
							$res[] = $sqlWhere->match($fname, $val, $cOperationType == "FT");
						}
						elseif ($cOperationType == "FTL")
						{
							$sqlWhere = new CSQLWhere();
							$res[] = $sqlWhere->matchLike($fname, $val);
						}
						elseif ($cOperationType == "?")
						{
							if ($val === 0 || $val <> '')
							{
								$sr = GetFilterQuery(
									$fname,
									$val,
									"Y",
									[],
									($fname == "BE.SEARCHABLE_CONTENT" || $fname == "BE.DETAIL_TEXT" ? "Y" : "N")
								);
								if ($sr != "0")
								{
									$res[] = $sr;
								}
							}
						}
						elseif (
							($cOperationType == "B" || $cOperationType == "NB") && is_array($val)
							&& count($val)
							== 2
						)
						{
							$res[] = ($cOperationType == "NB" ? " " . $fname . " IS NULL OR NOT " : "") .
								"(" .
								CIBlock::_Upper($fname) .
								" " .
								$strOperation[0] .
								" '" .
								CIBlock::_Upper($DB->ForSql($val[0])) .
								"' " .
								$strOperation[1] .
								" '" .
								CIBlock::_Upper($DB->ForSql($val[1])) .
								"')";
						}
						elseif ($cOperationType == "S" || $cOperationType == "NS")
						{
							$res[] = ($cOperationType == "NS" ? " " . $fname . " IS NULL OR NOT " : "") .
								"(" .
								CIBlock::_Upper($fname) .
								" LIKE " .
								CIBlock::_Upper("'%" . CIBlock::ForLIKE($val) . "%'") .
								")";
						}
						else
						{
							if ($val == '')
							{
								$res[] = ($bNegative ? "NOT" : "")
									. "("
									. $fname
									. " IS NULL OR "
									. $DB->Length($fname)
									. "<=0)";
							}
							elseif ($strOperation == "=" && $cOperationType != "I" && $cOperationType != "NI")
							{
								$res[] = ($cOperationType == "N" ? " " . $fname . " IS NULL OR NOT " : "") .
									"(" .
									($fname .
										" LIKE '" .
										$DB->ForSqlLike($val) .
										"'") .
									")";
							}
							else
							{
								$res[] = ($bNegative ? " " . $fname . " IS NULL OR NOT " : "") .
									"(" .
									($fname .
										" " .
										$strOperation .
										" '" .
										$DB->ForSql($val) .
										"'") .
									")";
							}
						}
						break;
					case "date":
						if ($val == '')
						{
							$res[] = ($cOperationType == "N" ? "NOT" : "") . "(" . $fname . " IS NULL)";
						}
						else
						{
							$res[] = "(" .
								($cOperationType == "N" ? " " . $fname . " IS NULL OR NOT (" : "") .
								$fname .
								" " .
								$strOperation .
								" " .
								$val .
								"" .
								($cOperationType == "N" ? ")" : "") .
								")";
						}
						break;

					case "number":
						$isOperationTypeN = $cOperationType === 'N';
						if ($vals[$key] === false || strlen($val) <= 0)
						{
							$res[] = ($isOperationTypeN ? 'NOT' : '') . "({$fname} IS NULL)";
						}
						else
						{
							$res[] = "("
								. ($isOperationTypeN ? "{$fname} IS NULL OR NOT (" : "")
								. "{$fname} {$strOperation} '" . DoubleVal($val) . "'"
								. ($isOperationTypeN ? ")" : "")
								. ")";
						}
						break;

					case "number_wo_nulls":
						$res[] = "(" .
							($cOperationType == "N" ? "NOT (" : "") .
							$fname .
							" " .
							$strOperation .
							" " .
							DoubleVal($val) .
							($cOperationType == "N" ? ")" : "") .
							")";
						break;

					case "null_or_zero":
						if ($cOperationType == "N")
						{
							$res[] = "((" . $fname . " IS NOT NULL) AND (" . $fname . " != 0))";
						}
						else
						{
							$res[] = "((" . $fname . " IS NULL) OR (" . $fname . " = 0))";
						}

						break;

					case "left_existence":

						if ($strOperation != '=')
						{
							CTaskAssert::logError('Operation type not supported for ' . $fname . ': ' . $strOperation);
						}
						elseif ($val != 'Y' && $val != 'N' && 0)
						{
							CTaskAssert::logError('Filter value not supported for ' . $fname . ': ' . $val);
						}
						else
						{
							$otNot = $cOperationType == "N";

							if (($val == 'Y' && !$otNot) || ($val == 'N' && $otNot))
							{
								$res[] = "(" . $fname . " IS NOT NULL)";
							}
							else
							{
								$res[] = "(" . $fname . " IS NULL)";
							}
						}

						break;

					case 'reference':

						$val = trim($val);

						if (preg_match('#^[a-z0-9_]+(\.{1}[a-z0-9_]+)*$#i', $val))
						{
							if ($cOperationType === 'E')
							{
								$res[] = '(' . $fname . ' = ' . $DB->ForSql($val) . ')';
							}
							elseif ($cOperationType === 'N')
							{
								$res[] = '(' . $fname . ' != ' . $DB->ForSql($val) . ')';
							}
							elseif ($cOperationType === 'L')
							{
								$res[] = '(' . $fname . ' < ' . $DB->ForSql($val) . ')';
							}
							elseif ($cOperationType === 'G')
							{
								$res[] = '(' . $fname . ' > ' . $DB->ForSql($val) . ')';
							}
							else
							{
								CTaskAssert::logError('[0xcf017223] Operation type not supported: ' . $cOperationType);
							}
						}
						else
						{
							CTaskAssert::logError("Bad reference: " . $fname . " => '" . $val . "'");
						}

						break;
				}

				// INNER JOIN in this case
				if (($val === 0 || $val <> '') && $cOperationType != "N")
				{
					$bFullJoin = true;
				}
				else
				{
					$bWasLeftJoin = true;
				}
			}
		}

		$strResult = "";
		for ($i = 0, $resCnt = count($res); $i < $resCnt; $i++)
		{
			if ($i > 0)
			{
				$strResult .= ($cOperationType == "N" ? " AND " : " OR ");
			}
			$strResult .= $res[$i];
		}

		if (count($res) > 1)
		{
			$strResult = "(" . $strResult . ")";
		}

		if ($bFullJoin && $bWasLeftJoin && $cOperationType != "N")
		{
			$bFullJoin = false;
		}

		return $strResult;
	}

	/**
	 * This method is deprecated. Use CTaskItem class instead.
	 *
	 * @deprecated
	 */
	public static function GetByID($ID, $bCheckPermissions = true, $arParams = [])
	{
		$bReturnAsArray = false;
		$bSkipExtraData = false;
		$arGetListParams = [];

		if (isset($arParams['returnAsArray']))
		{
			$bReturnAsArray = ($arParams['returnAsArray'] === true);
		}
		if (isset($arParams['bSkipExtraData']))
		{
			$bSkipExtraData = ($arParams['bSkipExtraData'] === true);
		}

		if (isset($arParams['USER_ID']))
		{
			$arGetListParams['USER_ID'] = $arParams['USER_ID'];
		}

		$permissionUserId = isset($arParams['USER_ID']) ? $arParams['USER_ID'] : User::getId();
		if (
			$bCheckPermissions
			&& !\Bitrix\Tasks\Access\TaskAccessController::can($permissionUserId, ActionDictionary::ACTION_TASK_READ,
				$ID)
		)
		{
			if ($bReturnAsArray)
			{
				return false;
			}

			$res = new CDBResult();
			$res->initFromArray([]);
			return $res;
		}

		$arFilter = ["ID" => (int)$ID];
		// no further access verification required
		$arFilter["CHECK_PERMISSIONS"] = "N";

		$select = ['*', 'UF_*'];
		if (array_key_exists('select', $arParams))
		{
			$select = $arParams['select'];
		}

		$select = array_unique(array_merge(['ID'], $select));

		$res = CTasks::GetList([], $arFilter, $select, $arGetListParams);
		if ($res && ($task = $res->Fetch()))
		{
			if (array_key_exists('TITLE', $task))
			{
				$task['TITLE'] = \Bitrix\Main\Text\Emoji::decode($task['TITLE']);
			}

			if (array_key_exists('DESCRIPTION', $task) && $task['DESCRIPTION'] !== '')
			{
				$task['DESCRIPTION'] = \Bitrix\Main\Text\Emoji::decode($task['DESCRIPTION']);
			}

			if (in_array('AUDITORS', $select) || in_array('ACCOMPLICES', $select) || in_array('*', $select))
			{
				$task["ACCOMPLICES"] = $task["AUDITORS"] = [];
				$rsMembers = CTaskMembers::GetList([], ["TASK_ID" => $ID]);
				while ($arMember = $rsMembers->Fetch())
				{
					if ($arMember["TYPE"] == "A" && (in_array('ACCOMPLICES', $select) || in_array('*', $select)))
					{
						$task["ACCOMPLICES"][] = $arMember["USER_ID"];
					}
					elseif ($arMember["TYPE"] == "U" && (in_array('AUDITORS', $select) || in_array('*', $select)))
					{
						$task["AUDITORS"][] = $arMember["USER_ID"];
					}
				}
			}

			if (!$bSkipExtraData)
			{
				if (in_array('SCENARIO_NAME', $select) || in_array('*', $select))
				{
					$task['SCENARIO_NAME'] = [];
					$scenarios = \Bitrix\Tasks\Internals\Task\ScenarioTable::getList([
						'select' => ['SCENARIO'],
						'filter' => [
							'=TASK_ID' => $ID,
						],
					])->fetchAll();

					foreach ($scenarios as $row)
					{
						$task['SCENARIO_NAME'][] = $row['SCENARIO'];
					}
				}

				if (in_array('TAGS', $select) || in_array('*', $select))
				{
					$arTagsFilter = ["TASK_ID" => $ID];
					$rsTags = CTaskTags::GetList([], $arTagsFilter);
					$task["TAGS"] = [];
					while ($arTag = $rsTags->Fetch())
					{
						$task["TAGS"][] = $arTag["NAME"];
					}
				}

				if (in_array('CHECKLIST', $select) || in_array('*', $select))
				{
					$task["CHECKLIST"] = TaskCheckListFacade::getByEntityId($ID);
				}

				if (in_array('FILES', $select) || in_array('*', $select))
				{
					$rsFiles = CTaskFiles::GetList([], ["TASK_ID" => $ID]);
					$task["FILES"] = [];
					while ($arFile = $rsFiles->Fetch())
					{
						$task["FILES"][] = $arFile["FILE_ID"];
					}
				}

				if (in_array('DEPENDS_ON', $select) || in_array('*', $select))
				{
					$rsDependsOn = CTaskDependence::GetList([], ["TASK_ID" => $ID]);
					$task["DEPENDS_ON"] = [];
					while ($arDependsOn = $rsDependsOn->Fetch())
					{
						$task["DEPENDS_ON"][] = $arDependsOn["DEPENDS_ON_ID"];
					}
				}
			}

			if ($bReturnAsArray)
			{
				return ($task);
			}
			else
			{
				$rsTask = new CDBResult;
				$rsTask->InitFromarray([$task]);

				return $rsTask;
			}
		}
		else
		{
			if ($bReturnAsArray)
			{
				return (false);
			}
			else
			{
				return $res;
			}
		}
	}

	/**
	 * @param null $userID
	 *
	 * @return array
	 * @deprecated
	 */
	public static function GetSubordinateDeps($userID = null)
	{
		return Integration\Intranet\Department::getSubordinateIds($userID, true);
	}

	/**
	 * @param array $arParams
	 *
	 * @return mixed
	 * @deprecated
	 * @see Integration\SocialNetwork\Group::getIdsByAllowedAction
	 */
	public static function GetAllowedGroups($arParams = [])
	{
		global $DB;
		static $ALLOWED_GROUPS = [];

		$userId = null;

		if (is_array($arParams) && isset($arParams['USER_ID']))
		{
			$userId = $arParams['USER_ID'];
		}
		else
		{
			$userId = User::getId();
		}

		if (!($userId >= 1))
		{
			$userId = 0;
		}

		if (!isset($ALLOWED_GROUPS[$userId]) && CModule::IncludeModule("socialnetwork"))
		{
			// bottleneck
			$strSql = "SELECT DISTINCT(T.GROUP_ID) FROM b_tasks T WHERE T.GROUP_ID IS NOT NULL";

			$rsGroups = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			$ALLOWED_GROUPS[$userId] = $arGroupsWithTasks = [];
			while ($arGroup = $rsGroups->Fetch())
			{
				$arGroupsWithTasks[] = $arGroup["GROUP_ID"];
			}
			if (is_array($arGroupsWithTasks) && sizeof($arGroupsWithTasks))
			{
				if ($userId === 0)
				{
					$featurePerms = CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
						SONET_ENTITY_GROUP,
						$arGroupsWithTasks,
						"tasks",
						"view_all"
					);
				}
				else
				{
					$featurePerms = CSocNetFeaturesPerms::CanPerformOperation(
						$userId,
						SONET_ENTITY_GROUP,
						$arGroupsWithTasks,
						"tasks",
						"view_all"
					);
				}

				if (is_array($featurePerms))
				{
					$ALLOWED_GROUPS[$userId] = array_keys(array_filter($featurePerms));
				}
			}
		}

		return $ALLOWED_GROUPS[$userId];
	}

	public static function GetDepartmentManagers($arDepartments, $skipUserId = false, $arSelectFields = ['ID'])
	{
		global $CACHE_MANAGER;

		if ((!is_array($arDepartments)) || empty($arDepartments) || (!is_array($arSelectFields)))
		{
			return false;
		}

		// We need ID in any case
		if (!in_array('ID', $arSelectFields))
		{
			$arSelectFields[] = 'ID';
		}

		$arManagers = [];
		$obCache = new CPHPCache();
		$lifeTime = CTasksTools::CACHE_TTL_UNLIM;
		$cacheDir = "/tasks/subordinatedeps";
		$cacheFPrint = sha1(
			serialize($arDepartments) . '|' . serialize($arSelectFields)
		);
		if ($obCache->InitCache($lifeTime, $cacheFPrint, $cacheDir))
		{
			$arManagers = $obCache->GetVars();
		}
		elseif ($obCache->StartDataCache())
		{
			$IBlockID = COption::GetOptionInt('intranet', 'iblock_structure', 0);

			$CACHE_MANAGER->StartTagCache($cacheDir);
			$CACHE_MANAGER->RegisterTag("iblock_id_" . $IBlockID);

			$arUserIDs = self::GetDepartmentManagersIDs($arDepartments, $IBlockID);

			if (count($arUserIDs) > 0)
			{
				$arFilter = [
					'ID' => implode('|', $arUserIDs),
				];

				// Prevent using users, that doesn't activate it's account
				// http://jabber.bx/view.php?id=29118
				if (IsModuleInstalled('bitrix24'))
				{
					$arFilter['!LAST_LOGIN'] = false;
				}

				$dbUser = CUser::GetList(
					'ID',
					'ASC',
					$arFilter,
					['FIELDS' => $arSelectFields]    // selects only $arSelectFields fields
				);
				while ($arUser = $dbUser->GetNext())
				{
					$arManagers[(int)$arUser["ID"]] = $arUser;
				}
			}

			$CACHE_MANAGER->EndTagCache();
			$obCache->EndDataCache($arManagers);
		}

		// remove user to be skipped
		if (($skipUserId !== false) && (isset($arManagers[(int)$skipUserId])))
		{
			unset ($arManagers[(int)$skipUserId]);
		}

		return $arManagers;
	}

	protected static function GetDepartmentManagersIDs($arDepartments, $IBlockID)
	{
		if (!CModule::IncludeModule('iblock'))
		{
			return [];
		}

		$dbSections = CIBlockSection::GetList(
			['SORT' => 'ASC'],
			[
				'ID' => $arDepartments,
				'IBLOCK_ID' => $IBlockID,
				'CHECK_PERMISSIONS' => 'N',
			],
			false,                                // don't count
			[
				'ID',
				'UF_HEAD',
				'IBLOCK_SECTION_ID',
			]
		);

		$arUserIDs = [];
		while ($arSection = $dbSections->Fetch())
		{
			if ($arSection['UF_HEAD'] > 0)
			{
				$arUserIDs[] = $arSection['UF_HEAD'];
			}

			if ($arSection['IBLOCK_SECTION_ID'] > 0)
			{
				$arUserIDs = array_merge(
					$arUserIDs,
					self::GetDepartmentManagersIDs([$arSection['IBLOCK_SECTION_ID']], $IBlockID)
				);
			}
		}

		return $arUserIDs;
	}

	/**
	 * @param $employeeID1
	 * @param $employeeID2
	 *
	 * @return bool true if $employeeID2 is manager of $employeeID1
	 */
	public static function IsSubordinate($employeeID1, $employeeID2)
	{
		if ($employeeID1 == $employeeID2)
		{
			return false;
		}

		$dbRes = CUser::GetList(
			'ID',
			'ASC',
			['ID' => $employeeID1],
			['SELECT' => ['UF_DEPARTMENT']]
		);

		if (($arRes = $dbRes->Fetch()) && is_array($arRes['UF_DEPARTMENT']) && (count($arRes['UF_DEPARTMENT']) > 0))
		{
			$arManagers = array_keys(CTasks::GetDepartmentManagers($arRes['UF_DEPARTMENT'], $employeeID1));

			if (in_array($employeeID2, $arManagers))
			{
				return true;
			}
		}

		return false;
	}

	public static function getSelectSqlByFilter(array $filter = [], $alias = '', array $filterParams = [])
	{
		$userId = intval($filterParams['USER_ID']);

		$obUserFieldsSql = new CUserTypeSQL();
		$obUserFieldsSql->SetEntity("TASKS_TASK", $alias . "T.ID");
		$obUserFieldsSql->SetFilter($filter);

		if (isset($filter['::LOGIC']))
		{
			CTaskAssert::assert($filter['::LOGIC'] === 'AND');
		}

		$optimized = static::tryOptimizeFilter($filter, $alias . "T", $alias . "TM_SPEC");
		$sqlSearch = CTasks::GetFilter($optimized['FILTER'], $alias, $filterParams);

		$r = $obUserFieldsSql->GetFilter();
		if ($r <> '')
		{
			$sqlSearch[] = "(" . $r . ")";
		}

		$params = [
			'USER_ID' => $userId,
			'JOIN_ALIAS' => $alias,
			'SOURCE_ALIAS' => "{$alias}T",
		];
		$relatedJoins = static::getRelatedJoins([], $filter, [], $params);
		$relatedJoins = array_merge($relatedJoins, $optimized['JOINS']);

		return "
			SELECT {$alias}T.ID
			FROM b_tasks {$alias}T
			INNER JOIN b_user {$alias}CU ON {$alias}CU.ID = {$alias}T.CREATED_BY
			INNER JOIN b_user {$alias}RU ON {$alias}RU.ID = {$alias}T.RESPONSIBLE_ID
			" . implode("\n", $relatedJoins) . "
			" . $obUserFieldsSql->GetJoin($alias . "T.ID") . "
			" . (count($sqlSearch) ? " WHERE " . implode(" AND ", $sqlSearch) : "") . "
		";
	}

	/**
	 * Get tasks fields info (for rest, etc)
	 *
	 * @param bool $getUf
	 * @return array
	 */
	public static function getFieldsInfo($getUf = true): array
	{
		global $USER_FIELD_MANAGER;

		$fields = [
			'ID' => [
				'type' => 'integer',
				'primary' => true,
			],
			'PARENT_ID' => [
				'type' => 'integer',
				'default' => 0,
			],
			'TITLE' => [
				'type' => 'string',
				'required' => true,
			],
			'DESCRIPTION' => [
				'type' => 'string',
			],
			'MARK' => [
				'type' => 'enum',
				'values' => [
					self::MARK_NEGATIVE => Loc::getMessage('TASKS_FIELDS_MARK_NEGATIVE'),
					self::MARK_POSITIVE => Loc::getMessage('TASKS_FIELDS_MARK_POSITIVE'),
				],
				'default' => null,
			],
			'PRIORITY' => [
				'type' => 'enum',
				'values' => [
					self::PRIORITY_HIGH => Loc::getMessage('TASKS_FIELDS_PRIORITY_HIGH'),
					self::PRIORITY_AVERAGE => Loc::getMessage('TASKS_FIELDS_PRIORITY_AVERAGE'),
					self::PRIORITY_LOW => Loc::getMessage('TASKS_FIELDS_PRIORITY_LOW'),
				],
				'default' => self::PRIORITY_AVERAGE,
			],
			'STATUS' => [
				'type' => 'enum',
				'values' => [
					self::STATE_PENDING => Loc::getMessage('TASKS_FIELDS_STATUS_PENDING'),
					self::STATE_IN_PROGRESS => Loc::getMessage('TASKS_FIELDS_STATUS_IN_PROGRESS'),
					self::STATE_SUPPOSEDLY_COMPLETED => Loc::getMessage('TASKS_FIELDS_STATUS_SUPPOSEDLY_COMPLETED'),
					self::STATE_COMPLETED => Loc::getMessage('TASKS_FIELDS_STATUS_COMPLETED'),
					self::STATE_DEFERRED => Loc::getMessage('TASKS_FIELDS_STATUS_DEFERRED'),
				],
				'default' => self::STATE_PENDING,
			],
			'MULTITASK' => [
				'type' => 'enum',
				'values' => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N',
			],
			'NOT_VIEWED' => [
				'type' => 'enum',
				'values' => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N',
			],
			'REPLICATE' => [
				'type' => 'enum',
				'values' => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N',
			],
			'GROUP_ID' => [
				'type' => 'integer',
				'default' => 0,
			],
			'STAGE_ID' => [
				'type' => 'integer',
				'default' => 0,
			],
			'CREATED_BY' => [
				'type' => 'integer',
				'required' => true,
			],
			'CREATED_DATE' => [
				'type' => 'datetime',
			],
			'RESPONSIBLE_ID' => [
				'type' => 'integer',
				'required' => true,
			],
			'ACCOMPLICES' => [
				'type' => 'array',
			],
			'AUDITORS' => [
				'type' => 'array',
			],
			'CHANGED_BY' => [
				'type' => 'integer',
			],
			'CHANGED_DATE' => [
				'type' => 'datetime',
			],
			'STATUS_CHANGED_BY' => [
				'type' => 'integer',
			],
			'STATUS_CHANGED_DATE' => [
				'type' => 'datetime',
			],
			'CLOSED_BY' => [
				'type' => 'integer',
				'default' => null,
			],
			'CLOSED_DATE' => [
				'type' => 'datetime',
				'default' => null,
			],
			'ACTIVITY_DATE' => [
				'type' => 'datetime',
				'default' => null,
			],
			'DATE_START' => [
				'type' => 'datetime',
				'default' => null,
			],
			'DEADLINE' => [
				'type' => 'datetime',
				'default' => null,
			],
			'START_DATE_PLAN' => [
				'type' => 'datetime',
				'default' => null,
			],
			'END_DATE_PLAN' => [
				'type' => 'datetime',
				'default' => null,
			],
			'GUID' => [
				'type' => 'string',
				'default' => null,
			],
			'XML_ID' => [
				'type' => 'string',
				'default' => null,
			],
			'COMMENTS_COUNT' => [
				'type' => 'integer',
				'default' => 0,
			],
			'SERVICE_COMMENTS_COUNT' => [
				'type' => 'integer',
				'default' => 0,
			],
			'NEW_COMMENTS_COUNT' => [
				'type' => 'integer',
				'default' => 0,
			],
			'ALLOW_CHANGE_DEADLINE' => [
				'type' => 'enum',
				'values' => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N',
			],
			'ALLOW_TIME_TRACKING' => [
				'type' => 'enum',
				'values' => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N',
			],
			'TASK_CONTROL' => [
				'type' => 'enum',
				'values' => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N',
			],
			'ADD_IN_REPORT' => [
				'type' => 'enum',
				'values' => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N',
			],
			'FORKED_BY_TEMPLATE_ID' => [
				'type' => 'enum',
				'values' => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N',
			],
			'TIME_ESTIMATE' => [
				'type' => 'integer',
			],
			'TIME_SPENT_IN_LOGS' => [
				'type' => 'integer',
			],
			'MATCH_WORK_TIME' => [
				'type' => 'integer',
			],
			'FORUM_TOPIC_ID' => [
				'type' => 'integer',
			],
			'FORUM_ID' => [
				'type' => 'integer',
			],
			'SITE_ID' => [
				'type' => 'string',
			],
			'SUBORDINATE' => [
				'type' => 'enum',
				'values' => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => null,
			],
			'FAVORITE' => [
				'type' => 'enum',
				'values' => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => null,
			],
			'EXCHANGE_MODIFIED' => [
				'type' => 'datetime',
				'default' => null,
			],
			'EXCHANGE_ID' => [
				'type' => 'integer',
				'default' => null,
			],
			'OUTLOOK_VERSION' => [
				'type' => 'integer',
				'default' => null,
			],
			'VIEWED_DATE' => [
				'type' => 'datetime',
			],
			'SORTING' => [
				'type' => 'double',
			],
			'DURATION_PLAN' => [
				'type' => 'integer',
			],
			'DURATION_FACT' => [
				'type' => 'integer',
			],
			'CHECKLIST' => [
				'type' => 'array',
			],
			'DURATION_TYPE' => [
				'type' => 'enum',
				'values' => [
					'secs',
					'mins',
					'hours',
					'days',
					'weeks',
					'monts',
					'years',
				],
				'default' => 'days',
			],
			'IS_MUTED' => [
				'type' => 'enum',
				'values' => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N',
			],
			'IS_PINNED' => [
				'type' => 'enum',
				'values' => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N',
			],
			'IS_PINNED_IN_GROUP' => [
				'type' => 'enum',
				'values' => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N',
			],
		];

		foreach ($fields as $fieldId => &$fieldData)
		{
			$fieldData = array_merge(['title' => Loc::getMessage('TASKS_FIELDS_' . $fieldId)], $fieldData);
		}
		unset($fieldData);

		if ($getUf)
		{
			$uf = $USER_FIELD_MANAGER->GetUserFields('TASKS_TASK', 0, LANGUAGE_ID);
			foreach ($uf as $key => $item)
			{
				$fields[$key] = [
					'title' => $item['EDIT_FORM_LABEL'],
					'type' => $item['USER_TYPE_ID'],
				];
			}
		}

		return $fields;
	}

	/**
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param array $arSelect
	 * @param array $arParams
	 * @param array $arGroup
	 * @return bool|CDBResult
	 * @throws TasksException
	 */
	public static function GetList($arOrder = [], $arFilter = [], $arSelect = [], $arParams = [], array $arGroup = [])
	{
		global $DB, $USER_FIELD_MANAGER;

		$provider = new \Bitrix\Tasks\Provider\TaskProvider($DB, $USER_FIELD_MANAGER);
		return $provider->getList($arOrder, $arFilter, $arSelect, $arParams, $arGroup);
	}

	public static function getAvailableOrderFields()
	{
		return [
			'ID',
			'TITLE',
			'TIME_SPENT_IN_LOGS',
			'DATE_START',
			'CREATED_DATE',
			'CHANGED_DATE',
			'CLOSED_DATE',
			'ACTIVITY_DATE',
			'START_DATE_PLAN',
			'END_DATE_PLAN',
			'DEADLINE',
			'REAL_STATUS',
			'STATUS_COMPLETE',
			'PRIORITY',
			'MARK',
			'CREATED_BY_LAST_NAME',
			'RESPONSIBLE_LAST_NAME',
			'GROUP_ID',
			'TIME_ESTIMATE',
			'ALLOW_CHANGE_DEADLINE',
			'ALLOW_TIME_TRACKING',
			'MATCH_WORK_TIME',
			'FAVORITE',
			'SORTING',
			'IS_PINNED',
			'IS_PINNED_IN_GROUP',
		];
	}

	/**
	 * Checks if we need to build access sql
	 *
	 * @param $runtimeOptions
	 * @return bool
	 */
	public static function checkAccessSqlBuilding($runtimeOptions)
	{
		$fields = $runtimeOptions['FIELDS'];

		foreach (array_keys($fields) as $key)
		{
			if (preg_match('/^ROLE_/', $key))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns related joins
	 *
	 * @param $select
	 * @param $filter
	 * @param $order
	 * @param $params
	 * @return array
	 */
	public static function getRelatedJoins($select, $filter, $order, $params)
	{
		$relatedJoins = [];

		$userId = ($params['USER_ID'] ? (int)$params['USER_ID'] : User::getId());
		$viewedBy = (int)($params['VIEWED_BY'] ?? $userId);
		$sortingGroupId = (int)($params['SORTING_GROUP_ID'] ?? 0);
		$joinAlias = $params['JOIN_ALIAS'] ?? '';
		$sourceAlias = $params['SOURCE_ALIAS'] ?? 'T';

		$filterKeys = static::GetFilteredKeys($filter);
		$possibleJoins = [
			'CREATOR',
			'RESPONSIBLE',
			'VIEWED',
			'SORTING',
			'FAVORITE',
			'STAGES',
			'FORUM',
			'FORUM_MESSAGE',
			'USER_OPTION',
			'COUNTERS',
			'SCRUM',
			'SCENARIO',
			'IM_CHAT',
		];

		foreach ($possibleJoins as $join)
		{
			switch ($join)
			{
				case 'CREATOR':
					if (
						in_array('CREATED_BY_NAME', $select, true)
						|| in_array('CREATED_BY_LAST_NAME', $select, true)
						|| in_array('CREATED_BY_SECOND_NAME', $select, true)
						|| in_array('CREATED_BY_LOGIN', $select, true)
						|| in_array('CREATED_BY_WORK_POSITION', $select, true)
						|| in_array('CREATED_BY_PHOTO', $select, true)
						|| array_key_exists('ORIGINATOR_NAME', $order)
						|| array_key_exists('CREATED_BY', $order)
					)
					{
						$tableName = UserTable::getTableName();
						$relatedJoins[$join] = "INNER JOIN {$tableName} {$joinAlias}CU "
							. "ON {$joinAlias}CU.ID = {$sourceAlias}.CREATED_BY";
					}
					break;

				case 'RESPONSIBLE':
					if (
						in_array('RESPONSIBLE_NAME', $select, true)
						|| in_array('RESPONSIBLE_LAST_NAME', $select, true)
						|| in_array('RESPONSIBLE_SECOND_NAME', $select, true)
						|| in_array('RESPONSIBLE_LOGIN', $select, true)
						|| in_array('RESPONSIBLE_WORK_POSITION', $select, true)
						|| in_array('RESPONSIBLE_PHOTO', $select, true)
						|| array_key_exists('RESPONSIBLE_NAME', $order)
						|| array_key_exists('RESPONSIBLE_ID', $order)
					)
					{
						$tableName = UserTable::getTableName();
						$relatedJoins[$join] = "INNER JOIN {$tableName} {$joinAlias}RU "
							. "ON {$joinAlias}RU.ID = {$sourceAlias}.RESPONSIBLE_ID";
					}
					break;

				case 'VIEWED':
					if (
						in_array('STATUS', $select, true)
						|| in_array('NOT_VIEWED', $select, true)
						|| in_array('VIEWED_DATE', $select, true)
						|| in_array('STATUS', $filterKeys, true)
						|| in_array('VIEWED_BY', $filterKeys, true)
						|| in_array('WITH_NEW_COMMENTS', $filterKeys, true)
					)
					{
						$tableName = ViewedTable::getTableName();
						$relatedJoins[$join] = "LEFT JOIN {$tableName} {$joinAlias}TV "
							. "ON {$joinAlias}TV.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}TV.USER_ID = {$viewedBy}";
					}
					break;

				case 'SORTING':
					if (
						in_array('SORTING', $select, true)
						|| in_array('SORTING', $filterKeys, true)
						|| array_key_exists('SORTING', $order)
					)
					{
						$tableName = SortingTable::getTableName();
						$relatedJoins[$join] = "LEFT JOIN {$tableName} {$joinAlias}SRT "
							. "ON {$joinAlias}SRT.TASK_ID = {$sourceAlias}.ID "
							. "AND " . (
							$sortingGroupId > 0
								? "{$joinAlias}SRT.GROUP_ID = {$sortingGroupId}"
								: "{$joinAlias}SRT.USER_ID = {$userId}"
							);
					}
					break;

				case 'FAVORITE':
					if (
						in_array('FAVORITE', $select, true)
						|| in_array('FAVORITE', $filterKeys, true)
						|| array_key_exists('FAVORITE', $order)
					)
					{
						$tableName = FavoriteTable::getTableName();
						$relatedJoins[$join] = "LEFT JOIN {$tableName} {$joinAlias}FVT "
							. "ON {$joinAlias}FVT.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}FVT.USER_ID = {$userId}";
					}
					break;

				case 'STAGES':
					if (in_array('STAGES_ID', $filterKeys, true))
					{
						$tableName = TaskStageTable::getTableName();
						$relatedJoins[$join] = "INNER JOIN {$tableName} {$joinAlias}STG "
							. "ON STG.TASK_ID = {$sourceAlias}.ID";
					}
					break;

				case 'FORUM':
					if (!\Bitrix\Main\Loader::includeModule('forum'))
					{
						break;
					}
					if (
						in_array('COMMENTS_COUNT', $select, true)
						|| in_array('SERVICE_COMMENTS_COUNT', $select, true)
						|| in_array('FORUM_ID', $select, true)
					)
					{
						$tableName = \Bitrix\Forum\TopicTable::getTableName();
						$relatedJoins[$join] = "LEFT JOIN {$tableName} {$joinAlias}FT "
							. "ON {$joinAlias}FT.ID = {$sourceAlias}.FORUM_TOPIC_ID";
					}
					break;

				case 'FORUM_MESSAGE':
					if (!\Bitrix\Main\Loader::includeModule('forum'))
					{
						break;
					}
					if (in_array('WITH_NEW_COMMENTS', $filterKeys, true))
					{
						$tableName = \Bitrix\Forum\MessageTable::getTableName();
						$relatedJoins[$join] = "LEFT JOIN {$tableName} {$joinAlias}FM "
							. "ON {$joinAlias}FM.TOPIC_ID = {$sourceAlias}.FORUM_TOPIC_ID\n";
						$relatedJoins[$join] .= "LEFT JOIN b_uts_forum_message {$joinAlias}BUF_FM "
							. "ON {$joinAlias}BUF_FM.VALUE_ID = {$joinAlias}FM.ID";
					}
					break;

				case 'USER_OPTION':
					if (
						array_key_exists('IS_PINNED', $order)
						|| array_key_exists('IS_PINNED_IN_GROUP', $order)
					)
					{
						$tableName = UserOptionTable::getTableName();
						$relatedJoins[$join] = "LEFT JOIN {$tableName} {$joinAlias}TUO "
							. "ON {$joinAlias}TUO.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}TUO.USER_ID = {$userId}";
					}
					break;

				case 'COUNTERS':
					if (
						in_array('WITH_COMMENT_COUNTERS', $filterKeys, true)
						|| in_array('PROJECT_EXPIRED', $filterKeys, true)
						|| in_array('PROJECT_NEW_COMMENTS', $filterKeys, true)
					)
					{
						$tableName = Counter\CounterTable::getTableName();
						$relatedJoins[$join] = "LEFT JOIN {$tableName} {$joinAlias}TSC "
							. "ON {$joinAlias}TSC.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}TSC.USER_ID = {$userId}";
					}
					break;
				case 'SCRUM':
					$isScrumRequest = isset($filter['SCRUM_TASKS']) && ($filter['SCRUM_TASKS'] === 'Y');
					$hasStatusKey = (
						in_array('REAL_STATUS', $filterKeys, true)
						&& self::containCompletedInActiveSprintStatus($filter)
					);
					$hasStoryPointsKey = in_array('STORY_POINTS', $filterKeys, true);
					$hasEpicKey = in_array('EPIC', $filterKeys, true);

					$scrumJoin = '';
					$statusJoin = '';
					$storyPointsJoin = '';
					$epicJoin = '';

					$storyPointsValue = null;
					$epicValue = null;
					foreach (static::getFilteredValues($filter) as $filterValue)
					{
						if ($hasStoryPointsKey && isset($filterValue['STORY_POINTS']))
						{
							$storyPointsValue = $filterValue['STORY_POINTS'];
						}

						if ($hasEpicKey && isset($filterValue['EPIC']))
						{
							$epicValue = $filterValue['EPIC'];
						}
					}

					if ($isScrumRequest)
					{
						$scrumEntityTableName = EntityTable::getTableName();
						$scrumItemTableName = ItemTable::getTableName();

						$scrumJoin = " INNER JOIN {$scrumEntityTableName} {$joinAlias}BTSE
							ON {$joinAlias}BTSE.GROUP_ID = {$sourceAlias}.GROUP_ID
						";
						if (isset($filter['SCRUM_ENTITY_IDS']))
						{
							$entityIds = $filter['SCRUM_ENTITY_IDS'];
							$scrumJoin .= "AND {$joinAlias}BTSE.ID IN (" . implode(', ', $entityIds) . ")";
						}

						$scrumJoin .= " INNER JOIN {$scrumItemTableName} {$joinAlias}BTSI
							ON {$joinAlias}BTSI.SOURCE_ID = {$sourceAlias}.ID
							AND {$joinAlias}BTSI.ENTITY_ID = {$joinAlias}BTSE.ID
							AND {$joinAlias}BTSI.ACTIVE = 'Y'
						";
					}

					if ($hasStatusKey)
					{
						$scrumEntityTableName = EntityTable::getTableName();
						$scrumItemTableName = ItemTable::getTableName();

						$activeSprintStatus = EntityForm::SPRINT_ACTIVE;

						$statusJoin = " LEFT JOIN {$scrumEntityTableName} {$joinAlias}TSE
							ON {$joinAlias}TSE.GROUP_ID = {$sourceAlias}.GROUP_ID
							AND {$joinAlias}TSE.STATUS = '{$activeSprintStatus}'
						";

						$statusJoin .= " LEFT JOIN {$scrumItemTableName} {$joinAlias}TSI
							ON {$joinAlias}TSI.SOURCE_ID = {$sourceAlias}.ID
							AND {$joinAlias}TSI.ENTITY_ID = {$joinAlias}TSE.ID
						";
					}

					if ($hasStoryPointsKey)
					{
						$scrumEntityTableName = EntityTable::getTableName();
						$scrumItemTableName = ItemTable::getTableName();

						$storyPointsJoin = " INNER JOIN {$scrumEntityTableName} {$joinAlias}TSES
							ON {$joinAlias}TSES.GROUP_ID = {$sourceAlias}.GROUP_ID
						";

						$storyPointsJoin .= " INNER JOIN {$scrumItemTableName} {$joinAlias}TSIS
							ON {$joinAlias}TSIS.SOURCE_ID = {$sourceAlias}.ID
							AND {$joinAlias}TSIS.ENTITY_ID = {$joinAlias}TSES.ID
						";

						if ($storyPointsValue === 'Y')
						{
							$storyPointsJoin .= " AND NULLIF({$joinAlias}TSIS.STORY_POINTS, '') IS NOT NULL";
						}
						else
						{
							$storyPointsJoin .= " AND NULLIF({$joinAlias}TSIS.STORY_POINTS, '') IS NULL";
						}
					}

					if ($hasEpicKey)
					{
						$epicId = (int)$epicValue;

						if ($epicId)
						{
							$scrumEntityTableName = EntityTable::getTableName();
							$scrumItemTableName = ItemTable::getTableName();

							$epicJoin = " INNER JOIN {$scrumEntityTableName} {$joinAlias}TSEE
								ON {$joinAlias}TSEE.GROUP_ID = {$sourceAlias}.GROUP_ID
							";

							$epicJoin .= " INNER JOIN {$scrumItemTableName} {$joinAlias}TSIE
								ON {$joinAlias}TSIE.SOURCE_ID = {$sourceAlias}.ID
								AND {$joinAlias}TSIE.ENTITY_ID = {$joinAlias}TSEE.ID
								AND {$joinAlias}TSIE.EPIC_ID = '{$epicId}'
							";
						}
					}

					$relatedJoins[$join] = $scrumJoin . $statusJoin . $storyPointsJoin . $epicJoin;

					break;

				case 'SCENARIO':
					if (
						in_array('SCENARIO_NAME', $select, true)
						|| in_array('SCENARIO_NAME', $filterKeys, true)
						|| array_key_exists('SCENARIO_NAME', $order)
					)
					{
						$tableName = \Bitrix\Tasks\Internals\Task\ScenarioTable::getTableName();
						$relatedJoins[$join] = "LEFT JOIN {$tableName} {$joinAlias}SCR "
							. "ON {$joinAlias}SCR.TASK_ID = {$sourceAlias}.ID";
					}
					break;

				case 'IM_CHAT':
					if (
						!Main\Loader::includeModule('im')
						|| !class_exists(\Bitrix\Im\Model\LinkTaskTable::class)
					)
					{
						break;
					}

					if (!in_array('IM_CHAT_CHAT_ID', $filterKeys))
					{
						break;
					}

					$dialogId = (int) $filter['IM_CHAT_CHAT_ID'];

					$dialogJoin = "
						INNER JOIN ". \Bitrix\Im\Model\LinkTaskTable::getTableName() ." {$joinAlias}CTT
							ON {$joinAlias}CTT.TASK_ID = {$sourceAlias}.ID
							AND {$joinAlias}CTT.CHAT_ID = {$dialogId}
					";

					$relatedJoins[$join] = $dialogJoin;

					break;
			}
		}

		return $relatedJoins;
	}

	/**
	 * Creates filter runtime options from given sub filter
	 *
	 * @param $filter
	 * @param $parameters
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function makeAccessFilterRuntimeOptions($filter, $parameters)
	{
		$runtimeOptions = [
			'FIELDS' => [],
			'FILTERS' => [],
		];

		$fields = [
			// ROLES
			'CREATED_BY' => true,
			'RESPONSIBLE_ID' => true,
			'ACCOMPLICE' => true,
			'AUDITOR' => true,
			'ROLEID' => true,

			// TASK FIELDS
			'ID' => true,
			'TITLE' => true,
			'PRIORITY' => true,
			'STATUS' => true,
			'GROUP_ID' => true,
			'TAG' => true,
			'MARK' => true,
			'ALLOW_TIME_TRACKING' => true,

			// DATES
			'DEADLINE' => true,
			'CREATED_DATE' => true,
			'CLOSED_DATE' => true,
			'DATE_START' => true,
			'START_DATE_PLAN' => true,
			'END_DATE_PLAN' => true,

			// DIFFICULT PARAMS
			'ACTIVE' => true,
			'PARAMS' => true,
			'PROBLEM' => true,
		];

		if (is_array($filter) && !empty($filter))
		{
			foreach ($filter as $key => $value)
			{
				$newKey =
					mb_substr((string)$key, 0, 12) === '::SUBFILTER-'
						? mb_substr((string)$key, 12)
						: null
				;

				if ($newKey && ($fields[$newKey] ?? null))
				{
					$fieldRuntimeOptions = static::getFieldRuntimeOptions($newKey, $value, $parameters);

					$runtimeOptions['FIELDS'] = array_merge($runtimeOptions['FIELDS'], $fieldRuntimeOptions['FIELDS']);
					$runtimeOptions['FILTERS'] = array_merge($runtimeOptions['FILTERS'],
						$fieldRuntimeOptions['FILTERS']);
				}
			}
		}

		return $runtimeOptions;
	}

	/**
	 * Returns field's runtime options
	 *
	 * @param $key
	 * @param $value
	 * @param $parameters
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function getFieldRuntimeOptions($key, $value, $parameters)
	{
		$runtimeOptions = [
			'FIELDS' => [],
			'FILTERS' => [],
		];
		$dates = ['DEADLINE', 'CREATED_DATE', 'CLOSED_DATE', 'DATE_START', 'START_DATE_PLAN', 'END_DATE_PLAN'];

		switch ($key)
		{
			case 'ID':
			case 'PRIORITY':
			case 'MARK':
			case 'ALLOW_TIME_TRACKING':
			case 'DEADLINE':
			case 'CREATED_DATE':
			case 'CLOSED_DATE':
			case 'DATE_START':
			case 'START_DATE_PLAN':
			case 'END_DATE_PLAN':
				foreach ($value as $name => $val)
				{
					if (in_array($key, $dates))
					{
						$val = new Type\DateTime($val);
					}

					$fieldKeyData = static::parseFieldKey($name, $key);
					$runtimeOptions['FILTERS'][$key] = Query::filter()->where($key, $fieldKeyData['OPERATOR'], $val);
				}
				break;

			case 'TITLE':
				foreach ($value as $name => $val)
				{
					$fieldKeyData = static::parseFieldKey($name, $key);

					$field = Query::expr()->upper($key);
					$val = '%' . ToUpper($val) . '%';

					$runtimeOptions['FILTERS'][$key] = Query::filter()->where($field, $fieldKeyData['OPERATOR'], $val);
				}
				break;

			case 'CREATED_BY':
			case 'RESPONSIBLE_ID':
			case 'GROUP_ID':
				$fieldKeyData = static::parseFieldKey(key($value), $key, 'in');
				$runtimeOptions['FILTERS'][$key] = Query::filter()
					->where($key, $fieldKeyData['OPERATOR'], current($value));
				break;

			case 'STATUS':
				if (!empty($value['REAL_STATUS']))
				{
					$runtimeOptions['FILTERS'][$key] = Query::filter()->where($key, 'in', $value['REAL_STATUS']);
				}
				break;

			case 'ACCOMPLICE':
			case 'AUDITOR':
			case 'TAG':
				$parameters['USER_ID'] = $parameters['NAME'] = current($value);
				$parameters['TYPE_CONDITION'] = true;

				$runtimeOptions['FILTERS'][$key] = Query::filter()
					->whereExists(static::getSelectionExpressionByType($key, $parameters));
				break;

			case 'ROLEID':
			case 'PROBLEM':
				if ($key === 'ROLEID')
				{
					$filterOptions = static::getFilterOptionsFromRoleField($value);
				}
				else
				{
					$filterOptions = static::getFilterOptionsFromProblemField($value, $parameters);
				}

				$runtimeOptions['FIELDS'] = $filterOptions['FIELDS'];
				$runtimeOptions['FILTERS'] = $filterOptions['FILTERS'];
				break;

			case 'ACTIVE':
				$date = $value[$key];
				$dateStart = $dateEnd = false;

				if (MakeTimeStamp($date['START']) > 0)
				{
					$dateStart = new Type\DateTime($date['START']);
				}
				if (MakeTimeStamp($date['END']))
				{
					$dateEnd = new Type\DateTime($date['END']);
				}

				if ($dateStart !== false && $dateEnd !== false)
				{
					$runtimeOptions['FILTERS'][$key] = Query::filter()->where(
						Query::filter()
							->logic('or')
							->where(
								Query::filter()
									->where('CREATED_DATE', '>=', $dateStart)
									->where('CLOSED_DATE', '<=', $dateEnd)
							)
							->where(
								Query::filter()
									->where('CHANGED_DATE', '>=', $dateStart)
									->where('CHANGED_DATE', '<=', $dateEnd)
							)
							->where(
								Query::filter()
									->where('CREATED_DATE', '<=', $dateStart)
									->where('CLOSED_DATE', '=', null)
							)
					);
				}
				break;

			case 'PARAMS':
				foreach ($value as $name => $val)
				{
					$fieldKeyData = static::parseFieldKey($name);
					$fieldName = $fieldKeyData['FIELD_NAME'];

					if ($fieldName == 'MARK' || $fieldName == 'ADD_IN_REPORT')
					{
						$operator = $fieldKeyData['OPERATOR'];
						$runtimeOptions['FILTERS'][$fieldName] = Query::filter()->where($fieldName, $operator, $val);
					}
					elseif ($fieldName == 'FAVORITE')
					{
						$runtimeOptions['FIELDS'][$fieldName] = new Entity\ReferenceField(
							'FVT',
							FavoriteTable::class,
							Join::on('ref.TASK_ID', 'this.ID')
								->where('ref.USER_ID', $parameters['USER_ID'])
						);
						$runtimeOptions['FILTERS'][$fieldName] = Query::filter()->where('FVT.TASK_ID', '!=', null);
					}
					elseif ($fieldName == 'OVERDUED')
					{
						$runtimeOptions['FILTERS'][$fieldName] = Query::filter()
							->where('DEADLINE', '!=', null)
							->where('CLOSED_DATE', '!=', null)
							->whereColumn('DEADLINE', '<', 'CLOSED_DATE');
					}
				}
				break;
		}

		return $runtimeOptions;
	}

	/**
	 * Tries to parse string like '>=DEADLINE' to separate operator '>=' suitable for orm and pure name 'DEADLINE'
	 *
	 * @param $key
	 * @param string $fieldName
	 * @param string $defaultOperator
	 * @return array
	 */
	private static function parseFieldKey($key, $fieldName = '', $defaultOperator = '=')
	{
		$operators = [
			'>=' => '>=',
			'<=' => '<=',
			'!=' => '!=',
			'%' => 'like',
			'=%' => 'like',
			'%=' => 'like',
			'=' => '=',
			'>' => '>',
			'<' => '<',
			'!' => '!=',
			'@' => 'in',
		];

		if ($fieldName)
		{
			$operator = str_replace($fieldName, '', $key);
			$operator = ($operator && isset($operators[$operator]) ? $operators[$operator] : $defaultOperator);
		}
		else
		{
			$pattern = '/^(' . implode('|', array_keys($operators)) . ')/';
			$matches = [];

			preg_match($pattern, $key, $matches);

			if (!empty($matches))
			{
				$operator = $operators[$matches[0]];
				$fieldName = str_replace($matches[0], '', $key);
			}
			else
			{
				$operator = $defaultOperator;
				$fieldName = $key;
			}
		}

		return [
			'OPERATOR' => $operator,
			'FIELD_NAME' => $fieldName,
		];
	}

	/**
	 * Returns role field type based on its conditions
	 *
	 * @param $role
	 * @return string
	 */
	private static function getRoleFieldType($role)
	{
		if (array_key_exists('MEMBER', $role))
		{
			return 'MEMBER';
		}

		if (array_key_exists('=CREATED_BY', $role))
		{
			return 'CREATED_BY';
		}

		if (array_key_exists('=RESPONSIBLE_ID', $role))
		{
			return 'RESPONSIBLE_ID';
		}

		if (array_key_exists('=ACCOMPLICE', $role))
		{
			return 'ACCOMPLICE';
		}

		if (array_key_exists('=AUDITOR', $role))
		{
			return 'AUDITOR';
		}

		return '';
	}

	/**
	 * Returns filter options of role filter field
	 *
	 * @param $role
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function getFilterOptionsFromRoleField($role)
	{
		$fields = [];
		$filters = [];

		$key = 'ROLE_';
		$roleType = static::getRoleFieldType($role);
		$userId = ($role[($roleType === 'MEMBER' ? '' : '=') . $roleType] ?? null);

		$referenceFilter = Query::filter()
			->whereColumn('ref.TASK_ID', 'this.ID')
			->where('ref.USER_ID', $userId);

		switch ($roleType)
		{
			case 'MEMBER':
				$fields[$key . $roleType] = static::getMemberTableReferenceField($referenceFilter);
				break;

			case 'CREATED_BY':
			case 'RESPONSIBLE_ID':
			case 'ACCOMPLICE':
			case 'AUDITOR':
				$map = [
					'CREATED_BY' => 'O',
					'RESPONSIBLE_ID' => 'R',
					'ACCOMPLICE' => 'A',
					'AUDITOR' => 'U',
				];
				$referenceFilter->where('ref.TYPE', $map[$roleType]);

				$fields[$key . $roleType] = static::getMemberTableReferenceField($referenceFilter);

				if ($roleType == 'CREATED_BY')
				{
					$filters[$key . $roleType] = Query::filter()->whereColumn('CREATED_BY', '!=', 'RESPONSIBLE_ID');
				}
				break;
		}

		return [
			'FIELDS' => $fields,
			'FILTERS' => $filters,
		];
	}

	/**
	 * Returns reference field for joining member table
	 *
	 * @param $referenceFilter
	 * @return Entity\ReferenceField
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function getMemberTableReferenceField($referenceFilter)
	{
		$joinOn = $referenceFilter;
		$joinType = ['join_type' => 'inner'];

		return new Entity\ReferenceField('TM', MemberTable::class, $joinOn, $joinType);
	}

	/**
	 * Returns filter options of problem filter field
	 *
	 * @param $problem
	 * @param $parameters
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function getFilterOptionsFromProblemField($problem, $parameters)
	{
		$fields = [];
		$filters = [];

		if (array_key_exists('VIEWED', $problem))
		{
			$userId = ($problem['VIEWED_BY'] ?: $parameters['USER_ID']);
			$filterKey = 'PROBLEM_NOT_VIEWED';

			$fields[$filterKey] = new Entity\ReferenceField(
				'TV',
				ViewedTable::class,
				Join::on('ref.TASK_ID', 'this.ID')
					->where('ref.USER_ID', $userId)
			);
			$filters[$filterKey] = Query::filter()
				->where('TV.USER_ID', null)
				->where('STATUS', 'in', [1, 2]);
		}
		elseif ($problemFilter = static::parseLogicProblemFilter($problem))
		{
			$filters['PROBLEM'] = $problemFilter;
		}

		return [
			'FIELDS' => $fields,
			'FILTERS' => $filters,
		];
	}

	/**
	 * Parse logic filter
	 *
	 * @param $problem
	 * @return array|\Bitrix\Main\ORM\Query\Filter\ConditionTree
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private static function parseLogicProblemFilter($problem)
	{
		$filter = Query::filter();

		foreach ($problem as $key => $condition)
		{
			if (static::isSubFilterKey($key))
			{
				$filter->where(static::parseLogicProblemFilter($condition));
				continue;
			}

			if ($key === '::LOGIC')
			{
				$filter->logic($condition);
				continue;
			}

			$fieldKeyData = static::parseFieldKey($key);
			$fieldKeyName = ($fieldKeyData['FIELD_NAME'] === 'REAL_STATUS' ? 'STATUS' : $fieldKeyData['FIELD_NAME']);
			$fieldKeyOperator = $fieldKeyData['OPERATOR'];

			if (in_array($fieldKeyName, ['IS_MUTED', 'IS_PINNED', 'IS_PINNED_IN_GROUP', 'WITH_NEW_COMMENTS'], true))
			{
				continue;
			}

			if (mb_strpos($fieldKeyName, 'REFERENCE:') === 0)
			{
				$filter->whereColumn(mb_substr($fieldKeyName, 10), $fieldKeyOperator, $condition);
			}
			elseif ($fieldKeyOperator === '=')
			{
				if ($condition === null)
				{
					$filter->whereNull($fieldKeyName);
				}
				elseif (is_array($condition))
				{
					$filter->where($fieldKeyName, 'in', $condition);
				}
			}
			else
			{
				$filter->where($fieldKeyName, $fieldKeyOperator, $condition);
			}
		}

		return ($filter->hasConditions() ? $filter : []);
	}

	/**
	 * Gets selection sql expression by expression type
	 *
	 * @param $type
	 * @param $parameters
	 * @return SqlExpression|string
	 */
	private static function getSelectionExpressionByType($type, $parameters)
	{
		try
		{
			switch ($type)
			{
				case 'MEMBER':
				case 'ACCOMPLICE':
				case 'AUDITOR':
					$userIdsConditions = [];
					foreach ($parameters['USER_ID'] as $userId)
					{
						$userIdsConditions[] = "(TM.USER_ID = '" . intval($userId) . "')";
					}
					$typeCondition = ($parameters['TYPE_CONDITION'] ? ' AND TM.TYPE = ?s' : '');

					$sql = new SqlExpression(
						'SELECT TM.?# FROM ?# TM WHERE TM.?# = ?#.ID AND ('
						. implode(" OR ", $userIdsConditions)
						. ')'
						. $typeCondition,
						'TASK_ID',
						'b_tasks_member',
						'TASK_ID',
						'tasks_internals_task',
						($type == 'ACCOMPLICE' ? 'A' : 'U')
					);
					break;

				case 'TAG':
					$sql = new SqlExpression(
						'SELECT TT.?# FROM ?# TT WHERE TT.?# = ?#.ID AND TT.NAME = ?s',
						'TASK_ID',
						'b_tasks_tag',
						'TASK_ID',
						'tasks_internals_task',
						$parameters['NAME']
					);
					break;

				default:
					$sql = '';
					break;
			}
		}
		catch (Exception $ex)
		{
			$sql = '';
		}

		return $sql;
	}

	/**
	 * @param $filter
	 * @return array
	 */
	public static function makePossibleForwardedFilter($filter)
	{
		$result = [];

		$allowedFields = [
			'ID' => true, // number_wo_nulls
			'TITLE' => true, // string
			'STATUS_CHANGED_BY' => true, // number
			'SITE_ID' => true, // string_equal

			'PRIORITY' => true, // number_wo_nulls
			'STAGE_ID' => true, // number_wo_nulls
			'RESPONSIBLE_ID' => true, // number_wo_nulls
			'TIME_ESTIMATE' => true, // number_wo_nulls
			'CREATED_BY' => true, // number_wo_nulls
			'GUID' => true, // string
			'XML_ID' => true, // string_equal
			'MARK' => true, // string_equal
			'ALLOW_CHANGE_DEADLINE' => true, // string_equal
			'ALLOW_TIME_TRACKING' => true, // string_equal
			'ADD_IN_REPORT' => true, // string_equal
			'GROUP_ID' => true, // number
			'PARENT_ID' => true, // number
			'FORUM_TOPIC_ID' => true, // number
			'MATCH_WORK_TIME' => true, // string_equal

			//dates
			/*
			'DATE_START' => true,
			'DEADLINE' => true,
			'START_DATE_PLAN' => true,
			'END_DATE_PLAN' => true,
			'CREATED_DATE' => true,
			'STATUS_CHANGED_DATE' => true,
			 */
		];

		$stringEqual = [
			'SITE_ID' => true, // string_equal
			'XML_ID' => true, // string_equal
			'MARK' => true, // string_equal
			'ALLOW_CHANGE_DEADLINE' => true, // string_equal
			'ALLOW_TIME_TRACKING' => true, // string_equal
			'ADD_IN_REPORT' => true, // string_equal
			'MATCH_WORK_TIME' => true, // string_equal
		];

		if (is_array($filter) && !empty($filter))
		{
			// cannot forward filer with LOGIC OR or LOGIC NOT
			if (array_key_exists('LOGIC', $filter) && $filter['LOGIC'] != 'AND')
			{
				return $result;
			}
			if (array_key_exists('::LOGIC', $filter) && $filter['::LOGIC'] != 'AND')
			{
				return $result;
			}

			$filter = \Bitrix\Tasks\Internals\DataBase\Helper\Common::parseFilter($filter);
			foreach ($filter as $k => $condition)
			{
				$field = $condition['FIELD'];

				if (!array_key_exists($field, $allowedFields))
				{
					continue;
				}

				// convert like into strict check
				if (array_key_exists($field, $stringEqual))
				{
					// '' => '='
					if ($condition['OPERATION'] == 'E')
					{
						$condition['OPERATION'] = 'I';
						unset($condition['ORIG_KEY']);
					}
					// '!' => '!='
					if ($condition['OPERATION'] == 'N')
					{
						$condition['OPERATION'] = 'NI';
						unset($condition['ORIG_KEY']);
					}
				}

				// actually, allow only "equal" and "not equal"
				$op = $condition['OPERATION'];
				if ($op != 'E' && $op != 'I' && $op != 'N' && $op != 'NI')
				{
					continue;
				}

				$result[] = $condition;
			}

			$result = \Bitrix\Tasks\Internals\DataBase\Helper\Common::makeFilter($result);
		}

		return $result;
	}

	public static function needAccessRestriction(array $arFilter, $arParams)
	{
		if (
			is_array($arParams)
			&& array_key_exists('USER_ID', $arParams)
			&& $arParams['USER_ID'] > 0
		)
		{
			$userId = (int)$arParams['USER_ID'];
		}
		else
		{
			$userId = User::getId();
		}

		return
			!User::isSuper($userId)
			&& ($arFilter['CHECK_PERMISSIONS'] ?? null) != 'N' // and not setted flag "skip permissions check"
			&& ($arFilter['SUBORDINATE_TASKS'] ?? null) != 'Y' // and not rights via subordination
		;
	}

	/**
	 * @param array $filter
	 * @param string $aliasPrefix
	 * @param array $params
	 * @return string
	 */
	private static function GetRootSubQuery($filter = [], $aliasPrefix = '', $params = [])
	{
		$filter = ($params['SOURCE_FILTER'] ?? $filter);
		$userId = ($params['USER_ID'] ?? User::getId());

		$sqlSearch = ["(PT.ID = " . $aliasPrefix . "T.PARENT_ID)"];

		if (
			isset($filter['SAME_GROUP_PARENT'])
			&& $filter['SAME_GROUP_PARENT'] === 'Y'
		)
		{
			$sqlSearch[] = "(PT.GROUP_ID = " . $aliasPrefix . "T.GROUP_ID
				OR (PT.GROUP_ID IS NULL AND " . $aliasPrefix . "T.GROUP_ID IS NULL)
				OR (PT.GROUP_ID IS NULL AND " . $aliasPrefix . "T.GROUP_ID = 0)
				OR (PT.GROUP_ID = 0 AND " . $aliasPrefix . "T.GROUP_ID IS NULL)
				)";
		}

		unset($filter["ONLY_ROOT_TASKS"], $filter["SAME_GROUP_PARENT"]);

		$searchParams = [];
		if (array_key_exists('ENABLE_LEGACY_ACCESS', $params))
		{
			$searchParams['ENABLE_LEGACY_ACCESS'] = $params['ENABLE_LEGACY_ACCESS'];
		}

		$optimized = static::tryOptimizeFilter($filter, 'PT', 'PTM_SPEC');
		$sqlSearch = array_merge($sqlSearch, CTasks::GetFilter($optimized['FILTER'], "P", $searchParams));

		$relatedParams = [
			'USER_ID' => $userId,
			'JOIN_ALIAS' => 'P',
			'SOURCE_ALIAS' => 'PT',
		];
		$relatedJoins = static::getRelatedJoins([], $filter, [], $relatedParams);
		$relatedJoins = array_merge($relatedJoins, $optimized['JOINS']);

		return "
			SELECT PT.ID
			FROM b_tasks PT
			" . implode("\n", $relatedJoins) . "
			WHERE " . implode(" AND ", $sqlSearch) . "
		";
	}

	/**
	 * @param array $arFilter
	 * @param array $arParams
	 * @param array $arGroupBy
	 * @return bool|CDBResult
	 */
	public static function GetCount($arFilter = [], $arParams = [], $arGroupBy = [])
	{
		/**
		 * @global CDatabase $DB
		 */
		global $DB, $USER_FIELD_MANAGER;

		$provider = new \Bitrix\Tasks\Provider\TaskProvider($DB, $USER_FIELD_MANAGER);
		return $provider->getCount($arFilter, $arParams, $arGroupBy);
	}

	/**
	 * @param $sql
	 * @param $arParams
	 * @return string
	 *
	 *
	 * @deprecated since tasks 20.6.0
	 */
	public static function appendJoinRights($sql, $arParams)
	{
		$arParams['THIS_TABLE_ALIAS'] = 'T';

		$access = \Bitrix\Tasks\Internals\RunTime\Task::getAccessCheckSql($arParams);
		$accessSql = $access['sql'];

		if ($accessSql != '')
		{
			if (isset($arParams['PUT_SELECT_INTO_WHERE']) && $arParams['PUT_SELECT_INTO_WHERE'])
			{
				$sql .= "T.ID IN ($accessSql)";
			}
			else
			{
				$sql .= "\n\n/*access BEGIN*/\n\n inner join ($accessSql) TASKS_ACCESS on T.ID = TASKS_ACCESS.TASK_ID\n\n/*access END*/\n\n";
			}
		}

		return $sql;
	}

	/**
	 * Optimizes filter
	 *
	 * @param array $filter
	 * @param $sourceTableAlias
	 * @param $joinTableAlias
	 * @return array
	 */
	public static function tryOptimizeFilter(array $filter, $sourceTableAlias = 'T', $joinTableAlias = 'TM')
	{
		$additionalJoins = [];
		$roleKey = '::SUBFILTER-ROLEID';

		$joinAlias = $joinTableAlias;
		$sourceAlias = $sourceTableAlias;

		// get rid of ::SUBFILTER-ROOT if can
		if (array_key_exists('::SUBFILTER-ROOT', $filter) && count($filter) == 1)
		{
			// we have only one element in the root, and logic is not "OR". then we could remove subfilter-root
			$filter = $filter['::SUBFILTER-ROOT'];
		}

		// we can optimize only if there is no "or-logic"
		if (
			(!array_key_exists('::LOGIC', $filter) || $filter['::LOGIC'] !== 'OR')
			&& (!array_key_exists('LOGIC', $filter) || $filter['LOGIC'] !== 'OR')
		)
		{
			// MEMBER
			if (
				array_key_exists('MEMBER', $filter)
				|| isset($filter[$roleKey])
				&& array_key_exists('MEMBER', $filter[$roleKey])
			)
			{
				if (array_key_exists('MEMBER', $filter))
				{
					$member = intval($filter['MEMBER']);
					unset($filter['MEMBER']);
				}
				else
				{
					$member = intval($filter[$roleKey]['MEMBER']);
					unset($filter[$roleKey]);
				}

				$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$member}";
			}
			// DOER
			elseif (array_key_exists('DOER', $filter))
			{
				$doer = intval($filter['DOER']);
				unset($filter['DOER']);

				$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$doer} AND {$joinAlias}.TYPE in ('R', 'A')";
			}
			// RESPONSIBLE
			elseif (isset($filter[$roleKey]) && array_key_exists('=RESPONSIBLE_ID', $filter[$roleKey]))
			{
				$responsible = (int)$filter[$roleKey]['=RESPONSIBLE_ID'];
				unset($filter[$roleKey]);

				$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$responsible} AND {$joinAlias}.TYPE = 'R'";
			}
			// CREATOR
			elseif (isset($filter[$roleKey]) && array_key_exists('=CREATED_BY', $filter[$roleKey]))
			{
				$creator = (int)$filter[$roleKey]['=CREATED_BY'];
				unset($filter[$roleKey]['=CREATED_BY']);

				if (!empty($filter[$roleKey]))
				{
					$filter += $filter[$roleKey];
				}
				unset($filter[$roleKey]);

				$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$creator} AND {$joinAlias}.TYPE = 'O'";
			}
			// ACCOMPLICE
			elseif (
				array_key_exists('ACCOMPLICE', $filter)
				|| isset($filter[$roleKey])
				&& array_key_exists('=ACCOMPLICE', $filter[$roleKey])
			)
			{
				if (array_key_exists('ACCOMPLICE', $filter))
				{
					if (!is_array($filter['ACCOMPLICE'])) // we have single value, not array which will cause "in ()" instead of =
					{
						$accomplice = intval($filter['ACCOMPLICE']);
						unset($filter['ACCOMPLICE']);

						$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$accomplice} AND {$joinAlias}.TYPE = 'A'";
					}
				}
				else
				{
					if (!is_array($filter[$roleKey]['=ACCOMPLICE']))
					{
						$accomplice = intval($filter[$roleKey]['=ACCOMPLICE']);
						unset($filter[$roleKey]);

						$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$accomplice} AND {$joinAlias}.TYPE = 'A'";
					}
				}
			}
			// AUDITOR
			elseif (
				array_key_exists('AUDITOR', $filter)
				|| isset($filter[$roleKey])
				&& array_key_exists('=AUDITOR', $filter[$roleKey])
			)
			{
				if (array_key_exists('AUDITOR', $filter))
				{
					if (!is_array($filter['AUDITOR'])) // we have single value, not array which will cause "in ()" instead of =
					{
						$auditor = intval($filter['AUDITOR']);
						unset($filter['AUDITOR']);

						$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$auditor} AND {$joinAlias}.TYPE = 'U'";
					}
				}
				else
				{
					if (!is_array($filter[$roleKey]['=AUDITOR']))
					{
						$auditor = intval($filter[$roleKey]['=AUDITOR']);
						unset($filter[$roleKey]);

						$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$auditor} AND {$joinAlias}.TYPE = 'U'";
					}
				}
			}
		}

		return [
			'FILTER' => $filter,
			'JOINS' => $additionalJoins,
		];
	}

	/**
	 * Gets user's id task list we are looking at
	 *
	 * @param $filter
	 * @param $currentUserId
	 * @return mixed
	 */
	public static function getViewedUserId($filter, $currentUserId)
	{
		$viewedBy = static::getViewedBy($filter, $currentUserId);

		if ($viewedBy !== $currentUserId)
		{
			$viewedUserId = $viewedBy;
		}
		else
		{
			if (array_key_exists('::SUBFILTER-ROLEID', $filter) && !empty($filter['::SUBFILTER-ROLEID']))
			{
				$viewedUserId = current($filter['::SUBFILTER-ROLEID']);
			}
			else
			{
				$viewedUserId = $currentUserId;
			}
		}

		return $viewedUserId;
	}

	/**
	 * Get user id b_tasks_viewed table joined on by filter or default value if filter haven't VIEWED_BY option
	 *
	 * @param $filter
	 * @param $defaultValue
	 * @return int
	 */
	public static function getViewedBy($filter, $defaultValue)
	{
		$viewedBy = $defaultValue;

		if (
			array_key_exists('::SUBFILTER-PROBLEM', $filter)
			&& array_key_exists('VIEWED_BY', $filter['::SUBFILTER-PROBLEM'])
			&& intval($filter['::SUBFILTER-PROBLEM']['VIEWED_BY'])
		)
		{
			$viewedBy = intval($filter['::SUBFILTER-PROBLEM']['VIEWED_BY']);
		}
		elseif (array_key_exists('VIEWED_BY', $filter) && intval($filter['VIEWED_BY']))
		{
			$viewedBy = intval($filter['VIEWED_BY']);
		}

		return $viewedBy;
	}

	public static function getUsersViewedTask($taskId)
	{
		global $DB;

		$taskId = (int)$taskId;

		$res = $DB->query(
			"SELECT USER_ID
			FROM b_tasks_viewed
			WHERE TASK_ID = " . $taskId,
			true    // ignore DB errors
		);

		if ($res === false)
		{
			throw new TasksException ('', TasksException::TE_SQL_ERROR);
		}

		$arUsers = [];

		while ($ar = $res->fetch())
		{
			$arUsers[] = (int)$ar['USER_ID'];
		}

		return ($arUsers);
	}

	public static function GetCountInt($arFilter = [], $arParams = [])
	{
		$count = 0;

		$rsCount = CTasks::GetCount($arFilter, $arParams);
		if ($arCount = $rsCount->Fetch())
		{
			$count = intval($arCount["CNT"]);
		}

		return $count;
	}

	public static function GetChildrenCount($filter, $parentIds)
	{
		if (!$parentIds)
		{
			return false;
		}

		global $DB;

		$obUserFieldsSql = new CUserTypeSQL;
		$obUserFieldsSql->SetEntity("TASKS_TASK", "T.ID");
		$obUserFieldsSql->SetFilter($filter);

		if (!is_array($filter))
		{
			$filter = [];
		}

		$userId = User::getId();

		$filter["PARENT_ID"] = $parentIds;
		unset($filter["ONLY_ROOT_TASKS"]);

		$sqlSearch = CTasks::GetFilter($filter);

		$r = $obUserFieldsSql->GetFilter();
		if ($r <> '')
		{
			$sqlSearch[] = "(" . $r . ")";
		}

		$relatedJoins = static::getRelatedJoins([], $filter, [], ['USER_ID' => $userId]);
		$relatedJoins = implode("\n", $relatedJoins);

		$strSql = "
			SELECT T.PARENT_ID, COUNT(T.ID) AS CNT
			FROM (";

		$strSql .= "
			SELECT T.PARENT_ID AS PARENT_ID, T.ID
			FROM b_tasks T
			INNER JOIN b_user CU ON CU.ID = T.CREATED_BY
			INNER JOIN b_user RU ON RU.ID = T.RESPONSIBLE_ID
			" . $relatedJoins . "
			" . $obUserFieldsSql->GetJoin("T.ID") . "
			" . (sizeof($sqlSearch) ? "WHERE " . implode(" AND ", $sqlSearch) : "") . "
			GROUP BY T.ID
		";

		$strSql .= ") T
			GROUP BY T.PARENT_ID
		";

		$res = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);

		return $res;
	}

	/**
	 *
	 * @access private
	 */
	public static function GetOriginatorsByFilter($arFilter, $loggedInUserId)
	{
		return static::GetFieldGrouppedByFilter('CREATED_BY', $arFilter, $loggedInUserId);
	}

	/**
	 *
	 * @access private
	 */
	public static function GetResponsiblesByFilter($arFilter, $loggedInUserId)
	{
		return static::GetFieldGrouppedByFilter('RESPONSIBLE_ID', $arFilter, $loggedInUserId);
	}

	private static function GetFieldGrouppedByFilter($column, $arFilter, $loggedInUserId)
	{
		CTaskAssert::assert($loggedInUserId && is_array($arFilter));

		$arSqlSearch = CTasks::GetFilter($arFilter, '', ['USER_ID' => $loggedInUserId]);

		$keysFiltered = CTasks::GetFilteredKeys($arFilter);

		$bNeedJoinFavoritesTable = in_array('FAVORITE', $keysFiltered, true);

		$sql = "SELECT T." . $column . " AS USER_ID, COUNT(T.ID) AS TASKS_CNT
			FROM b_tasks T
			LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = " . $loggedInUserId . "

			" . ($bNeedJoinFavoritesTable ? "
				LEFT JOIN "
				. FavoriteTable::getTableName()
				. " FVT ON FVT.TASK_ID = T.ID and FVT.USER_ID = '"
				. $loggedInUserId
				/*always int, no sqli*/
				. "'
				" : "") . "

			WHERE " . implode('AND', $arSqlSearch)
			. " GROUP BY T." . $column;

		return $GLOBALS['DB']->query($sql);
	}

	public static function GetSubordinateSql($sAliasPrefix = "", $arParams = [], $behaviour = [])
	{
		$userId = $arParams['USER_ID'] ?? 0;
		$arDepsIDs = Integration\Intranet\Department::getSubordinateIds($userId, true);

		if (sizeof($arDepsIDs))
		{
			$rsDepartmentField = CUserTypeEntity::GetList([], ["ENTITY_ID" => "USER", "FIELD_NAME" => "UF_DEPARTMENT"]);
			if ($arDepartmentField = $rsDepartmentField->Fetch())
			{
				return CTasks::GetDeparmentSql($arDepsIDs, $sAliasPrefix, $arParams, $behaviour);
			}
		}

		return false;
	}

	public static function GetDeparmentSql($arDepsIDs, $sAliasPrefix = "", $arParams = [], $behaviour = [])
	{
		if (!is_array($arDepsIDs))
		{
			$arDepsIDs = [intval($arDepsIDs)];
		}
		else
		{
			$arDepsIDs = array_map('intval', $arDepsIDs);
		}

		if (!is_array($behaviour))
		{
			$behaviour = [];
		}
		if (!isset($behaviour['ALIAS']))
		{
			$behaviour['ALIAS'] = $sAliasPrefix;
		}
		if (!isset($arParams['FIELDS']))
		{
			$arParams['FIELDS'] = [];
		}

		$a = $sAliasPrefix;
		$b = $behaviour;
		$f =& $arParams['FIELDS'];

		//static::placeFieldSql('CREATED_BY', 	$b, $f)

		$rsDepartmentField = CUserTypeEntity::GetList([], ["ENTITY_ID" => "USER", "FIELD_NAME" => "UF_DEPARTMENT"]);
		$cntOfDepartments = count($arDepsIDs);
		if ($cntOfDepartments && $arDepartmentField = $rsDepartmentField->Fetch())
		{
			$strConstraint = $sAliasPrefix . "BUF1.VALUE_INT IN (" . implode(",", $arDepsIDs) . ")";

			// EXISTS!
			$strSql = "
				SELECT
					'x'
				FROM
					b_utm_user " . $sAliasPrefix . "BUF1
				WHERE
					" . $sAliasPrefix . "BUF1.FIELD_ID = " . $arDepartmentField["ID"] . "
				AND
					(" . $sAliasPrefix . "BUF1.VALUE_ID = " . static::placeFieldSql('RESPONSIBLE_ID', $b, $f) . "
						OR " . $sAliasPrefix . "BUF1.VALUE_ID = " . static::placeFieldSql('CREATED_BY', $b, $f) . "
						OR EXISTS(
							SELECT 'x'
							FROM b_tasks_member " . $sAliasPrefix . "DSTM
							WHERE " . $sAliasPrefix . "DSTM.TASK_ID = " . static::placeFieldSql('ID', $b, $f) . "
								AND " . $sAliasPrefix . "DSTM.USER_ID = " . $sAliasPrefix . "BUF1.VALUE_ID
						)
					)
				AND
					" . $strConstraint . "
			";

			return $strSql;
		}

		return false;
	}

	/**
	 * Use CTaskItem->update() instead (with key 'ACCOMPLICES')
	 *
	 * @deprecated
	 */
	public static function AddAccomplices($ID, $arAccompleces = [])
	{
		if ($arAccompleces)
		{
			$arAccompleces = array_unique($arAccompleces);
			foreach ($arAccompleces as $accomplice)
			{
				$arMember = [
					"TASK_ID" => $ID,
					"USER_ID" => $accomplice,
					"TYPE" => "A",
				];
				$member = new CTaskMembers();
				$member->Add($arMember);
			}
		}
	}

	/**
	 * Use CTaskItem->update() instead (with key 'AUDITORS')
	 *
	 * @deprecated
	 */
	public static function AddAuditors($ID, $arAuditors = [])
	{
		if ($arAuditors)
		{
			$arAuditors = array_unique($arAuditors);
			foreach ($arAuditors as $auditor)
			{
				$arMember = [
					"TASK_ID" => $ID,
					"USER_ID" => $auditor,
					"TYPE" => "U",
				];
				$member = new CTaskMembers();
				$member->Add($arMember);
			}
		}
	}

	static function AddFiles($ID, $arFiles = [], $arParams = [])
	{
		$arFilesIds = [];

		$userId = null;

		$bCheckRightsOnFiles = false;

		if (is_array($arParams))
		{
			if (isset($arParams['USER_ID']) && ($arParams['USER_ID'] > 0))
			{
				$userId = (int)$arParams['USER_ID'];
			}

			if (isset($arParams['CHECK_RIGHTS_ON_FILES']))
			{
				$bCheckRightsOnFiles = $arParams['CHECK_RIGHTS_ON_FILES'];
			}
		}

		if ($userId === null)
		{
			$userId = User::getId();
			if (!$userId)
			{
				$userId = User::getAdminId();
			}
		}

		if ($arFiles)
		{
			foreach ($arFiles as $file)
			{
				$arFilesIds[] = (int)$file;
			}

			if (count($arFilesIds))
			{
				CTaskFiles::AddMultiple(
					$ID,
					$arFilesIds,
					[
						'USER_ID' => $userId,
						'CHECK_RIGHTS_ON_FILES' => $bCheckRightsOnFiles,
					]
				);
			}
		}
	}

	/**
	 * @param $taskId
	 * @param $userId
	 * @param array $sourceTags
	 * @param null $effectiveUserId
	 */
	public static function AddTags($taskId, $userId, $sourceTags = [], $effectiveUserId = null, $groupId = null): void
	{
		(new Tag($userId))->set($taskId, $sourceTags);
	}

	function AddPrevious($ID, $arPrevious = [])
	{
		$oDependsOn = new CTaskDependence();
		$oDependsOn->DeleteByTaskID($ID);

		if ($arPrevious)
		{
			$arPrevious = array_unique(array_map('intval', $arPrevious));

			foreach ($arPrevious as $dependsOn)
			{
				$arDependsOn = [
					"TASK_ID" => $ID,
					"DEPENDS_ON_ID" => $dependsOn,
				];
				$oDependsOn = new CTaskDependence();
				$oDependsOn->Add($arDependsOn);
			}
		}
	}

	public static function Index($arTask, $tags)
	{
		$arTask['SE_TAG'] = $tags;
		Integration\Search\Task::index($arTask);
	}

	public static function OnSearchReindex($nextStep = [], $callback = null, $callback_method = '')
	{
		$arResult = [];
		$order = ['ID' => 'ASC'];
		$filter = [];

		if (
			isset($nextStep['MODULE'], $nextStep['ID'])
			&& $nextStep['MODULE'] === 'tasks'
			&& $nextStep['ID'] > 0
		)
		{
			$filter['>ID'] = (int)$nextStep['ID'];
		}
		else
		{
			$filter['>ID'] = 0;
		}

		$tasksResult = self::GetList($order, $filter);
		while ($task = $tasksResult->Fetch())
		{
			$taskId = $task['ID'];

			$members = self::getMembers($taskId);
			$task['ACCOMPLICES'] = $members[MemberTable::MEMBER_TYPE_ACCOMPLICE];
			$task['AUDITORS'] = $members[MemberTable::MEMBER_TYPE_AUDITOR];

			$path = self::getPathToTask($task);
			$permissions = self::__GetSearchPermissions($task);

			$result = [
				'ID' => $taskId,
				'TITLE' => $task['TITLE'],
				'BODY' => (strip_tags($task['DESCRIPTION']) ?: $task['TITLE']),
				'LAST_MODIFIED' => ($task['CHANGED_DATE'] ?: $task['CREATED_DATE']),
				'TAGS' => implode(',', self::getTags($taskId)),
				'URL' => $path,
				'SITE_ID' => $task['SITE_ID'],
				'PERMISSIONS' => $permissions,
			];

			if ($callback)
			{
				if (!call_user_func([$callback, $callback_method], $result))
				{
					return $result['ID'];
				}
			}
			else
			{
				$arResult[] = $result;
			}

			self::UpdateForumTopicIndex(
				$task['FORUM_TOPIC_ID'],
				'U',
				$task['RESPONSIBLE_ID'],
				'tasks',
				'view_all',
				$path,
				$permissions,
				$task['SITE_ID']
			);
		}

		if ($callback)
		{
			return false;
		}

		return $arResult;
	}

	private static function getTags(int $taskId): array
	{
		$tags = [];

		$tagsResult = CTaskTags::GetList([], ['TASK_ID' => $taskId]);
		while ($tag = $tagsResult->Fetch())
		{
			$tags[] = $tag['NAME'];
		}

		return $tags;
	}

	private static function getMembers(int $taskId): array
	{
		$members = array_fill_keys(MemberTable::possibleTypes(), []);

		$membersResult = CTaskMembers::GetList([], ['TASK_ID' => $taskId]);
		while ($member = $membersResult->Fetch())
		{
			$members[$member['TYPE']][] = $member['USER_ID'];
		}

		return $members;
	}

	private static function getPathToTask(array $task): string
	{
		// todo: get path form socnet
		if ($task['GROUP_ID'] > 0)
		{
			$path = str_replace(
				'#group_id#',
				$task['GROUP_ID'],
				COption::GetOptionString(
					'tasks',
					'paths_task_group_entry',
					'/workgroups/group/#group_id#/tasks/task/view/#task_id#/',
					$task['SITE_ID']
				)
			);
		}
		else
		{
			$path = str_replace(
				'#user_id#',
				$task['RESPONSIBLE_ID'],
				COption::GetOptionString(
					'tasks',
					'paths_task_user_entry',
					'/company/personal/user/#user_id#/tasks/task/view/#task_id#/',
					$task['SITE_ID']
				)
			);
		}

		return str_replace('#task_id#', $task['ID'], $path);
	}

	static function UpdateForumTopicIndex(
		$topicId,
		$entityType,
		$entityId,
		$feature,
		$operation,
		$path,
		$permissions,
		$siteId
	)
	{
		global $DB;

		if (!CModule::IncludeModule('forum'))
		{
			return;
		}

		$topicId = (int)$topicId;

		$forumTopicResult = $DB->Query("SELECT FORUM_ID FROM b_forum_topic WHERE ID = {$topicId}");
		if (!($forumTopic = $forumTopicResult->Fetch()))
		{
			return;
		}

		CSearch::ChangePermission('forum', $permissions, false, $forumTopic['FORUM_ID'], $topicId);

		$forumMessageResult = $DB->Query("SELECT ID FROM b_forum_message WHERE TOPIC_ID = {$topicId}");
		while ($message = $forumMessageResult->Fetch())
		{
			CSearch::ChangeSite('forum', [$siteId => $path], $message['ID']);
		}

		$params = [
			'feature_id' => "S{$entityType}_{$entityId}_{$feature}_{$operation}",
			'socnet_user' => $entityId,
		];

		CSearch::ChangeIndex(
			'forum',
			['PARAMS' => $params],
			false,
			$forumTopic['FORUM_ID'],
			$topicId
		);
	}

	public static function __GetSearchPermissions($arTask)
	{
		$arPermissions = [];

		// check task members
		if (!isset($arTask['ACCOMPLICES']) || !isset($arTask['AUDITORS']))
		{
			if (!isset($arTask['ACCOMPLICES']))
			{
				$arTask['ACCOMPLICES'] = [];
			}
			if (!isset($arTask['AUDITORS']))
			{
				$arTask['AUDITORS'] = [];
			}

			$members = self::getMembers($arTask['ID']);
			$arTask['ACCOMPLICES'] = array_merge($arTask['ACCOMPLICES'], $members[MemberTable::MEMBER_TYPE_ACCOMPLICE]);
			$arTask['AUDITORS'] = array_merge($arTask['AUDITORS'], $members[MemberTable::MEMBER_TYPE_AUDITOR]);
		}

		// group id is set, then take permissions from socialnetwork settings
		if ($arTask["GROUP_ID"] > 0 && CModule::IncludeModule("socialnetwork"))
		{
			$prefix = "SG" . $arTask["GROUP_ID"] . "_";
			$letter = CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_GROUP, $arTask["GROUP_ID"], "tasks",
				"view_all");
			switch ($letter)
			{
				case "N"://All
					$arPermissions[] = 'G2';
					break;
				case "L"://Authorized
					$arPermissions[] = 'AU';
					break;
				case "K"://Group members includes moderators and admins
					$arPermissions[] = $prefix . 'K';
				case "E"://Moderators includes admins
					$arPermissions[] = $prefix . 'E';
				case "A"://Admins
					$arPermissions[] = $prefix . 'A';
					break;
			}
		}

		// if neither "all users" nor "authorized user" enabled, turn permissions on at least for task members
		if (!in_array("G2", $arPermissions) && !in_array("AU", $arPermissions))
		{
			if (!$arTask["ACCOMPLICES"])
			{
				$arTask["ACCOMPLICES"] = [];
			}

			if (!$arTask["AUDITORS"])
			{
				$arTask["AUDITORS"] = [];
			}

			$arParticipants = array_unique(array_merge([$arTask["CREATED_BY"], $arTask["RESPONSIBLE_ID"]],
				$arTask["ACCOMPLICES"], $arTask["AUDITORS"]));
			foreach ($arParticipants as $userId)
			{
				$arPermissions[] = "U" . $userId;
			}

			$arDepartments = [];

			$arSubUsers = array_unique([$arTask['RESPONSIBLE_ID'], $arTask['CREATED_BY']]);

			foreach ($arSubUsers as $subUserId)
			{
				$arUserDepartments = CTasks::GetUserDepartments($subUserId);

				if (is_array($arUserDepartments) && count($arUserDepartments))
				{
					$arDepartments = array_merge($arDepartments, $arUserDepartments);
				}
			}

			$arDepartments = array_unique($arDepartments);
			$arManagersTmp = CTasks::GetDepartmentManagers($arDepartments);

			if (is_array($arManagersTmp))
			{
				$arManagers = array_keys($arManagersTmp);

				// Remove $arSubUsers from $arManagers
				$arManagers = array_diff($arManagers, $arSubUsers);

				foreach ($arManagers as $userId)
				{
					if (!in_array("U" . $userId, $arPermissions))
					{
						$arPermissions[] = "U" . $userId;
					}
				}
			}
		}

		// adimins always allowed to view search result
		$arPermissions[] = 'G1';

		return $arPermissions;
	}

	/**
	 * Agent handler for repeating tasks.
	 * Create new task based on given template.
	 *
	 * @param integer $templateId - id of task template
	 * @param integer $flipFlop unused
	 * @param mixed[] $debugHere
	 *
	 * @return string empty string.
	 * @deprecated
	 */
	public static function RepeatTaskByTemplateId($templateId, $flipFlop = 1, array &$debugHere = [])
	{
		if (\Bitrix\Tasks\Update\TemplateConverter::isProceed())
		{
			return 'CTasks::RepeatTaskByTemplateId(' . $templateId . ');';
		}

		return Replicator\Task\FromTemplate::repeatTask(
			$templateId,
			[
				// todo: get rid of use of CTasks one day...
				'AGENT_NAME_TEMPLATE' => 'CTasks::RepeatTaskByTemplateId(#ID#);',
				'RESULT' => &$debugHere,
			]
		);
	}

	/**
	 * @param $arParams
	 * @param bool $template
	 * @param integer $agentTime Time in server timezone
	 * @return bool|string
	 */
	public static function getNextTime($arParams, $template = false, $agentTime = false)
	{
		if (!is_array($arParams))
		{
			return false;
		}

		$templateData = false;
		if (is_array($template))
		{
			$templateData = $template;
		}
		elseif ($template = intval($template))
		{
			$item = \CTaskTemplates::getList([], ['ID' => $template], [], [],
				['CREATED_BY', 'REPLICATE_PARAMS', 'TPARAM_REPLICATION_COUNT'])->fetch();
			if ($item)
			{
				$templateData = $item;
			}
		}

		if (!$templateData)
		{
			$templateData = [];
		}
		$templateData['REPLICATE_PARAMS'] = $arParams;

		$result = Replicator\Task\FromTemplate::getNextTime($templateData, $agentTime);
		$rData = $result->getData();

		return $rData['TIME'] == '' ? false : $rData['TIME'];
	}

	public static function CanGivenUserDelete($userId, $taskCreatedBy, $taskGroupId,
		/** @noinspection PhpUnusedParameterInspection */ $site_id = SITE_ID)
	{
		$userId = (int)$userId;
		$taskGroupId = (int)$taskGroupId;

		$site_id = null;    // not used, left in function declaration for backward compatibility

		if ($userId <= 0)
		{
			throw new TasksException();
		}

		if (
			CTasksTools::IsAdmin($userId)
			|| CTasksTools::IsPortalB24Admin($userId)
			|| ($userId == $taskCreatedBy)
		)
		{
			return (true);
		}
		elseif (($taskGroupId > 0) && CModule::IncludeModule('socialnetwork'))
		{
			return (boolean)CSocNetFeaturesPerms::CanPerformOperation($userId, SONET_ENTITY_GROUP, $taskGroupId,
				"tasks", "delete_tasks");
		}

		return false;
	}

	public static function CanCurrentUserDelete($task, $site_id = SITE_ID)
	{
		if (!$userID = User::getId()) // wtf?
		{
			return false;
		}

		return (self::CanGivenUserDelete($userID, $task['CREATED_BY'], $task['GROUP_ID'], $site_id));
	}

	public static function CanGivenUserEdit($userId, $taskCreatedBy, $taskGroupId,
		/** @noinspection PhpUnusedParameterInspection */ $site_id = SITE_ID)
	{
		$userId = (int)$userId;
		$taskGroupId = (int)$taskGroupId;

		$site_id = null;    // not used, left in function declaration for backward compatibility    /** @noinspection PhpUnusedParameterInspection */

		if ($userId <= 0)
		{
			throw new TasksException();
		}

		if (
			CTasksTools::IsAdmin($userId)
			|| CTasksTools::IsPortalB24Admin($userId)
			|| ($userId == $taskCreatedBy)
		)
		{
			return (true);
		}
		elseif (($taskGroupId > 0) && CModule::IncludeModule('socialnetwork'))
		{
			return (boolean)CSocNetFeaturesPerms::CanPerformOperation($userId, SONET_ENTITY_GROUP, $taskGroupId,
				"tasks", "edit_tasks");
		}

		return false;
	}

	public static function CanCurrentUserEdit($task, $site_id = SITE_ID)
	{
		if (!$userID = User::getId())
		{
			return false;
		}

		return (self::CanGivenUserEdit($userID, $task['CREATED_BY'], $task['GROUP_ID'], $site_id));
	}

	/**
	 * @deprecated
	 * @see ViewedTable::set
	 */
	public static function UpdateViewed($TASK_ID, $USER_ID)
	{
		ViewedTable::set((int)$TASK_ID, (int)$USER_ID);
	}

	/**
	 * @deprecated
	 */
	public static function __updateViewed($TASK_ID, $USER_ID, $onTaskAdd = false)
	{
		$USER_ID = (int)$USER_ID;
		$TASK_ID = (int)$TASK_ID;

		$list = \Bitrix\Tasks\Internals\Task\ViewedTable::getList([
			"select" => ["TASK_ID", "USER_ID"],
			"filter" => [
				"=TASK_ID" => $TASK_ID,
				"=USER_ID" => $USER_ID,
			],
		]);
		if ($item = $list->fetch())
		{
			\Bitrix\Tasks\Internals\Task\ViewedTable::update($item, [
				"VIEWED_DATE" => new \Bitrix\Main\Type\DateTime(),
			]);
		}
		else
		{
			\Bitrix\Tasks\Internals\Task\ViewedTable::add([
				"TASK_ID" => $TASK_ID,
				"USER_ID" => $USER_ID,
				"VIEWED_DATE" => new \Bitrix\Main\Type\DateTime(),
			]);
		}

		$pullData = [
			'USER_ID' => $USER_ID,
			'TASK_ID' => $TASK_ID,
		];
		self::EmitPullWithTag([$USER_ID], 'TASKS_TASK_' . $TASK_ID, 'task_view', $pullData);

		$event = new \Bitrix\Main\Event(
			'tasks',
			'onTaskUpdateViewed',
			[
				'taskId' => $TASK_ID,
				'userId' => $USER_ID,
			]
		);
		$event->send();
	}

	public static function GetUpdatesCount($arViewed)
	{
		global $DB;
		if ($userID = User::getId())
		{
			$arSqlSearch = [];
			$arUpdatesCount = [];
			foreach ($arViewed as $key => $val)
			{
				$arSqlSearch[] = "(CREATED_DATE > "
					. Db::charToDateFunction($val)
					. " AND TASK_ID = "
					. (int)$key
					. ")";
				$arUpdatesCount[$key] = 0;
			}

			if (!empty($arSqlSearch))
			{
				$strSql = "
					SELECT
						TL.TASK_ID AS TASK_ID,
						COUNT(TL.TASK_ID) AS CNT
					FROM
						b_tasks_log TL
					WHERE
						USER_ID != " . $userID . "
						AND (
						" . implode(" OR ", $arSqlSearch) . "
						)
					GROUP BY
						TL.TASK_ID
				";

				$rsUpdatesCount = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
				while ($arUpdate = $rsUpdatesCount->Fetch())
				{
					$arUpdatesCount[$arUpdate["TASK_ID"]] = $arUpdate["CNT"];
				}

				return $arUpdatesCount;
			}
		}

		return false;
	}

	function GetFilesCount($arTasksIDs)
	{
		global $DB;

		$arFilesCount = [];

		$arTasksIDs = array_filter($arTasksIDs);

		if (sizeof($arTasksIDs))
		{
			$strSql = "
				SELECT
					TF.TASK_ID,
					COUNT(TF.FILE_ID) AS CNT
				FROM
					b_tasks_file TF
				WHERE
					TF.TASK_ID IN (" . implode(",", $arTasksIDs) . ")
			";
			$rsFilesCount = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			while ($arFile = $rsFilesCount->Fetch())
			{
				$arFilesCount[$arFile["TASK_ID"]] = $arFile["CNT"];
			}
		}

		return $arFilesCount;
	}

	public static function CanCurrentUserViewTopic($topicID)
	{
		$isSocNetModuleIncluded = CModule::IncludeModule("socialnetwork");

		if (($topicID = intval($topicID)) && User::getId())
		{
			if (User::isSuper())
			{
				return true;
			}

			$rsTask = $res = CTasks::GetList([], ["FORUM_TOPIC_ID" => $topicID]);
			if ($arTask = $rsTask->Fetch())
			{
				if (((int)$arTask['GROUP_ID']) > 0)
				{
					if (
						in_array(CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_GROUP, $arTask["GROUP_ID"],
							"tasks", "view_all"), ["G2", "AU"])
					)
					{
						return true;
					}
					elseif (
						$isSocNetModuleIncluded
						&& (false !== CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP,
								$arTask['GROUP_ID'], 'tasks', 'view_all'))
					)
					{
						return (true);
					}
				}

				$members = self::getMembers($arTask['ID']);
				$arTask['ACCOMPLICES'] = $members[MemberTable::MEMBER_TYPE_ACCOMPLICE];
				$arTask['AUDITORS'] = $members[MemberTable::MEMBER_TYPE_AUDITOR];

				if (
					in_array(User::getId(), array_unique(array_merge([$arTask["CREATED_BY"], $arTask["RESPONSIBLE_ID"]],
						$arTask["ACCOMPLICES"], $arTask["AUDITORS"])))
				)
				{
					return true;
				}

				$dbRes = CUser::GetList('ID', 'ASC', ['ID' => $arTask["RESPONSIBLE_ID"]],
					['SELECT' => ['UF_DEPARTMENT']]);

				if (
					($arRes = $dbRes->Fetch()) && is_array($arRes['UF_DEPARTMENT'])
					&& count($arRes['UF_DEPARTMENT'])
					> 0
				)
				{
					if (
						in_array(User::getId(), array_keys(CTasks::GetDepartmentManagers($arRes['UF_DEPARTMENT'],
							$arTask["RESPONSIBLE_ID"])))
					)
					{
						return true;
					}
				}
			}
		}

		return false;
	}

	public static function getParentOfTask($taskId)
	{
		$taskId = intval($taskId);
		if (!$taskId)
		{
			return false;
		}

		global $DB;

		$item = $DB->query("select PARENT_ID from b_tasks where ID = '" . $taskId . "'")->fetch();

		return intval($item['PARENT_ID']) ? intval($item['PARENT_ID']) : false;
	}

	public static function GetUserDepartments($USER_ID)
	{
		static $cache = [];
		$USER_ID = (int)$USER_ID;

		if (!isset($cache[$USER_ID]))
		{
			$dbRes = CUser::GetList('ID', 'ASC', ['ID' => $USER_ID], ['SELECT' => ['UF_DEPARTMENT']]);

			if ($arRes = $dbRes->Fetch())
			{
				$cache[$USER_ID] = $arRes['UF_DEPARTMENT'];
			}
			else
			{
				$cache[$USER_ID] = false;
			}
		}

		return $cache[$USER_ID];
	}

	public static function onBeforeSocNetGroupDelete($inGroupId)
	{
		global $DB, $APPLICATION;

		$bCanDelete = false;    // prohibit group removing by default

		$groupId = (int)$inGroupId;

		$strSql =
			"SELECT ID AS TASK_ID
			FROM b_tasks
			WHERE GROUP_ID = $groupId
			";

		$result = $DB->Query($strSql, false, 'File: ' . __FILE__ . '<br>Line: ' . __LINE__);
		if ($result === false)
		{
			$APPLICATION->ThrowException('EA_SQL_ERROR_OCCURED');
			return (false);
		}

		$arResult = $result->Fetch();

		// permit group deletion only when there is no tasks
		if ($arResult === false)
		{
			$bCanDelete = true;
		}
		else
		{
			$APPLICATION->ThrowException(GetMessage('TASKS_ERR_GROUP_IN_USE'));
		}

		return ($bCanDelete);
	}

	public static function OnBeforeUserDelete($userId)
	{
		global $APPLICATION;

		$userId = (int)$userId;
		if (!$userId)
		{
			$APPLICATION->ThrowException(GetMessage('TASKS_BAD_USER_ID'));
			return false;
		}

		$tasks = static::getTasksForUser($userId);
		$templates = static::getTemplatesForUser($userId);
		$errorMessages = static::getErrorMessagesOnBeforeUserDelete($tasks, $templates);

		if ($errorMessages != '')
		{
			$APPLICATION->ThrowException(
				GetMessage('TASKS_ERR_USER_IN_USE_TASKS_PREFIX', ['#ENTITIES#' => $errorMessages])
			);
		}

		return (empty($tasks) && empty($templates));
	}

	private static function getTasksForUser($userId): array
	{
		$taskEntityType = Integration\Recyclebin\Manager::TASKS_RECYCLEBIN_ENTITY;
		$tasksFromRecycleBin = static::getEntitiesFromRecycleBin($userId, $taskEntityType);

		$activeTasksResult = Application::getConnection()->query("
			SELECT DISTINCT T.ID
			FROM b_tasks T
			INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.USER_ID = {$userId}
		");
		$activeTasks = [];
		while ($item = $activeTasksResult->fetch())
		{
			$activeTasks[] = $item['ID'];
		}

		return array_unique(array_merge($activeTasks, $tasksFromRecycleBin));
	}

	private static function getTemplatesForUser($userId): array
	{
		$templateEntityType = Integration\Recyclebin\Manager::TASKS_TEMPLATE_RECYCLEBIN_ENTITY;
		$templatesFromRecycleBin = static::getEntitiesFromRecycleBin($userId, $templateEntityType);

		$activeTemplatesResult = \Bitrix\Tasks\TemplateTable::getList([
			'select' => ['ID'],
			'filter' => [
				'LOGIC' => 'OR',
				['=CREATED_BY' => $userId],
				['=RESPONSIBLE_ID' => $userId],
			],
		]);
		$activeTemplates = [];
		while ($item = $activeTemplatesResult->fetch())
		{
			$activeTemplates[] = $item['ID'];
		}

		return array_unique(array_merge($activeTemplates, $templatesFromRecycleBin));
	}

	private static function getEntitiesFromRecycleBin($userId, $entityType): array
	{
		$ids = [];

		if (\Bitrix\Main\Loader::includeModule('recyclebin'))
		{
			$result = Application::getConnection()->query("
				SELECT R.ENTITY_ID AS TASK_ID
				FROM b_recyclebin R
					INNER JOIN b_recyclebin_data RD ON RD.RECYCLEBIN_ID = R.ID
				WHERE R.ENTITY_TYPE = '{$entityType}'
					AND RD.ACTION = 'MEMBERS'
					AND RD.DATA like '%s:7:\"USER_ID\";s:1:\"{$userId}\"%'
			");
			while ($item = $result->fetch())
			{
				$ids[] = $item['TASK_ID'];
			}
		}

		return $ids;
	}

	private static function getErrorMessagesOnBeforeUserDelete($tasks, $templates): string
	{
		$errorMessages = [];

		if (!empty($tasks))
		{
			$tail = '';
			$count = count($tasks);
			if ($count > 10)
			{
				$tasks = array_slice($tasks, 0, 10);
				$tail = GetMessage('TASKS_ERR_USER_IN_USE_TAIL', ['#N#' => $count - 10]);
			}

			$errorMessages[] = GetMessage('TASKS_ERR_USER_IN_USE_TASKS', ['#IDS#' => implode(', ', $tasks)]) . $tail;
		}

		if (!empty($templates))
		{
			$tail = '';
			$count = count($templates);
			if ($count > 10)
			{
				$templates = array_slice($templates, 0, 10);
				$tail = GetMessage('TASKS_ERR_USER_IN_USE_TAIL', ['#N#' => $count - 10]);
			}

			$errorMessages[] = GetMessage('TASKS_ERR_USER_IN_USE_TEMPLATES', ['#IDS#' => implode(', ', $templates)])
				. $tail;
		}

		return implode(', ', $errorMessages);
	}

	// $value comes in seconds, we must translate to units of $type
	public static function convertDurationFromSeconds($value, $type)
	{
		if ($type == self::TIME_UNIT_TYPE_HOUR)
		{
			// hours to seconds
			return round(intval($value) / 3600, 0);
		}
		elseif ($type == self::TIME_UNIT_TYPE_DAY || (string)$type == ''/*days by default, see install/db*/)
		{
			// days to seconds
			return round(intval($value) / 86400, 0);
		}

		return $value;
	}

	public static function OnUserDelete($USER_ID)
	{
		global $CACHE_MANAGER, $DB;
		$USER_ID = intval($USER_ID);
		$strSql = "
			SELECT RESPONSIBLE_ID AS USER_ID FROM b_tasks WHERE CREATED_BY = "
			. $USER_ID
			. " AND CREATED_BY != RESPONSIBLE_ID
			UNION
			SELECT CREATED_BY AS USER_ID FROM b_tasks WHERE RESPONSIBLE_ID = "
			. $USER_ID
			. " AND CREATED_BY != RESPONSIBLE_ID
			UNION
			SELECT USER_ID FROM b_tasks_member WHERE TASK_ID IN (SELECT TASK_ID FROM b_tasks_member WHERE USER_ID = "
			. $USER_ID
			. ")
		";
		$result = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
		while ($arResult = $result->Fetch())
		{
			$CACHE_MANAGER->ClearByTag("tasks_user_" . $arResult["USER_ID"]);
		}
	}

	public static function EmitPullWithTagPrefix($arRecipients, $tagPrefix, $cmd, $arParams)
	{
		if (!is_array($arRecipients))
		{
			throw new TasksException('EA_PARAMS', TasksException::TE_WRONG_ARGUMENTS);
		}

		$arRecipients = array_unique($arRecipients);

		if (!CModule::IncludeModule('pull'))
		{
			return;
		}

		/*
		$arEventData = array(
			'module_id' => 'tasks',
			'command'   => 'notify',
			'params'    => CIMNotify::GetFormatNotify(
				array(
					'ID' => -3
				)
			),
		);
		*/

		$bWasFatalError = false;

		foreach ($arRecipients as $userId)
		{
			$userId = (int)$userId;

			if ($userId < 1)
			{
				$bWasFatalError = true;
				continue;    // skip invalid items
			}

			//\Bitrix\Pull\Event::add($userId, $arEventData);
			CPullWatch::AddToStack(
				$tagPrefix . $userId,
				[
					'module_id' => 'tasks',
					'command' => $cmd,
					'params' => $arParams,
				]
			);
		}

		if ($bWasFatalError)
		{
			throw new TasksException('EA_PARAMS', TasksException::TE_WRONG_ARGUMENTS);
		}
	}

	public static function EmitPullWithTag($arRecipients, $tag, $cmd, $arParams)
	{
		if (!is_array($arRecipients))
		{
			throw new TasksException('EA_PARAMS', TasksException::TE_WRONG_ARGUMENTS);
		}

		$arRecipients = array_unique($arRecipients);

		if (!CModule::IncludeModule('pull'))
		{
			return;
		}

		$bWasFatalError = false;

		foreach ($arRecipients as $userId)
		{
			$userId = (int)$userId;

			if ($userId < 1)
			{
				$bWasFatalError = true;
				continue;    // skip invalid items
			}

			CPullWatch::Add($userId, $tag);

			//\Bitrix\Pull\Event::add($userId, $arEventData);
			CPullWatch::AddToStack(
				$tag,
				[
					'module_id' => 'tasks',
					'command' => $cmd,
					'params' => $arParams,
				]
			);
		}

		if ($bWasFatalError)
		{
			throw new TasksException('EA_PARAMS', TasksException::TE_WRONG_ARGUMENTS);
		}
	}

	/**
	 * Get list of IDs groups, which contains tasks where given user is member
	 *
	 * @param integer $userId
	 * @return array
	 * @throws TasksException
	 */
	public static function GetGroupsWithTasksForUser($userId)
	{
		global $DB;

		$userId = (int)$userId;

		// EXISTS!
		$rc = $DB->Query(
			"SELECT GROUP_ID
			FROM b_tasks T
			WHERE (
				T.CREATED_BY = $userId
				OR T.RESPONSIBLE_ID = $userId
				OR EXISTS(
					SELECT 'x'
					FROM b_tasks_member TM
					WHERE TM.TASK_ID = T.ID
						AND TM.USER_ID = $userId
					)
				)
				AND GROUP_ID IS NOT NULL
				AND GROUP_ID != 0
			GROUP BY GROUP_ID
			"
		);

		if (!$rc)
		{
			throw new TasksException();
		}

		$arGroups = [];

		while ($ar = $rc->Fetch())
		{
			$arGroups[] = (int)$ar['GROUP_ID'];
		}

		return (array_unique($arGroups));
	}

	/**
	 * Convert every given string in array from BB-code to HTML
	 *
	 * @param array $arStringsInBbcode
	 *
	 * @return array of strings converted to HTML, keys maintaned
	 * @throws TasksException
	 */
	public static function convertBbcode2Html($arStringsInBbcode)
	{
		if (!is_array($arStringsInBbcode))
		{
			throw new TasksException();
		}

		static $delimiter = '--------This is unique BB-code strings delimiter at high confidence level (CL)--------';

		$stringsCount = count($arStringsInBbcode);
		$arStringsKeys = array_keys($arStringsInBbcode);

		$concatenatedStrings = implode($delimiter, $arStringsInBbcode);

		// While not unique identifier, try to
		$i = -150;
		while (count(explode($delimiter, $concatenatedStrings)) !== $stringsCount)
		{
			// prevent an infinite loop
			if (!($i++))
			{
				throw new TasksException();
			}

			$delimiter = '--------' . sha1(uniqid()) . '--------';
			$concatenatedStrings = implode($delimiter, $arStringsInBbcode);
		}

		$oParser = new CTextParser();

		$arHtmlStringsWoKeys = explode(
			$delimiter,
			str_replace(
				"\t",
				' &nbsp; &nbsp;',
				$oParser->convertText($concatenatedStrings)
			)
		);

		$arHtmlStrings = [];

		// Do job in compatibility mode, if count of resulted strings not match source
		if (count($arHtmlStringsWoKeys) !== $stringsCount)
		{
			foreach ($arStringsInBbcode as $key => $str)
			{
				$oParser = new CTextParser();
				$arHtmlStrings[$key] = str_replace(
					"\t",
					' &nbsp; &nbsp;',
					$oParser->convertText($str)
				);
				unset($oParser);
			}
		}
		else
		{
			// Maintain original array keys
			$i = 0;
			foreach ($arStringsKeys as $key)
			{
				$arHtmlStrings[$key] = $arHtmlStringsWoKeys[$i++];
			}
		}

		return ($arHtmlStrings);
	}

	public static function getTaskSubTree($taskId)
	{
		$taskId = intval($taskId);
		if (!$taskId)
		{
			return [];
		}

		$queue = [$taskId];
		$met = [];
		$limit = 1000;
		$result = [];

		$i = 0;
		while (true)
		{
			if ($i > $limit)
			{
				break;
			}

			$next = array_shift($queue);
			if (isset($met[$next]))
			{
				break;
			}
			if (!intval($next))
			{
				break;
			}

			$subTasks = self::getSubTaskIdsForTask($next);
			foreach ($subTasks as $sTId)
			{
				$result[] = $sTId;
				$queue[] = $sTId;
			}

			$met[$next] = true;
			$i++;
		}

		return $result;
	}

	private static function getSubTaskIdsForTask($taskId)
	{
		global $DB;

		$taskId = intval($taskId);

		$result = [];
		$res = $DB->query("select ID from b_tasks where " . ($taskId ? "PARENT_ID = '" . $taskId . "'"
				: "PARENT_ID is null or PARENT_ID = '0'"));
		while ($item = $res->fetch())
		{
			if (intval($item['ID']))
			{
				$result[] = $item['ID'];
			}
		}

		return array_unique($result);
	}

	public static function runRestMethod($executiveUserId, $methodName, $args, $navigation)
	{
		CTaskAssert::assert($methodName === 'getlist');

		// Force & limit NAV_PARAMS (in 4th argument)
		while (count($args) < 4)
		{
			$args[] = [];
		}        // All params in CTasks::GetList() by default are empty arrays

		$arParams = &$args[3];

		if ($navigation['iNumPage'] > 1)
		{
			$arParams['NAV_PARAMS'] = [
				'nPageSize' => CTaskRestService::TASKS_LIMIT_PAGE_SIZE,
				'iNumPage' => (int)$navigation['iNumPage'],
			];
		}
		elseif (isset($arParams['NAV_PARAMS']))
		{
			if (isset($arParams['NAV_PARAMS']['nPageTop']))
			{
				$arParams['NAV_PARAMS']['nPageTop'] = min(CTaskRestService::TASKS_LIMIT_TOP_COUNT,
					(int)$arParams['NAV_PARAMS']['nPageTop']);
			}

			if (isset($arParams['NAV_PARAMS']['nPageSize']))
			{
				$arParams['NAV_PARAMS']['nPageSize'] = min(CTaskRestService::TASKS_LIMIT_PAGE_SIZE,
					(int)$arParams['NAV_PARAMS']['nPageSize']);
			}

			if (
				(!isset($arParams['NAV_PARAMS']['nPageTop']))
				&& (!isset($arParams['NAV_PARAMS']['nPageSize']))
			)
			{
				$arParams['NAV_PARAMS'] = [
					'nPageSize' => CTaskRestService::TASKS_LIMIT_PAGE_SIZE,
					'iNumPage' => 1,
				];
			}
		}
		else
		{
			$arParams['NAV_PARAMS'] = [
				'nPageSize' => CTaskRestService::TASKS_LIMIT_PAGE_SIZE,
				'iNumPage' => 1,
			];
		}

		// Check and parse params
		$argsParsed = CTaskRestService::_parseRestParams('ctasks', $methodName, $args);

		$arParams['USER_ID'] = $executiveUserId;

		// TODO: remove this hack (needs for select tasks with GROUP_ID === NULL or 0)
		if (isset($argsParsed[1]))
		{
			$arFilter = $argsParsed[1];
			foreach ($arFilter as $key => $value)
			{
				if (($key === 'GROUP_ID') && ($value == 0))
				{
					$argsParsed[1]['META:GROUP_ID_IS_NULL_OR_ZERO'] = 1;
					unset($argsParsed[1][$key]);
					break;
				}
			}

			if (
				isset($argsParsed[1]['ID'])
				&& is_array($argsParsed[1]['ID'])
				&& empty($argsParsed[1]['ID'])
			)
			{
				$argsParsed[1]['ID'] = -1;
			}
		}

		$rsTasks = call_user_func_array(['self', 'getlist'], $argsParsed);

		$arTasks = [];
		while ($arTask = $rsTasks->fetch())
		{
			$arTasks[] = $arTask;
		}

		return ([$arTasks, $rsTasks]);
	}

	public static function getPublicFieldMap()
	{
		// READ, WRITE, SORT, FILTER, DATE
		return [
			'TITLE' => [1, 1, 1, 1, 0],
			'STAGE_ID' => [1, 1, 0, 1, 0],
			'STAGES_ID' => [0, 0, 0, 1, 0],
			'DESCRIPTION' => [1, 1, 0, 0, 0],
			'DEADLINE' => [1, 1, 1, 1, 1],
			'START_DATE_PLAN' => [1, 1, 1, 1, 1],
			'END_DATE_PLAN' => [1, 1, 1, 1, 1],
			'PRIORITY' => [1, 1, 1, 1, 0],
			'ACCOMPLICES' => [1, 1, 0, 0, 0],
			'AUDITORS' => [1, 1, 0, 0, 0],
			'TAGS' => [1, 1, 0, 0, 0],
			'ALLOW_CHANGE_DEADLINE' => [1, 1, 1, 0, 0],
			'TASK_CONTROL' => [1, 1, 0, 0, 0],
			'PARENT_ID' => [1, 1, 0, 1, 0],
			'DEPENDS_ON' => [1, 1, 0, 1, 0],
			'GROUP_ID' => [1, 1, 1, 1, 0],
			'RESPONSIBLE_ID' => [1, 1, 1, 1, 0],
			'TIME_ESTIMATE' => [1, 1, 1, 1, 0],
			'ID' => [1, 0, 1, 1, 0],
			'CREATED_BY' => [1, 1, 1, 1, 0],
			'DESCRIPTION_IN_BBCODE' => [1, 0, 0, 0, 0],
			'DECLINE_REASON' => [1, 1, 0, 0, 0],
			'REAL_STATUS' => [1, 0, 0, 1, 0],
			'STATUS' => [1, 1, 1, 1, 0],
			'RESPONSIBLE_NAME' => [1, 0, 0, 0, 0],
			'RESPONSIBLE_LAST_NAME' => [1, 0, 0, 0, 0],
			'RESPONSIBLE_SECOND_NAME' => [1, 0, 0, 0, 0],
			'DATE_START' => [1, 0, 1, 1, 1],
			'DURATION_FACT' => [1, 0, 0, 0, 0],
			'DURATION_PLAN' => [1, 1, 0, 0, 0],
			'DURATION_TYPE' => [1, 1, 0, 0, 0],
			'CREATED_BY_NAME' => [1, 0, 0, 0, 0],
			'CREATED_BY_LAST_NAME' => [1, 0, 0, 0, 0],
			'CREATED_BY_SECOND_NAME' => [1, 0, 0, 0, 0],
			'CREATED_DATE' => [1, 1, 1, 1, 1],
			'CHANGED_BY' => [1, 1, 0, 1, 0],
			'CHANGED_DATE' => [1, 1, 1, 1, 1],
			'STATUS_CHANGED_BY' => [1, 1, 0, 1, 0],
			'STATUS_CHANGED_DATE' => [1, 1, 0, 0, 1],
			'CLOSED_BY' => [1, 0, 0, 0, 0],
			'CLOSED_DATE' => [1, 0, 1, 1, 1],
			'ACTIVITY_DATE' => [1, 0, 1, 1, 1],
			'GUID' => [1, 0, 0, 1, 0],
			'MARK' => [1, 1, 1, 1, 0],
			'VIEWED_DATE' => [1, 0, 0, 0, 1],
			'TIME_SPENT_IN_LOGS' => [1, 0, 0, 0, 0],
			'FAVORITE' => [1, 0, 1, 1, 0],
			'ALLOW_TIME_TRACKING' => [1, 1, 1, 1, 0],
			'MATCH_WORK_TIME' => [1, 1, 1, 1, 0],
			'ADD_IN_REPORT' => [1, 1, 0, 1, 0],
			'FORUM_ID' => [1, 0, 0, 0, 0],
			'FORUM_TOPIC_ID' => [1, 0, 0, 1, 0],
			'COMMENTS_COUNT' => [1, 0, 0, 0, 0],
			'SITE_ID' => [1, 1, 0, 1, 0],
			'SUBORDINATE' => [1, 0, 0, 0, 0],
			'FORKED_BY_TEMPLATE_ID' => [1, 0, 0, 0, 0],
			'MULTITASK' => [1, 0, 0, 0, 0],
			'ACCOMPLICE' => [0, 0, 0, 1, 0],
			'AUDITOR' => [0, 0, 0, 1, 0],
			'DOER' => [0, 0, 0, 1, 0],
			'MEMBER' => [0, 0, 0, 1, 0],
			'TAG' => [0, 0, 0, 1, 0],
			'EPIC' => [1, 1, 0, 1, 0],
			'ONLY_ROOT_TASKS' => [0, 0, 0, 1, 0],
			'SCENARIO_NAME' => [1, 1, 0, 1, 0],
		];
	}

	public static function getManifest()
	{
		static $fieldMap;

		if ($fieldMap == null)
		{
			$fieldMap = static::getPublicFieldMap();
		}

		static $fieldManifest;

		if ($fieldManifest === null)
		{
			foreach ($fieldMap as $field => $permissions)
			{
				if ($permissions[0]) // read
				{
					$fieldManifest['READ'][] = $field;
				}

				if ($permissions[1]) // write
				{
					$fieldManifest['WRITE'][] = $field;
				}

				if ($permissions[2]) // sort
				{
					$fieldManifest['SORT'][] = $field;
				}

				if ($permissions[3]) // filter
				{
					$fieldManifest['FILTER'][] = $field;
				}

				if ($permissions[4]) // filter
				{
					$fieldManifest['DATE'][] = $field;
				}
			}
		}

		return ([
			'Manifest version' => '2.1',
			'Warning' => 'don\'t rely on format of this manifest, it can be changed without any notification',
			'REST: shortname alias to class' => 'items',
			'REST: writable task data fields' => $fieldManifest['WRITE'],
			'REST: readable task data fields' => $fieldManifest['READ'],
			'REST: sortable task data fields' => $fieldManifest['SORT'],
			'REST: filterable task data fields' => $fieldManifest['FILTER'],
			'REST: date fields' => $fieldManifest['DATE'],
			'REST: available methods' => [
				'getlist' => [
					'mandatoryParamsCount' => 0,
					'params' => [
						[
							'description' => 'arOrder',
							'type' => 'array',
							'allowedKeys' => $fieldManifest['SORT'],
						],
						[
							'description' => 'arFilter',
							'type' => 'array',
							'allowedKeys' => $fieldManifest['FILTER'],
							'allowedKeyPrefixes' => [
								'=',
								'!=',
								'%',
								'!%',
								'?',
								'><',
								'!><',
								'>=',
								'>',
								'<',
								'<=',
								'!',
							],
						],
						[
							'description' => 'arSelect',
							'type' => 'array',
							'allowedValues' => $fieldManifest['READ'],
						],
						[
							'description' => 'arParams',
							'type' => 'array',
							'allowedKeys' => ['NAV_PARAMS', 'bGetZombie'],
						],
					],
					'allowedKeysInReturnValue' => $fieldManifest['READ'],
					'collectionInReturnValue' => true,
				],
			],
		]);
	}
}