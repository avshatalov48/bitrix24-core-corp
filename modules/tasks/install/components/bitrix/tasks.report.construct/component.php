<?php

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

$arResult['tasksReportEnabled'] = \Bitrix\Tasks\Integration\Bitrix24::checkFeatureEnabled(
	\Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary::TASK_REPORTS
);
$arResult['tasksReportFeatureId'] = \Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary::TASK_REPORTS;

$arResult['pathToTasks'] = str_replace(
	'#user_id#',
	$arParams['USER_ID'] ?? 0,
	$arParams['PATH_TO_USER_TASKS'] ?? ''
);

$this->IncludeComponentTemplate();
