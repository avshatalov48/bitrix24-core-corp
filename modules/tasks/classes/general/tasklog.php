<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Main\Text\Emoji;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\SprintService;

class CTaskLog
{
	// left for compatibility
	static $arComparedFields = array(
		'TITLE' => 'string',
		'DESCRIPTION' => 'text',
		'STATUS' => 'integer',
		'PRIORITY' => 'integer',
		'MARK' => 'string',
		'PARENT_ID' => 'integer',
		'GROUP_ID' => 'integer',
		'STAGE_ID' => 'integer',
		'CREATED_BY' => 'integer',
		'RESPONSIBLE_ID' => 'integer',
		'ACCOMPLICES' => 'array',
		'AUDITORS' => 'array',
		'DEADLINE' => 'date',
		'START_DATE_PLAN' => 'date',
		'END_DATE_PLAN' => 'date',
		'DURATION_PLAN' => 'integer',
		'DURATION_PLAN_SECONDS' => 'integer',
		'DURATION_FACT' => 'integer',
		'TIME_ESTIMATE' => 'integer',
		'TIME_SPENT_IN_LOGS' => 'integer',
		'TAGS' => 'array',
		'DEPENDS_ON' => 'array',
		'FILES' => 'array',
		'UF_TASK_WEBDAV_FILES' => 'array',
		'CHECKLIST_ITEM_CREATE' => 'string',
		'CHECKLIST_ITEM_RENAME' => 'string',
		'CHECKLIST_ITEM_REMOVE' => 'string',
		'CHECKLIST_ITEM_CHECK' => 'string',
		'CHECKLIST_ITEM_UNCHECK' => 'string',
		'ADD_IN_REPORT' => 'bool',
		'TASK_CONTROL' => 'bool',
		'ALLOW_TIME_TRACKING' => 'bool',
		'ALLOW_CHANGE_DEADLINE' => 'bool',
	);

	public static function getTrackedFields()
	{
		static $fields;

		if (!$fields) {
			$fields = array();

			foreach (static::$arComparedFields as $code => $type) {
				$fields[$code] = array('TYPE' => $type);
			}

			// get also ufs
			$ufs = $GLOBALS['USER_FIELD_MANAGER']->getUserFields('TASKS_TASK', 0, LANGUAGE_ID);
			foreach ($ufs as $code => $desc) {
				// exception for system disk files
				$title = '';
				if ($code != \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode()) {
					$title = $desc['EDIT_FORM_LABEL'];
				}

				$fields[$code] = array(
					'TITLE' => $title,
					'TYPE' => $desc['MULTIPLE'] == 'Y' ? 'array' : 'string'
				);
			}
		}

		return $fields;
	}


	public static function CheckFields(
		/** @noinspection PhpUnusedParameterInspection */
		&$arFields, $ID = false
	)
	{
		if ((string)($arFields['CREATED_DATE'] ?? null) == '')
		{
			$arFields['CREATED_DATE'] = \Bitrix\Tasks\Util\Type\DateTime::getCurrentTimeString();
		}

		return true;
	}

	public function Add($arFields)
	{
		if ($this->CheckFields($arFields))
		{
			if ($arFields['CREATED_DATE'])
			{
				$createdDate = Bitrix\Main\Type\DateTime::createFromUserTime($arFields['CREATED_DATE']);
			}
			else
			{
				$createdDate = new Bitrix\Main\Type\DateTime();
			}

			$addResult = \Bitrix\Tasks\Internals\Task\LogTable::add([
				'CREATED_DATE' => $createdDate,
				'USER_ID' => $arFields["USER_ID"],
				'TASK_ID' => $arFields["TASK_ID"],
				'FIELD' => $arFields["FIELD"],
				'FROM_VALUE' => ($arFields["FROM_VALUE"] ?? null),
				'TO_VALUE' => ($arFields["TO_VALUE"] ?? null),
			]);

			if ($addResult->isSuccess())
			{
				return $addResult->getId();
			}
		}

		return false;
	}


