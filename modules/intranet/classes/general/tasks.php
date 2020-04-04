<?
IncludeModuleLangFile(__FILE__);

if (!CModule::IncludeModule("bizproc"))
	return;
if (!CModule::IncludeModule("iblock"))
	return;
if (!CModule::IncludeModule("socialnetwork"))
	return;

class CIntranetTasks
{
	function SendRemindEventAgent($iblockId, $taskId, $pathTemplate)
	{
		if (!CModule::IncludeModule("socialnetwork") && !CModule::IncludeModule("iblock"))
			return;

		$iblockId = IntVal($iblockId);
		$taskId = IntVal($taskId);

		if (!isset($GLOBALS["USER"]) || !is_object($GLOBALS["USER"]))
		{
			$bTmpUser = True;
			$GLOBALS["USER"] = new CUser;
		}

		$arTasksCustomProps = array();
		$dbTasksCustomProps = CIBlockProperty::GetList(
			array("sort" => "asc", "name" => "asc"),
			array("ACTIVE" => "Y", "IBLOCK_ID" => $iblockId, "CHECK_PERMISSIONS" => "N")
		);
		while ($arTasksCustomProp = $dbTasksCustomProps->Fetch())
		{
			$ind = ((StrLen($arTasksCustomProp["CODE"]) > 0) ? $arTasksCustomProp["CODE"] : $arTasksCustomProp["ID"]);
			$arTasksCustomProps[StrToUpper($ind)] = $arTasksCustomProp;
		}

		$dbTasksList = CIBlockElement::GetList(
			array(),
			array(
				"IBLOCK_ID" => $iblockId,
				"ACTIVE" => "Y",
				"ID" => $taskId,
				"CHECK_PERMISSIONS" => "N",
            ),
			false,
			false,
			array("ID", "NAME", "IBLOCK_ID", "CREATED_BY", "PROPERTY_".$arTasksCustomProps["TASKASSIGNEDTO"]["ID"])
		);
		while ($arTask = $dbTasksList->GetNext())
		{
			$ar = array();
			$dbElementSections = CIBlockElement::GetElementGroups($arTask["ID"]);
			while ($arElementSection = $dbElementSections->Fetch())
			{
				if ($arElementSection["IBLOCK_ID"] == $iblockId)
					$ar[] = $arElementSection["ID"];
			}

			if (Count($ar) <= 0)
				continue;

			$taskType = "";
			$taskOwnerId = 0;

			$dbSectionsChain = CIBlockSection::GetNavChain($iblockId, $ar[0]);
			if ($arSect = $dbSectionsChain->Fetch())
			{
				$taskType = (($arSect["XML_ID"] == "users_tasks") ? "user" : "group");
				$taskOwnerId = IntVal(($taskType == "user") ?  $arTask["PROPERTY_".$arTasksCustomProps["TASKASSIGNEDTO"]["ID"]."_VALUE"] : $arSect["XML_ID"]);
			}

			if (!In_Array($taskType, array("user", "group")) || $taskOwnerId <= 0)
				continue;

			$path2view = ($GLOBALS["APPLICATION"]->IsHTTPS() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].CComponentEngine::MakePathFromTemplate($pathTemplate, array("owner_id" => $taskOwnerId, "task_id" => $arTask["ID"], "action" => "view"));

			$arMessageFields = array(
				"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"MESSAGE_TYPE" => SONET_MESSAGE_SYSTEM,
				"FROM_USER_ID" => $arTask["CREATED_BY"],
				"TO_USER_ID" => $arTask["PROPERTY_".$arTasksCustomProps["TASKASSIGNEDTO"]["ID"]."_VALUE"],
				"MESSAGE" => str_replace(
					array("#URL_VIEW#", "#NAME#"),
					array($path2view, $arTask["NAME"]),
					GetMessage("INTE_REMIND_TASK_MESSAGE")
				),
			);

			CSocNetMessages::Add($arMessageFields);

			//CIBlockElement::SetPropertyValueCode($arTask["ID"], $arTasksCustomProps["TASKREMIND"]["ID"], false);
		}

		if ($bTmpUser)
			unset($GLOBALS["USER"]);

		//return "CIntranetTasks::SendRemindEventAgent($iblockId, $taskId, \"$pathTemplate\");";
	}

