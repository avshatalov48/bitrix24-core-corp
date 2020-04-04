<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/prolog.php");
IncludeModuleLangFile(__FILE__);

$adminChain->AddItem(array("TEXT" => GetMessage("TASKS_SETTINGS"), "LINK" => "all_settings_index.php?lang=".LANG));
$adminChain->AddItem(array("TEXT" => GetMessage("TASKS_PRODUCT_SETTINGS"), "LINK" => "settings_index.php?lang=".LANG));
$adminChain->AddItem(array("TEXT" => GetMessage("TASKS_MODULES"), "LINK" => "module_admin.php?lang=".LANG));
$adminChain->AddItem(array("TEXT" => GetMessage("TASKS_CONVERT_TASKS"), "LINK" => "tasks_convert_admin.php?lang=".LANG));

$adminMenu->aActiveSections[] = $adminMenu->aGlobalMenu["global_menu_settings"];

if (!$USER->IsAdmin())
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

CModule::IncludeModule('iblock');

$CNT = 0;

function GetTasksList($iblockId, $arOrder = array("SORT" => "ASC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields=array())
{
	$userId = \Bitrix\Tasks\Util\User::getId();

	$iblockId = IntVal($iblockId);

	$arFilter["IBLOCK_ID"] = $iblockId;
	$arFilter["SHOW_NEW"] = "Y";

	if (count($arSelectFields) > 0)
	{
		if (!in_array("IBLOCK_SECTION_ID", $arSelectFields))
			$arSelectFields[] = "IBLOCK_SECTION_ID";
		if (!in_array("ID", $arSelectFields))
			$arSelectFields[] = "ID";
		if (!in_array("IBLOCK_ID", $arSelectFields))
			$arSelectFields[] = "IBLOCK_ID";
		if (!in_array("CREATED_BY", $arSelectFields))
			$arSelectFields[] = "CREATED_BY";
	}

	$arResultList = array();
	$arCache = array();
	$isInSecurity = CModule::IncludeModule("security");

	$dbTasksList = CIBlockElement::GetList(
		$arOrder,
		$arFilter,
		$arGroupBy,
		$arNavStartParams,
		$arSelectFields
	);
	while ($obTask = $dbTasksList->GetNextElement())
	{
		$arResult = array();

		$arFields = $obTask->GetFields();
		foreach ($arFields as $fieldKey => $fieldValue)
		{
			if (substr($fieldKey, 0, 1) == "~")
				continue;

			$arResult[$fieldKey] = $fieldValue;

			if (in_array($fieldKey, array("MODIFIED_BY", "CREATED_BY")))
			{
				$arResult[$fieldKey."_PRINTABLE"] = CIntranetTasks::PrepareUserForPrint($fieldValue);
			}
			elseif ($fieldKey == "DETAIL_TEXT")
			{
				if ($isInSecurity)
				{
					$filter = new CSecurityFilter;
					$arResult["DETAIL_TEXT_PRINTABLE"] = $filter->TestXSS($arFields["~DETAIL_TEXT"]);
					$arResult["DETAIL_TEXT"] = $arResult["DETAIL_TEXT_PRINTABLE"];
				}
				else
				{
					$arResult["DETAIL_TEXT_PRINTABLE"] = nl2br($arFields["DETAIL_TEXT"]);
					$arResult["DETAIL_TEXT"] = $arFields["DETAIL_TEXT"];
				}
			}
			else
			{
				$arResult[$fieldKey."_PRINTABLE"] = $fieldValue;
			}
		}

		$arProperties = $obTask->GetProperties();
		foreach ($arProperties as $propertyKey => $propertyValue)
		{
			$arResult["PROPERTY_".$propertyKey] = $propertyValue["VALUE"];

			if (strtoupper($propertyKey) == "TASKCOMPLETE")
			{
				$ps = intval($propertyValue["VALUE"]);
				if ($ps > 100)
					$ps = 100;
				elseif ($ps < 0)
					$ps = 0;
				$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = '<div class="task-complete-bar-out" title="'.GetMessage("INTASK_L_TASKCOMPLETE", array("#PRC#" => IntVal($propertyValue["VALUE"]))).'">';
				if ($ps > 0)
					$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] .= '<div class="task-complete-bar-in" style="width:'.$ps.'%;"><div class="empty"></div></div>';
				$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] .= '</div>';
			}
			elseif (strlen($propertyValue["USER_TYPE"]) > 0)
			{
				if ($propertyValue["USER_TYPE"] == "UserID")
					$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = CIntranetTasks::PrepareUserForPrint($propertyValue["VALUE"]);
				else
					$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = $propertyValue["VALUE"];
			}
			elseif ($propertyValue["PROPERTY_TYPE"] == "G")
			{
				$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = array();
				$vx = CIntranetTasks::PrepareSectionForPrint($propertyValue["VALUE"], $propertyValue["LINK_IBLOCK_ID"]);
				foreach ($vx as $vx1 => $vx2)
					$arResult["PROPERTY_".$propertyKey."_PRINTABLE"][$vx1] = $vx2["NAME"];
			}
			elseif ($propertyValue["PROPERTY_TYPE"] == "L")
			{
				$arResult["PROPERTY_".$propertyKey] = array();

				$arPropertyValue = $propertyValue["VALUE"];
				$arPropertyKey = $propertyValue["VALUE_ENUM_ID"];
				if (!is_array($arPropertyValue))
				{
					$arPropertyValue = array($arPropertyValue);
					$arPropertyKey = array($arPropertyKey);
				}

				for ($i = 0, $cnt = count($arPropertyValue); $i < $cnt; $i++)
					$arResult["PROPERTY_".$propertyKey][$arPropertyKey[$i]] = $arPropertyValue[$i];

				$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = $arResult["PROPERTY_".$propertyKey];
			}
			elseif ($propertyValue["PROPERTY_TYPE"] == "S" && $propertyValue["ROW_COUNT"] > 1)
			{
				if (is_array($propertyValue["VALUE"]))
				{
					$arResult["PROPERTY_".$propertyKey] = array();
					$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = array();

					if ($isInSecurity)
					{
						foreach ($propertyValue["~VALUE"] as $k => $v)
						{
							$filter = new CSecurityFilter;
							$arResult["PROPERTY_".$propertyKey."_PRINTABLE"][$k] = $filter->TestXSS($v);
							$arResult["PROPERTY_".$propertyKey][$k] = $arResult["PROPERTY_".$propertyKey."_PRINTABLE"][$k];
						}
					}
					else
					{
						foreach ($propertyValue["VALUE"] as $k => $v)
						{
							$arResult["PROPERTY_".$propertyKey."_PRINTABLE"][$k] = nl2br($v);
							$arResult["PROPERTY_".$propertyKey][$k] = $v;
						}
					}
				}
				else
				{
					if ($isInSecurity)
					{
						$filter = new CSecurityFilter;
						$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = $filter->TestXSS($propertyValue["~VALUE"]);
						$arResult["PROPERTY_".$propertyKey] = $arResult["PROPERTY_".$propertyKey."_PRINTABLE"];
					}
					else
					{
						$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = nl2br($propertyValue["VALUE"]);
						$arResult["PROPERTY_".$propertyKey] = $propertyValue["VALUE"];
					}
				}
			}
		}

		$arResult["ROOT_SECTION_ID"] = 0;
		$arResult["IBLOCK_SECTION_ID_PRINTABLE"] = array();
		$v = CIntranetTasks::PrepareSectionForPrint($arResult["IBLOCK_SECTION_ID"], $iblockId);
		if (is_array($v))
		{
			foreach ($v as $k1 => $v1)
			{
				if ($arResult["ROOT_SECTION_ID"] == 0)
				{
					$arResult["ROOT_SECTION_ID"] = $k1;
					$taskType = ($v1["XML_ID"] == "users_tasks" ? "user" : "group");
					$ownerId = ($taskType == "user" ? $arResult["PROPERTY_TaskAssignedTo"] : $v1["XML_ID"]);
				}
				else
				{
					$arResult["IBLOCK_SECTION_ID_PRINTABLE"][$k1] = $v1["NAME"];
				}
			}
		}

		if (!array_key_exists($taskType."_".$ownerId, $arCache))
		{
			$arCurrentUserGroups = array();
			if ($taskType == "group")
			{
				$arCurrentUserGroups[] = SONET_ROLES_ALL;

				if (\Bitrix\Tasks\Util\User::isAuthorized())
					$arCurrentUserGroups[] = SONET_ROLES_AUTHORIZED;

				$r = CSocNetUserToGroup::GetUserRole($userId, $ownerId);
				if (strlen($r) > 0)
					$arCurrentUserGroups[] = $r;
			}
			else
			{
				$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_ALL;

				if (\Bitrix\Tasks\Util\User::isAuthorized())
					$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_AUTHORIZED;

				if (CSocNetUserRelations::IsFriends($userId, $ownerId))
					$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_FRIENDS;
			}
			$arCache[$taskType."_".$ownerId] = $arCurrentUserGroups;
		}

		$arCurrentUserGroups = $arCache[$taskType."_".$ownerId];

		if ($userId == $arResult["CREATED_BY"])
			$arCurrentUserGroups[] = "author";
		if ($userId == $arResult["PROPERTY_TaskAssignedTo"])
			$arCurrentUserGroups[] = "responsible";
		if (is_array($arResult["PROPERTY_TaskTrackers"]) && in_array($userId, $arResult["PROPERTY_TaskTrackers"]))
			$arCurrentUserGroups[] = "trackers";

		$arResult["DocumentState"] = array();

		$arDocumentStates = CBPDocument::GetDocumentStates(
			array("intranet", "CIntranetTasksDocument", "x".$iblockId),
			array("intranet", "CIntranetTasksDocument", $arResult["ID"])
		);
		$kk = array_keys($arDocumentStates);
		foreach ($kk as $k)
		{
			$arResult["DocumentState"] = $arDocumentStates[$k];
			$arResult["DocumentState"]["AllowableEvents"] = CBPDocument::GetAllowableEvents($userId, $arCurrentUserGroups, $arDocumentStates[$k]);
		}

		$arResult["TaskType"] = $taskType;
		$arResult["OwnerId"] = $ownerId;

		$arResult["CurrentUserCanViewTask"] = CIntranetTasksDocument::CanUserOperateDocument(
			INTASK_DOCUMENT_OPERATION_READ_DOCUMENT,
			$userId,
			$arResult["ID"],
			array("TaskType" => $taskType, "OwnerId" => $ownerId, "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
		);
		$arResult["CurrentUserCanCommentTask"] = CIntranetTasksDocument::CanUserOperateDocument(
			INTASK_DOCUMENT_OPERATION_COMMENT_DOCUMENT,
			$userId,
			$arResult["ID"],
			array("TaskType" => $taskType, "OwnerId" => $ownerId, "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
		);
		$arResult["CurrentUserCanDeleteTask"] = CIntranetTasksDocument::CanUserOperateDocument(
			INTASK_DOCUMENT_OPERATION_DELETE_DOCUMENT,
			$userId,
			$arResult["ID"],
			array("TaskType" => $taskType, "OwnerId" => $ownerId, "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
		);
		$arResult["CurrentUserCanWriteTask"] = CIntranetTasksDocument::CanUserOperateDocument(
			INTASK_DOCUMENT_OPERATION_WRITE_DOCUMENT,
			$userId,
			$arResult["ID"],
			array("TaskType" => $taskType, "OwnerId" => $ownerId, "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
		);

		$arResultList[] = $arResult;
	}

	$dbTasksList = new CDBResult();
	$dbTasksList->InitFromArray($arResultList);

	return $dbTasksList;
}


if (CModule::IncludeModule("intranet") && CModule::IncludeModule("tasks"))
{
	$arStatusMappings = array(
		"NotStarted" => 2,
		"Waiting" => 2,
		"InProgress" => 3,
		"Completed" => 4,
		"Closed" => 5,
		"Deferred" => 6
	);

	if (isset($_GET["ID"]))
	{
		$ID  = (int)$_GET["ID"];
	}
	else
	{
		$ID = 0;
	}

	if (isset($_GET["CNT"]))
	{
		$CNT  = (int)$_GET["CNT"];
	}
	else
	{
		$CNT = 0;
	}

	if (isset($_GET["IBN"]))
	{
		$IBN  = (int)$_GET["IBN"];
	}
	else
	{
		$IBN = 0;
	}

	$rsSites = CSite::GetList(($b = ""), ($o = ""), Array("ACTIVE" => "Y"));
	$arIBlockIDs = array();
	while ($site = $rsSites->Fetch())
	{
		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0, $site["LID"]);
		if ($iblockId > 0)
		{
			$arIBlockIDs[] = $iblockId;
		}
	}
	$arIBlockIDs = array_values(array_unique($arIBlockIDs));

	for ($i = $IBN; $i < sizeof($arIBlockIDs); $i++)
	{
		$dbTasksList = GetTasksList($arIBlockIDs[$i], array("ID" => "ASC"), array(">ID" => $ID));
		$start_time = microtime(true);
		while ($task = $dbTasksList->Fetch())
		{
			$ID = $task["ID"];
			$newTask = array(
				"SITE_ID" => $task["LID"],
				"TITLE" => htmlspecialchars_decode($task["NAME"]),
				"DESCRIPTION" => $task["DETAIL_TEXT"],
				"RESPONSIBLE_ID" => $task["PROPERTY_TaskAssignedTo"],
				"CREATED_BY" => $task["CREATED_BY"],
				"CREATED_DATE" => $task["DATE_CREATE"],
				"CHANGED_BY" => $task["MODIFIED_BY"],
				"CHANGED_DATE" => $task["TIMESTAMP_X"],
				"XML_ID" => $task["XML_ID"],
				"PRIORITY" => 0,
				"STATUS" => 1,
				"DURATION_PLAN" => intval($task["PROPERTY_TaskSize"]),
				"DURATION_FACT" => intval($task["PROPERTY_TaskSizeReal"]),
				"DURATION_TYPE" => "hours",
				"TAGS" => $task["TAGS"]
			);

			if ($task["DATE_ACTIVE_TO"])
			{
				$newTask["DEADLINE"] = $task["DATE_ACTIVE_TO"];
			}

			if (is_array($task["PROPERTY_TaskPriority"]) && sizeof($task["PROPERTY_TaskPriority"]) > 0)
			{
				$rsProperty = CIBlockProperty::GetByID(key($task["PROPERTY_TaskPriority"]));
				if ($arProperty = $rsProperty->Fetch())
				{
					if (in_array($arProperty["XML_ID"], array(1, 2, 3)))
					{
						$newTask["PRIORITY"] = $arProperty["XML_ID"] - 1;
					}
				}
			}

			if (isset($task["DocumentState"]["STATE_NAME"]) && array_key_exists($task["DocumentState"]["STATE_NAME"], $arStatusMappings))
			{
				$newTask["STATUS"] = $arStatusMappings[$task["DocumentState"]["STATE_NAME"]];
			}
			elseif ($newTask["STATUS"] == 1 && $newTask["RESPONSIBLE_ID"] == $newTask["CREATED_BY"])
			{
				$newTask["STATUS"] = 2;
			}
			elseif ($newTask["STATUS"] == 4 && $newTask["RESPONSIBLE_ID"] == $newTask["CREATED_BY"])
			{
				$newTask["STATUS"] = 5;
			}

			if (is_array($task["PROPERTY_TaskFiles"]) && sizeof($task["PROPERTY_TaskFiles"]) > 0)
			{
				$newTask["FILES"] = $task["PROPERTY_TaskFiles"];
			}

			if (is_array($task["PROPERTY_TaskTrackers"]) && sizeof($task["PROPERTY_TaskTrackers"]) > 0)
			{
				$newTask["AUDITORS"] = $task["PROPERTY_TaskTrackers"];
			}

			if (intval($task["PROPERTY_FORUM_TOPIC_ID"]) > 0)
			{
				if (CModule::IncludeModule("forum"))
				{
					$arTopic = CForumTopic::GetByID(intval($task["PROPERTY_FORUM_TOPIC_ID"]));
					if ($arTopic)
					{
						$newTask["FORUM_TOPIC_ID"] = intval($task["PROPERTY_FORUM_TOPIC_ID"]);
					}
				}
			}

			if ($task["TaskType"] == "group")
			{
				$newTask["GROUP_ID"] = $task["OwnerId"];
			}

			$rsTaskSections  = CIBlockElement::GetElementGroups($task["ID"]);
			$arSections = array();
			while ($section = $rsTaskSections->Fetch())
			{
				if (($task["TaskType"] == "group" && $section["ID"] != $task["ROOT_SECTION_ID"]) || ($task["TaskType"] != "group" && $section["XML_ID"] != "users_tasks"))
				{
					$arSections[] = $section["NAME"];
				}
			}

			$sSections = implode(",", $arSections);
			$newTask["TAGS"] = $newTask["TAGS"].(strlen($task["TAGS"]) > 0 && strlen($sSections) > 0 ? "," : "").$sSections;

			$oTask = new CTasks();

			$updateID = false;
			if ($newTask["XML_ID"])
			{
				$strSql = "SELECT * FROM b_tasks WHERE XML_ID = '".$newTask["XML_ID"]."'";
				$rsTask = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if ($arTask = $rsTask->Fetch())
				{
					$updateID = $arTask["ID"];
				}
			}
			else
			{
				$strSql = "SELECT * FROM b_tasks WHERE CREATED_BY = '".$newTask["CREATED_BY"]."' AND CREATED_DATE = '".$newTask["CREATED_DATE"]."'";
				$rsTask = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				while ($arTask = $rsTask->Fetch())
				{
					$updateID = $arTask["ID"];
				}
			}

			if ($updateID)
			{
				$oTask->Update($updateID, $newTask);
			}
			else
			{
				$oTask->Add($newTask);
			}

			if (sizeof($oTask->GetErrors()) == 0)
			{
				$CNT++;
			}

			if (microtime(true) - $start_time > 1)
			{
				header("Location: ?ID=".$ID."&CNT=".$CNT."&IBN=".$i);
			}
		}
		$ID = 0;
	}
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");

	CAdminMessage::ShowNote(str_replace("#TASKS_NUM#", $CNT, GetMessage("TASKS_ADDED")));

	echo "<form action=\"/bitrix/admin/module_admin.php\"><input type=\"hidden\" name=\"lang\" value=\"".LANG."\" /><input type=\"submit\" value=\"".GetMessage("MOD_BACK")."\" />";
}
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
?>