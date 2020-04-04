<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */


IncludeModuleLangFile(__FILE__);

class CTaskSync
{
	public static $PriorityMapping = array(
		"low" => 0,
		"normal" => 1,
		"high" => 2
	);


	public static $StatusMapping = array(
		"notstarted" => 2,
		"inprogress" => 3,
		"completed" => 5,
		"waitingonothers" => 6,
		"deferred" => 6
	);


	public static $StatusMappingReverse = array(
		1 => "notstarted",
		2 => "notstarted",
		3 => "inprogress",
		4 => "completed",
		5 => "completed",
		6 => "deferred",
		7 => "notstarted"
	);


	public static function SyncTaskItems($type, $userId, $arUserTaskItems)
	{
		global $DB;

		$arTasksReturn = array();
		$arDelete = array();
		$strSql = "SELECT ID 
			FROM b_tasks 
			WHERE 
				EXCHANGE_ID IS NOT NULL 
				AND ZOMBIE = 'N'
				AND RESPONSIBLE_ID = ".intval($userId);
		$rsIDs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		while ($arID = $rsIDs->Fetch())
			$arDelete[] = (int) $arID["ID"];

		foreach ($arUserTaskItems as $taskItem)
		{
			$strSql = "SELECT ID, EXCHANGE_MODIFIED 
				FROM b_tasks 
				WHERE 
					EXCHANGE_ID = '" . $DB->ForSql($taskItem["XML_ID"]) . "' 
					AND ZOMBIE = 'N'
					AND RESPONSIBLE_ID = ".intval($userId);
			$rsTask = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($task = $rsTask->Fetch())
			{
				$key = array_search($task["ID"], $arDelete);
				if ($key !== false)
				{
					unset($arDelete[$key]);
				}
				if ($task["EXCHANGE_MODIFIED"] != $taskItem["MODIFICATION_LABEL"])
				{
					$arTasksReturn[] = array(
						"XML_ID" => $taskItem["XML_ID"],
						"ID" => $task["ID"]
					);
					$arDoNotDelete[] = $task["ID"];
				}
			}
			else
			{
				$arTasksReturn[] = array(
					"XML_ID" => $taskItem["XML_ID"],
					"ID" => 0
				);
			}
		}

		if (sizeof($arDelete))
		{
			// Remove only tasks with RESPONSIBLE_ID = $userId
			$strSql = "SELECT ID FROM b_tasks WHERE ID IN (" . implode(",", $arDelete).") AND RESPONSIBLE_ID = " . (int) $userId;
			$rc = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			while ($arItem = $rc->Fetch())
				CTasks::Delete($arItem['ID'], array('skipExchangeSync' => true));
		}

		return $arTasksReturn;
	}


	public static function SyncModifyTaskItem($arModifyEventArray)
	{
		global $DB;

		$ID = $arModifyEventArray["ID"];

		// sanitize description here
		$Sanitizer = new CBXSanitizer();
		$Sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);
		$Sanitizer->ApplyHtmlSpecChars(false);
		$Sanitizer->DeleteSanitizedTags(true);

		$arModifyEventArray['BODY'] = trim($Sanitizer->SanitizeHtml($arModifyEventArray['BODY']));

		$arFields = array(
			"RESPONSIBLE_ID" => $arModifyEventArray["USER_ID"],
			"SITE_ID" => SITE_ID,
			"EXCHANGE_ID" => $arModifyEventArray["XML_ID"],
			"EXCHANGE_MODIFIED" => $arModifyEventArray["MODIFICATION_LABEL"],
			"TITLE" => $arModifyEventArray["SUBJECT"],
			"DESCRIPTION" => $arModifyEventArray["BODY"],
			"DESCRIPTION_IN_BBCODE" => 'N',
			"CREATED_DATE" => $arModifyEventArray["DATE_CREATE"],
			"PRIORITY" => self::$PriorityMapping[strtolower($arModifyEventArray["IMPORTANCE"])],
			"DURATION_FACT" => ceil($arModifyEventArray["ACTUAL_WORK"] / 60),
			"START_DATE_PLAN" => $arModifyEventArray["START_DATE"],
			"DEADLINE" => $arModifyEventArray["DUE_DATE"],
			"STATUS" => self::$StatusMapping[strtolower($arModifyEventArray["STATUS"])],
			"DURATION_PLAN" => ceil($arModifyEventArray["TOTAL_WORK"] / 60),
			"DURATION_TYPE" => "hours"
		);

		$arExtraFields = array();

		if (
			isset($arModifyEventArray['ExtendedProperty'])
			&& is_array($arModifyEventArray['ExtendedProperty'])
		)
		{
			foreach($arModifyEventArray['ExtendedProperty'] as $arExtendedProperty)
				$arExtraFields[$arExtendedProperty['Name']] = $arExtendedProperty['Value'];
		}

