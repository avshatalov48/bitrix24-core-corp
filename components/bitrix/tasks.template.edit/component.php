<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

##################
### checkRequiredModules

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_INSTALLED"));
	return;
}
if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("SOCNET_MODULE_NOT_INSTALLED"));
	return;
}

CModule::IncludeModule("fileman");

##################
### onPrepareComponentParams

global $APPLICATION;
$cUserId = \Bitrix\Tasks\Util\User::getId();

$arParams["TASK_VAR"] = trim($arParams["TASK_VAR"]);
if (strlen($arParams["TASK_VAR"]) <= 0)
	$arParams["TASK_VAR"] = "task_id";

$arParams["GROUP_VAR"] = trim($arParams["GROUP_VAR"]);
if (strlen($arParams["GROUP_VAR"]) <= 0)
	$arParams["GROUP_VAR"] = "group_id";

$arParams["ACTION_VAR"] = trim($arParams["ACTION_VAR"]);
if (strlen($arParams["ACTION_VAR"]) <= 0)
	$arParams["ACTION_VAR"] = "action";

if (strlen($arParams["PAGE_VAR"]) <= 0)
	$arParams["PAGE_VAR"] = "page";

$arParams["TEMPLATE_VAR"] = trim($arParams["TEMPLATE_VAR"]);
if (strlen($arParams["TASK_VAR"]) <= 0)
	$arParams["TEMPLATE_VAR"] = "template_id";

$arParams["TEMPLATE_ID"] = intval($arParams["TEMPLATE_ID"]);

if(intval($arParams["TEMPLATE_ID"]))
{
	if(isset($_REQUEST['ACTION']) && $_REQUEST['controller_id'] === 'tasks.template.edit') // to avoid conflict of several components placed at the same page and call/serve same actions
		$arResult["ACTION"] = $_REQUEST['ACTION'] == "delete" ? "delete" : "edit";
	else
		$arResult["ACTION"] = "edit";
}
else
	$arResult["ACTION"] = "create";

$arParams["USER_ID"] = intval($arParams["USER_ID"]) > 0 ? intval($arParams["USER_ID"]) : $cUserId;

$arParams["GROUP_ID"] = intval($arParams["GROUP_ID"]);

//user paths
$arParams["PATH_TO_USER_TASKS"] = trim($arParams["PATH_TO_USER_TASKS"]);
if (strlen($arParams["PATH_TO_USER_TASKS"]) <= 0)
{
	$arParams["PATH_TO_USER_TASKS"] = COption::GetOptionString("tasks", "paths_task_user", null, SITE_ID);
}
$arParams["PATH_TO_USER_TASKS_TASK"] = trim($arParams["PATH_TO_USER_TASKS_TASK"]);
if (strlen($arParams["PATH_TO_USER_TASKS_TASK"]) <= 0)
{
	$arParams["PATH_TO_USER_TASKS_TASK"] = COption::GetOptionString("tasks", "paths_task_user_action", null, SITE_ID);
}
$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = trim($arParams["PATH_TO_USER_TASKS_TEMPLATES"]);
if (strlen($arParams["PATH_TO_USER_TASKS_TEMPLATES"]) <= 0)
{
	$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks_templates&".$arParams["USER_VAR"]."=#user_id#");
}
$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = trim($arParams["PATH_TO_USER_TASKS_TEMPLATES"]);
$arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"] = trim($arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]);
if (strlen($arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]) <= 0)
{
	$arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_templates_template&".$arParams["USER_VAR"]."=#user_id#&".$arParams["TEMPLATE_VAR"]."=#template_id#&".$arParams["ACTION_VAR"]."=#action#");
}

