<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

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

global $USER, $APPLICATION;

__checkForum($arParams["FORUM_ID"]);

if (!is_object($USER) || !$USER->IsAuthorized())
{
	$APPLICATION->AuthForm("");
	return;
}

$arParams["TASK_VAR"] = trim($arParams["TASK_VAR"]);
if (strlen($arParams["TASK_VAR"]) <= 0)
	$arParams["TASK_VAR"] = "task_id";

$arParams["GROUP_VAR"] = isset($arParams["GROUP_VAR"]) ? trim($arParams["GROUP_VAR"]) : "";
if (strlen($arParams["GROUP_VAR"]) <= 0)
	$arParams["GROUP_VAR"] = "group_id";

$arParams["ACTION_VAR"] = trim($arParams["ACTION_VAR"]);
if (strlen($arParams["ACTION_VAR"]) <= 0)
	$arParams["ACTION_VAR"] = "action";

if (strlen($arParams["PAGE_VAR"]) <= 0)
	$arParams["PAGE_VAR"] = "page";

$arParams["TASK_ID"] = intval($arParams["TASK_ID"]);

$arResult["ACTION"] = ($arParams["TASK_ID"] > 0 ? "edit" : "create");

$arParams["USER_ID"] = intval($arParams["USER_ID"]) > 0 ? intval($arParams["USER_ID"]) : $USER->GetID();

$arParams["GROUP_ID"] = isset($arParams["GROUP_ID"]) ? intval($arParams["GROUP_ID"]) : 0;

$taskType = $arResult["TASK_TYPE"] = ($arParams["GROUP_ID"] > 0 ? "group" : "user");

$arResult["IS_IFRAME"] = (isset($_GET["IFRAME"]) && $_GET["IFRAME"] == "Y");
if (isset($_GET["CALLBACK"]) && ($_GET["CALLBACK"] == "ADDED" || $_GET["CALLBACK"] == "CHANGED"))
{
	$arResult["CALLBACK"] = $_GET["CALLBACK"];
}

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

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

//group paths
$arParams["PATH_TO_GROUP_TASKS"] = trim($arParams["PATH_TO_GROUP_TASKS"]);
if (strlen($arParams["PATH_TO_GROUP_TASKS"]) <= 0)
{
	$arParams["PATH_TO_GROUP_TASKS"] = COption::GetOptionString("tasks", "paths_task_group", null, SITE_ID);
}
$arParams["PATH_TO_GROUP_TASKS_TASK"] = isset($arParams["PATH_TO_GROUP_TASKS_TASK"]) ? trim($arParams["PATH_TO_GROUP_TASKS_TASK"]) : "";
if (strlen($arParams["PATH_TO_GROUP_TASKS_TASK"]) <= 0)
{
	$arParams["PATH_TO_GROUP_TASKS_TASK"] = COption::GetOptionString("tasks", "paths_task_group_action", null, SITE_ID);
}

