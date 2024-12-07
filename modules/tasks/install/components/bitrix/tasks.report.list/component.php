<?php

use Bitrix\Tasks\Integration\Intranet\Settings;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('tasks'))
{
	ShowError(GetMessage("F_NO_MODULE"));
	return 0;
}

if (isset($arParams['USER_ID']) && ($arParams['USER_ID'] >= 1))
{
	$arResult['USER_ID'] = (int)$arParams['USER_ID'];
}
else
{
	$arResult['USER_ID'] = $USER->getId();
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
