<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if(!defined("BX_INTASKS_FROM_COMPONENT") || BX_INTASKS_FROM_COMPONENT!==true)die();

CModule::IncludeModule("socialnetwork");

CComponentUtil::__IncludeLang(BX_PERSONAL_ROOT."/components/bitrix/intranet.tasks", "action.php");

function __InTaskDeleteTask($delTaskId, $iblockId, $taskType, $ownerId, $arParams)
{
	$delTaskId = IntVal($delTaskId);
	$iblockId = IntVal($iblockId);
	$ownerId = IntVal($ownerId);
	if ($delTaskId <= 0 || $iblockId <= 0 || $ownerId <= 0)
		return "";

	$errorMessage = "";

	if (StrLen($errorMessage) <= 0)
	{
		$sectionId = 0;
		$dbElementSections = CIBlockElement::GetElementGroups($delTaskId);
		while ($arElementSection = $dbElementSections->Fetch())
		{
			if ($arElementSection["IBLOCK_ID"] == $iblockId)
			{
				$sectionId = $arElementSection["ID"];
				break;
			}
		}

		if ($sectionId <= 0)
			$errorMessage .= GetMessage("INTL_TASK_NOT_FOUND").".";
	}

	if (StrLen($errorMessage) <= 0)
	{
		$dbSectionsChain = CIBlockSection::GetNavChain($iblockId, $sectionId);
		if ($arSect = $dbSectionsChain->GetNext())
		{
			if ($taskType == 'group' && $arSect["XML_ID"] != $ownerId)
				$errorMessage .= GetMessage("INTL_TASK_INTERNAL_ERROR")." GTK003".". ";
			elseif ($taskType != 'group' && $arSect["XML_ID"] != "users_tasks")
				$errorMessage .= GetMessage("INTL_TASK_INTERNAL_ERROR")." GTK004".". ";
		}
		else
		{
			$errorMessage .= GetMessage("INTL_FOLDER_NOT_FOUND").". ";
		}
	}

	if (StrLen($errorMessage) <= 0)
	{
		if (!CIBlockElement::Delete($delTaskId))
			$errorMessage .= GetMessage("INTL_ERROR_DELETE_TASK").". ";

		CAgent::RemoveAgent("CIntranetTasks::SendRemindEventAgent(".$iblockId.", ".$delTaskId.", \"".$arParams[($taskType == "user" ? "PATH_TO_USER_TASKS_TASK" : "PATH_TO_GROUP_TASKS_TASK")]."\");", "intranet");
	}

	return $errorMessage;
}

function __InTaskDeleteView($delViewId, $iblockId, $taskType, $ownerId)
{
	$delViewId = IntVal($delViewId);
	$iblockId = IntVal($iblockId);
	$ownerId = IntVal($ownerId);
	if ($delViewId <= 0 || $iblockId <= 0 || $ownerId <= 0)
		return "";

	$errorMessage = "";

	$userSettingsCategory = "IntranetTasks";
	$userSettingsNamePart = "Settings_";

	$arUserSettings = CUserOptions::GetOption($userSettingsCategory, $userSettingsNamePart.$delViewId, false, $GLOBALS["USER"]->GetID());
	if (!$arUserSettings)
		$errorMessage .= GetMessage("INTL_VIEW_NOT_FOUND").".";

	if (StrLen($errorMessage) <= 0)
	{
		if ($arUserSettings["IBLOCK_ID"] != $iblockId || $arUserSettings["TASK_TYPE"] != $taskType || $arUserSettings["OWNER_ID"] != $ownerId)
			$errorMessage .= GetMessage("INTL_WRONG_VIEW").".";
	}

	if (StrLen($errorMessage) <= 0)
	{
		if ($arUserSettings["COMMON"] != "N")
		{
			$canModifyCommon = (
				$taskType == 'user' && CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), SONET_ENTITY_USER, $ownerId, "tasks", 'modify_common_views') 
				|| $taskType == 'group' && CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), SONET_ENTITY_GROUP, $ownerId, "tasks", 'modify_common_views')
			);
			if (!$canModifyCommon)
				$errorMessage .= GetMessage("INTL_NO_VIEW_PERMS").".";
		}
	}

	if (StrLen($errorMessage) <= 0)
	{
		CUserOptions::DeleteOption($userSettingsCategory, $userSettingsNamePart.$delViewId, $arUserSettings["COMMON"] == "Y" ? true : false, $GLOBALS["USER"]->GetID());
	}

	return $errorMessage;
}