if ($taskType == "user")
{
	$arParams["PATH_TO_TASKS"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TASK"]);

	$rsUser = CUser::GetByID($arParams["USER_ID"]);
	if ($user = $rsUser->GetNext())
	{
		$arResult["USER"] = $user;
	}
	else
	{
		return;
	}
}
else
{
	$arParams["PATH_TO_TASKS"] = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS_TASK"]);

	$arResult["GROUP"] = CSocNetGroup::GetByID($arParams["GROUP_ID"]);
	if (!$arResult["GROUP"])
	{
		return;
	}
}

$loggedInUserId = (int) $USER->getId();

if (!$arResult["USER"])
{
	$rsUser = CUser::GetByID($loggedInUserId);
	$arResult["USER"] = $rsUser->GetNext();
}

$arResult["bVarsFromForm"] = false;

if ($arResult["ACTION"] == "edit")
{
	try
	{
		$oTask  = new CTaskItem($arParams['TASK_ID'], $loggedInUserId);
		$arTask = $oTask->getData();
		$arResult['ALLOWED_ACTIONS'] = $oTask->getAllowedActions(true);

		$arTask['~TAGS']       = $arTask['TAGS']       = $oTask->getTags();
		$arTask['~FILES']      = $arTask['FILES']      = $oTask->getFiles();
		$arTask['~DEPENDS_ON'] = $arTask['DEPENDS_ON'] = $oTask->getDependsOn();

		if ( ! $oTask->isActionAllowed(CTaskItem::ACTION_EDIT) )
			throw new TasksException();
	}
	catch (TasksException $e)
	{
		ShowError(GetMessage("TASKS_TASK_NOT_FOUND"));
		return;
	}
}

if (array_key_exists("back_url", $_REQUEST) && strlen($_REQUEST["back_url"]) > 0)
{
	$arResult["RETURN_URL"] = htmlspecialcharsbx(trim($_REQUEST["back_url"]));
}

// a bundle of parameters considering copying and creating by template
$arResult['COPY_PARAMS'] = array(

	// copying
	'ORIGIN_TASK' => intval($_POST['COPY_PARAMS']['ORIGIN_TASK']),
	'COPY_CHILD_TASKS' => isset($_POST['COPY_PARAMS']) ? $_POST['COPY_PARAMS']['COPY_CHILD_TASKS'] == 'Y' : true,

	// replicating from template
	'ORIGIN_TEMPLATE' => intval($_POST['COPY_PARAMS']['ORIGIN_TEMPLATE']),
	'COPY_CHILD_TEMPLATES' => isset($_POST['COPY_PARAMS']) ? $_POST['COPY_PARAMS']['COPY_CHILD_TEMPLATES'] == 'Y' : true,
);

$arData = array();

$arResult['TASK'] = null;
$arResult['FORM_GUID'] = CTasksTools::genUuid();

$arResult["USER_FIELDS"] = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("TASKS_TASK", $arParams["TASK_ID"] ? $arParams["TASK_ID"] : 0, LANGUAGE_ID);

//Form submitted
$arResult['needStep'] = false;
if($_SERVER["REQUEST_METHOD"] == "POST" && check_bitrix_sessid() && ($arResult["ACTION"] == "create" || $arResult["ACTION"] == "edit"))
{
	if ( ! function_exists('lambda_sgkrg455d_funcCreateSubtasks') )
	{
		function lambda_sgkrg455d_funcCreateSubtasks($arFields, $arAllResponsibles, $index, $loggedInUserId, $woStepper = false, $parameters = array())
		{
			$allResponsiblesCount = count($arAllResponsibles);
			$arResponsibles = array_slice($arAllResponsibles, $index);

			$cutoffTime = microtime(true) + 5;

			foreach($arResponsibles as $responsible)
			{
				$arFields['RESPONSIBLE_ID'] = $responsible;

				++$index;

				try
				{
					$arFieldsToSave = $arFields;

					// transform UF files
					if(is_array($arFieldsToSave['UF_TASK_WEBDAV_FILES']) && !empty($arFieldsToSave['UF_TASK_WEBDAV_FILES']) && \Bitrix\Main\Loader::includeModule('disk'))
					{
						// find which files are new and which are old
						$old = array();
						$new = array();
						foreach($arFieldsToSave['UF_TASK_WEBDAV_FILES'] as $fileId)
						{
							if((string) $fileId)
							{
								if(strpos($fileId, 'n') === 0)
									$new[] = $fileId;
								else
									$old[] = $fileId;
							}
						}

						if(!empty($old))
						{
							$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();
							$old = $userFieldManager->cloneUfValuesFromAttachedObject($old, $loggedInUserId);

							if(is_array($old) && !empty($old))
							{
								$new = array_merge($new, $old);
							}
						}

						$arFieldsToSave['UF_TASK_WEBDAV_FILES'] = $new;
					}

					$oTask = CTaskItem::add($arFieldsToSave, $loggedInUserId);

					// Save checklist data
					$GLOBALS['APPLICATION']->IncludeComponent(
						"bitrix:tasks.task.detail.parts",
						".default",
						array(
							'MODE'    => 'JUST AFTER TASK CREATED',
							'BLOCKS'  => array("checklist"),
							'TASK_ID' => (int) $oTask->getId()
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);

					// copy child tasks
					if($parameters['COPY_PARAMS']['COPY_CHILD_TASKS'] && intval($parameters['COPY_PARAMS']['ORIGIN_TASK']))
					{
						$parentTaskInstance = CTaskItem::getInstance(intval($parameters['COPY_PARAMS']['ORIGIN_TASK']), $loggedInUserId);
						$parentTaskInstance->duplicateChildTasks($oTask); // task access rights check inside
					}

					// create child tasks by child templates
					if($parameters['COPY_PARAMS']['COPY_CHILD_TEMPLATES'] && intval($parameters['COPY_PARAMS']['ORIGIN_TEMPLATE']))
					{
						// get template to ensure we own it...
						$templateData = CTaskTemplates::GetList(false, array('ID' => intval($parameters['COPY_PARAMS']['ORIGIN_TEMPLATE'])), false, array('USER_ID' => $loggedInUserId))->fetch();
						if(is_array($templateData))
						{
							$oTask->addChildTasksByTemplate(intval($parameters['COPY_PARAMS']['ORIGIN_TEMPLATE'])); // task access rights check inside
						}
					}
				}
				catch (Exception $e)
				{
				}

				// Timeout only if multistepper can be used
				if (
					( ! $woStepper )
					&& (microtime(true) > $cutoffTime)
				)
				{
					break;
				}
			}

			if ($woStepper)
				$needStep = false;
			else
			{
				$needStep = true;

				if ($index >= $allResponsiblesCount)
					$needStep = false;
			}

			return (array(
				$needStep,
				$index,
				$allResponsiblesCount
			));
		}
	}

	if (isset($_POST['FORM_GUID']))
		$arResult['PREV_FORM_GUID'] = $_POST['FORM_GUID'];

	if (
		isset($_POST['FORM_GUID'])
		&& isset($_POST['_JS_STEPPER_DO_NEXT_STEP'])
		&& ($_POST['_JS_STEPPER_DO_NEXT_STEP'] === 'Y')
	)
	{
		$arFields       = $_SESSION['TASKS']['EDIT_COMPONENT']['STEPPER'][$_POST['FORM_GUID']]['arFields'];
		$arResponsibles = $_SESSION['TASKS']['EDIT_COMPONENT']['STEPPER'][$_POST['FORM_GUID']]['RESPONSIBLES'];
		$index          = $_SESSION['TASKS']['EDIT_COMPONENT']['STEPPER'][$_POST['FORM_GUID']]['index'];
		$redirectPath   = $_SESSION['TASKS']['EDIT_COMPONENT']['STEPPER'][$_POST['FORM_GUID']]['redirectPath'];

		list(
			$arResult['needStep'],
			$arResult['stepIndex'],
			$arResult['stepIndexesTotal']
		) = lambda_sgkrg455d_funcCreateSubtasks($arFields, $arResponsibles, $index, $loggedInUserId);

		$_SESSION['TASKS']['EDIT_COMPONENT']['STEPPER'][$_POST['FORM_GUID']]['index'] = $arResult['stepIndex'];

		if ($arResult['needStep'])
		{
			if ($arResult['IS_IFRAME'])
				ShowInFrame($this);
			else
				$this->IncludeComponentTemplate();

			exit();
		}
		else
		{
			unset($_SESSION['TASKS']['EDIT_COMPONENT']['STEPPER'][$_POST['FORM_GUID']]);
			LocalRedirect($redirectPath);
		}
	}

	$_POST["WEEK_DAYS"] = explode(",", $_POST["WEEK_DAYS"]);

	// Prevent duplicated POSTs
	$bDuplicatePostRequest = false;
	$parentTaskGUID = false;
	if (
		isset($_POST['FORM_GUID'])
		&& ($arResult['ACTION'] === 'create')
		&& ($_POST["MULTITASK"] == "Y")
		&& (
			(
				isset($_POST['RESPONSIBLES_IDS'])
				&& strlen($_POST['RESPONSIBLES_IDS'])
			)
			||
			(
				isset($_POST['RESPONSIBLES'])
				&& (count($_POST["RESPONSIBLES"]) > 0)
			)
		)		
	)
	{
		$parentTaskGUID = $_POST['FORM_GUID'];
		$rs = CTasks::GetList(array(), array('GUID' => $parentTaskGUID));
		if ($ar = $rs->Fetch())
			$bDuplicatePostRequest = true;
	}

	if (!$bDuplicatePostRequest)
	{
		$arResult['needStep'] = false;

		if (isset($_POST["save"]) || isset($_POST["apply"]))
		{
			if ($_POST["AJAX_POST"] == "Y")
				CUtil::decodeURIComponent($_POST);
			if (array_key_exists("ACCOMPLICES_IDS", $_POST))
				$_POST["ACCOMPLICES"] = array_filter(explode(",", $_POST["ACCOMPLICES_IDS"]));
			if (array_key_exists("RESPONSIBLES_IDS", $_POST))
				$_POST["RESPONSIBLES"] = array_filter(explode(",", $_POST["RESPONSIBLES_IDS"]));
			if (array_key_exists("PREV_TASKS_IDS", $_POST))
				$_POST["DEPENDS_ON"] = array_filter(explode(",", $_POST["PREV_TASKS_IDS"]));
			if (array_key_exists("REPLICATE_WEEK_DAYS", $_POST) && is_string($_POST["REPLICATE_WEEK_DAYS"]))
				$_POST["REPLICATE_WEEK_DAYS"] = array_filter(explode(",", $_POST["REPLICATE_WEEK_DAYS"]));

			$timeEstimate = 0;
			if (isset($_POST['ESTIMATE']))
				$timeEstimate = (int) $_POST['ESTIMATE'];
			else
			{
				if (isset($_POST['ESTIMATE_HOURS']))
					$timeEstimate += 3600 * (int) $_POST['ESTIMATE_HOURS'];

				if (isset($_POST['ESTIMATE_MINUTES']))
					$timeEstimate += 60 * (int) $_POST['ESTIMATE_MINUTES'];
			}
			$timeEstimate = max(0, $timeEstimate);

			$arFields = array(
				"TITLE" => trim($_POST["TITLE"]),
				"DESCRIPTION" => $_POST["DESCRIPTION"],
				"DEADLINE" => ConvertDateTime($_POST["DEADLINE"]),
				'TIME_ESTIMATE' => $timeEstimate,
				'ALLOW_TIME_TRACKING' => isset($_POST["ALLOW_TIME_TRACKING"]) ? "Y" : "N",
				"START_DATE_PLAN" => ConvertDateTime($_POST["START_DATE_PLAN"]),
				"END_DATE_PLAN" => ConvertDateTime($_POST["END_DATE_PLAN"]),
				"DURATION_PLAN" => $_POST["DURATION_PLAN"],
				"DURATION_TYPE" => $_POST["DURATION_TYPE"],
				"PRIORITY" => $_POST["PRIORITY"],
				"ACCOMPLICES" => $_POST["ACCOMPLICES"],
				"AUDITORS" => sizeof($_POST["AUDITORS"]) > 0 ? array_filter($_POST["AUDITORS"]) : array(),
				"TAGS" => $_POST["TAGS"],
				"ALLOW_CHANGE_DEADLINE" => isset($_POST["ALLOW_CHANGE_DEADLINE"]) ? "Y" : "N",
				"MATCH_WORK_TIME" => isset($_POST["MATCH_WORK_TIME"]) ? "Y" : "N",
				"TASK_CONTROL" => isset($_POST["TASK_CONTROL"]) ? "Y" : "N",
				"ADD_IN_REPORT" => isset($_POST["ADD_IN_REPORT"]) ? "Y" : "N",
				"FILES" => $_POST["FILES"] ? $_POST["FILES"] : array(),
				"PARENT_ID" => intval($_POST["PARENT_ID"]) > 0 ? intval($_POST["PARENT_ID"]) : false,
				"DEPENDS_ON" => $_POST["DEPENDS_ON"],
				"REPLICATE" => isset($_POST["REPLICATE"]) ? "Y" : "N",
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"]
			);

			if (
				isset($_POST['DESCRIPTION_IN_BBCODE'])
				&& in_array($_POST['DESCRIPTION_IN_BBCODE'], array('Y', 'N'))
			)
			{
				$arFields['DESCRIPTION_IN_BBCODE'] = $_POST['DESCRIPTION_IN_BBCODE'];
			}
			else
				$arFields['DESCRIPTION_IN_BBCODE'] = 'N';	// for compatibility

			if (isset($_POST["GROUP_ID"]))
			{
				if (($groupID = intval($_POST["GROUP_ID"])) > 0)
				{
					if (CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $groupID, "tasks", "create_tasks"))
					{
						$arFields["GROUP_ID"] = $groupID;
					}
				}
				else
				{
					$arFields["GROUP_ID"] = false;
				}
			}

			$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields('TASKS_TASK', $arFields);

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

			$arFields["REPLICATE_PARAMS"] = array();
			foreach ($_POST as $field=>$value)
			{
				if (substr($field, 0, 10) == "REPLICATE_") // parameters of replication
				{
					$arFields["REPLICATE_PARAMS"][substr($field, 10)] = substr($field, -5) == "_DATE" ?  ConvertDateTime($value) : $value;
				}
			}

			$arChecklistItems = array();
			$arResult["ERRORS"] = array();
			if ($arResult["ACTION"] == "edit")
			{
				$arFields["RESPONSIBLE_ID"] = $_POST["RESPONSIBLE_ID"];

				try
				{
					$oTask = CTaskItem::getInstanceFromPool($arParams['TASK_ID'], $loggedInUserId);

					if ($oTask->isActionAllowed(CTaskItem::ACTION_CHANGE_DIRECTOR) && isset($_POST['CREATED_BY']))
						$arFields["CREATED_BY"] = $_POST['CREATED_BY'];

					// Save checklist data
					$arChecklistItems = $APPLICATION->IncludeComponent(
						"bitrix:tasks.task.detail.parts",
						".default",
						array(
							'MODE'    => 'JUST AFTER TASK EDITED',
							'BLOCKS'  => array("checklist"),
							'TASK_ID' => (int) $arParams['TASK_ID']
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);

					$oTask->update($arFields);
				}
				catch (Exception $e)
				{
					if ($e->GetCode() & TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE)
						$arResult['ERRORS'] = unserialize($e->GetMessage());
					else
					{
						$arResult['ERRORS'][] = array(
							'text' => 'UNKNOWN ERROR OCCURED',
							'id'   => 'ERROR_TASKS_UNKNOWN'
						);
					}
				}

				$taskID = $arParams['TASK_ID'];
			}
			else
			{
				$arSectionIDs = CTasks::GetSubordinateDeps();

				if ($_POST["MULTITASK"] == "Y" && sizeof($_POST["RESPONSIBLES"]) > 0)
				{
					$arFields["MULTITASK"] = "Y";
					$arFields["RESPONSIBLE_ID"] = $loggedInUserId;

					// only admin can set CREATED_BY to smth that differs from $GLOBALS['USER']->GetId()
					if ($USER->IsAdmin() || CTasksTools::IsPortalB24Admin())
						$arFields["CREATED_BY"] = $_POST["CREATED_BY"];
				}
				else
				{
					$arFields["MULTITASK"] = "N";

					// why there is no restriction on CREATED_BY?
					$arFields["CREATED_BY"] = $_POST["CREATED_BY"];

					if ($arFields["CREATED_BY"] != $loggedInUserId
						&& !$USER->IsAdmin()
						&& !CTasksTools::IsPortalB24Admin()
					)
					{
						$arFields["RESPONSIBLE_ID"] = $loggedInUserId;
					}
					else
					{
						$arFields["RESPONSIBLE_ID"] = $_POST["RESPONSIBLE_ID"];
					}
				}

				$arFields["SITE_ID"] = SITE_ID;

				$arFieldsToSave = $arFields;

				// transform UF files
				if(is_array($arFieldsToSave['UF_TASK_WEBDAV_FILES']) && !empty($arFieldsToSave['UF_TASK_WEBDAV_FILES']) && \Bitrix\Main\Loader::includeModule('disk'))
				{
					// find which files are new and which are old
					$old = array();
					$new = array();
					foreach($arFieldsToSave['UF_TASK_WEBDAV_FILES'] as $fileId)
					{
						if((string) $fileId)
						{
							if(strpos($fileId, 'n') === 0)
								$new[] = $fileId;
							else
								$old[] = $fileId;
						}
					}

					if(!empty($old))
					{
						$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();
						$old = $userFieldManager->cloneUfValuesFromAttachedObject($old, $loggedInUserId);

						if(is_array($old) && !empty($old))
						{
							$new = array_merge($new, $old);
						}
					}

					$arFieldsToSave['UF_TASK_WEBDAV_FILES'] = $new;
				}

				try
				{
					if (($arFields["MULTITASK"] == "Y") && ($parentTaskGUID !== false))
						$arFieldsToSave['GUID'] = $parentTaskGUID;

					$oTask = CTaskItem::add($arFieldsToSave, $loggedInUserId);
					$taskID = $oTask->getId();
				}
				catch (Exception $e)
				{
					$taskID = false;

					if ($e->GetCode() & TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE)
						$arResult['ERRORS'] = unserialize($e->GetMessage());
					else
					{
						$arResult['ERRORS'][] = array(
							'text' => 'UNKNOWN ERROR OCCURED',
							'id'   => 'ERROR_TASKS_UNKNOWN'
						);
					}
				}

				$arTemplateFields = $arFields;
				$arTemplateFields['CREATED_BY'] = $_POST["CREATED_BY"];

				if ($arTemplateFields["MULTITASK"] == "Y")
				{
					$arTemplateFields["RESPONSIBLES"] = serialize($_POST["RESPONSIBLES"]);
				}

				// Save checklist data
				$arChecklistItems = $APPLICATION->IncludeComponent(
					"bitrix:tasks.task.detail.parts",
					".default",
					array(
						'MODE'    => 'JUST AFTER TASK CREATED',
						'BLOCKS'  => array("checklist"),
						'TASK_ID' => (int) $taskID
					),
					null,
					array('HIDE_ICONS' => 'Y')
				);

				if ($taskID)
				{
					if(isset($arResult['COPY_PARAMS']))
					{
						try
						{
							// copy child tasks
							if($arResult['COPY_PARAMS']['COPY_CHILD_TASKS'] && intval($arResult['COPY_PARAMS']['ORIGIN_TASK']))
							{
								$parentTaskInstance = new CTaskItem(intval($arResult['COPY_PARAMS']['ORIGIN_TASK']), $loggedInUserId);
								$parentTaskInstance->duplicateChildTasks($oTask);
							}

							// create child tasks by child templates
							if($arResult['COPY_PARAMS']['COPY_CHILD_TEMPLATES'] && intval($arResult['COPY_PARAMS']['ORIGIN_TEMPLATE']))
							{
								// get template to ensure we own it...
								$templateData = CTaskTemplates::GetList(false, array('ID' => intval($arResult['COPY_PARAMS']['ORIGIN_TEMPLATE'])), false, array('USER_ID' => $loggedInUserId), array('ID'))->fetch();
								if(is_array($templateData))
								{
									$oTask->addChildTasksByTemplate(intval($arResult['COPY_PARAMS']['ORIGIN_TEMPLATE'])); // task access rights check inside
								}
							}
						}
						catch (Exception $e)
						{
							if ($e->GetCode() & TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE)
								$arResult['ERRORS'] = unserialize($e->GetMessage());
							else
							{
								$arResult['ERRORS'][] = array(
									'text' => 'UNKNOWN ERROR OCCURED',
									'id'   => 'ERROR_TASKS_UNKNOWN'
								);
							}
						}
					}

					if ($_POST["ADD_TO_TIMEMAN"] == "Y")
					{
						CTaskPlannerMaintance::plannerActions(array('add' => array($taskID)));
					}

					if ($arFields["REPLICATE"] == "Y")
					{
						unset(
							$arTemplateFields["DEADLINE"],
							$arTemplateFields["START_DATE_PLAN"],
							$arTemplateFields["END_DATE_PLAN"]
						);

						$arTemplateFields["TASK_ID"] = $taskID;
						$arTemplateFields["ACCOMPLICES"] = sizeof($arTemplateFields["ACCOMPLICES"]) ?  serialize($arTemplateFields["ACCOMPLICES"]) : false;
						$arTemplateFields["AUDITORS"] = sizeof($arTemplateFields["AUDITORS"]) ?  serialize($arTemplateFields["AUDITORS"]) : false;
						$arTemplateFields["TAGS"] = strlen(trim($arTemplateFields["TAGS"])) > 0 ?  serialize(explode(",", $arTemplateFields["TAGS"])) : false;
						$arTemplateFields["FILES"] = sizeof($arTemplateFields["FILES"]) ?  serialize($arTemplateFields["FILES"]) : false;
						$arTemplateFields["DEPENDS_ON"] = sizeof($arTemplateFields["DEPENDS_ON"]) ?  serialize($arTemplateFields["DEPENDS_ON"]) : false;
						$arTemplateFields["REPLICATE_PARAMS"] = serialize($arTemplateFields["REPLICATE_PARAMS"]);

						$taskTemplate = new CTaskTemplates();
						$templateId = $taskTemplate->Add($arTemplateFields, array('CHECK_RIGHTS_ON_FILES' => 'Y', 'USER_ID' => $USER->getId()));

						if(intval($templateId))
						{
							// checklist
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

								if(!empty($items))
								{
									// add\update check list items here
									try
									{
										\Bitrix\Tasks\Template\CheckListItemTable::updateForTemplate($templateId, $items);
									}
									catch(\Bitrix\Main\SystemException $e)
									{
									}
								}
							}
						}
					}

					$arFields["MULTITASK"] = $arFields["REPLICATE"] = "N";
					$arFields["PARENT_ID"] = $taskID;

					if (!is_array($arFields["ACCOMPLICES"]))
						$arFields["ACCOMPLICES"] = array();

					// Save TASK_CONTROL and ALLOW_TIME_TRACKING checkboxes states
					$arPopupOptions = CTasksTools::getPopupOptions();
					if (
						($arPopupOptions['time_tracking'] !== $arFields['ALLOW_TIME_TRACKING'])
						|| ($arPopupOptions['task_control'] !== $arFields['TASK_CONTROL'])
					)
					{
						$arPopupOptions['task_control']  = $arFields['TASK_CONTROL'];
						$arPopupOptions['time_tracking'] = $arFields['ALLOW_TIME_TRACKING'];
						CTasksTools::savePopupOptions($arPopupOptions);
					}

					if ($_POST["MULTITASK"] == "Y")
					{
						// If multistep supported and multitask creation in process, store data in $_SESSION
						$responsiblesCount = count($_POST['RESPONSIBLES']);
						if (
							isset($_POST['_JS_STEPPER_SUPPORTED'])
							&& ($_POST['_JS_STEPPER_SUPPORTED'] === 'Y')
							&& isset($_POST['FORM_GUID'])
							&& $responsiblesCount
						)
						{
							$_SESSION['TASKS']['EDIT_COMPONENT']['STEPPER'][$_POST['FORM_GUID']] = array(
								'arFields'     => $arFields,
								'RESPONSIBLES' => $_POST['RESPONSIBLES'],
								'index'        => 0
							);

							list(
								$arResult['needStep'],
								$arResult['stepIndex'],
								$arResult['stepIndexesTotal']
								) = lambda_sgkrg455d_funcCreateSubtasks($arFields, $_POST['RESPONSIBLES'], 0, $loggedInUserId, false, array('COPY_PARAMS' => $arResult['COPY_PARAMS']));

							$_SESSION['TASKS']['EDIT_COMPONENT']['STEPPER'][$_POST['FORM_GUID']]['index'] = $arResult['stepIndex'];
						}
						else
							lambda_sgkrg455d_funcCreateSubtasks($arFields, $_POST['RESPONSIBLES'], 0, $loggedInUserId, $woStepper = true, array('COPY_PARAMS' => $arResult['COPY_PARAMS']));
					}
				}
			}

			if (sizeof($arResult["ERRORS"]) == 0)
			{
				if (is_array($arFields["FILES"]) && count($arFields["FILES"]))
				{
					foreach ($arFields["FILES"] as $fileId)
						CTaskFiles::removeTemporaryFile($loggedInUserId, (int) $fileId);
				}

				if (sizeof($_POST["REMINDERS"]))
				{
					if ($arResult["ACTION"] == "edit")
					{
						CTaskReminders::Delete(array(
							"TASK_ID" => $taskID,
							"USER_ID" => $loggedInUserId
						));
					}
					$obReminder = new CTaskReminders();
					foreach($_POST["REMINDERS"] as $reminder)
					{
						$arReminderFields = array(
							"TASK_ID" => $taskID,
							"USER_ID" => $loggedInUserId,
							"REMIND_DATE" => $reminder["date"],
							"TYPE" => $reminder["type"],
							"TRANSPORT" => $reminder["transport"]
						);
						$obReminder->Add($arReminderFields);
					}
				}
				if ($arResult["ACTION"] == "create" && $_POST["apply"] == "save_and_back")
				{
					$redirectPath = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"));
					if ($arResult["IS_IFRAME"])
					{
						$redirectPath .= (strpos($redirectPath, "?") === false ? "?" :  "&")."IFRAME=Y";
						$redirectPath .= "&CALLBACK=".($arResult["ACTION"] == "edit" ? "CHANGED" : "ADDED");
						$redirectPath .= "&TASK_ID=" . $taskID;

						if (isset($_POST['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE']) && ($_POST['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE'] !== ''))
							$redirectPath .= '&TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE=' . $_POST['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE'];
					}
				}
				elseif (
					($arResult['ACTION'] === 'create')
					&& ($_POST['apply'] === 'save_and_create_new')
				)
				{
					$redirectPath = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_TASKS_TASK'], array('task_id' => 0, 'action' => 'edit'));
					if ($arResult['IS_IFRAME'])
					{
						if (strpos($redirectPath, '?') === false)
							$redirectPath .= '?';
						else
							$redirectPath .= '&';

						$redirectPath .= 'IFRAME=Y';
					}

					if (isset($_GET['PARENT_ID']))
						$redirectPath .= (strpos($redirectPath, "?") === false ? "?" :  "&") . "PARENT_ID=" . (int) $_GET['PARENT_ID'];

					if (isset($arFields['GROUP_ID']) && $arFields['GROUP_ID'])
						$redirectPath .= (strpos($redirectPath, "?") === false ? "?" :  "&") . "GROUP_ID=" . (int) $arFields['GROUP_ID'];
				}
				elseif (strlen($arResult["RETURN_URL"]) > 0)
				{
					$redirectPath = $arResult["RETURN_URL"];
				}
				else
				{
					$redirectPath = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => $taskID, "action" => "view"));
					if ($arResult["IS_IFRAME"])
					{
						$redirectPath .= (strpos($redirectPath, "?") === false ? "?" :  "&")."IFRAME=Y";
						$redirectPath .= "&CALLBACK=".($arResult["ACTION"] == "edit" ? "CHANGED" : "ADDED");

						if (isset($_POST['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE']) && ($_POST['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE'] !== ''))
							$redirectPath .= '&TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE=' . $_POST['TOP_FRAME_COLUMNS_IDS_DURING_ADD_UPDATE'];
					}
				}

				// break execution here, template will resend POST-request to current page
				// for next step of subtasks creation
				if ($arResult['needStep'])
				{
					$_SESSION['TASKS']['EDIT_COMPONENT']['STEPPER'][$_POST['FORM_GUID']]['redirectPath'] = $redirectPath;

					if ($arResult['IS_IFRAME'])
						ShowInFrame($this);
					else
						$this->IncludeComponentTemplate();

					exit();
				}

				LocalRedirect($redirectPath);
			}
			else
			{
				$arResult["bVarsFromForm"] = true;
				$arData = $_POST;
				$arData['CHECKLIST_ITEMS'] = $arChecklistItems;
			}
		}
	}
	else
	{
		$arResult['ERRORS'] = array();
		$arResult['ERRORS'][] = array(
			'text' => 'Duplicate POST-request',
			'id'   => 'ERROR_TASKS_DUPLICATE_POST_REQUEST'
		);

		$arResult["bVarsFromForm"] = true;
		$arData = $_POST;
	}
}
else
{
	if (isset($arResult["CALLBACK"]) && $arResult["CALLBACK"] && intval($_GET["TASK_ID"]) > 0)
	{
		$rsTask = CTasks::GetByID(intval($_GET["TASK_ID"]));
		if ($callbackTask = $rsTask->GetNext())
		{
			$arResult["TASK"] = $callbackTask;
			$rsChildrenCount = CTasks::GetChildrenCount(array(), ($arResult["TASK"]["ID"]));
			if ($arChildrenCount = $rsChildrenCount->Fetch())
			{
				$arResult["TASK"]["CHILDREN_COUNT"] = $arChildrenCount["CNT"];
			}
			$rsTaskFiles = CTaskFiles::GetList(array(), array("TASK_ID" => $arResult["TASK"]["ID"]));
			$arResult["TASK"]["FILES"] = array();
			while ($arTaskFile = $rsTaskFiles->Fetch())
			{
				$rsFile = CFile::GetByID($arTaskFile["FILE_ID"]);
				if ($arFile = $rsFile->Fetch())
				{
					$arResult["TASK"]["FILES"][] = $arFile;
				}
			}
		}
	}
	if ($arResult["ACTION"] == "edit")
	{
		$arData = $arTask;
		$arData["DESCRIPTION"] = $arData["~DESCRIPTION"];
		$arData["CREATED_BY_NAME"] = $arData["~CREATED_BY_NAME"];
		$arData["CREATED_BY_LAST_NAME"] = $arData["~CREATED_BY_LAST_NAME"];
		$arData["CREATED_BY_SECOND_NAME"] = $arData["~CREATED_BY_SECOND_NAME"];
		$arData["CREATED_BY_LOGIN"] = $arData["~CREATED_BY_LOGIN"];

		// reminders
		$arData["REMINDERS"] = array();
		$rsReminders = CTaskReminders::GetList(array("date" => "asc"), array("USER_ID" => $loggedInUserId, "TASK_ID" => $arTask["ID"]));
		while($arReminder = $rsReminders->Fetch())
		{
			$arData["REMINDERS"][] = array(
				"date" => $arReminder["REMIND_DATE"],
				"type" => $arReminder["TYPE"],
				"transport" => $arReminder["TRANSPORT"]
			);
		}

		// checklist items
		$arData['CHECKLIST_ITEMS'] = array();

		list($arChecklistItems, $arMetaData) = CTaskCheckListItem::fetchList($oTask, array('SORT_INDEX' => 'ASC'));
		unset($arMetaData);

		foreach ($arChecklistItems as $oChecklistItem)
		{
			$checklistItemId = $oChecklistItem->getId();
			$arData['CHECKLIST_ITEMS'][$checklistItemId] = $oChecklistItem->getData();
			$arData['CHECKLIST_ITEMS'][$checklistItemId]['META:CAN_MODIFY'] = $oChecklistItem->isActionAllowed(CTaskCheckListItem::ACTION_MODIFY);
			$arData['CHECKLIST_ITEMS'][$checklistItemId]['META:CAN_REMOVE'] = $oChecklistItem->isActionAllowed(CTaskCheckListItem::ACTION_REMOVE);
			$arData['CHECKLIST_ITEMS'][$checklistItemId]['META:CAN_TOGGLE'] = $oChecklistItem->isActionAllowed(CTaskCheckListItem::ACTION_TOGGLE);
		}
	}
	else	// case when $arResult['ACTION'] === 'create'
	{
		if (intval($_GET["TEMPLATE"]) > 0) // create task from a template
		{
			$rsTemplate = CTaskTemplates::GetByID(intval($_GET["TEMPLATE"]));
			if ($arTemplate = $rsTemplate->GetNext())
			{
				if ($arTemplate["CREATED_BY"] == $loggedInUserId)
				{
					if (isset($arTemplate["~DESCRIPTION_IN_BBCODE"]))
						$arTemplate["DESCRIPTION_IN_BBCODE"] = $arTemplate["~DESCRIPTION_IN_BBCODE"];

					$arTemplate["ACCOMPLICES"] = $arTemplate["~ACCOMPLICES"] ? unserialize($arTemplate["~ACCOMPLICES"]) : array();
					$arTemplate["AUDITORS"] = $arTemplate["~AUDITORS"] ? unserialize($arTemplate["~AUDITORS"]) : array();
					$arTemplate["RESPONSIBLES"] = $arTemplate["~RESPONSIBLES"] ? unserialize($arTemplate["~RESPONSIBLES"]) : array();
					$arTemplate["FILES"] = $arTemplate["~FILES"] ? unserialize($arTemplate["~FILES"]) : array();
					$arTemplate["TAGS"] = $arTemplate["~TAGS"] = $arTemplate["~TAGS"] ? unserialize($arTemplate["~TAGS"]) : "";
					$arTemplate["DEPENDS_ON"] = $arTemplate["~DEPENDS_ON"] ? unserialize($arTemplate["~DEPENDS_ON"]) : array();
					$arTemplate["DESCRIPTION"] = $arTemplate["~DESCRIPTION"];
					$arTemplate["CREATED_BY_NAME"] = $arTemplate["~CREATED_BY_NAME"];
					$arTemplate["CREATED_BY_LAST_NAME"] = $arTemplate["~CREATED_BY_LAST_NAME"];
					$arTemplate["CREATED_BY_SECOND_NAME"] = $arTemplate["~CREATED_BY_SECOND_NAME"];
					$arTemplate["CREATED_BY_LOGIN"] = $arTemplate["~CREATED_BY_LOGIN"];

					if ( ! empty($arTemplate["FILES"]) )
					{
						foreach($arTemplate["FILES"] as $key=>$file)
						{
							$newFile = CFile::CopyFile($file);
							if ($newFile > 0)
							{
								CTaskFiles::markFileTemporary($loggedInUserId, $newFile);
								$arTemplate["FILES"][$key] = $newFile;
							}
						}
					}

					$arTemplate["REPLICATE_PARAMS"] = unserialize($arTemplate["~REPLICATE_PARAMS"]);
					if(is_array($arTemplate["REPLICATE_PARAMS"]))
					{
						foreach($arTemplate["REPLICATE_PARAMS"] as $field=>$value)
						{
							$arTemplate["REPLICATE_".$field] = $value;
						}
					}

					if ($arTemplate["DEADLINE_AFTER"])
					{
						$deadlineAfter = $arTemplate["DEADLINE_AFTER"] / (24 * 60 * 60);
						$arTemplate["DEADLINE"] = date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")), strtotime(date("Y-m-d 00:00")." +".$deadlineAfter." days"));
					}

					// check list items
					$res = \Bitrix\Tasks\Template\CheckListItemTable::getList(array(
						'filter' => array('=TEMPLATE_ID' => intval($_GET["TEMPLATE"])),
						'select' => array('ID', 'TITLE', 'SORT', 'IS_COMPLETE'),
						'order' => array('SORT' => 'asc')
					));
					while($item = $res->fetch())
					{
						unset($item['ID']);
						$arTemplate['CHECKLIST_ITEMS'][] = $item;
					}

					$arResult['COPY_PARAMS']['ORIGIN_TEMPLATE'] = intval($_GET["TEMPLATE"]); // get template.php to know task being copied

					$arData = $arTemplate;

					// Remove replication data from task created by matrix
					// Due to http://jabber.bx/view.php?id=29556
					{
						$arData['REPLICATE']         = 'N';
						$arData['~REPLICATE']        = 'N';
						$arData['REPLICATE_PARAMS']  = array();
						$arData['~REPLICATE_PARAMS'] = array();

						foreach ($arData as $key => $value)
						{
							if (substr($key, 0, 10) === 'REPLICATE_')
								unset ($arData[$key]);
						}
					}
				}
			}
		}
		elseif (intval($_GET["COPY"]) > 0) // copy task
		{
			$rsCopy = CTasks::GetByID(intval($_GET["COPY"]));
			if ($arCopy = $rsCopy->GetNext())
			{
				if (isset($arCopy["~DESCRIPTION_IN_BBCODE"]))
					$arCopy["DESCRIPTION_IN_BBCODE"] = $arCopy["~DESCRIPTION_IN_BBCODE"];

				$arCopy["DESCRIPTION"] = $arCopy["~DESCRIPTION"];
				$arCopy["CREATED_BY_NAME"] = $arCopy["~CREATED_BY_NAME"];
				$arCopy["CREATED_BY_LAST_NAME"] = $arCopy["~CREATED_BY_LAST_NAME"];
				$arCopy["CREATED_BY_SECOND_NAME"] = $arCopy["~CREATED_BY_SECOND_NAME"];
				$arCopy["CREATED_BY_LOGIN"] = $arCopy["~CREATED_BY_LOGIN"];
				$arCopy["MULTITASK"] = "N";

				if (sizeof($arCopy["FILES"]))
				{
					foreach($arCopy["FILES"] as $key=>$file)
					{
						$newFile = CFile::CopyFile($file);
						$arCopy["FILES"][$key] = $newFile;
					}
				}

				$arCopy['CHECKLIST_ITEMS'] = array();

				try
				{
					$oTask  = new CTaskItem((int) $_GET["COPY"], $loggedInUserId);
					list($arChecklistItems, $arMetaData) = CTaskCheckListItem::fetchList($oTask, array('SORT_INDEX' => 'ASC'));
					unset($arMetaData);

					foreach ($arChecklistItems as $oChecklistItem)
					{
						$checklistItemId = -1 * $oChecklistItem->getId();
						$arCopy['CHECKLIST_ITEMS'][$checklistItemId] = $oChecklistItem->getData();
						$arCopy['CHECKLIST_ITEMS'][$checklistItemId]['ID'] = $checklistItemId;
						$arCopy['CHECKLIST_ITEMS'][$checklistItemId]['~ID'] = $checklistItemId;
						$arCopy['CHECKLIST_ITEMS'][$checklistItemId]['META:CAN_MODIFY'] = $oChecklistItem->isActionAllowed(CTaskCheckListItem::ACTION_MODIFY);
						$arCopy['CHECKLIST_ITEMS'][$checklistItemId]['META:CAN_REMOVE'] = $oChecklistItem->isActionAllowed(CTaskCheckListItem::ACTION_REMOVE);
						$arCopy['CHECKLIST_ITEMS'][$checklistItemId]['META:CAN_TOGGLE'] = $oChecklistItem->isActionAllowed(CTaskCheckListItem::ACTION_TOGGLE);
					}
				}
				catch (Exception $e)
				{
					CTaskAssert::logError('[0xb490adaa] ');
					// We can't do anything good here.
					// Warning for user is useless, so don't generate it.
					// And it is better what we can do - let's continue work without checklist data
				}

				/*
				if (
					! isset($arCopy["PARENT_ID"]) 
					|| ( ! $arCopy["PARENT_ID"])
				)
				{
					$arCopy["PARENT_ID"] = intval($_GET["COPY"]);
				}
				*/

				$arResult['COPY_PARAMS']['ORIGIN_TASK'] = intval($_GET["COPY"]); // get template.php to know task being copied

				$arData = $arCopy;
			}
		}
		elseif (intval($_GET["PARENT_ID"]) > 0) // set parent
		{
			$rsParent = CTasks::GetByID(intval($_GET["PARENT_ID"]));
			if ($rsParent = $rsParent->GetNext())
			{
				$arData["GROUP_ID"] = $rsParent["GROUP_ID"];

				if (isset($rsParent["DESCRIPTION_IN_BBCODE"]))
					$arData["DESCRIPTION_IN_BBCODE"] = $rsParent["DESCRIPTION_IN_BBCODE"];
			}
		}

		$bNeedDecodeUtf8 = false;
		if (isset($_GET['UTF8encoded']) && (ToUpper(SITE_CHARSET) !== 'UTF-8'))
			$bNeedDecodeUtf8 = true;

		$arGotData = array();

		foreach($_GET as $key=>$val)
		{
			if ($key === 'UTF8encoded')
				continue;
			elseif ($key === 'ACCOMPLICES_IDS')
			{
				if (strlen($val))
					$arGotData['ACCOMPLICES'] = array_map('intval', explode(',', $val));
			}
			elseif ($key === 'AUDITORS_IDS')
			{
				if (strlen($val))
					$arGotData['AUDITORS'] = array_map('intval', explode(',', $val));
			}
			elseif ($key === 'UF_TASK_WEBDAV_FILES')
			{
				if (strlen($val))
				{
					// check file array
					$arGotData['UF_TASK_WEBDAV_FILES'] = array_filter(array_map(
						function($item)
						{
							if(preg_match('#^n?\d+$#', (string) $item))
								return $item;

							return false;
						}, 
						explode(',', $val)
					));

					if (
						isset($arResult['USER_FIELDS']['UF_TASK_WEBDAV_FILES'])
						&& is_array($arGotData['UF_TASK_WEBDAV_FILES'])
						&& ( ! empty($arGotData['UF_TASK_WEBDAV_FILES']) )
					)
					{
						$arResult['USER_FIELDS']['UF_TASK_WEBDAV_FILES']['VALUE'] = $arGotData['UF_TASK_WEBDAV_FILES'];
					}
				}
			}
			elseif (!is_int($val))
			{
				if ($bNeedDecodeUtf8)
					$val = $APPLICATION->ConvertCharset($val, 'utf-8', SITE_CHARSET);

				// Description field always expected as unescaped, because of backward compatibility
				if ($key === 'DESCRIPTION')
					$arGotData[$key] = $val;
				else
					$arGotData[$key] = htmlspecialcharsbx($val);
			}
		}

		$arData = array_merge($arData, $arGotData);

		// left for compatibility
		foreach (array_keys($arData) as $fieldName)
		{
			if (substr($fieldName, 0, 3) === 'UF_')
			{
				$arResult["bVarsFromForm"] = true;
				break;
			}
		}
	}

	// use BB-code for new tasks (but still use HTML for task which created from template or other task with HTML description)
	if ($arResult["ACTION"] == "create")
	{
		if (isset($arData['DESCRIPTION_IN_BBCODE']) && ($arData['DESCRIPTION_IN_BBCODE'] === 'N'))
			$arData['DESCRIPTION_IN_BBCODE'] = 'N';
		else
			$arData['DESCRIPTION_IN_BBCODE'] = 'Y';
	}

	if ($arResult["TASK_TYPE"] == "group" && !isset($arData["GROUP_ID"]))
	{
		$arData["GROUP_ID"] = $arParams["GROUP_ID"];
	}

	if (!isset($arData["PRIORITY"]))
	{
		$arData["PRIORITY"] = 1;
	}
}

