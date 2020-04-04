<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

class CTaskLog
{
	// left for compatibility
	static $arComparedFields = array(
		"TITLE" => "string",
		"DESCRIPTION" => "text",
		"CREATED_BY" => "integer",
		"RESPONSIBLE_ID" => "integer",
		"DEADLINE" => "date",
		"START_DATE_PLAN" => "date",
		"END_DATE_PLAN" => "date",
		"ACCOMPLICES" => "array",
		"AUDITORS" => "array",
		"FILES" => "array",
		"TAGS" => "array",
		"PRIORITY" => "integer",
		"GROUP_ID" => "integer",
		"DURATION_PLAN" => "integer",
		"DURATION_PLAN_SECONDS" => "integer",
		"DURATION_FACT" => "integer",
		"TIME_ESTIMATE" => "integer",
		"TIME_SPENT_IN_LOGS" => "integer",
		"PARENT_ID" => "integer",
		"DEPENDS_ON" => "array",
		"STATUS" => "integer",
		"MARK" => "string",
		"ADD_IN_REPORT" => "bool",
		'CHECKLIST_ITEM_CREATE' => 'string',
		'CHECKLIST_ITEM_RENAME' => 'string',
		'CHECKLIST_ITEM_REMOVE' => 'string',
		'CHECKLIST_ITEM_CHECK' => 'string',
		'CHECKLIST_ITEM_UNCHECK' => 'string',
		'UF_TASK_WEBDAV_FILES' => 'array',
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
		if ((string)$arFields['CREATED_DATE'] == '') {
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

			$addResult = \Bitrix\Tasks\Internals\Task\LogTable::add(array(
				'CREATED_DATE' => $createdDate,
				'USER_ID' => $arFields["USER_ID"],
				'TASK_ID' => $arFields["TASK_ID"],
				'FIELD' => $arFields["FIELD"],
				'FROM_VALUE' => $arFields["FROM_VALUE"],
				'TO_VALUE' => $arFields["TO_VALUE"],
			));

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

			$key = strtoupper($key);

			switch ($key) {
				case "CREATED_DATE":
					$arSqlSearch[] = CTasks::FilterCreate("TL." . $key, $DB->CharToDateFunction($val), "date", $bFullJoin, $cOperationType);
					break;

				case "USER_ID":
				case "TASK_ID":
					$arSqlSearch[] = CTasks::FilterCreate("TL." . $key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "FIELD":
					$arSqlSearch[] = CTasks::FilterCreate("TL." . $key, $val, "string_equal", $bFullJoin, $cOperationType);
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
			$by = strtolower($by);
			$order = strtolower($order);
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
		$arChanges = array();

		array_walk($currentFields, array("CTaskLog", "UnifyFields"));
		array_walk($newFields, array("CTaskLog", "UnifyFields"));

		if (array_key_exists('REAL_STATUS', $currentFields)) {
			$currentFields["STATUS"] = $currentFields["REAL_STATUS"];
		}

		$comparedFields = static::getTrackedFields();

		foreach ($newFields as $key => $value) {
			if (array_key_exists($key, $comparedFields) && $currentFields[$key] != $newFields[$key]) {
				if (!array_key_exists($key, $currentFields) || !array_key_exists($key, $newFields)) {
					continue;
				}

				if ($key == "FILES") {
					$arDeleted = array_diff($currentFields[$key], $newFields[$key]);
					if (sizeof($arDeleted) > 0) {
						/** @noinspection PhpDynamicAsStaticMethodCallInspection */
						$rsFiles = CFile::GetList(array(), array("@ID" => implode(",", $arDeleted)));
						$arFilesNames = array();
						while ($arFile = $rsFiles->Fetch()) {
							$arFilesNames[] = $arFile["ORIGINAL_NAME"];
						}
						if (sizeof($arFilesNames)) {
							$arChanges["DELETED_FILES"] = array("FROM_VALUE" => implode(", ", $arFilesNames), "TO_VALUE" => false);
						}
					}

					$arNew = array_diff($newFields[$key], $currentFields[$key]);
					if (sizeof($arNew) > 0) {
						/** @noinspection PhpDynamicAsStaticMethodCallInspection */
						$rsFiles = CFile::GetList(array(), array("@ID" => implode(",", $arNew)));
						$arFilesNames = array();
						while ($arFile = $rsFiles->Fetch()) {
							$arFilesNames[] = $arFile["ORIGINAL_NAME"];
						}
						if (sizeof($arFilesNames)) {
							$arChanges["NEW_FILES"] = array("FROM_VALUE" => false, "TO_VALUE" => implode(", ", $arFilesNames));
						}
					}
				} else {
					if ($comparedFields[$key]['TYPE'] == "text") {
						$currentFields[$key] = false;
						$newFields[$key] = false;
					} elseif ($comparedFields[$key]['TYPE'] == "array") {
						$currentFields[$key] = implode(",", $currentFields[$key]);
						$newFields[$key] = implode(",", $newFields[$key]);
					}

					$arChanges[$key] = array(
						"FROM_VALUE" => $currentFields[$key] || $key == "PRIORITY" ? $currentFields[$key] : false,
						"TO_VALUE" => $newFields[$key] || $key == "PRIORITY" ? $newFields[$key] : false
					);
				}
			}
		}

		return $arChanges;
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