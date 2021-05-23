<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("intranet"))
	return ShowError(GetMessage("EC_IBLOCK_MODULE_NOT_INSTALLED"));
if (!CModule::IncludeModule("iblock"))
	return ShowError(GetMessage("EC_IBLOCK_MODULE_NOT_INSTALLED"));
if (!CModule::IncludeModule("socialnetwork"))
	return ShowError(GetMessage("EC_SONET_MODULE_NOT_INSTALLED"));
if (!CModule::IncludeModule("bizproc"))
	return ShowError(GetMessage("EC_BIZPROC_MODULE_NOT_INSTALLED"));

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$GLOBALS["DBType"]."/favorites.php");

$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
$arParams["IBLOCK_ID"] = $iblockId;

$taskType = strtolower($arParams["TASK_TYPE"]);
if (!in_array($taskType, array("group", "user")))
	$taskType = "user";

global $USER, $APPLICATION;

$ownerId = IntVal($arParams["OWNER_ID"]);
if ($ownerId <= 0)
{
	$taskType = "user";
	$ownerId = $USER->GetID();
}
$ownerId = IntVal($ownerId);

$arParams["TASK_VAR"] = Trim($arParams["TASK_VAR"]);
if (StrLen($arParams["TASK_VAR"]) <= 0)
	$arParams["TASK_VAR"] = "task_id";

$arParams["GROUP_VAR"] = Trim($arParams["GROUP_VAR"]);
if (StrLen($arParams["GROUP_VAR"]) <= 0)
	$arParams["GROUP_VAR"] = "group_id";

$arParams["USER_VAR"] = Trim($arParams["USER_VAR"]);
if (StrLen($arParams["USER_VAR"]) <= 0)
	$arParams["USER_VAR"] = "user_id";

$arParams["VIEW_VAR"] = Trim($arParams["VIEW_VAR"]);
if (StrLen($arParams["VIEW_VAR"]) <= 0)
	$arParams["VIEW_VAR"] = "view_id";

$arParams["ACTION_VAR"] = Trim($arParams["ACTION_VAR"]);
if (StrLen($arParams["ACTION_VAR"]) <= 0)
	$arParams["ACTION_VAR"] = "action";

if (StrLen($arParams["PAGE_VAR"]) <= 0)
	$arParams["PAGE_VAR"] = "page";