	public static function GetFilter($arFilter)
	{
		global $DB;

		if (!is_array($arFilter))
			$arFilter = Array();

		$arSqlSearch = Array();

		foreach ($arFilter as $key => $val) {
			$res = CTasks::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = mb_strtoupper($key);

			switch ($key) {
				case "CREATED_DATE":
					$arSqlSearch[] = CTasks::FilterCreate("TL." . $key, \Bitrix\Tasks\Util\Db::charToDateFunction($val), "date", $bFullJoin, $cOperationType);
					break;

				case "USER_ID":
				case "TASK_ID":
					$arSqlSearch[] = CTasks::FilterCreate("TL." . $key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "FIELD":
					$arSqlSearch[] = CTasks::FilterCreate("TL." . $key, $val, "string", $bFullJoin, $cOperationType);
					break;
			}
		}

		return $arSqlSearch;
	}


	public static function GetList($arOrder, $arFilter)
	{
		global $DB;

		$arSqlSearch = CTaskLog::GetFilter($arFilter);

		$strSql = "
			SELECT
				TL.*,
				" . $DB->DateToCharFunction("TL.CREATED_DATE", "FULL") . " AS CREATED_DATE,
				U.NAME AS USER_NAME,
				U.LAST_NAME AS USER_LAST_NAME,
				U.SECOND_NAME AS USER_SECOND_NAME,
				U.LOGIN AS USER_LOGIN
			FROM
				b_tasks_log TL
			INNER JOIN
				b_user U
			ON
				U.ID = TL.USER_ID
			" . (sizeof($arSqlSearch) ? "WHERE " . implode(" AND ", $arSqlSearch) : "") . "
		";

		if (!is_array($arOrder) || sizeof($arOrder) == 0)
			$arOrder = array("CREATED_DATE" => "ASC");

		foreach ($arOrder as $by => $order) {
			$by = mb_strtolower($by);
			$order = mb_strtolower($order);
			if ($order != "asc")
				$order = "desc";

			if ($by == "user" || $by == "user_id")
				$arSqlOrder[] = " TL.USER_ID " . $order . " ";
			elseif ($by == "field")
				$arSqlOrder[] = " TL.FIELD " . $order . " ";
			elseif ($by == "task_id")
				$arSqlOrder[] = " TL.TASK_ID " . $order . " ";
			elseif ($by == "rand")
				$arSqlOrder[] = CTasksTools::getRandFunction();
			else
				$arSqlOrder[] = " TL.CREATED_DATE " . $order . " ";
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		for ($i = 0, $arSqlOrderCnt = count($arSqlOrder); $i < $arSqlOrderCnt; $i++) {
			if ($i == 0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		return $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
	}


	public static function GetChanges($currentFields, $newFields)
	{
		$changes = [];

		array_walk($currentFields, ['CTaskLog', 'UnifyFields']);
		array_walk($newFields, ['CTaskLog', 'UnifyFields']);

		if (array_key_exists('REAL_STATUS', $currentFields))
		{
			$currentFields['STATUS'] = $currentFields['REAL_STATUS'];
		}

		if (array_key_exists('TITLE', $currentFields))
		{
			$currentFields['TITLE'] = Emoji::encode($currentFields['TITLE']);
		}
		if (array_key_exists('DESCRIPTION', $currentFields))
		{
			$currentFields['DESCRIPTION'] = Emoji::encode($currentFields['DESCRIPTION']);
		}
		if (array_key_exists('TITLE', $newFields))
		{
			$newFields['TITLE'] = Emoji::encode($newFields['TITLE']);
		}
		if (array_key_exists('DESCRIPTION', $newFields))
		{
			$newFields['DESCRIPTION'] = Emoji::encode($newFields['DESCRIPTION']);
		}

		$comparedFields = static::getTrackedFields();

		foreach ($newFields as $key => $value)
		{
			if (array_key_exists($key, $comparedFields) && ($currentFields[$key] ?? null) != ($newFields[$key] ?? null))
			{
				if (!array_key_exists($key, $currentFields) || !array_key_exists($key, $newFields))
				{
					continue;
				}

				if ($key === 'FILES')
				{
					$filesChanges = static::getFilesChanges($currentFields[$key], $value);

					if (array_key_exists('DELETED_FILES', $filesChanges))
					{
						$changes['DELETED_FILES'] = $filesChanges['DELETED_FILES'];
					}
					if (array_key_exists('NEW_FILES', $filesChanges))
					{
						$changes['NEW_FILES'] = $filesChanges['NEW_FILES'];
					}
				}
				elseif ($key === 'STAGE_ID')
				{
					$oldGroupId = $currentFields['GROUP_ID'];
					$newGroupId = (array_key_exists('GROUP_ID', $newFields) ? $newFields['GROUP_ID'] : $oldGroupId);
					$stageChanges = static::getStageChanges($currentFields[$key], $value, $oldGroupId, $newGroupId);
					if (!empty($stageChanges))
					{
						$changes['STAGE'] = $stageChanges;
					}
				}
				elseif ($key === 'UF_CRM_TASK')
				{
					if (!empty($added = implode(',', array_diff($value, $currentFields[$key]))))
					{
						$changes['UF_CRM_TASK_ADDED'] = [
							'FROM_VALUE' => false,
							'TO_VALUE' => $added,
						];
					}
					if (!empty($deleted = implode(',', array_diff($currentFields[$key], $value))))
					{
						$changes['UF_CRM_TASK_DELETED'] = [
							'FROM_VALUE' => $deleted,
							'TO_VALUE' => false,
						];
					}
				}
				else
				{
					if ($comparedFields[$key]['TYPE'] === 'text')
					{
						$currentFields[$key] = false;
						$newFields[$key] = false;
					}
					elseif ($comparedFields[$key]['TYPE'] === 'array')
					{
						$currentFields[$key] = implode(',', $currentFields[$key]);
						$newFields[$key] = implode(',', $value);
					}

					$changes[$key] = [
						'FROM_VALUE' => ($currentFields[$key] || $key === 'PRIORITY' ? $currentFields[$key] : false),
						'TO_VALUE' => ($newFields[$key] || $key === 'PRIORITY' ? $newFields[$key] : false),
					];
				}
			}
		}

		return $changes;
	}

	private static function getFilesChanges(array $currentFiles, array $newFiles): array
	{
		$filesChanges = [];

		$deleted = array_diff($currentFiles, $newFiles);
		if (count($deleted) > 0)
		{
			$fileNames = [];
			$res = CFile::GetList([], ['@ID' => implode(',', $deleted)]);
			while ($file = $res->Fetch())
			{
				$fileNames[] = $file['ORIGINAL_NAME'];
			}
			if (count($fileNames))
			{
				$filesChanges['DELETED_FILES'] = [
					'FROM_VALUE' => implode(', ', $fileNames),
					'TO_VALUE' => false,
				];
			}
		}

		$added = array_diff($newFiles, $currentFiles);
		if (count($added) > 0)
		{
			$fileNames = [];
			$res = CFile::GetList([], ['@ID' => implode(',', $added)]);
			while ($file = $res->Fetch())
			{
				$fileNames[] = $file['ORIGINAL_NAME'];
			}
			if (count($fileNames))
			{
				$filesChanges['NEW_FILES'] = [
					'FROM_VALUE' => false,
					'TO_VALUE' => implode(', ', $fileNames)
				];
			}
		}

		return $filesChanges;
	}

	public static function getStageChanges(int $oldStageId, int $newStageId, int $oldGroupId, int $newGroupId): array
	{
		if ($newGroupId !== $oldGroupId)
		{
			return [];
		}

		$isScrum = false;
		if (\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			$group = \Bitrix\Socialnetwork\Item\Workgroup::getById($newGroupId);
			$isScrum = ($group && $group->isScrumProject());
		}

		if ($isScrum)
		{
			$kanbanService = new KanbanService();

			if (!$oldStageId && $oldGroupId)
			{
				$sprintService = new SprintService();

				$sprint = $sprintService->getActiveSprintByGroupId($newGroupId);

				$oldStageId = (int) $kanbanService->getDefaultStageId($sprint->getId());
			}

			$stageTitles = $kanbanService->getStageTitles([$newStageId, $oldStageId]);

			return [
				'FROM_VALUE' => $stageTitles[$oldStageId],
				'TO_VALUE' => $stageTitles[$newStageId],
			];
		}

		if (!$oldStageId && $oldGroupId)
		{
			$oldStageId = (int)StagesTable::getDefaultStageId($oldGroupId);
		}

		$stageFrom = false;
		$stageTo = false;

		$res = StagesTable::getList([
			'select' => ['ID', 'TITLE'],
			'filter' => ['@ID' => [$oldStageId, $newStageId]],
		]);
		while ($stage = $res->fetch())
		{
			if ((int)$stage['ID'] === $oldStageId)
			{
				$stageFrom = $stage['TITLE'];
			}
			elseif ((int)$stage['ID'] === $newStageId)
			{
				$stageTo = $stage['TITLE'];
			}
		}

		return [
			'FROM_VALUE' => $stageFrom,
			'TO_VALUE' => $stageTo,
		];
	}

	public static function UnifyFields(&$value, $key)
	{
		$comparedFields = static::getTrackedFields();

		if (array_key_exists($key, $comparedFields)) {
			switch ($comparedFields[$key]['TYPE']) {
				case "integer":
					$value = intval((string)$value);
					break;

				case "string":
					$value = trim((string)$value);
					break;

				case "array":
					if (!is_array($value))
						$value = explode(",", $value);

					$value = array_unique(array_filter(array_map("trim", $value)));
					sort($value);
					break;

				case "date":
					$value = MakeTimeStamp($value);

					if (!$value) {
						$value = strtotime($value);        // There is correct Unix timestamp in return value
						// CTimeZone::getOffset() substraction here???
					} else {
						// It can be other date on server (relative to client), ...
						$bTzWasDisabled = !CTimeZone::enabled();

						if ($bTzWasDisabled)
							CTimeZone::enable();

						$value -= CTimeZone::getOffset();        // get correct UnixTimestamp

						if ($bTzWasDisabled)
							CTimeZone::disable();

						// We mustn't store result of MakeTimestamp() in DB,
						// because it is shifted for time zone offset already,
						// which can't be restored.
					}
					break;

				case "bool":
					if ($value != "Y")
						$value = "N";
					break;
			}
		}
	}


	/**
	 * Remove all log data for given task_id
	 * @param int $in_taskId
	 *
	 * @throws Exception on any error
	 */
	public static function DeleteByTaskId($in_taskId)
	{
		$taskId = (int)$in_taskId;

		if ((!is_numeric($in_taskId)) || ($taskId < 1))
			throw new Exception('EA_PARAMS');

		$list = \Bitrix\Tasks\Internals\Task\LogTable::getList(array(
			"select" => array("ID"),
			"filter" => array(
				"=TASK_ID" => $taskId,
			),
		));

		while ($item = $list->fetch()) {
			$result = \Bitrix\Tasks\Internals\Task\LogTable::delete($item);
		}
	}
}