<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_FOUND"));
	return;
}

$arResult['LOGGED_IN_USER'] = $USER->getId();

if (isset($arParams['~ALLOWED_ACTIONS']))
	$arResult['ALLOWED_ACTIONS'] = $arParams['~ALLOWED_ACTIONS'];
elseif (isset($arParams['~TASK']['META:ALLOWED_ACTIONS']))
	$arResult['ALLOWED_ACTIONS'] = $arParams['~TASK']['META:ALLOWED_ACTIONS'];
elseif ($arParams['~TASK']['ID'])
{
	$oTask = CTaskItem::getInstanceFromPool($arParams['~TASK']['ID'], $arResult['LOGGED_IN_USER']);
	$arResult['ALLOWED_ACTIONS'] = $oTask->getAllowedTaskActionsAsStrings();
	$arParams['~TASK']['META:ALLOWED_ACTIONS'] = $arResult['ALLOWED_ACTIONS'];
}

$arResult['IFRAME'] = null;
if (isset($arParams['IFRAME']))
	$arResult['IFRAME'] = $arParams['IFRAME'];

if ($arParams["~TASK"]['ALLOW_TIME_TRACKING'] === 'Y')
{
	if (
		($arParams['~TASK']['TIME_ESTIMATE'] > 0)
		&& ($arResult['CURRENT_TASK_SPENT_TIME'] > $arParams['~TASK']['TIME_ESTIMATE'])
	)
	{
		$arResult['TASK_TIMER_OVERDUE'] = 'Y';
	}
	else
		$arResult['TASK_TIMER_OVERDUE'] = 'N';

	$oTimer = CTaskTimerManager::getInstance($arResult['LOGGED_IN_USER']);	
	$arTimer = $oTimer->getRunningTask(false);	// false => allow use static cache
	if (
		($arTimer !== false)
		&& ($arTimer['TASK_ID'] == $arParams['~TASK']['ID'])
	)
	{
		$arResult['CURRENT_TASK_TIMER_RUN_FOR_USER'] = 'Y';
	}
	else
		$arResult['CURRENT_TASK_TIMER_RUN_FOR_USER'] = 'N';

	$arResult['TIMER'] = $arTimer;

	if (
		($arResult['CURRENT_TASK_TIMER_RUN_FOR_USER'] === 'Y')
		|| $arResult['ALLOWED_ACTIONS']['ACTION_START_TIME_TRACKING']
		|| ($arParams['~TASK']['ALLOW_TIME_TRACKING'] === 'Y')
	)
	{
		$arResult['SHOW_TIMER_NODE'] = 'Y';
	}
	else
		$arResult['SHOW_TIMER_NODE'] = 'N';
}

$this->IncludeComponentTemplate();