// override user fields
if(is_array($arResult['USER_FIELDS']))
{
	foreach($arResult['USER_FIELDS'] as $fld => &$fldData)
	{
		if(isset($arData[$fld]))
		{
			$fldData['VALUE'] = $arData[$fld];
		}
	}
}

if ($arData["RESPONSIBLE_ID"] && !$arData["RESPONSIBLE_NAME"] && !$arData["RESPONSIBLE_LAST_NAME"] && !$arData["RESPONSIBLE_LOGIN"])
{
	$rsResponsible = CUser::GetByID($arData["RESPONSIBLE_ID"]);
	if ($arResponsible = $rsResponsible->GetNext())
	{
		$arData["RESPONSIBLE_NAME"]        = $arResponsible["NAME"];
		$arData["RESPONSIBLE_LAST_NAME"]   = $arResponsible["LAST_NAME"];
		$arData["RESPONSIBLE_SECOND_NAME"] = $arResponsible["SECOND_NAME"];
		$arData["RESPONSIBLE_LOGIN"]       = $arResponsible["LOGIN"];

		$arData["~RESPONSIBLE_NAME"]        = $arResponsible["~NAME"];
		$arData["~RESPONSIBLE_LAST_NAME"]   = $arResponsible["~LAST_NAME"];
		$arData["~RESPONSIBLE_SECOND_NAME"] = $arResponsible["~SECOND_NAME"];
		$arData["~RESPONSIBLE_LOGIN"]       = $arResponsible["~LOGIN"];
	}
	else
	{
		unset($arData["RESPONSIBLE_ID"]);
	}
}