$arParams["PATH_TO_GROUP_TASKS"] = Trim($arParams["PATH_TO_GROUP_TASKS"]);
if (StrLen($arParams["PATH_TO_GROUP_TASKS"]) <= 0)
	$arParams["PATH_TO_GROUP_TASKS"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_tasks&".$arParams["GROUP_VAR"]."=#group_id#");
$arParams["PATH_TO_USER_TASKS"] = Trim($arParams["PATH_TO_USER_TASKS"]);
if (StrLen($arParams["PATH_TO_USER_TASKS"]) <= 0)
	$arParams["PATH_TO_USER_TASKS"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks&".$arParams["USER_VAR"]."=#user_id#");

$arParams["PATH_TO_GROUP_TASKS_TASK"] = Trim($arParams["PATH_TO_GROUP_TASKS_TASK"]);
if (StrLen($arParams["PATH_TO_GROUP_TASKS_TASK"]) <= 0)
	$arParams["PATH_TO_GROUP_TASKS_TASK"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_tasks_task&".$arParams["GROUP_VAR"]."=#group_id#&".$arParams["TASK_VAR"]."=#task_id#&".$arParams["ACTION_VAR"]."=#action#");
$arParams["PATH_TO_USER_TASKS_TASK"] = Trim($arParams["PATH_TO_USER_TASKS_TASK"]);
if (StrLen($arParams["PATH_TO_USER_TASKS_TASK"]) <= 0)
	$arParams["PATH_TO_USER_TASKS_TASK"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks_task&".$arParams["USER_VAR"]."=#user_id#&".$arParams["TASK_VAR"]."=#task_id#&".$arParams["ACTION_VAR"]."=#action#");

$arParams["PATH_TO_GROUP_TASKS_VIEW"] = Trim($arParams["PATH_TO_GROUP_TASKS_VIEW"]);
if (StrLen($arParams["PATH_TO_GROUP_TASKS_VIEW"]) <= 0)
	$arParams["PATH_TO_GROUP_TASKS_VIEW"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_tasks_view&".$arParams["GROUP_VAR"]."=#group_id#&".$arParams["VIEW_VAR"]."=#view_id#&".$arParams["ACTION_VAR"]."=#action#");
$arParams["PATH_TO_USER_TASKS_VIEW"] = Trim($arParams["PATH_TO_USER_TASKS_VIEW"]);
if (StrLen($arParams["PATH_TO_USER_TASKS_VIEW"]) <= 0)
	$arParams["PATH_TO_USER_TASKS_VIEW"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks_view&".$arParams["USER_VAR"]."=#user_id#&".$arParams["VIEW_VAR"]."=#view_id#&".$arParams["ACTION_VAR"]."=#action#");

if ($taskType == "user")
{
	$arParams["PATH_TO_TASKS"] = Str_Replace("#user_id#", "#owner_id#", $arParams["PATH_TO_USER_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = Str_Replace("#user_id#", "#owner_id#", $arParams["PATH_TO_USER_TASKS_TASK"]);
	$arParams["PATH_TO_TASKS_VIEW"] = Str_Replace("#user_id#", "#owner_id#", $arParams["PATH_TO_USER_TASKS_VIEW"]);
}
else
{
	$arParams["PATH_TO_TASKS"] = Str_Replace("#group_id#", "#owner_id#", $arParams["PATH_TO_GROUP_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = Str_Replace("#group_id#", "#owner_id#", $arParams["PATH_TO_GROUP_TASKS_TASK"]);
	$arParams["PATH_TO_TASKS_VIEW"] = Str_Replace("#group_id#", "#owner_id#", $arParams["PATH_TO_GROUP_TASKS_VIEW"]);
}

$arParams["ITEMS_COUNT"] = IntVal($arParams["ITEMS_COUNT"]);
if ($arParams["ITEMS_COUNT"] <= 0)
	$arParams["ITEMS_COUNT"] = 5;

if ($GLOBALS["USER"]->IsAuthorized())
{	
	$arResult["FatalError"] = "";

	$arParams["TASK_TYPE"] = $taskType;
	$arParams["OWNER_ID"] = $ownerId;

	if (strlen($arResult["FatalError"]) <= 0)
	{
		if (!CIntranetTasks::IsTasksFeatureActive($taskType, $ownerId))
			$arResult["FatalError"] .= GetMessage("INTE_TASKS_OFF").".";
	}

	if (strlen($arResult["FatalError"]) <= 0)
	{
		if (!CIntranetTasks::CanCurrentUserPerformOperation($taskType, $ownerId, "view"))
			$arResult["FatalError"] .= GetMessage("INTE_NO_SONET_PERMS").".";
	}

	if (strlen($arResult["FatalError"]) <= 0)
	{
		$globalParentSectionId = CIntranetTasks::InitializeIBlock($taskType, $ownerId, $arParams["FORUM_ID"]);
		if ($globalParentSectionId <= 0)
			$arResult["FatalError"] .= GetMessage("INTE_TASK_NOT_FOUND").". ";
	}

	if (strlen($arResult["FatalError"]) <= 0)
	{
		$arResult["TaskFields"] = CIntranetTasks::GetTaskFields($taskType, $ownerId);
		$arResult["TaskFields"]["TASKSTATUS"] = array(
			"NAME" => GetMessage("INTASK_L_TASKSTATUS"),
			"FULL_NAME" => GetMessage("INTASK_L_TASKSTATUS"),
			"FILTERABLE" => true,
		);
		$arResult["TaskFieldsMap"] = CIntranetTasks::GetTaskFieldsMap($arResult["TaskFields"]);
		$arResult["TaskFieldsMap"]["TASKSTATUS"] = "TASKSTATUS";


		$arTaskStatusOldLink = array();
		$arTaskStatusOldLinkMap = array("NOTACCEPTED" => "NotAccepted", "NOTSTARTED" => "NotStarted", "INPROGRESS" => "InProgress", "COMPLETED" => "Closed", "WAITING" => "Waiting", "DEFERRED" => "Deferred");
		$dbRes = CIBlockProperty::GetPropertyEnum("TaskStatus", Array("SORT" => "ASC"), Array("IBLOCK_ID" => $iblockId));
		while ($arRes = $dbRes->Fetch())
			$arTaskStatusOldLink[$arRes["ID"]] = $arTaskStatusOldLinkMap[strtoupper($arRes["XML_ID"])];
	}

	/**************************  SETTINGS  **********************************/
	if (StrLen($arResult["FatalError"]) <= 0)
	{
		for ($i = 0; $i < 3; $i++)
		{
			$orderBy = $arParams["ORDER_BY_".$i];
			$orderDir = $arParams["ORDER_DIR_".$i];

			$orderBy = StrToUpper(Trim($orderBy));
			if (Array_Key_Exists($orderBy, $arResult["TaskFieldsMap"]))
			{
				$arParams["ORDER_BY_".$i] = $arResult["TaskFieldsMap"][$orderBy];
				$arParams["ORDER_DIR_".$i] = StrToUpper(Trim($orderDir));
				if (!In_Array($arParams["ORDER_DIR_".$i], array("ASC", "DESC")))
					$arParams["ORDER_DIR_".$i] = "ASC";
			}
			else
			{
				$arParams["ORDER_BY_".$i] = "";
				$arParams["ORDER_DIR_".$i] = "";
			}
		}

		foreach ($arParams as $key => $value)
		{
			if (StrToLower(SubStr($key, 0, 4)) != "FLT_")
				continue;
			if (!Is_Array($value) && StrLen($value) <= 0 || Is_Array($value) && Count($value) <= 0)
				continue;

			$key = StrToUpper(SubStr($key, 4));

			if (Array_Key_Exists($key, $arResult["TaskFieldsMap"]) && $arResult["TaskFields"][$arResult["TaskFieldsMap"][$key]]["FILTERABLE"])
			{
				$realKey = $arResult["TaskFieldsMap"][$key];
				$arFld = $arResult["TaskFields"][$realKey];
				if ($arFld["Type"] == "datetime")
				{
					if ($value == "current")
						$arParams["FILTER"][$realKey] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE));
					else
						$arParams["FILTER"][$realKey] = $value;
				}
				elseif ($arFld["Type"] == "user")
				{
					if ($value == "current")
						$arParams["FILTER"][$realKey] = $GLOBALS["USER"]->GetID();
					else
						$arParams["FILTER"][$realKey] = $value;
				}
				else
				{
					$arParams["FILTER"][$realKey] = $value;
				}
			}
		}

		if (count($arParams["COLUMNS"]) <= 0)
		{
			$arParams["COLUMNS"] = array(
				"NAME", "PROPERTY_TASKASSIGNEDTO", "PROPERTY_TASKPRIORITY", "DATE_ACTIVE_FROM", "DATE_ACTIVE_TO", "TASKSTATUS", "PROPERTY_TASKCOMPLETE"
			);
		}

		$arParams["FILTER"]["!TASKSTATUS"] = "Closed";
	}
	/**************************  END SETTINGS  **********************************/

	/**************************  FILTER  **********************************/
	if (strlen($arResult["FatalError"]) <= 0)
	{
		$arResult["Error"] = "";

		$arResult["isCurrentUser"] = false;
		if ($taskType == "user")
			$arResult["isCurrentUser"] = ($ownerId == $GLOBALS["USER"]->GetID());

		$arOrderBy = array();
		for ($i = 0; $i < 3; $i++)
		{
			if (strlen($arParams["ORDER_BY_".$i]) <= 0)
				continue;

			if (array_key_exists($arParams["ORDER_BY_".$i], $arResult["TaskFields"]))
			{
				$arParams["ORDER_DIR_".$i] = (strtoupper($arParams["ORDER_DIR_".$i]) == "ASC" ? "ASC" : "DESC");
				$arOrderBy[$arParams["ORDER_BY_".$i]] = $arParams["ORDER_DIR_".$i];
			}
		}

		if (count($arOrderBy) <= 0)
		{
			$arOrderBy = array(
				"DATE_ACTIVE_TO" => "ASC",
				"PROPERTY_TASKPRIORITY" => "ASC",
			);
		}

		if (count($arOrderBy) < 3)
			$arOrderBy["SORT"] = "ASC";
	}
	/**************************  END FILTER  **********************************/

	if (strlen($arResult["FatalError"]) <= 0)
	{
		$arSectionsChain = array();
		if (is_array($arParams["FILTER"]))
		{
			foreach ($arParams["FILTER"] as $key => $value)
			{
				$op = "";
				$opTmp = substr($key, 0, 1);
				if (in_array($opTmp, array("!", "<", ">")))
				{
					$op = $opTmp;
					$key = substr($key, 1);
				}

				if (array_key_exists($key, $arResult["TaskFields"]) && $arResult["TaskFields"][$key]["FILTERABLE"])
				{
					if ($key == "IBLOCK_SECTION_ID")
					{
						$bFirst = true;
						$dbSectionsChain = CIBlockSection::GetNavChain($iblockId, $value);
						while ($arSect = $dbSectionsChain->GetNext())
						{
							if ($bFirst)
							{
								if ($globalParentSectionId > 0 && $arSect["ID"] != $globalParentSectionId)
									break;
								$bFirst = false;
								continue;
							}
							$arSectionsChain[] = $arSect;
						}
						if (!$bFirst)
							$arFilter["SECTION_ID"] = $value;
					}
					else
					{
						$arFilter[$op.$key] = $value;
					}
				}
			}
		}

		$arFilter["CHECK_BP_TASKS_PERMISSIONS"] = $taskType."_".$ownerId."_read";

		$strCurrentUrlTmp = $GLOBALS["APPLICATION"]->GetCurPageParam("", array("back_url"));
		$strUrlAppendix = "back_url=".urlencode($strCurrentUrlTmp);
		if (array_key_exists("flt_iblock_section", $_REQUEST))
		{
			$fsId = intval($_REQUEST["flt_iblock_section"]);
			if ($fsId > 0)
				$strUrlAppendix .= "&flt_iblock_section=".$fsId;
		}

		$arNavStartParams = array("nPageSize" => $arParams["ITEMS_COUNT"], "bShowAll" => false, "bDescPageNumbering" => false);
		$arNavigation = CDBResult::GetNavParams($arNavStartParams);

		$arSelectFields = array("ID", "NAME", "IBLOCK_ID", "CREATED_BY", "DATE_ACTIVE_FROM", "DATE_ACTIVE_TO", "DETAIL_TEXT");
		foreach ($arParams["COLUMNS"] as $field)
		{
			if (!in_array($field, $arSelectFields) && array_key_exists($field, $arResult["TaskFieldsMap"]))
				$arSelectFields[] = $field;
		}

		$arResult["TasksPropsShow"] = array();
		foreach ($arParams["COLUMNS"] as $field)
		{
			if (array_key_exists($field, $arResult["TaskFieldsMap"]))
				$arResult["TasksPropsShow"][] = $arResult["TaskFieldsMap"][$field];
		}

		$arPermsCache = array();
		$dbTasksList = false;
		$arTasksListArray = array();

		if ($taskType == "user")
		{
			//if (!$arResult["isCurrentUser"])
			$arFilter["PROPERTY_TASKASSIGNEDTO"] = $ownerId;
		}
		else
		{
			$arFilter["SECTION_ID"] = $globalParentSectionId;
			$arFilter["INCLUDE_SUBSECTIONS"] = "Y";
		}

		//echo "<pre><b>CIBlockElement::GetList</b>\n".print_r(array($arOrderBy, $arFilter, false, $arNavStartParams, $arSelectFields), true)."</pre>";
		//echo "<pre>".print_r($arFilter, true)."</pre>";

		$dbTasksList = CIntranetTasks::GetList(
			$arOrderBy,
			$arFilter,
			false,
			$arNavStartParams,
			$arSelectFields
		);
		$dbTasksList->NavStart($arParams["ITEMS_COUNT"]);
		while ($arTask = $dbTasksList->Fetch())
		{
			$currentTaskAssignedToUser = $arTask["PROPERTY_TaskAssignedTo"];

			$currentTaskStatusId = $arTask["DocumentState"]["STATE_NAME"];
			$currentTaskStatus = $arTask["DocumentState"]["STATE_TITLE"];

			if (is_array($arTask["DocumentState"]["PROPERTY_TaskPriority"]))
			{
				foreach ($arTask["DocumentState"]["PROPERTY_TaskPriority"] as $k => $v)
				{
					$currentTaskPriorityId = $k;
					$currentTaskPriority = $v;
				}
			}

			$arTask["TASKSTATUS_PRINTABLE"] = $arTask["DocumentState"]["STATE_TITLE"];
			if (is_array($arTask["PROPERTY_TaskPriority_PRINTABLE"]))
			{
				foreach ($arTask["PROPERTY_TaskPriority_PRINTABLE"] as $v)
					$arTask["TASKPRIORITY_PRINTABLE"] = trim($v);
			}

			if ($arTask["TaskType"] == "group")
				$p1 = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_TASKS_TASK"], array("group_id" => $arTask["OwnerId"], "task_id" => $arTask["ID"], "action" => "view"));
			else
				$p1 = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_TASKS_TASK"], array("user_id" => $arTask["OwnerId"], "task_id" => $arTask["ID"], "action" => "view"));

			$arResult["Tasks"][] = array(
				"FIELDS" => $arTask,
				"VIEW_URL" => $p1.(StrPos($p1, "?") === false ? "?" : "&").$strUrlAppendix,
				"IS_CURRENT_USER_TASK" => ($currentTaskAssignedToUser == $GLOBALS["USER"]->GetID()),
				"TASK_STATUS" => $currentTaskStatusId,
				"TASK_PRIORITY" => $currentTaskPriority,
			);
		}
	}
	//echo "<pre>".print_r($arResult, true)."</pre>";

	$this->IncludeComponentTemplate();
}
?>