$arParams["PATH_TO_TASKS"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS"]);
$arParams["PATH_TO_TASKS_TASK"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TASK"]);
$arParams["PATH_TO_TASKS_TEMPLATES"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TEMPLATES"]);
$arParams["PATH_TO_TEMPLATES_TEMPLATE"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]);

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$rsUser = CUser::GetByID($arParams["USER_ID"]);
if ($user = $rsUser->GetNext())
{
	$arResult["USER"] = $user;
}
else
{
	return;
}
$arResult['USER_IS_ADMIN'] = CTasksTools::IsAdmin() || CTasksTools::IsPortalB24Admin();

if (array_key_exists("back_url", $_REQUEST) && strlen($_REQUEST["back_url"]) > 0)
{
	$arResult["RETURN_URL"] = htmlspecialcharsbx(trim($_REQUEST["back_url"]));
}
else
{
	$arResult["RETURN_URL"] = $arParams["PATH_TO_TASKS_TEMPLATES"];
}

##################
### dispatchAction

$arData = array();
if (($arResult["ACTION"] == "edit" || $arResult["ACTION"] == "delete") && intval($arParams["TEMPLATE_ID"])) // check access rights and get data (better to rewrite the condition to: if(intval($arParams["TEMPLATE_ID"])) )
{
	$rsTemplate = CTaskTemplates::GetList(Array(), Array("ID" => $arParams["TEMPLATE_ID"]), array(), array(
		'USER_ID' => $cUserId		// check permissions for current user
	), array('*', 'UF_*', 'BASE_TEMPLATE_ID', 'TEMPLATE_CHILDREN_COUNT'));

	if (!($arData = $rsTemplate->GetNext()))
	{
		ShowError(GetMessage("TASKS_TEMPLATE_NOT_FOUND"));
		return;
	}
	else
	{
		$arData["ACCOMPLICES"] = \Bitrix\Tasks\Util\Type::unSerializeArray($arData["~ACCOMPLICES"]);
		$arData["AUDITORS"] = \Bitrix\Tasks\Util\Type::unSerializeArray($arData["~AUDITORS"]);
		$arData["RESPONSIBLES"] = \Bitrix\Tasks\Util\Type::unSerializeArray($arData["~RESPONSIBLES"]);
		$arData["FILES"] = $arData["~FILES"] ? unserialize($arData["~FILES"]) : array();
		$arData["TAGS"] = $arData["~TAGS"] ? unserialize($arData["~TAGS"]) : "";
		$arData["DEPENDS_ON"] = Bitrix\Tasks\Util\Type::unSerializeArray($arData["~DEPENDS_ON"]);
		$arData["DESCRIPTION"] = $arData["~DESCRIPTION"];

		$arData["CREATED_BY_NAME"] = $arData["~CREATED_BY_NAME"];
		$arData["CREATED_BY_LAST_NAME"] = $arData["~CREATED_BY_LAST_NAME"];
		$arData["CREATED_BY_SECOND_NAME"] = $arData["~CREATED_BY_SECOND_NAME"];
		$arData["CREATED_BY_LOGIN"] = $arData["~CREATED_BY_LOGIN"];

		$arData["DEADLINE_AFTER"] = $arData["~DEADLINE_AFTER"] / (24 * 60 * 60);

		$arData["REPLICATE_PARAMS"] = unserialize($arData["~REPLICATE_PARAMS"]);

		// check list
		$arData['CHECKLIST_ITEMS'] = array();
		$res = \Bitrix\Tasks\Internals\Task\Template\CheckListTable::getList(array(
			'filter' => array(
				'=TEMPLATE_ID' => $arParams["TEMPLATE_ID"]
			),
			'order' => array(
				'SORT' => 'asc'
			),
			'select' => array(
				'ID', 'TITLE', 'CHECKED', 'IS_COMPLETE'
			)
		));
		while($item = $res->fetch())
			$arData['CHECKLIST_ITEMS'][$item['ID']] = $item;

		// check accomplices & auditors existence here...
		$users = array();
		foreach($arData["ACCOMPLICES"] as $user)
		{
			$users[$user] = true;
		}
		foreach($arData["AUDITORS"] as $user)
		{
			$users[$user] = true;
		}

		$users = array_keys($users);

		if(!empty($users))
		{
			$res = \Bitrix\Main\UserTable::getList(array('filter' => array('ID' => $users), 'select' => array('ID')));
			$users = array();
			while($item = $res->fetch())
			{
				$users[$item['ID']] = true;
			}

			foreach($arData["ACCOMPLICES"] as $k => $user)
			{
				if(!isset($users[$user]))
					unset($arData["ACCOMPLICES"][$k]);
			}
			foreach($arData["AUDITORS"] as $k => $user)
			{
				if(!isset($users[$user]))
					unset($arData["AUDITORS"][$k]);
			}
		}
	}
}
else
{
	$arData["PRIORITY"] = 1;
}

$arReplicateParams = CTaskTemplates::parseReplicationParams($arData["REPLICATE_PARAMS"] ? $arData["REPLICATE_PARAMS"] : array());
$prefixed = array();
foreach($arReplicateParams as $k => $v)
{
	$prefixed['REPLICATE_'.$k] = $v;
}
$arReplicateParams = $prefixed;
$arData = array_merge($arData, $arReplicateParams);

// read existed user fileds
$arResult["USER_FIELDS"] = \Bitrix\Tasks\Util\UserField\Task\Template::getScheme($arParams["TEMPLATE_ID"]);

//Form submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && check_bitrix_sessid() && ($arResult["ACTION"] == "create" || $arResult["ACTION"] == "edit") && !isset($_POST['controller_id']))
{
	$_POST["WEEK_DAYS"] = explode(",", $_POST["WEEK_DAYS"]);

	if(isset($_POST["save"]) || isset($_POST["apply"]))
	{
		$_POST["TAGS"] = array_filter(explode(",", $_POST["TAGS"]));
		$_POST["ACCOMPLICES"] = array_filter(explode(",", $_POST["ACCOMPLICES_IDS"]));
		$_POST["RESPONSIBLES"] = array_filter(explode(",", $_POST["RESPONSIBLES_IDS"]));
		$_POST["DEPENDS_ON"] = array_filter(explode(",", $_POST["PREV_TASKS_IDS"]));
		$_POST["REPLICATE_WEEK_DAYS"] = array_filter(explode(",", $_POST["REPLICATE_WEEK_DAYS"]));

		$arFields = array(
			"TITLE" => trim($_POST["TITLE"]),
			"DESCRIPTION" => $_POST["DESCRIPTION"],
			"DEADLINE" => $_POST["DEADLINE"],
			"START_DATE_PLAN" => $_POST["START_DATE_PLAN"],
			"END_DATE_PLAN" => $_POST["END_DATE_PLAN"],
			"DURATION_PLAN" => $_POST["DURATION_PLAN"],
			"DURATION_TYPE" => $_POST["DURATION_TYPE"],
			"PRIORITY" => $_POST["PRIORITY"],
			"ACCOMPLICES" => sizeof($_POST["ACCOMPLICES"]) > 0 ? serialize($_POST["ACCOMPLICES"]) : false,
			"AUDITORS" => sizeof($_POST["AUDITORS"]) > 0 ? serialize($_POST["AUDITORS"]) : false,
			"TAGS" => sizeof($_POST["TAGS"]) > 0 ? serialize($_POST["TAGS"]) : false,
			"RESPONSIBLES" => sizeof($_POST["RESPONSIBLES"]) > 0 ? serialize($_POST["RESPONSIBLES"]) : false,
			"DEPENDS_ON" => sizeof($_POST["DEPENDS_ON"]) > 0 ? serialize($_POST["DEPENDS_ON"]) : false,
			"FILES" => sizeof($_POST["FILES"]) > 0 ? serialize($_POST["FILES"]) : false,
			"ALLOW_CHANGE_DEADLINE" => isset($_POST["ALLOW_CHANGE_DEADLINE"]) ? "Y" : "N",
			"TASK_CONTROL" => isset($_POST["TASK_CONTROL"]) ? "Y" : "N",
			"ADD_IN_REPORT" => isset($_POST["ADD_IN_REPORT"]) ? "Y" : "N",
			"PARENT_ID" => intval($_POST["PARENT_ID"]) > 0 ? intval($_POST["PARENT_ID"]) : false,
			"GROUP_ID" => intval($_POST["GROUP_ID"]) > 0 && CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, intval($_POST["GROUP_ID"]), "tasks", "create_tasks") ? intval($_POST["GROUP_ID"]) : false,
			"REPLICATE" => isset($_POST["REPLICATE"]) ? "Y" : "N",
			"DEADLINE_AFTER" => intval($_POST["DEADLINE_AFTER"]) > 0 ? $_POST["DEADLINE_AFTER"] * 24 * 60 * 60 : false,
			"BASE_TEMPLATE_ID" => intval($_POST["BASE_TEMPLATE_ID"]) ? intval($_POST["BASE_TEMPLATE_ID"]) : false
		);

		if(intval($_POST["TPARAM_TYPE"]))
		{
			$arFields["TPARAM_TYPE"] = intval($_POST["TPARAM_TYPE"]);

			// non-admins can not create such kind of templates
			if(!$arResult['USER_IS_ADMIN'] && $arFields['TPARAM_TYPE'] == CTaskTemplates::TYPE_FOR_NEW_USER)
				$arFields['TPARAM_TYPE'] = '';
		}

		if (
			isset($_POST['DESCRIPTION_IN_BBCODE'])
			&& in_array($_POST['DESCRIPTION_IN_BBCODE'], array('Y', 'N'))
		)
		{
			$arFields['DESCRIPTION_IN_BBCODE'] = $_POST['DESCRIPTION_IN_BBCODE'];
		}
		else
			$arFields['DESCRIPTION_IN_BBCODE'] = 'N';	// for compatibility

		$arFields["REPLICATE_PARAMS"] = array();
		foreach ($_POST as $field=>$value)
		{
			if (substr($field, 0, 10) == "REPLICATE_") // parameters of replication
			{
				$arFields["REPLICATE_PARAMS"][substr($field, 10)] = substr($field, -5) == "_DATE" ?  ConvertDateTime($value) : $value;
			}
		}
		$arFields["REPLICATE_PARAMS"] = serialize($arFields["REPLICATE_PARAMS"]);

		$arFields["SITE_ID"] = SITE_ID;

		$arSectionIDs = CTasks::GetSubordinateDeps();

		if ($_POST["MULTITASK"] == "Y" && sizeof($_POST["RESPONSIBLES"]) > 0)
		{
			$arFields["MULTITASK"] = "Y";
			$arFields["CREATED_BY"] = $arFields["RESPONSIBLE_ID"] = $cUserId;
		}
		else
		{
			$arFields["MULTITASK"] = "N";

			$arFields["CREATED_BY"] = $_POST["CREATED_BY"];

			// if creator is not a current user and creator is not an admin
			if ($arFields["CREATED_BY"] != $cUserId && !$arResult['USER_IS_ADMIN'])
			{
				$arFields["RESPONSIBLE_ID"] = $cUserId;
			}
			else
			{
				$arFields["RESPONSIBLE_ID"] = $_POST["RESPONSIBLE_ID"];
			}
		}

		if ($arFields["RESPONSIBLE_ID"] != $cUserId)
		{
			$rsUser = CUser::GetByID($arFields["RESPONSIBLE_ID"]);
			if ($arUser = $rsUser->Fetch())
			{
				if(!$arUser["UF_DEPARTMENT"] || !sizeof(array_intersect($arSectionIDs, $arUser["UF_DEPARTMENT"])))
				{
					$arFields["ADD_IN_REPORT"] = "N";
				}
			}
		}

		// USER FIELDS check BEGIN

		$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields('TASKS_TASK_TEMPLATE', $arFields);

		foreach ($arResult["USER_FIELDS"] as $ufName => $ufMetaData)
		{
			if ($ufMetaData['USER_TYPE_ID'] !== 'file')
				continue;

			if (isset($arFields[$ufName]))
			{
				if ($ufMetaData['MULTIPLE'] === 'Y')
				{
					foreach ($arFields[$ufName] as $key => $value)
					{
						if ( ! is_array($value) )
							$arFields[$ufName][$key] = '';
					}
				}
				else
				{
					if ( ! is_array($arFields[$ufName]) )
						$arFields[$ufName] = '';
				}
			}
		}

		// USER FIELDS END

		$template = new CTaskTemplates();
		if ($arResult["ACTION"] == "edit")
		{
			$arFields["RESPONSIBLE_ID"] = $_POST["RESPONSIBLE_ID"];

			if (isset($_POST["FILES_TO_DELETE"]) && sizeof($_POST["FILES_TO_DELETE"]))
			{
				$arFilesToUnlink = array();
				foreach($_POST["FILES_TO_DELETE"] as $file)
				{
					if (in_array($file, $arData["FILES"]))
					{
						// Skip files, that attached to some existing tasks
						$rsFiles = CTaskFiles::GetList(
							array(),
							array('FILE_ID' => $file)
						);

						// There is no tasks with this file, so it can be removed
						if (!$arFile = $rsFiles->Fetch())
							$arFilesToUnlink[] = $file;
					}
				}

				foreach ($arFilesToUnlink as $file)
					CFile::Delete($file);
			}
			$result = $template->Update(
				$arParams["TEMPLATE_ID"],
				$arFields,
				array(
					'CHECK_RIGHTS_ON_FILES' => true,
					'USER_ID'               => (int) $cUserId
				)
			);
			$templateID = $arParams["TEMPLATE_ID"];
		}
		else
		{
			$templateID = $result = $template->Add(
				$arFields,
				array(
					'CHECK_RIGHTS_ON_FILES' => true,
					'USER_ID'               => (int) $cUserId
				)
			);
		}

		// format check list items
		if(is_array($_POST['CHECKLIST_ITEM_ID']))
		{
			$sort = 0;
			$items = array();
			foreach($_POST['CHECKLIST_ITEM_ID'] as $clId)
			{
				if((string) $_POST['CHECKLIST_ITEM_TITLE'][$clId])
				{
					$id = ((string) $clId === (string) intval($clId)) ? intval($clId) : false;
					$data = array(
						'TEMPLATE_ID' => $templateID,
						'TITLE' => $_POST['CHECKLIST_ITEM_TITLE'][$clId],
						'CHECKED' => $_POST['CHECKLIST_ITEM_IS_CHECKED'][$clId] == 'Y',
						'SORT' => $sort++
					);

					if(intval($id))
					{
						$data['ID'] = $id;
						$items[$id] = $data;
					}
					else
					{
						$items[] = $data;
					}
				}
			}

			$_POST['CHECKLIST_ITEMS'] = $items; // too bad
		}

		$arResult["ERRORS"] = $template->GetErrors();
		if (sizeof($arResult["ERRORS"]) == 0)
		{
			$arUploadedFils = unserialize($arFields["FILES"]);

			if (is_array($arUploadedFils) && count($arUploadedFils))
			{
				foreach ($arUploadedFils as $fileId)
					CTaskFiles::removeTemporaryFile($cUserId, (int) $fileId);
			}

			$checkList2Store = $_POST['CHECKLIST_ITEMS'];
			if(!is_array($checkList2Store))
				$checkList2Store = array();

			// add\update check list items here
			try
			{
				\Bitrix\Tasks\Internals\Task\Template\CheckListTable::updateForTemplate($templateID, $checkList2Store);
			}
			catch(\Bitrix\Main\ArgumentException $e)
			{
			}

			if (strlen($_POST["save"]) > 0)
			{
				if((string) $arParams['PATH_TO_TEMPLATES_TEMPLATE'] !== '' && intval($templateID))
					$redirectPath = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TEMPLATES_TEMPLATE"], array('template_id' => $templateID, 'action' => 'view'));
				else
					$redirectPath = $arResult["RETURN_URL"];
			}
			else
			{
				$redirectPath = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TEMPLATES"], array());
			}
			LocalRedirect($redirectPath);
		}
		else
		{
			$arData = $_POST;

			// specially for USER FIELDS
			if(is_array($arResult['USER_FIELDS']))
			{
				foreach($arResult['USER_FIELDS'] as $fld => &$fldData)
				{
					if(isset($arData[$fld]))
					{
						$fldData['VALUE'] = $arData[$fld];
					}
				}
				unset($fldData);
			}

		}
	}
}
elseif(check_bitrix_sessid() && $arResult["ACTION"] == "delete") // came from external widget with GET
{
	if(is_array($arData) && intval($arData['ID'])) // must ensure template exists and rights were checked properly
	{
		$templateInstance = new CTaskTemplates();
		if($templateInstance->Delete($arData['ID']))
		{
			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TEMPLATES"], array()));
		}
		else
		{
			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TEMPLATES_TEMPLATE"], array('action' => 'view', 'template_id' => $arData['ID'])));
		}
	}
}
else
{
	// some pre-sets for new template
	if ($arResult["ACTION"] == "create")
	{
		$arData['DESCRIPTION_IN_BBCODE'] = 'Y';	// create all new tasks in BB-code

		// set base template id, if it came from GET request
		if(intval($_REQUEST['BASE_TEMPLATE']))
			$arData['BASE_TEMPLATE_ID'] = intval($_REQUEST['BASE_TEMPLATE']);
	}
}

if ($arData["RESPONSIBLE_ID"] && !$arData["RESPONSIBLE_NAME"] && !$arData["RESPONSIBLE_LAST_NAME"] && !$arData["RESPONSIBLE_LOGIN"])
{
	$rsResponsible = CUser::GetByID($arData["RESPONSIBLE_ID"]);
	if ($arResponsible = $rsResponsible->GetNext())
	{
		$arData["RESPONSIBLE_NAME"] = $arResponsible["NAME"];
		$arData["RESPONSIBLE_LAST_NAME"] = $arResponsible["LAST_NAME"];
		$arData["RESPONSIBLE_SECOND_NAME"] = $arResponsible["SECOND_NAME"];
		$arData["RESPONSIBLE_LOGIN"] = $arResponsible["LOGIN"];
	}
	else // responsible not found, will be set as CREATED_BY below
	{
		unset($arData["RESPONSIBLE_ID"]);
	}
}

if ($arData["CREATED_BY"] && !$arData["CREATED_BY_NAME"] && !$arData["CREATED_BY_LAST_NAME"] && !$arData["CREATED_BY_LOGIN"])
{
	$rsResponsible = CUser::GetByID($arData["CREATED_BY"]);
	if ($arResponsible = $rsResponsible->Fetch())
	{
		$arData["CREATED_BY_NAME"] = $arResponsible["NAME"];
		$arData["CREATED_BY_LAST_NAME"] = $arResponsible["LAST_NAME"];
		$arData["CREATED_BY_SECOND_NAME"] = $arResponsible["SECOND_NAME"];
		$arData["CREATED_BY_LOGIN"] = $arResponsible["LOGIN"];
	}
	else
	{
		unset($arData["CREATED_BY"]);
	}
}

if($arData['TPARAM_TYPE'] != CTaskTemplates::TYPE_FOR_NEW_USER) // if this is not a "system" template (template that is used for newly-created user)
{
	if (!$arData["RESPONSIBLE_ID"])
	{
		if (($arData["CREATED_BY"] && $arData["CREATED_BY"] != $cUserId))
		{
			$arData["RESPONSIBLE_ID"] = $cUserId;

			$cUser = \Bitrix\Tasks\Util\User::get();
			if($cUser)
			{
				$arData["RESPONSIBLE_NAME"] = $cUser->GetFirstName();
				$arData["RESPONSIBLE_LAST_NAME"] = $cUser->GetLastName();
				$arData["RESPONSIBLE_SECOND_NAME"] = $cUser->GetSecondName();
				$arData["RESPONSIBLE_LOGIN"] = $cUser->GetLogin();
			}
		}
		else
		{
			$arData["RESPONSIBLE_ID"] = $arResult["USER"]["ID"];
			$arData["RESPONSIBLE_NAME"] = $arResult["USER"]["NAME"];
			$arData["RESPONSIBLE_LAST_NAME"] = $arResult["USER"]["LAST_NAME"];
			$arData["RESPONSIBLE_SECOND_NAME"] = $arResult["USER"]["SECOND_NAME"];
			$arData["RESPONSIBLE_LOGIN"] = $arResult["USER"]["LOGIN"];
		}
	}
}

if (!$arData["CREATED_BY"])
{
	$cUser = \Bitrix\Tasks\Util\User::get();
	if($cUser)
	{
		$arData["CREATED_BY"] = $cUser->getId();
		$arData["CREATED_BY_NAME"] = $cUser->GetFirstName();
		$arData["CREATED_BY_LAST_NAME"] = $cUser->GetLastName();
		$arData["CREATED_BY_SECOND_NAME"] = $cUser->GetSecondName();
		$arData["CREATED_BY_LOGIN"] = $cUser->GetLogin();
	}
}

// HTML-format must be supported in future, because old tasks' data not converted from HTML to BB
if ($arData['DESCRIPTION_IN_BBCODE'] !== 'Y')
{
	if (array_key_exists('DESCRIPTION', $arData))
		$arData['DESCRIPTION'] = CTasksTools::SanitizeHtmlDescriptionIfNeed($arData['DESCRIPTION']);
	if (array_key_exists('~DESCRIPTION', $arData))
		$arData['~DESCRIPTION'] = CTasksTools::SanitizeHtmlDescriptionIfNeed($arData['~DESCRIPTION']);
}
else
{
	$arData['META:DESCRIPTION_FOR_BBCODE'] = $arData['DESCRIPTION'];
	$parser = new CTextParser();
	$arData['~DESCRIPTION'] = $parser->convertText($arData['META:DESCRIPTION_FOR_BBCODE']);
	$arData['DESCRIPTION'] = $arData['~DESCRIPTION'];
}

$arResult["DATA"] = $arData;

// base template, if any
if(intval($arResult["DATA"]['BASE_TEMPLATE_ID']))
{
	$baseTemplate = CTaskTemplates::GetList(Array(), Array("ID" => $arResult["DATA"]['BASE_TEMPLATE_ID']), array(), array(
		'USER_ID' => $cUserId // check permissions for current user
	), array('*'))->GetNext();

	if(is_array($baseTemplate))
		$arResult["DATA"]['BASE_TEMPLATE_DATA'] = $baseTemplate;
	else
		unset($arResult["DATA"]['BASE_TEMPLATE_ID']);
}

// groups
$rsGroups = CSocNetGroup::GetList(array("NAME" => "ASC"), array("SITE_ID" => SITE_ID));
$arResult["GROUPS"] = array();
$groupIDs = array();
while($group = $rsGroups->GetNext())
{
	$arResult["GROUPS"][$group["ID"]] = $group;
	$groupIDs[] = $group["ID"];
}

if (sizeof($groupIDs) > 0)
{
	$arGroupsPerms = CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $groupIDs, "tasks", "create_tasks");
	foreach ($arResult["GROUPS"] as $key=>$group)
	{
		if (!$arGroupsPerms[$group["ID"]])
		{
			unset($arResult["GROUPS"][$key]);
		}
	}
}

$sTitle = "";
if ($arResult["ACTION"] == "edit")
{
	$sTitle = str_replace("#TEMPLATE_ID#", $arParams["TEMPLATE_ID"], GetMessage("TASKS_TITLE_EDIT_TEMPLATE"));
}
else
{
	$sTitle = GetMessage("TASKS_TITLE_CREATE_TEMPLATE");
}
if ($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle($sTitle);
}

if ($arParams["SET_NAVCHAIN"] != "N")
{
	$APPLICATION->AddChainItem(CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult["USER"]), CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_USER_PROFILE"], array("user_id" => $arParams["USER_ID"])));
	$APPLICATION->AddChainItem($sTitle);
}

$this->IncludeComponentTemplate();
?>