if ($arData["CREATED_BY"] && !$arData["CREATED_BY_NAME"] && !$arData["CREATED_BY_LAST_NAME"] && !$arData["CREATED_BY_LOGIN"])
{
	$rsAuthor = CUser::GetByID($arData["CREATED_BY"]);
	if ($arAuthor = $rsAuthor->Fetch())
	{
		$arData["CREATED_BY_NAME"] = $arAuthor["NAME"];
		$arData["CREATED_BY_LAST_NAME"] = $arAuthor["LAST_NAME"];
		$arData["CREATED_BY_SECOND_NAME"] = $arAuthor["SECOND_NAME"];
		$arData["CREATED_BY_LOGIN"] = $arAuthor["LOGIN"];
	}
	else
	{
		unset($arData["CREATED_BY"]);
	}
}

if (!$arData["RESPONSIBLE_ID"])
{
	if (($arData["CREATED_BY"] && $arData["CREATED_BY"] != $loggedInUserId))
	{
		$arData["RESPONSIBLE_ID"]          = $loggedInUserId;
		$arData["RESPONSIBLE_NAME"]        = htmlspecialcharsbx($USER->GetFirstName());
		$arData["RESPONSIBLE_LAST_NAME"]   = htmlspecialcharsbx($USER->GetLastName());
		$arData["RESPONSIBLE_SECOND_NAME"] = htmlspecialcharsbx($USER->GetSecondName());
		$arData["RESPONSIBLE_LOGIN"]       = htmlspecialcharsbx($USER->GetLogin());

		$arData["~RESPONSIBLE_NAME"]        = $USER->GetFirstName();
		$arData["~RESPONSIBLE_LAST_NAME"]   = $USER->GetLastName();
		$arData["~RESPONSIBLE_SECOND_NAME"] = $USER->GetSecondName();
		$arData["~RESPONSIBLE_LOGIN"]       = $USER->GetLogin();
	}
	else
	{
		$arData["RESPONSIBLE_ID"]          = $arResult["USER"]["ID"];
		$arData["RESPONSIBLE_NAME"]        = $arResult["USER"]["NAME"];
		$arData["RESPONSIBLE_LAST_NAME"]   = $arResult["USER"]["LAST_NAME"];
		$arData["RESPONSIBLE_SECOND_NAME"] = $arResult["USER"]["SECOND_NAME"];
		$arData["RESPONSIBLE_LOGIN"]       = $arResult["USER"]["LOGIN"];

		$arData["~RESPONSIBLE_NAME"]        = $arResult["USER"]["~NAME"];
		$arData["~RESPONSIBLE_LAST_NAME"]   = $arResult["USER"]["~LAST_NAME"];
		$arData["~RESPONSIBLE_SECOND_NAME"] = $arResult["USER"]["~SECOND_NAME"];
		$arData["~RESPONSIBLE_LOGIN"]       = $arResult["USER"]["~LOGIN"];
	}
}
if (!$arData["CREATED_BY"])
{
	$arData["CREATED_BY"] = $loggedInUserId;
	$arData["CREATED_BY_NAME"] = $USER->GetFirstName();
	$arData["CREATED_BY_LAST_NAME"] = $USER->GetLastName();
	$arData["CREATED_BY_SECOND_NAME"] = $USER->GetSecondName();
	$arData["CREATED_BY_LOGIN"] = $USER->GetLogin();
}