function __InTaskTaskConfirm($confirmTaskId, $iblockId, $taskType, $ownerId, $arTaskStatus, $arParams)
{
	$errorMessage = "";

	$confirmTaskId = IntVal($confirmTaskId);
	if ($confirmTaskId <= 0)
		return "";

	$arTask = __InTaskGetTask($confirmTaskId, $iblockId, $taskType, $ownerId);
	if (!$arTask)
		$errorMessage .= GetMessage("INTL_TASK_NOT_FOUND").".";

	if (StrLen($errorMessage) <= 0)
	{
		if (($arTask["PROPS"]["TASKASSIGNEDTO"]["VALUE"] != $GLOBALS["USER"]->GetID()) || (StrToUpper($arTask["PROPS"]["TASKSTATUS"]["VALUE_XML_ID"]) != "NOTACCEPTED"))
			$errorMessage .= GetMessage("INTL_CAN_NOT_APPLY").".";
	}

	if (StrLen($arResult["ErrorMessage"]) <= 0)
	{
		CIBlockElement::SetPropertyValueCode($confirmTaskId, $arTask["PROPS"]["TASKSTATUS"]["ID"], $arTaskStatus["NOTSTARTED"]["ID"]);

		$arMessageFields = array(
			"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"MESSAGE_TYPE" => SONET_MESSAGE_SYSTEM,
			"FROM_USER_ID" => $GLOBALS["USER"]->GetID(),
			"TO_USER_ID" => $arTask["FIELDS"]["CREATED_BY"],
		);

		if ($arMessageFields["FROM_USER_ID"] != $arMessageFields["TO_USER_ID"])
		{
			$path2view = ($GLOBALS["APPLICATION"]->IsHTTPS() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("owner_id" => $arTask["PROPS"]["TASKASSIGNEDTO"]["VALUE"], "task_id" => $confirmTaskId, "action" => "view"));

			$arMessageFields["MESSAGE"] = str_replace(
				array("#URL_VIEW#", "#NAME#"),
				array($path2view, $arTask["FIELDS"]["NAME"]),
				GetMessage("INTL_APPLY_MESSAGE")
			);
			CSocNetMessages::Add($arMessageFields);
		}
	}

	return $errorMessage;
}