	function SendRemindEventAgentNew($taskId)
	{
		if (!CModule::IncludeModule("socialnetwork") && !CModule::IncludeModule("iblock"))
			return;

		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			return;

		$taskId = IntVal($taskId);

		if (!isset($GLOBALS["USER"]) || !is_object($GLOBALS["USER"]))
		{
			$bTmpUser = True;
			$GLOBALS["USER"] = new CUser;
		}

		$arTask = CIntranetTasks::GetById($taskId);
		if (!$arTask)
			return;

		if ($arTask["TaskType"] == "user")
		{
			$path2view = str_replace(
				array("#USER_ID#", "#TASK_ID#"),
				array($arTask["OwnerId"], $taskId),
				COption::GetOptionString("intranet", "path_task_user_entry", "/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/")
			);
		}
		else
		{
			$path2view = str_replace(
				array("#GROUP_ID#", "#TASK_ID#"),
				array($arTask["OwnerId"], $taskId),
				COption::GetOptionString("intranet", "path_task_group_entry", "/workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/")
			);
		}

		$path2view = ($GLOBALS["APPLICATION"]->IsHTTPS() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$path2view;

		$arMessageFields = array(
			"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"MESSAGE_TYPE" => SONET_MESSAGE_SYSTEM,
			"FROM_USER_ID" => $arTask["CREATED_BY"],
			"TO_USER_ID" => $arTask["PROPERTY_TaskAssignedTo"],
			"MESSAGE" => str_replace(
				array("#URL_VIEW#", "#NAME#"),
				array($path2view, $arTask["NAME"]),
				GetMessage("INTE_REMIND_TASK_MESSAGE")
			),
		);

		CSocNetMessages::Add($arMessageFields);

		if ($bTmpUser)
			unset($GLOBALS["USER"]);

		//return "CIntranetTasks::SendRemindEventAgent($iblockId, $taskId, \"$pathTemplate\");";
	}

	function AddForumLog($taskId, $taskName, $arFields, &$arError)
	{
		$arError = array();

		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			return;

		$taskId = intval($taskId);
		if ($taskId <= 0)
			return;

		$db = CIBlockElement::GetProperty($iblockId, $taskId, "", "", array("CODE" => "FORUM_TOPIC_ID"));
		$ar = $db->Fetch();

		$forumTopicId = intval($ar["VALUE"]);

		if ($forumTopicId <= 0)
		{
			$arFields = array(
				"TITLE" => $taskName,
				"FORUM_ID" => $arFields["FORUM_ID"],
				"USER_START_ID"	=> $arFields["USER_ID"],
				"USER_START_NAME" => $arFields["USER_NAME"],
				"LAST_POSTER_NAME" => $arFields["USER_NAME"],
				"APPROVED" => "Y"
			);
			$forumTopicId = CForumTopic::Add($arFields);

			CIBlockElement::SetPropertyValues($taskId, $iblockId, $forumTopicId, "FORUM_TOPIC_ID");
		}

		$arFields = array(
			"POST_MESSAGE" => $arFields["POST_MESSAGE"],
			"AUTHOR_ID" => $arFields["USER_ID"],
			"AUTHOR_NAME" => $arFields["USER_NAME"],
			"FORUM_ID" => $arFields["FORUM_ID"],
			"TOPIC_ID" => $forumTopicId,
			"APPROVED" => "Y",
			"NEW_TOPIC" => "Y",
			"PARAM1" => "IB",
			"PARAM2" => $taskId
		);
		CForumMessage::Add($arFields, false, array("SKIP_INDEXING" => "Y", "SKIP_STATISTIC" => "N"));
	}

	function Add($arFields, &$arError)
	{
		$arError = array();
		try
		{
			return CIntranetTasksDocument::CreateDocument($arFields);
		}
		catch (Exception $e)
		{
			$arError[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}

		return 0;
	}

	function Update($id, $arFields, &$arError)
	{
		$arError = array();
		try
		{
			CIntranetTasksDocument::UpdateDocument($id, $arFields);
		}
		catch (Exception $e)
		{
			$arError[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}
		return (count($arError) <= 0);
	}

	function Delete($id, &$arError)
	{
		$arError = array();

		try
		{
			CIntranetTasksDocument::DeleteDocument($id);
		}
		catch (Exception $e)
		{
			$arError[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}

		if (count($arError) <= 0)
			CAgent::RemoveAgent("CIntranetTasks::SendRemindEventAgentNew(".$id.");", "intranet");

		return (count($arError) <= 0);
	}

	function GetList($arOrder = array("SORT" => "ASC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields=array())
	{
		global $USER;

		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			return false;

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

//$hFileTmp = fopen($_SERVER["DOCUMENT_ROOT"]."/+++++++.+++", "a");  // DUMPING
//fwrite($hFileTmp, "GetList:\n".print_r(array($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields), true)."\n");
//fclose($hFileTmp);

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
			$v = CIntranetTasks::PrepareSectionForPrint($arResult["IBLOCK_SECTION_ID"]);
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

			if (!array_key_exists($taskType."_".$ownerId, $arCache))
			{
				$arCurrentUserGroups = array();
				if ($taskType == "group")
				{
					$arCurrentUserGroups[] = SONET_ROLES_ALL;

					if ($GLOBALS["USER"]->IsAuthorized())
						$arCurrentUserGroups[] = SONET_ROLES_AUTHORIZED;

					$r = CSocNetUserToGroup::GetUserRole($USER->GetID(), $ownerId);
					if (strlen($r) > 0)
						$arCurrentUserGroups[] = $r;
				}
				else
				{
					$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_ALL;

					if ($GLOBALS["USER"]->IsAuthorized())
						$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_AUTHORIZED;

					if (CSocNetUserRelations::IsFriends($USER->GetID(), $ownerId))
						$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_FRIENDS;
					elseif (CSocNetUserRelations::IsFriends2($USER->GetID(), $ownerId))
						$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_FRIENDS2;
				}
				$arCache[$taskType."_".$ownerId] = $arCurrentUserGroups;
			}

			$arCurrentUserGroups = $arCache[$taskType."_".$ownerId];

			if ($USER->GetID() == $arResult["CREATED_BY"])
				$arCurrentUserGroups[] = "author";
			if ($USER->GetID() == $arResult["PROPERTY_TaskAssignedTo"])
				$arCurrentUserGroups[] = "responsible";
			if (is_array($arResult["PROPERTY_TaskTrackers"]) && in_array($USER->GetID(), $arResult["PROPERTY_TaskTrackers"]))
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
				$arResult["DocumentState"]["AllowableEvents"] = CBPDocument::GetAllowableEvents($GLOBALS["USER"]->GetID(), $arCurrentUserGroups, $arDocumentStates[$k]);
			}

			$arResult["TaskType"] = $taskType;
			$arResult["OwnerId"] = $ownerId;

			$arResult["CurrentUserCanViewTask"] = CIntranetTasksDocument::CanUserOperateDocument(
				INTASK_DOCUMENT_OPERATION_READ_DOCUMENT,
				$GLOBALS["USER"]->GetID(),
				$arResult["ID"],
				array("TaskType" => $taskType, "OwnerId" => $ownerId, "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
			);
			$arResult["CurrentUserCanCommentTask"] = CIntranetTasksDocument::CanUserOperateDocument(
				INTASK_DOCUMENT_OPERATION_COMMENT_DOCUMENT,
				$GLOBALS["USER"]->GetID(),
				$arResult["ID"],
				array("TaskType" => $taskType, "OwnerId" => $ownerId, "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
			);
			$arResult["CurrentUserCanDeleteTask"] = CIntranetTasksDocument::CanUserOperateDocument(
				INTASK_DOCUMENT_OPERATION_DELETE_DOCUMENT,
				$GLOBALS["USER"]->GetID(),
				$arResult["ID"],
				array("TaskType" => $taskType, "OwnerId" => $ownerId, "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
			);
			$arResult["CurrentUserCanWriteTask"] = CIntranetTasksDocument::CanUserOperateDocument(
				INTASK_DOCUMENT_OPERATION_WRITE_DOCUMENT,
				$GLOBALS["USER"]->GetID(),
				$arResult["ID"],
				array("TaskType" => $taskType, "OwnerId" => $ownerId, "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
			);

//$hFileTmp = fopen($_SERVER["DOCUMENT_ROOT"]."/+++++++.+++", "a");  // DUMPING
//fwrite($hFileTmp, "arResult:\n".print_r($arResult, true)."\n");
//fclose($hFileTmp);

			$arResultList[] = $arResult;
		}

		$dbTasksList = new CDBResult();
		$dbTasksList->InitFromArray($arResultList);

		return $dbTasksList;
	}

	function GetListEx($arOrder = array("SORT" => "ASC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields=array(), $nameTemplate = false, $bShowLogin = true, $bShowTooltip = false, $arTooltipParams = false)
	{
		global $USER;

		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			return false;

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

//$hFileTmp = fopen($_SERVER["DOCUMENT_ROOT"]."/+++++++.+++", "a");  // DUMPING
//fwrite($hFileTmp, "GetList:\n".print_r(array($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields), true)."\n");
//fclose($hFileTmp);

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
					$arResult[$fieldKey."_PRINTABLE"] = CIntranetTasks::PrepareUserForPrint($fieldValue, $nameTemplate, $bShowLogin, $bShowTooltip, $arTooltipParams);
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
						$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = CIntranetTasks::PrepareUserForPrint($propertyValue["VALUE"], $nameTemplate, $bShowLogin, $bShowTooltip, $arTooltipParams);
					else
						$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = $propertyValue["VALUE"];
				}
				elseif ($arField["PROPERTY_TYPE"] == "G")
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
			$v = CIntranetTasks::PrepareSectionForPrint($arResult["IBLOCK_SECTION_ID"]);
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

			if (!array_key_exists($taskType."_".$ownerId, $arCache))
			{
				$arCurrentUserGroups = array();
				if ($taskType == "group")
				{
					$arCurrentUserGroups[] = SONET_ROLES_ALL;

					if ($GLOBALS["USER"]->IsAuthorized())
						$arCurrentUserGroups[] = SONET_ROLES_AUTHORIZED;

					$r = CSocNetUserToGroup::GetUserRole($USER->GetID(), $ownerId);
					if (strlen($r) > 0)
						$arCurrentUserGroups[] = $r;
				}
				else
				{
					$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_ALL;

					if ($GLOBALS["USER"]->IsAuthorized())
						$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_AUTHORIZED;

					if (CSocNetUserRelations::IsFriends($USER->GetID(), $ownerId))
						$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_FRIENDS;
					elseif (CSocNetUserRelations::IsFriends2($USER->GetID(), $ownerId))
						$arCurrentUserGroups[] = SONET_RELATIONS_TYPE_FRIENDS2;
				}
				$arCache[$taskType."_".$ownerId] = $arCurrentUserGroups;
			}

			$arCurrentUserGroups = $arCache[$taskType."_".$ownerId];

			if ($USER->GetID() == $arResult["CREATED_BY"])
				$arCurrentUserGroups[] = "author";
			if ($USER->GetID() == $arResult["PROPERTY_TaskAssignedTo"])
				$arCurrentUserGroups[] = "responsible";
			if (is_array($arResult["PROPERTY_TaskTrackers"]) && in_array($USER->GetID(), $arResult["PROPERTY_TaskTrackers"]))
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
				$arResult["DocumentState"]["AllowableEvents"] = CBPDocument::GetAllowableEvents($GLOBALS["USER"]->GetID(), $arCurrentUserGroups, $arDocumentStates[$k]);
			}

			$arResult["TaskType"] = $taskType;
			$arResult["OwnerId"] = $ownerId;

			$arResult["CurrentUserCanViewTask"] = CIntranetTasksDocument::CanUserOperateDocument(
				INTASK_DOCUMENT_OPERATION_READ_DOCUMENT,
				$GLOBALS["USER"]->GetID(),
				$arResult["ID"],
				array("TaskType" => $taskType, "OwnerId" => $ownerId, "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
			);
			$arResult["CurrentUserCanCommentTask"] = CIntranetTasksDocument::CanUserOperateDocument(
				INTASK_DOCUMENT_OPERATION_COMMENT_DOCUMENT,
				$GLOBALS["USER"]->GetID(),
				$arResult["ID"],
				array("TaskType" => $taskType, "OwnerId" => $ownerId, "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
			);
			$arResult["CurrentUserCanDeleteTask"] = CIntranetTasksDocument::CanUserOperateDocument(
				INTASK_DOCUMENT_OPERATION_DELETE_DOCUMENT,
				$GLOBALS["USER"]->GetID(),
				$arResult["ID"],
				array("TaskType" => $taskType, "OwnerId" => $ownerId, "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
			);
			$arResult["CurrentUserCanWriteTask"] = CIntranetTasksDocument::CanUserOperateDocument(
				INTASK_DOCUMENT_OPERATION_WRITE_DOCUMENT,
				$GLOBALS["USER"]->GetID(),
				$arResult["ID"],
				array("TaskType" => $taskType, "OwnerId" => $ownerId, "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates)
			);

//$hFileTmp = fopen($_SERVER["DOCUMENT_ROOT"]."/+++++++.+++", "a");  // DUMPING
//fwrite($hFileTmp, "arResult:\n".print_r($arResult, true)."\n");
//fclose($hFileTmp);

			$arResultList[] = $arResult;
		}

		$dbTasksList1 = new CDBResult();
		$dbTasksList1->InitFromArray($arResultList);

		return array($dbTasksList1, $dbTasksList);
	}

	function PrepareUserForPrint($value, $nameTemplate = false, $bShowLogin = true, $bShowTooltip = false, $arTooltipParams = false)
	{
		static $cnt = 0;

		if ($nameTemplate === false)
			$nameTemplate = CSite::GetNameFormat();

		if ($bShowTooltip && ($arTooltipParams === false || !is_array($arTooltipParams)))
		{
			$arTooltipParams = array(
				"SHOW_FIELDS_TOOLTIP" => array("EMAIL", "PERSONAL_MOBILE", "WORK_PHONE", "PERSONAL_ICQ",	"PERSONAL_PHOTO", "PERSONAL_CITY", "WORK_COMPANY", "WORK_POSITION"),
				"USER_PROPERTY_TOOLTIP" => array("UF_DEPARTMENT", "UF_PHONE_INNER"),
				"DATE_TIME_FORMAT" => $GLOBALS["DB"]->DateFormatToPHP(CSite::GetDateFormat("FULL")),
				"THUMBNAIL_LIST_SIZE" => 30,
				"SHOW_YEAR" => "M",
				"CACHE_TYPE" => "A",
				"CACHE_TIME" => "3600",
			);
		}
		elseif($bShowTooltip)
		{
			if (!array_key_exists("SHOW_FIELDS_TOOLTIP", $arTooltipParams))
				$arTooltipParams["SHOW_FIELDS_TOOLTIP"] = array("EMAIL", "PERSONAL_MOBILE", "WORK_PHONE", "PERSONAL_ICQ",	"PERSONAL_PHOTO", "PERSONAL_CITY", "WORK_COMPANY", "WORK_POSITION");
			if (!array_key_exists("USER_PROPERTY_TOOLTIP", $arTooltipParams))
				$arTooltipParams["USER_PROPERTY_TOOLTIP"] = array("UF_DEPARTMENT", "UF_PHONE_INNER");
			if (!array_key_exists("DATE_TIME_FORMAT", $arTooltipParams))
				$arTooltipParams["DATE_TIME_FORMAT"] = $GLOBALS["DB"]->DateFormatToPHP(CSite::GetDateFormat("FULL"));
			if (!array_key_exists("THUMBNAIL_LIST_SIZE", $arTooltipParams))
				$arTooltipParams["THUMBNAIL_LIST_SIZE"] = 30;
			if (!array_key_exists("SHOW_YEAR", $arTooltipParams))
				$arTooltipParams["SHOW_YEAR"] = "M";
			if (!array_key_exists("CACHE_TYPE", $arTooltipParams))
				$arTooltipParams["CACHE_TYPE"] = "A";
			if (!array_key_exists("CACHE_TIME", $arTooltipParams))
				$arTooltipParams["CACHE_TIME"] = "3600";
			if (!array_key_exists("PATH_TO_SONET_MESSAGES_CHAT", $arTooltipParams))
				$arTooltipParams["PATH_TO_SONET_MESSAGES_CHAT"] = "/company/personal/messages/chat/#user_id#/";
			if (!array_key_exists("PATH_TO_SONET_USER_PROFILE", $arTooltipParams))
				$arTooltipParams["PATH_TO_SONET_USER_PROFILE"] = "/company/personal/user/#user_id#/";
			if (!array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arTooltipParams))
				$arTooltipParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
			if (!array_key_exists("INLINE", $arTooltipParams))
				$arTooltipParams["INLINE"] = "N";
		}

		$arReturn = array();

		$valueTmp = $value;
		if (!is_array($valueTmp))
			$valueTmp = array($valueTmp);

		foreach ($valueTmp as $val)
		{
			$dbUser = CUser::GetByID($val);
			if ($arUser = $dbUser->GetNext())
			{
				$name = trim($arUser["~NAME"]);
				$lastName = trim($arUser["~LAST_NAME"]);
				$secondName = trim($arUser["~SECOND_NAME"]);
				$login = trim($arUser["~LOGIN"]);

				$arTmpUser = array(
					"NAME" => $name,
					"LAST_NAME" => $lastName,
					"SECOND_NAME" => $secondName,
					"LOGIN" => $login,
				);

				$nameFormatted = CUser::FormatName($nameTemplate, $arTmpUser, $bUseLogin);

				if ($bShowTooltip)
				{
					$arReturn[] = $GLOBALS["APPLICATION"]->IncludeComponent("bitrix:main.user.link",
						'',
						array(
							"ID" => $val,
							"HTML_ID" => "tasks_".$cnt,
							"NAME" => $name,
							"LAST_NAME" => $lastName,
							"SECOND_NAME" => $secondName,
							"LOGIN" => $login,
							"PATH_TO_SONET_MESSAGES_CHAT" => $arTooltipParams["PATH_TO_SONET_MESSAGES_CHAT"],
							"PATH_TO_SONET_USER_PROFILE" => $arTooltipParams["PATH_TO_SONET_USER_PROFILE"],
							"PATH_TO_CONPANY_DEPARTMENT" => $arTooltipParams["PATH_TO_CONPANY_DEPARTMENT"],
							"PATH_TO_VIDEO_CALL" => $arTooltipParams["PATH_TO_VIDEO_CALL"],
							"USE_THUMBNAIL_LIST" => $arTooltipParams["USE_THUMBNAIL_LIST"],
							"THUMBNAIL_LIST_SIZE" => $arTooltipParams["THUMBNAIL_LIST_SIZE"],
							"DATE_TIME_FORMAT" => $arTooltipParams["DATE_TIME_FORMAT"],
							"SHOW_YEAR" => $arTooltipParams["SHOW_YEAR"],
							"CACHE_TYPE" => $arTooltipParams["CACHE_TYPE"],
							"CACHE_TIME" => $arTooltipParams["CACHE_TIME"],
							"INLINE" => $arTooltipParams["INLINE"],
							"NAME_TEMPLATE" => $nameTemplate,
							"SHOW_LOGIN" => $bShowLogin,
							"DO_RETURN" => "Y",
							"DUPLICATE_STYLES" => "Y",
						),
						false
						, array("HIDE_ICONS" => "Y")
					);
					$cnt++;
				}
				else
					$arReturn[] = $nameFormatted;
			}
		}

		return (is_array($value) ? $arReturn : ((count($arReturn) > 0) ? $arReturn[0] : ""));
	}

	function PrepareSectionForPrint($value, $iblockId = 0)
	{
		if ($iblockId <= 0)
			$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			return false;

		$arReturn = array();

		$valueTmp = $value;
		if (!is_array($valueTmp))
			$valueTmp = array($valueTmp);

		foreach ($valueTmp as $val)
		{
			$ar = array();

			$dbSectionsList = CIBlockSection::GetNavChain($iblockId, $val);
			while ($arSection = $dbSectionsList->GetNext())
				$ar[$arSection["ID"]] = array("NAME" => $arSection["NAME"], "XML_ID" => $arSection["XML_ID"]);

			$arReturn[] = $ar;
		}

		return (is_array($value) ? $arReturn : ((count($arReturn) > 0) ? $arReturn[0] : array()));
	}

	function GetById($id, $nameTemplate = false, $bShowLogin = true, $bShowTooltip = false, $arTooltipParams = false)
	{
		return CIntranetTasksDocument::GetDocument($id, $nameTemplate, $bShowLogin, $bShowTooltip, $arTooltipParams);
	}

	function GetTaskFields($taskType, $ownerId)
	{
		$arFields = CIntranetTasksDocument::GetDocumentFields($taskType."_".$ownerId);

		$arSort = array("ID" => 10, "NAME" => 20, "TIMESTAMP_X" => 1000, "MODIFIED_BY" => 100,
			"DATE_CREATE" => 100, "CREATED_BY" => 100, "ACTIVE_FROM" => 30, "ACTIVE_TO" => 40,
			"IBLOCK_SECTION_ID" => 45, "DETAIL_TEXT" => 50, "PROPERTY_TASKPRIORITY" => 60,
			"PROPERTY_TASKCOMPLETE" => 130, "PROPERTY_TASKASSIGNEDTO" => 10, "PROPERTY_TASKTRACKERS" => 70, "PROPERTY_TASKSIZE" => 140,
			"PROPERTY_TASKSIZEREAL" => 150, "PROPERTY_TASKFINISH" => 130, "PROPERTY_TASKREPORT" => 500,
			"PROPERTY_TASKREMIND" => 170, "PROPERTY_TASKFILES" => 80,
		);

		$arFieldsKeys = array_keys($arFields);
		foreach ($arFieldsKeys as $key)
		{
			$arFields[$key]["NAME"] = $arFields[$key]["Name"];
			$arFields[$key]["FULL_NAME"] = $arFields[$key]["Name"];
			$arFields[$key]["EDITABLE"] = $arFields[$key]["Editable"];
			$arFields[$key]["EDITABLE_AUTHOR"] = $arFields[$key]["Editable"];
			$arFields[$key]["EDITABLE_RESPONSIBLE"] = $arFields[$key]["Editable"];
			$arFields[$key]["IS_REQUIRED"] = $arFields[$key]["Required"];
			$arFields[$key]["FILTERABLE"] = $arFields[$key]["Filterable"];
			$arFields[$key]["PSORT"] = array_key_exists(strtoupper($key), $arSort) ? $arSort[strtoupper($key)] : 1000;
		}

		return $arFields;
	}

	function GetTaskFieldsMap($arTaskFields)
	{
		$arTaskFieldsKeys = array_keys($arTaskFields);
		foreach ($arTaskFieldsKeys as $key)
		{
			$arFields[$key] = $key;

			$key1 = strtoupper($key);
			$arFields[$key1] = $key;

			if (substr($key1, 0, strlen("PROPERTY_")) == "PROPERTY_")
				$arFields[substr($key1, strlen("PROPERTY_"))] = $key;
			if ($key1 == "IBLOCK_SECTION_ID")
				$arFields["IBLOCK_SECTION"] = $key;
		}

		return $arFields;
	}

	function IsTasksFeatureActive($taskType, $ownerId)
	{
		$taskType = strtolower($taskType);
		if (!in_array($taskType, array("group", "user")))
			$taskType = "user";

		$ownerId = intval($ownerId);
		if ($ownerId <= 0)
		{
			$taskType = "user";
			$ownerId = $GLOBALS["USER"]->GetID();
		}
		$ownerId = intval($ownerId);
		if ($ownerId <= 0)
			return false;

		return CSocNetFeatures::IsActiveFeature((($taskType == 'user') ? SONET_ENTITY_USER : SONET_ENTITY_GROUP), $ownerId, "tasks");
	}

	function CanCurrentUserPerformOperation($taskType, $ownerId, $operation)
	{
		global $USER;

		$taskType = StrToLower($taskType);
		if (!in_array($taskType, array("group", "user")))
			$taskType = "user";

		$ownerId = IntVal($ownerId);
		if ($ownerId <= 0)
		{
			$taskType = "user";
			$ownerId = $USER->GetID();
		}
		$ownerId = IntVal($ownerId);
		if ($ownerId <= 0)
			return false;

		if ($USER->IsAuthorized() && CSocNetUser::IsCurrentUserModuleAdmin())
			return true;

		return CSocNetFeaturesPerms::CanPerformOperation(
			$GLOBALS["USER"]->GetID(),
			(($taskType == 'user') ? SONET_ENTITY_USER : SONET_ENTITY_GROUP),
			$ownerId,
			"tasks",
			$operation
		);
	}

	function InitializeIBlock($taskType, $ownerId, $forumId)
	{
		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			return 0;

		$forumId = intval($forumId);

		$arTasksConverted2BP = array();
		$keyTasksConverted2BP = $iblockId."_".$taskType.($taskType == "group" ? "_".$ownerId : "");

		global $CACHE_MANAGER;
		if ($CACHE_MANAGER->Read(2592000, "IntranetTasksConverted2BP"))
		{
			$arTasksConverted2BP = $CACHE_MANAGER->Get("IntranetTasksConverted2BP");
			if (array_key_exists($keyTasksConverted2BP, $arTasksConverted2BP))
				return $arTasksConverted2BP[$keyTasksConverted2BP];
		}

		$globalParentSectionId = 0;

		$dbIBlock = CIBlock::GetList(array(), array("ID" => $iblockId, "ACTIVE" => "Y"));
		if ($arIBlock = $dbIBlock->Fetch())
		{
			$arIBlockProperties = array();

			$dbIBlockProps = CIBlock::GetProperties($iblockId);
			while ($arIBlockProps = $dbIBlockProps->Fetch())
			{
				$ind = ((StrLen($arIBlockProps["CODE"]) > 0) ? $arIBlockProps["CODE"] : $arIBlockProps["ID"]);
				$arIBlockProperties[StrToUpper($ind)] = $arIBlockProps;
			}

			$arTasksProps = array(
				"TASKPRIORITY" => array(
					"NAME" => GetMessage("INTI_TASKPRIORITY"),
					"ACTIVE" => "Y",
					"SORT" => 100,
					"CODE" => "TaskPriority",
					"PROPERTY_TYPE" => "L",
					"USER_TYPE" => false,
					"ROW_COUNT" => 1,
					"COL_COUNT" => 30,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "Y",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "Y",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
					"VALUES" => array(
						array(
							"VALUE" => "   ".GetMessage("INTI_TASKPRIORITY_1"),
							"DEF" => "N",
							"SORT" => 100,
							"XML_ID" => "1"
						),
						array(
							"VALUE" => "  ".GetMessage("INTI_TASKPRIORITY_2"),
							"DEF" => "Y",
							"SORT" => 200,
							"XML_ID" => "2"
						),
						array(
							"VALUE" => " ".GetMessage("INTI_TASKPRIORITY_3"),
							"DEF" => "N",
							"SORT" => 300,
							"XML_ID" => "3"
						),
					),
				),
				"TASKCOMPLETE" => array(
					"NAME" => GetMessage("INTI_TASKCOMPLETE"),
					"ACTIVE" => "Y",
					"SORT" => 300,
					"CODE" => "TaskComplete",
					"PROPERTY_TYPE" => "N",
					"USER_TYPE" => false,
					"ROW_COUNT" => 1,
					"COL_COUNT" => 5,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "Y",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKASSIGNEDTO" => array(
					"NAME" => GetMessage("INTI_TASKASSIGNEDTO"),
					"ACTIVE" => "Y",
					"SORT" => 400,
					"CODE" => "TaskAssignedTo",
					"PROPERTY_TYPE" => "S",
					"USER_TYPE" => "UserID",
					"ROW_COUNT" => 1,
					"COL_COUNT" => 30,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "Y",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "Y",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKTRACKERS" => array(
					"NAME" => GetMessage("INTI_TASKTRACKERS"),
					"ACTIVE" => "Y",
					"SORT" => 400,
					"CODE" => "TaskTrackers",
					"PROPERTY_TYPE" => "S",
					"USER_TYPE" => "UserID",
					"ROW_COUNT" => 1,
					"COL_COUNT" => 30,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "Y",
					"MULTIPLE"  => "Y",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKALERT" => array(
					"NAME" => GetMessage("INTI_TASKALERT"),
					"ACTIVE" => "Y",
					"SORT" => 500,
					"CODE" => "TaskAlert",
					"PROPERTY_TYPE" => "S",
					"USER_TYPE" => false,
					"DEFAULT_VALUE" => "Y",
					"ROW_COUNT" => 1,
					"COL_COUNT" => 30,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "N",
					"SEARCHABLE" => "N",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKSIZE" => array(
					"NAME" => GetMessage("INTI_TASKSIZE"),
					"ACTIVE" => "Y",
					"SORT" => 600,
					"CODE" => "TaskSize",
					"PROPERTY_TYPE" => "N",
					"USER_TYPE" => false,
					"ROW_COUNT" => 1,
					"COL_COUNT" => 5,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "Y",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKSIZEREAL" => array(
					"NAME" => GetMessage("INTI_TASKSIZEREAL"),
					"ACTIVE" => "Y",
					"SORT" => 700,
					"CODE" => "TaskSizeReal",
					"PROPERTY_TYPE" => "N",
					"USER_TYPE" => false,
					"ROW_COUNT" => 1,
					"COL_COUNT" => 5,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "Y",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKFINISH" => array(
					"NAME" => GetMessage("INTI_TASKFINISH"),
					"ACTIVE" => "Y",
					"SORT" => 800,
					"CODE" => "TaskFinish",
					"PROPERTY_TYPE" => "S",
					"USER_TYPE" => "DateTime",
					"ROW_COUNT" => 1,
					"COL_COUNT" => 30,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "Y",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKFILES" => array(
					"NAME" => GetMessage("INTI_TASKFILES"),
					"ACTIVE" => "Y",
					"SORT" => 900,
					"CODE" => "TaskFiles",
					"PROPERTY_TYPE" => "F",
					"USER_TYPE" => false,
					"ROW_COUNT" => 10,
					"COL_COUNT" => 60,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "N",
					"SEARCHABLE" => "N",
					"MULTIPLE"  => "Y",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKREPORT" => array(
					"NAME" => GetMessage("INTI_TASKREPORT"),
					"ACTIVE" => "Y",
					"SORT" => 1000,
					"CODE" => "TaskReport",
					"PROPERTY_TYPE" => "S",
					"USER_TYPE" => false,
					"ROW_COUNT" => 10,
					"COL_COUNT" => 60,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "N",
					"SEARCHABLE" => "N",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKREMIND" => array(
					"NAME" => GetMessage("INTI_TASKREMIND"),
					"ACTIVE" => "Y",
					"SORT" => 300,
					"CODE" => "TaskRemind",
					"PROPERTY_TYPE" => "S",
					"USER_TYPE" => "DateTime",
					"ROW_COUNT" => 1,
					"COL_COUNT" => 30,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "N",
					"SEARCHABLE" => "N",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"VERSION" => array(
					"NAME" => GetMessage("INTI_VERSION"),
					"ACTIVE" => "Y",
					"SORT" => 1100,
					"CODE" => "VERSION",
					"PROPERTY_TYPE" => "N",
					"USER_TYPE" => false,
					"ROW_COUNT" => 1,
					"COL_COUNT" => 10,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "N",
					"SEARCHABLE" => "N",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
				"TASKVERSION" => array(
					"NAME" => GetMessage("INTI_TASKVERSION"),
					"ACTIVE" => "Y",
					"SORT" => 1100,
					"CODE" => "TASKVERSION",
					"PROPERTY_TYPE" => "N",
					"USER_TYPE" => false,
					"ROW_COUNT" => 1,
					"COL_COUNT" => 10,
					"LINK_IBLOCK_ID" => 0,
					"WITH_DESCRIPTION" => "N",
					"FILTRABLE" => "N",
					"SEARCHABLE" => "N",
					"MULTIPLE"  => "N",
					"MULTIPLE_CNT" => 5,
					"IS_REQUIRED" => "N",
					"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
					"LIST_TYPE" => "L",
					"IBLOCK_ID" => $iblockId,
				),
			);

			foreach ($arTasksProps as $propKey => $arProp)
			{
				if (!array_key_exists($propKey, $arIBlockProperties))
				{
					$ibp = new CIBlockProperty;
					$ibp->Add($arProp);
				}
			}


			$dbSectionsList = CIBlockSection::GetList(
				array(),
				array(
					"GLOBAL_ACTIVE" => "Y",
					"XML_ID" => (($taskType == "group") ? $ownerId : "users_tasks"),
					"IBLOCK_ID" => $iblockId,
					"SECTION_ID" => 0
				),
				false
			);
			if ($arSection = $dbSectionsList->GetNext())
				$globalParentSectionId = $arSection["ID"];

			if ($globalParentSectionId <= 0)
			{
				if ($taskType == "group")
				{
					$folderName = "-";
					$folderXmlId = $ownerId;
					if ($arGroup = CSocNetGroup::GetByID($ownerId))
					{
						$folderName = $arGroup["NAME"]." [".$ownerId."]";
						$folderXmlId = $ownerId;
					}
				}
				else
				{
					$folderName = "User Tasks";
					$folderXmlId = "users_tasks";
				}

				$arFields = array(
					"IBLOCK_ID" => $iblockId,
					"IBLOCK_SECTION_ID" => 0,
					"ACTIVE" => "Y",
					"NAME" => $folderName,
					"XML_ID" => $folderXmlId,
				);

				$iblockSection = new CIBlockSection;
				$globalParentSectionId = $iblockSection->Add($arFields, true);

				CIntranetTasks::InstallDefaultViews($taskType, $ownerId);
			}

			$db = CBPWorkflowTemplateLoader::GetList(
				array(),
				array("DOCUMENT_TYPE" => array("intranet", "CIntranetTasksDocument", "x".$iblockId)),
				false,
				false,
				array("ID")
			);
//			while ($ar = $db->Fetch())
//			{
//				try
//				{
//					CBPWorkflowTemplateLoader::Delete($ar["ID"]);
//				}
//				catch(Exception $e)
//				{
//					CBPWorkflowTemplateLoader::Update($ar["ID"], array("AUTO_EXECUTE" => 0));
//				}
//			}

			$workflowTemplateId = 0;
			if ($ar = $db->Fetch())
			{
				$workflowTemplateId = $ar["ID"];
			}
			else
			{
				require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/classes/general/tasks_wf_template.php");
				$workflowTemplateId = CBPWorkflowTemplateLoader::Add($arFields);
			}

			$arTaskStatusTmp = array();
			$arTaskStatusTmpAlt = array();
			$dbRes = CIBlockProperty::GetPropertyEnum("TaskStatus", Array("SORT" => "ASC"), Array("IBLOCK_ID" => $iblockId));
			while ($arRes = $dbRes->Fetch())
			{
				$arTaskStatusTmp[StrToUpper($arRes["XML_ID"])] = $arRes["ID"];
				$arTaskStatusTmpAlt[$arRes["ID"]] = $arRes["XML_ID"];
			}

			$dbResult = CIBlockElement::GetList(
				array(),
				array("IBLOCK_ID" => $iblockId, "INCLUDE_SUBSECTIONS" => "Y", "!PROPERTY_TASKVERSION" => 2),
				false,
				false,
				array("ID", "PROPERTY_TASKSTATUS", "PROPERTY_TASKASSIGNEDTO", "IBLOCK_SECTION_ID")
			);
			if ($arResult = $dbResult->Fetch())
			{
				$arOldTasksWFs = array();
				$arOldTasksSTs = array();
				require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/classes/general/tasks_wf_template1.php");

				$arOldTasksWFsTmp = array();
				foreach ($arOldTasksWFs as $t1 => $t2)
				{
					if (array_key_exists($t1, $arTaskStatusTmp))
						$arOldTasksWFsTmp[$arTaskStatusTmp[$t1]] = $t2;
				}

				$arOldTasksSTsTmp = array();
				foreach ($arOldTasksSTs as $t1 => $t2)
				{
					if (array_key_exists($t1, $arTaskStatusTmp))
						$arOldTasksSTsTmp[$arTaskStatusTmp[$t1]] = $t2;
				}

				do
				{
					$taskTypeTmp = "";
					$ownerIdTmp = "";

					$v = CIntranetTasks::PrepareSectionForPrint($arResult["IBLOCK_SECTION_ID"]);
					foreach ($v as $k1 => $v1)
					{
						$taskTypeTmp = ($v1["XML_ID"] == "users_tasks" ? "user" : "group");
						$ownerIdTmp = ($taskTypeTmp == "user" ? $arResult["PROPERTY_TASKASSIGNEDTO_VALUE"] : $v1["XML_ID"]);
						break;
					}

					if ($taskTypeTmp == "group")
					{
						$pathTemplate = str_replace(
							array("#GROUP_ID#", "#TASK_ID#"),
							array($ownerIdTmp, "{=Document:ID}"),
							COption::GetOptionString("intranet", "path_task_group_entry", "/workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/")
						);
					}
					else
					{
						$pathTemplate = str_replace(
							array("#USER_ID#", "#TASK_ID#"),
							array($ownerIdTmp, "{=Document:ID}"),
							COption::GetOptionString("intranet", "path_task_user_entry", "/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/")
						);
					}
					$pathTemplate = str_replace('#HTTP_HOST#', $_SERVER['HTTP_HOST'], "http://#HTTP_HOST#".$pathTemplate);

					$workflowId = uniqid("", true);
					if (array_key_exists($arResult["PROPERTY_TASKSTATUS_ENUM_ID"], $arOldTasksWFsTmp))
					{
						$v = $arOldTasksWFsTmp[$arResult["PROPERTY_TASKSTATUS_ENUM_ID"]];
						$v = str_replace(
							array("#TPT_TASK_ID_LEN#", "#TPT_TASK_ID#", "#TPT_WORKFLOW_ID_LEN#", "#TPT_WORKFLOW_ID#", "#TPT_DOCUMENT_LEN1#", "#TPT_DOCUMENT_LEN2#", "#TPT_DOCUMENT_LEN3#", "#TPT_DOCUMENT_ROOT#", "#TPT_OWNER_ID#", "#TPT_TASK_TYPE_LEN#", "#TPT_TASK_TYPE#", "#TPT_PATH_TEMPLATE_LEN#", "#TPT_PATH_TEMPLATE#", "#TPT_FORUM_ID#", "#TPT_IBLOCKID_ID#"),
							array(strlen($arResult["ID"]), $arResult["ID"], strlen($workflowId), $workflowId, 25 + strlen($_SERVER["DOCUMENT_ROOT"]), 25 + strlen($_SERVER["DOCUMENT_ROOT"]), 34 + strlen($_SERVER["DOCUMENT_ROOT"]), $_SERVER["DOCUMENT_ROOT"], $ownerIdTmp, strlen($taskTypeTmp), $taskTypeTmp, strlen($pathTemplate), $pathTemplate, $forumId, $iblockId),
							$v
						);
						CBPWorkflowPersister::__InsertWorkflowHack($workflowId, $v);
					}

					if (array_key_exists($arResult["PROPERTY_TASKSTATUS_ENUM_ID"], $arOldTasksSTsTmp))
						CBPStateService::__InsertStateHack($workflowId, "intranet", "CIntranetTasksDocument", $arResult["ID"], $workflowTemplateId, $arOldTasksSTsTmp[$arResult["PROPERTY_TASKSTATUS_ENUM_ID"]]["STATE"], $arOldTasksSTsTmp[$arResult["PROPERTY_TASKSTATUS_ENUM_ID"]]["STATE_TITLE"], $arOldTasksSTsTmp[$arResult["PROPERTY_TASKSTATUS_ENUM_ID"]]["PARAMS"], $arOldTasksSTsTmp[$arResult["PROPERTY_TASKSTATUS_ENUM_ID"]]["PERMS"]);
					else
						CBPStateService::__InsertStateHack($workflowId, "intranet", "CIntranetTasksDocument", $arResult["ID"], $workflowTemplateId, $arOldTasksSTs["COMPLETED"]["STATE"], $arOldTasksSTs["COMPLETED"]["STATE_TITLE"], $arOldTasksSTs["COMPLETED"]["PARAMS"], $arOldTasksSTs["COMPLETED"]["PERMS"]);

					CIBlockElement::SetPropertyValues($arResult["ID"], $iblockId, 2, "TASKVERSION");
				}
				while ($arResult = $dbResult->Fetch());
			}

			$arTasksConverted2BP[$keyTasksConverted2BP] = $globalParentSectionId;

			$CACHE_MANAGER->Clean("IntranetTasksConverted2BP");
			$CACHE_MANAGER->Read(2592000, "IntranetTasksConverted2BP");
			$CACHE_MANAGER->Set("IntranetTasksConverted2BP", $arTasksConverted2BP);
		}

		return $globalParentSectionId;
	}

	function ChangeStatus($taskId, $newStatus, $userId = 0)
	{
		CModule::IncludeModule("socialnetwork");

		$taskId = intval($taskId);
		$userId = intval($userId);
		if ($userId == 0)
			$userId = $GLOBALS["USER"]->GetID();

		$arTask = CIntranetTasks::GetById($taskId);
		if (!$arTask)
			return;

		$arDocumentStates = CBPDocument::GetDocumentStates(
			array("intranet", "CIntranetTasksDocument", "x".$arTask["IBLOCK_ID"]),
			array("intranet", "CIntranetTasksDocument", $taskId)
		);

		$arCurrentUserGroups = array();

		if ($arTask["TaskType"] == "group")
		{
			$arCurrentUserGroups[] = SONET_ROLES_ALL;

			if ($GLOBALS["USER"]->IsAuthorized())
				$arCurrentUserGroups[] = SONET_ROLES_AUTHORIZED;

			$r = CSocNetUserToGroup::GetUserRole($userId, $arTask["OwnerId"]);
			if (strlen($r) > 0)
				$arCurrentUserGroups[] = $r;
		}

		if ($userId == $arTask["CREATED_BY"])
			$arCurrentUserGroups[] = "author";
		if ($userId == $arTask["PROPERTY_TaskAssignedTo"])
			$arCurrentUserGroups[] = "responsible";
		if (is_array($arTask["PROPERTY_TaskTrackers"]) && in_array($userId, $arTask["PROPERTY_TaskTrackers"]))
			$arCurrentUserGroups[] = "trackers";

		$arStateCommand = array(
			"NotAccepted" => array(
				"NotStarted" => array("HEEA_NotAccepted_ApproveEvent"),
				"InProgress" => array("HEEA_NotAccepted_InProgressEvent"),
				"Completed" => array("HEEA_NotAccepted_CompleteEvent"),
				"Closed" => array("HEEA_NotAccepted_CloseEvent"),
				"Waiting" => array("HEEA_NotAccepted_ApproveEvent", "HEEA_NotStarted_WaitingEvent"),
				"Deferred" => array("HEEA_NotAccepted_ApproveEvent", "HEEA_NotStarted_DeferredEvent"),
			),
			"NotStarted" => array(
				"InProgress" => array("HEEA_NotStarted_InProgressEvent"),
				"Completed" => array("HEEA_NotStarted_CompleteEvent"),
				"Closed" => array("HEEA_NotStarted_CloseEvent"),
				"Waiting" => array("HEEA_NotStarted_WaitingEvent"),
				"Deferred" => array("HEEA_NotStarted_DeferredEvent"),
			),
			"InProgress" => array(
				"Completed" => array("HEEA_InProgress_CompleteEvent"),
				"Closed" => array("HEEA_InProgress_CloseEvent"),
				"Waiting" => array("HEEA_InProgress_WaitingEvent"),
				"Deferred" => array("HEEA_InProgress_DeferredEvent"),
			),
			"Completed" => array(
				"InProgress" => array("HEEA_Completed_InProgressEvent"),
				"Closed" => array("HEEA_Completed_CloseEvent"),
			),
			"Waiting" => array(
				"NotStarted" => array("HEEA_Waiting_NotStartedEvent"),
				"InProgress" => array("HEEA_Waiting_InProgressEvent"),
				"Completed" => array("HEEA_Waiting_CompleteEvent"),
				"Closed" => array("HEEA_Waiting_CloseEvent"),
				"Deferred" => array("HEEA_Waiting_DeferredEvent"),
			),
			"Deferred" => array(
				"NotStarted" => array("HEEA_Deferred_NotStartedEvent"),
				"InProgress" => array("HEEA_Deferred_InProgressEvent"),
				"Completed" => array("HEEA_Deferred_CompleteEvent"),
				"Closed" => array("HEEA_Deferred_CloseEvent"),
				"Waiting" => array("HEEA_Deferred_WaitingEvent"),
			),
		);

		foreach ($arDocumentStates as $documentState)
		{
			$oldState = $documentState["STATE_NAME"];

			if (!array_key_exists($oldState, $arStateCommand) || count($arStateCommand[$oldState]) <= 0)
				continue;
			if (!array_key_exists($newStatus, $arStateCommand[$oldState]) || count($arStateCommand[$oldState][$newStatus]) <= 0)
				continue;

			foreach ($arStateCommand[$oldState][$newStatus] as $sc)
			{
				CBPDocument::SendExternalEvent(
					$documentState["ID"],
					$sc,
					array("Groups" => $arCurrentUserGroups, "User" => $userId),
					$arErrorTmp
				);
			}
		}
	}



	function GetRootSectionId($taskType, $ownerId)
	{
		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			return 0;

		$globalParentSectionId = 0;

		$dbSectionsList = CIBlockSection::GetList(
			array(),
			array(
				"GLOBAL_ACTIVE" => "Y",
				"XML_ID" => (($taskType == "group") ? $ownerId : "users_tasks"),
				"IBLOCK_ID" => $iblockId,
				"SECTION_ID" => 0
			),
			false
		);
		if ($arSection = $dbSectionsList->GetNext())
			$globalParentSectionId = $arSection["ID"];

		if ($globalParentSectionId <= 0)
		{
			if ($taskType == "group")
			{
				$folderName = "-";
				$folderXmlId = $ownerId;
				if ($arGroup = CSocNetGroup::GetByID($ownerId))
				{
					$folderName = $arGroup["NAME"]." [".$ownerId."]";
					$folderXmlId = $ownerId;
				}
			}
			else
			{
				$folderName = "User Tasks";
				$folderXmlId = "users_tasks";
			}

			$arFields = array(
				"IBLOCK_ID" => $iblockId,
				"IBLOCK_SECTION_ID" => 0,
				"ACTIVE" => "Y",
				"NAME" => $folderName,
				"XML_ID" => $folderXmlId,
			);

			$iblockSection = new CIBlockSection;
			$globalParentSectionId = $iblockSection->Add($arFields, true);

			CIntranetTasks::InstallDefaultViews($taskType, $ownerId);
		}

		return $globalParentSectionId;
	}

	function InstallDefaultViews($taskType, $ownerId)
	{
		$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
		if ($iblockId <= 0)
			return;

		$ownerId = IntVal($ownerId);
		if (!In_Array($taskType, array("user", "group")))
			$taskType = "user";

		$newID = 0;

		$dbUserOptionsList = CUserOptions::GetList(
			array("ID" => "DESC"),
			array()
		);
		if ($arUserOptionTmp = $dbUserOptionsList->Fetch())
			$newID = IntVal($arUserOptionTmp["ID"]);

		$arTaskStatus = array();
		$dbRes = CIBlockProperty::GetPropertyEnum("TASKSTATUS", Array("SORT" => "ASC"), Array("IBLOCK_ID" => $iblockId));
		while ($arRes = $dbRes->Fetch())
			$arTaskStatus[StrToUpper($arRes["XML_ID"])] = $arRes;

		if ($taskType == "group")
		{
			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_ASSIGNED2ME_ACT"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TIMESTAMP_X",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"TASKASSIGNEDTO" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "Y",
				),
				true
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_BY_PRIORITY"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TASKPRIORITY",
					"ORDER_DIR_0" => "ASC",
					"ORDER_BY_1" => "DATE_ACTIVE_TO",
					"ORDER_DIR_1" => "ASC",
					"FILTER" => Array(
						"TASKASSIGNEDTO" => "current",
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "Y",
				),
				true
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_ASSIGNED2ME_FIN"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"CREATED_BY" => 2,
						"TASKSIZE" => 3,
						"TASKSIZEREAL" => 4,
						"TASKFINISH" => 5,
					),
					"ORDER_BY_0" => "TASKFINISH",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "DATE_ACTIVE_TO",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"TASKASSIGNEDTO" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "Y",
				),
				true
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_CREATED_BY_ACT"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"TASKASSIGNEDTO" => 3,
						"DATE_ACTIVE_FROM" => 4,
						"DATE_ACTIVE_TO" => 5,
						"TASKSTATUS" => 6,
						"TASKCOMPLETE" => 7,
					),
					"ORDER_BY_0" => "TIMESTAMP_X",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"CREATED_BY" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "Y",
				),
				true
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_CREATED_BY_FIN"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TASKASSIGNEDTO" => 2,
						"TASKSIZE" => 3,
						"TASKSIZEREAL" => 4,
						"TASKFINISH" => 5,
					),
					"ORDER_BY_0" => "TASKFINISH",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DATE_ACTIVE_TO",
					"FILTER" => Array(
						"TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"CREATED_BY" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "Y",
				),
				true
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_TODAY"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TASKPRIORITY",
					"ORDER_DIR_0" => "ASC",
					"ORDER_BY_1" => "DATE_ACTIVE_TO",
					"ORDER_DIR_1" => "ASC",
					"FILTER" => Array(
						"TASKASSIGNEDTO" => "current",
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"<DATE_ACTIVE_FROM" => "current",
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "Y",
				),
				true
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => "gant",
					"TITLE" => GetMessage("INTASK_I_GANT"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKASSIGNEDTO" => 4,
						"TASKPRIORITY" => 5,
						"DATE_ACTIVE_FROM" => 6,
						"DATE_ACTIVE_TO" => 7,
						"TASKSTATUS" => 8,
						"TASKCOMPLETE" => 9,
					),
					"ORDER_BY_0" => "DATE_ACTIVE_TO",
					"ORDER_DIR_0" => "ASC",
					"ORDER_BY_1" => "TASKPRIORITY",
					"ORDER_DIR_1" => "ASC",
					"ORDER_BY_3" => "DATE_ACTIVE_FROM",
					"ORDER_DIR_3" => "ASC",
					"FILTER" => Array(
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "Y",
				),
				true
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_FIN"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TASKASSIGNEDTO" => 2,
						"TASKSIZE" => 3,
						"TASKSIZEREAL" => 4,
						"TASKFINISH" => 5,
					),
					"ORDER_BY_0" => "TASKFINISH",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DATE_ACTIVE_TO",
					"FILTER" => Array(
						"TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "Y",
				),
				true
			);
		}
		elseif ($taskType == "user" && $ownerId == $GLOBALS["USER"]->GetID())
		{
			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_ASSIGNED2ME_ACT"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TIMESTAMP_X",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"TASKASSIGNEDTO" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "N",
				),
				false
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_BY_PRIORITY"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TASKPRIORITY",
					"ORDER_DIR_0" => "ASC",
					"ORDER_BY_1" => "DATE_ACTIVE_TO",
					"ORDER_DIR_1" => "ASC",
					"FILTER" => Array(
						"TASKASSIGNEDTO" => "current",
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "N",
				),
				false
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_ASSIGNED2ME_FIN"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"CREATED_BY" => 2,
						"TASKSIZE" => 3,
						"TASKSIZEREAL" => 4,
						"TASKFINISH" => 5,
					),
					"ORDER_BY_0" => "TASKFINISH",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "DATE_ACTIVE_TO",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"TASKASSIGNEDTO" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "N",
				),
				false
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_CREATED_BY_ACT"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"TASKASSIGNEDTO" => 3,
						"DATE_ACTIVE_FROM" => 4,
						"DATE_ACTIVE_TO" => 5,
						"TASKSTATUS" => 6,
						"TASKCOMPLETE" => 7,
					),
					"ORDER_BY_0" => "TIMESTAMP_X",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"CREATED_BY" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "N",
				),
				false
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_CREATED_BY_FIN"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TASKASSIGNEDTO" => 2,
						"TASKSIZE" => 3,
						"TASKSIZEREAL" => 4,
						"TASKFINISH" => 5,
					),
					"ORDER_BY_0" => "TASKFINISH",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DATE_ACTIVE_TO",
					"FILTER" => Array(
						"TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"CREATED_BY" => "current",
					),
					"THROUGH_SAMPLING" => ($taskType == "user" ? "Y" : "N"),
					"COMMON" => "N",
				),
				false
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_TODAY"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TASKPRIORITY",
					"ORDER_DIR_0" => "ASC",
					"ORDER_BY_1" => "DATE_ACTIVE_TO",
					"ORDER_DIR_1" => "ASC",
					"FILTER" => Array(
						"TASKASSIGNEDTO" => "current",
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
						"<DATE_ACTIVE_FROM" => "current",
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "N",
				),
				false
			);

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => "gant",
					"TITLE" => GetMessage("INTASK_I_GANT"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKASSIGNEDTO" => 4,
						"TASKPRIORITY" => 5,
						"DATE_ACTIVE_FROM" => 6,
						"DATE_ACTIVE_TO" => 7,
						"TASKSTATUS" => 8,
						"TASKCOMPLETE" => 9,
					),
					"ORDER_BY_0" => "DATE_ACTIVE_TO",
					"ORDER_DIR_0" => "ASC",
					"ORDER_BY_1" => "TASKPRIORITY",
					"ORDER_DIR_1" => "ASC",
					"ORDER_BY_3" => "DATE_ACTIVE_FROM",
					"ORDER_DIR_3" => "ASC",
					"FILTER" => Array(
						"TASKASSIGNEDTO" => "current",
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "N",
				),
				false
			);

			$userIBlockSectionId = 0;
			$dbSectionsList = CIBlockSection::GetList(
				array(),
				array(
					"GLOBAL_ACTIVE" => "Y",
					"EXTERNAL_ID" => "users_tasks",
					"IBLOCK_ID" => $iblockId,
					"SECTION_ID" => 0
				),
				false
			);
			if ($arSection = $dbSectionsList->GetNext())
				$userIBlockSectionId = $arSection["ID"];

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_PERSONAL"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TIMESTAMP_X",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"TASKASSIGNEDTO" => $ownerId,
						"IBLOCK_SECTION" => $userIBlockSectionId,
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "N",
				),
				false
			);
		}
		elseif ($taskType == "user")
		{
			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => "gant",
					"TITLE" => GetMessage("INTASK_I_GANT"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKASSIGNEDTO" => 4,
						"TASKPRIORITY" => 5,
						"DATE_ACTIVE_FROM" => 6,
						"DATE_ACTIVE_TO" => 7,
						"TASKSTATUS" => 8,
						"TASKCOMPLETE" => 9,
					),
					"ORDER_BY_0" => "DATE_ACTIVE_TO",
					"ORDER_DIR_0" => "ASC",
					"ORDER_BY_1" => "TASKPRIORITY",
					"ORDER_DIR_1" => "ASC",
					"ORDER_BY_3" => "DATE_ACTIVE_FROM",
					"ORDER_DIR_3" => "ASC",
					"FILTER" => Array(
						"!TASKSTATUS" => $arTaskStatus["COMPLETED"]["ID"],
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "N",
				),
				false
			);

			$userIBlockSectionId = 0;
			$dbSectionsList = CIBlockSection::GetList(
				array(),
				array(
					"GLOBAL_ACTIVE" => "Y",
					"EXTERNAL_ID" => "users_tasks",
					"IBLOCK_ID" => $iblockId,
					"SECTION_ID" => 0
				),
				false
			);
			if ($arSection = $dbSectionsList->GetNext())
				$userIBlockSectionId = $arSection["ID"];

			$newID++;

			CUserOptions::SetOption(
				"IntranetTasks",
				"Settings_".$newID,
				Array(
					"TEMPLATE" => ".default",
					"TITLE" => GetMessage("INTASK_I_PERSONAL"),
					"IBLOCK_ID" => $iblockId,
					"TASK_TYPE" => $taskType,
					"OWNER_ID" => $ownerId,
					"COLUMNS" => Array(
						"NAME" => 1,
						"TIMESTAMP_X" => 2,
						"CREATED_BY" => 3,
						"TASKPRIORITY" => 4,
						"DATE_ACTIVE_FROM" => 5,
						"DATE_ACTIVE_TO" => 6,
						"TASKSTATUS" => 7,
						"TASKCOMPLETE" => 8,
					),
					"ORDER_BY_0" => "TIMESTAMP_X",
					"ORDER_DIR_0" => "DESC",
					"ORDER_BY_1" => "ID",
					"ORDER_DIR_1" => "DESC",
					"FILTER" => Array(
						"TASKASSIGNEDTO" => $ownerId,
						"IBLOCK_SECTION" => $userIBlockSectionId,
					),
					"THROUGH_SAMPLING" => "Y",
					"COMMON" => "N",
				),
				false
			);
		}
	}

	function __InTaskDeleteView($delViewId, $iblockId, $taskType, $ownerId)
	{
		$delViewId = intval($delViewId);
		$iblockId = intval($iblockId);
		$ownerId = intval($ownerId);
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
}
?>