		if ($ID == 0)
		{
			$arFields["STATUS_CHANGED_BY"] = $arFields["CHANGED_BY"] = $arFields["CREATED_BY"] = $arFields["RESPONSIBLE_ID"];
			$arFields["STATUS_CHANGED_DATE"] = $arFields["CHANGED_DATE"] = $arFields["CREATED_DATE"];
			$ID = $DB->Add("b_tasks", $arFields, Array("DESCRIPTION"), "tasks");
			if ($ID)
			{
				$arFields["ID"] = $ID;
				CTaskNotifications::SendAddMessage($arFields);

				$arLogFields = array(
					"TASK_ID" => $ID,
					"USER_ID" => $arFields["CREATED_BY"],
					"CREATED_DATE" => $arFields["CREATED_DATE"],
					"FIELD" => "NEW"
				);
				$log = new CTaskLog();
				$log->Add($arLogFields);
			}
		}
		else
		{
			$strUpdate = $DB->PrepareUpdate("b_tasks", $arFields, "tasks");
			$strSql = "UPDATE b_tasks SET ".$strUpdate." WHERE ID=".$ID;
			$arBinds = array('DESCRIPTION' => $arFields['DESCRIPTION']);
			$result = $DB->QueryBind($strSql, $arBinds, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			if ($result)
			{
				$rsTask = CTasks::GetByID($ID, false);
				if ($arTask = $rsTask->Fetch())
				{
					$arFields["CHANGED_BY"] = $arFields["RESPONSIBLE_ID"];
					$arFields["CHANGED_DATE"] = \Bitrix\Tasks\UI::formatDateTime(\Bitrix\Tasks\Util\User::getTime());
					CTaskNotifications::SendUpdateMessage($arFields, $arTask);

					$arChanges = CTaskLog::GetChanges($arTask, $arFields);
					foreach ($arChanges as $key => $value)
					{
						$arLogFields = array(
							"TASK_ID" => $ID,
							"USER_ID" => $arFields["CHANGED_BY"],
							"CREATED_DATE" => $arFields["CHANGED_DATE"],
							"FIELD" => $key,
							"FROM_VALUE" => $value["FROM_VALUE"],
							"TO_VALUE" => $value["TO_VALUE"],
						);
						$log = new CTaskLog();
						$log->Add($arLogFields);
					}
				}
			}
		}
	}


