<?php

use Bitrix\Tasks\Integration\Intranet\Settings;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$requiredModules = array('tasks', 'intranet', 'socialnetwork');

foreach ($requiredModules as $requiredModule)
{
	if (!CModule::IncludeModule($requiredModule))
	{
		ShowError(GetMessage("F_NO_MODULE"));
		return 0;
	}
}

// user path
$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"] ?? '');
if ($arParams["PATH_TO_USER"] == '')
{
	$arParams["PATH_TO_USER"] = COption::GetOptionString("tasks", "paths_task_user", null, SITE_ID);
	$arParams["PATH_TO_USER"] = preg_replace('/tasks\/$/', '', $arParams["PATH_TO_USER"]);
}
CTasksReportHelper::setPathToUser($arParams["PATH_TO_USER"]);

if (isset($arParams['USER_ID']) && ($arParams['USER_ID'] >= 1))
	$arResult['USER_ID'] = (int) $arParams['USER_ID'];
else
	$arResult['USER_ID'] = $USER->getId();

if ($arParams["NAME_TEMPLATE"] == '')
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();

$arResult['IS_HEAD_OF_DEPT'] = false;
$IBlockID = COption::GetOptionInt('intranet', 'iblock_structure', 0);
$userID = is_object($USER) ? intval($USER->GetID()) : 0;

if(CModule::IncludeModule('iblock'))
{
	$rsSections = CIBlockSection::GetList(array(), array("IBLOCK_ID" => $IBlockID, "UF_HEAD" => $userID, "ACTIVE" => "Y", "CHECK_PERMISSIONS" => "N"), false, array('UF_HEAD'));
	while ($arSection = $rsSections->Fetch())
	{
		$arResult['IS_HEAD_OF_DEPT'] = true;
		break;
	}
}

if (!isset($_GET['select_my_tasks']) && !isset($_GET['select_depts_tasks']) && !isset($_GET['select_group_tasks']))
{
	// tasks owners filter by default
	if (isset($arParams['GROUP_ID']))
	{
		// group tasks
		$_GET['select_group_tasks'] = 1;
	}
	else
	{
		// own tasks
		$_GET['select_my_tasks'] = 1;

		// depts tasks if head of dept
		if ($arResult['IS_HEAD_OF_DEPT'])
		{
			$_GET['select_depts_tasks'] = 1;
		}
	}
}

$arResult['IS_TOOL_AVAILABLE'] = (new Settings())->isToolAvailable(Settings::TOOLS['report']);

$arResult['tasksReportEnabled'] = \Bitrix\Tasks\Integration\Bitrix24::checkFeatureEnabled(
	\Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary::TASK_REPORTS
);
$arResult['tasksReportFeatureId'] = \Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary::TASK_REPORTS;

$arResult['pathToTasks'] = str_replace(
	'#user_id#',
	$arResult['USER_ID'],
	$arParams['PATH_TO_USER_TASKS'] ?? ''
);

$this->IncludeComponentTemplate();