function __InTaskTaskReject($taskRejectId, $iblockId, $taskType, $ownerId, $arParams)
{
	$errorMessage = "";

	$taskRejectId = IntVal($taskRejectId);
	if ($taskRejectId <= 0)
		return "";

	$arTask = __InTaskGetTask($taskRejectId, $iblockId, $taskType, $ownerId);
	if (!$arTask)
		$errorMessage .= GetMessage("INTL_TASK_NOT_FOUND").".";

	if (StrLen($errorMessage) <= 0)
	{
		if (($arTask["PROPS"]["TASKASSIGNEDTO"]["VALUE"] != $GLOBALS["USER"]->GetID()) || (StrToUpper($arTask["PROPS"]["TASKSTATUS"]["VALUE_XML_ID"]) != "NOTACCEPTED"))
			$errorMessage .= GetMessage("INTL_CAN_NOT_REJECT").".";
	}

	if (StrLen($errorMessage) <= 0)
	{
		if ($arTask["PROPS"]["TASKASSIGNEDTO"]["VALUE"] == $arTask["FIELDS"]["CREATED_BY"])
			$errorMessage .= GetMessage("INTL_CAN_NOT_REJECT_OWN").".";
	}

	if (StrLen($arResult["ErrorMessage"]) <= 0)
	{
		CIBlockElement::SetPropertyValueCode($taskRejectId, $arTask["PROPS"]["TASKASSIGNEDTO"]["ID"], $arTask["FIELDS"]["CREATED_BY"]);

		$arMessageFields = array(
			"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"MESSAGE_TYPE" => SONET_MESSAGE_SYSTEM,
			"FROM_USER_ID" => $GLOBALS["USER"]->GetID(),
			"TO_USER_ID" => $arTask["FIELDS"]["CREATED_BY"],
		);

		if ($arMessageFields["FROM_USER_ID"] != $arMessageFields["TO_USER_ID"])
		{
			$path2view = ($GLOBALS["APPLICATION"]->IsHTTPS() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("owner_id" => $arTask["PROPS"]["TASKASSIGNEDTO"]["VALUE"], "task_id" => $taskRejectId, "action" => "view"));

			$arMessageFields["MESSAGE"] = str_replace(
				array("#URL_VIEW#", "#NAME#"),
				array($path2view, $arTask["FIELDS"]["NAME"]),
				GetMessage("INTL_REJECT_MESSAGE")
			);
			CSocNetMessages::Add($arMessageFields);
		}
	}

	return $errorMessage;
}

function __InTaskTaskComplete($taskCompleteId, $iblockId, $taskType, $ownerId, $arTaskStatus, $arParams)
{
	$errorMessage = "";

	$taskCompleteId = IntVal($taskCompleteId);
	if ($taskCompleteId <= 0)
		return "";

	$arTask = __InTaskGetTask($taskCompleteId, $iblockId, $taskType, $ownerId);
	if (!$arTask)
		$errorMessage .= GetMessage("INTL_TASK_NOT_FOUND").".";

	if (StrLen($errorMessage) <= 0)
	{
		if ($arTask["PROPS"]["TASKASSIGNEDTO"]["VALUE"] != $GLOBALS["USER"]->GetID())
			$errorMessage .= GetMessage("INTL_CAN_NOT_FINISH").".";
	}

	if (StrLen($arResult["ErrorMessage"]) <= 0)
	{
		$obIB = new CIBlockElement();
		$obIB->SetPropertyValueCode($taskCompleteId, $arTask["PROPS"]["TASKSTATUS"]["ID"], $arTaskStatus["COMPLETED"]["ID"]);
		$obIB->SetPropertyValueCode($taskCompleteId, $arTask["PROPS"]["TASKCOMPLETE"]["ID"], array(100));
		$obIB->SetPropertyValueCode($taskCompleteId, $arTask["PROPS"]["TASKFINISH"]["ID"], array(Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME))));
		
		// added by sigurd
		CIntranetUtils::UpdateOWSVersion($iblockId, $taskCompleteId);
		$obIB->Update($taskCompleteId, array('TIMESTAMP_X' => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME))));

		$arMessageFields = array(
			"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"MESSAGE_TYPE" => SONET_MESSAGE_SYSTEM,
			"FROM_USER_ID" => $GLOBALS["USER"]->GetID(),
			"TO_USER_ID" => $arTask["FIELDS"]["CREATED_BY"],
		);

		if ($arMessageFields["FROM_USER_ID"] != $arMessageFields["TO_USER_ID"])
		{
			$path2view = ($GLOBALS["APPLICATION"]->IsHTTPS() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("owner_id" => $arTask["PROPS"]["TASKASSIGNEDTO"]["VALUE"], "task_id" => $taskCompleteId, "action" => "view"));

			$arMessageFields["MESSAGE"] = str_replace(
				array("#URL_VIEW#", "#NAME#"),
				array($path2view, $arTask["FIELDS"]["NAME"]),
				GetMessage("INTL_FINISH_MESSAGE")
			);
			CSocNetMessages::Add($arMessageFields);
		}
	}

	return $errorMessage;
}
?>