	public static function AddItem($arFields)
	{
		global $DB;

		if ( ! (CModule::IncludeModule("dav") && CDavExchangeTasks::IsExchangeEnabled()) )
			return;

		$bodyType = 'html';

		if (
			isset($arFields['DESCRIPTION_IN_BBCODE'])
			&& ($arFields['DESCRIPTION_IN_BBCODE'] === 'Y')
		)
		{
			$bodyType = 'text';
		}

		$priorityMapping = array_flip(self::$PriorityMapping);
		$arModifyEventArray = array(
			"USER_ID" => $arFields["RESPONSIBLE_ID"],
			"SUBJECT" => $arFields["TITLE"],
			"BODY" => $arFields["DESCRIPTION"],
			"IMPORTANCE" => $priorityMapping[strtolower($arFields["PRIORITY"])],
			'GUID'        => $arFields['GUID'],
			//'SERIALIZED_DATA' => serialize(array('DESCRIPTION' => $arFields["DESCRIPTION"], 'TITLE' => $arFields["TITLE"])),
			"ACTUAL_WORK" => $arFields["DURATION_FACT"] * 60,
			"STATUS" => self::$StatusMappingReverse[$arFields["STATUS"]],
			"TOTAL_WORK" => $arFields["DURATION_PLAN"] * 60,
			"BODY_TYPE" => $bodyType
		);
		if ($arFields["START_DATE_PLAN"])
		{
			$arModifyEventArray["START_DATE"] = $arFields["START_DATE_PLAN"];
		}
		if ($arFields["DEADLINE"])
		{
			$arModifyEventArray["DUE_DATE"] = $arFields["DEADLINE"];
		}

		$result = CDavExchangeTasks::DoAddItem($arModifyEventArray["USER_ID"], $arModifyEventArray);

		if (array_key_exists("XML_ID", $result))
		{
			$arExchangeFields = array(
				"EXCHANGE_MODIFIED" => $result["MODIFICATION_LABEL"],
				"EXCHANGE_ID" => $result["XML_ID"]
			);
			$strUpdate = $DB->PrepareUpdate("b_tasks", $arExchangeFields, "tasks");
			$strSql = "UPDATE b_tasks SET ".$strUpdate." WHERE ID=".$arFields["ID"];
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}

	protected static function checkExchangeAvailable()
	{
		static $available;

		if($available === null)
		{
			$available = CModule::IncludeModule("dav") && CDavExchangeTasks::IsExchangeEnabled();
		}

		return $available;
	}

	public static function UpdateItem($arFields, $arTask)
	{
		global $DB;

		// do not call static::checkExchangeAvailable() untill you sure exchange is needed for the task, or else you`ll load the module with no purpose

		$isResponsibleChanged = isset($arFields['RESPONSIBLE_ID'])
			&& isset($arTask['RESPONSIBLE_ID'])
			&& ((int)$arFields['RESPONSIBLE_ID'] !== (int)$arTask['RESPONSIBLE_ID']);

		// If responsible changed, we must reassign task to other Exchange-account,
		// but not update the existing task in account of prev. responsible
		if ($isResponsibleChanged && static::checkExchangeAvailable())
		{
			// Prevent unexpected resynchronization of this task in case if DeleteItem() or AddItem() will not complete they work
			$strSql = "UPDATE b_tasks SET EXCHANGE_ID = NULL, EXCHANGE_MODIFIED = NULL WHERE ID = " . (int) $arTask['ID'];
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			self::DeleteItem($arTask);
			$arActualTaskData = array_merge($arTask, $arFields);
			self::AddItem($arActualTaskData);

			return;
		}

		// If item is not in Exchange, skip it ...
		if ( ! ($arTask["EXCHANGE_ID"] && $arTask["EXCHANGE_MODIFIED"]) )
		{
			// ... but if there is responsible changed - create task in Exchange
			if ($isResponsibleChanged && static::checkExchangeAvailable())
			{
				$arActualTaskData = array_merge($arTask, $arFields);
				self::AddItem($arActualTaskData);
			}

			return;
		}

		if(!static::checkExchangeAvailable())
		{
			return;
		}

		$bodyType = 'html';

		if (isset($arFields['DESCRIPTION_IN_BBCODE']))
		{
			if ($arFields['DESCRIPTION_IN_BBCODE'] === 'Y')
				$bodyType = 'text';
		}

		$priorityMapping = array_flip(self::$PriorityMapping);
		$arModifyEventArray = array(
			"USER_ID" => $arFields["RESPONSIBLE_ID"] ? $arFields["RESPONSIBLE_ID"] : $arTask["RESPONSIBLE_ID"],
			"BODY_TYPE" => $bodyType
		);
		if ($arFields["TITLE"])
		{
			$arModifyEventArray["SUBJECT"] = $arFields["TITLE"];
		}
		if ($arFields["DESCRIPTION"])
		{
			$arModifyEventArray["BODY"] = $arFields["DESCRIPTION"];
		}
		if ($arFields["PRIORITY"])
		{
			$arModifyEventArray["IMPORTANCE"] = $priorityMapping[strtolower($arFields["PRIORITY"])];
		}

		if (isset($arFields['GUID']))
			$arModifyEventArray['GUID'] = $arFields['GUID'];

		//$arModifyEventArray['SERIALIZED_DATA'] = serialize($arFields);

		if ($arFields["DURATION_FACT"])
		{
			$arModifyEventArray["ACTUAL_WORK"] = $arFields["DURATION_FACT"] * 60;
		}
		if ($arFields["START_DATE_PLAN"])
		{
			$arModifyEventArray["START_DATE"] = $arFields["START_DATE_PLAN"];
		}
		if ($arFields["DEADLINE"])
		{
			$arModifyEventArray["DUE_DATE"] = $arFields["DEADLINE"];
		}
		if ($arFields["STATUS"])
		{
			$arModifyEventArray["STATUS"] = self::$StatusMappingReverse[$arFields["STATUS"]];
		}
		if ($arFields["DURATION_PLAN"])
		{
			$arModifyEventArray["TOTAL_WORK"] = $arFields["DURATION_PLAN"] * 60;
		}

		$result = CDavExchangeTasks::DoUpdateItem($arModifyEventArray["USER_ID"], $arTask["EXCHANGE_ID"], $arTask["EXCHANGE_MODIFIED"], $arModifyEventArray);

		if (array_key_exists("XML_ID", $result))
		{
			$arExchangeFields = array(
				"EXCHANGE_MODIFIED" => $result["MODIFICATION_LABEL"]
			);
			$strUpdate = $DB->PrepareUpdate("b_tasks", $arExchangeFields, "tasks");
			$strSql = "UPDATE b_tasks SET ".$strUpdate." WHERE ID=".$arFields["ID"];
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}


	public static function DeleteItem($arTask)
	{
		if (CModule::IncludeModule("dav") && CDavExchangeTasks::IsExchangeEnabled())
			CDavExchangeTasks::DoDeleteItem($arTask["RESPONSIBLE_ID"], $arTask["EXCHANGE_ID"]);
	}
}