// HTML-format must be supported in future, because old tasks' data not converted from HTML to BB
$arData['~~DESCRIPTION'] = $arData['~DESCRIPTION'];
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

if (!isset($arData['CHECKLIST_ITEMS']))
	$arData['CHECKLIST_ITEMS'] = array();

$arResult["DATA"] = $arData;
if (isset($arData['TIME_ESTIMATE']) && ($arData['TIME_ESTIMATE'] > 0))
{
	$arResult['ESTIMATE_HOURS']   = (int) floor($arData['TIME_ESTIMATE'] / 3600);
	$arResult['ESTIMATE_MINUTES'] = str_pad((int) ($arData['TIME_ESTIMATE'] - $arResult['ESTIMATE_HOURS'] * 3600) / 60, 2, '0', STR_PAD_LEFT);
}
else
{
	$arResult['ESTIMATE_HOURS']   = '';
	$arResult['ESTIMATE_MINUTES'] = '';
}

// groups
$rsGroups = CSocNetGroup::GetList(array("NAME" => "ASC"), array("SITE_ID" => SITE_ID));
$arResult["GROUPS"] = array();
$groupIDs = array();
while($group = $rsGroups->GetNext())
{
	$arResult["GROUPS"][] = $group;
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
$arResult['META:ENVIRONMENT'] = array(
	'TIMEMAN_AVAILABLE' => (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite())
);
if ($arResult["ACTION"] == "edit")
{
	$sTitle = str_replace("#TASK_ID#", $arParams["TASK_ID"], GetMessage("TASKS_TITLE_EDIT_TASK"));
}
else
{
	$sTitle = GetMessage("TASKS_TITLE_CREATE_TASK");

}
if ($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle($sTitle);
}

if (!isset($arParams["SET_NAVCHAIN"]) || $arParams["SET_NAVCHAIN"] != "N")
{
	if ($taskType == "user")
	{
		$APPLICATION->AddChainItem(CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult["USER"]), CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_USER_PROFILE"], array("user_id" => $arParams["USER_ID"])));
		$APPLICATION->AddChainItem($sTitle);
	}
	else
	{
		$APPLICATION->AddChainItem($arResult["GROUP"]["NAME"], CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_GROUP"], array("group_id" => $arParams["GROUP_ID"])));
		$APPLICATION->AddChainItem($sTitle);
	}
}

if ($arResult['ACTION'] === 'create')
{
	$arPopupOptions = CTasksTools::getPopupOptions();
	$arResult['DATA']['TASK_CONTROL']        = $arPopupOptions['task_control'];
	$arResult['DATA']['ALLOW_TIME_TRACKING'] = $arPopupOptions['time_tracking'];
	$arResult['RECCOMEND_TASK_CONTROL']      = $arPopupOptions['task_control'];
}

$arResult['COMPANY_WORKTIME'] = array(
	'START' => array('H' => 9, 'M' => 0, 'S' => 0),
	'END' => array('H' => 19, 'M' => 0, 'S' => 0),
);
if(CModule::IncludeModule('calendar'))
{
	$calendarSettings = CCalendar::GetSettings(array('getDefaultForEmpty' => false));

	$time = explode('.', (string) $calendarSettings['work_time_start']);
	if(intval($time[0]))
		$arResult['COMPANY_WORKTIME']['START']['H'] = intval($time[0]);
	if(intval($time[1]))
		$arResult['COMPANY_WORKTIME']['START']['M'] = intval($time[1]);

	$time = explode('.', (string) $calendarSettings['work_time_end']);
	if(intval($time[0]))
		$arResult['COMPANY_WORKTIME']['END']['H'] = intval($time[0]);
	if(intval($time[1]))
		$arResult['COMPANY_WORKTIME']['END']['M'] = intval($time[1]);
}

if ($arResult["IS_IFRAME"])
{
	ShowInFrame($this);
}
else
{
	$this->IncludeComponentTemplate();
}
