<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @global CMain $APPLICATION
 * @global CUserTypeManager $USER_FIELD_MANAGER
 * @param array $arParams
 * @param CBitrixComponent $this
 */

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_FOUND"));
	return;
}

$arParams["DEFER"]         = isset($arParams["DEFER"]) ? $arParams["DEFER"] : false;
$arParams["SITE_ID"]       = isset($arParams["SITE_ID"]) ? $arParams["SITE_ID"] : SITE_ID;
$arParams["PLAIN"]         = false; //isset($arParams["PLAIN"]) ? $arParams["PLAIN"] : false;
$arParams["TASK_ADDED"]    = isset($arParams["TASK_ADDED"]) ? $arParams["TASK_ADDED"] : false;
$arParams["NAME_TEMPLATE"] = $arParams["NAME_TEMPLATE"] ?  $arParams["NAME_TEMPLATE"] : CSite::GetNameFormat();

$arResult['LOGGED_IN_USER'] = $loggedInUser = $USER->getId();
$arResult['COLUMNS_IDS']    = array();

$arResult['IFRAME'] = null;
if (isset($arParams['IFRAME']))
	$arResult['IFRAME'] = $arParams['IFRAME'];

if (isset($arParams['COLUMNS_IDS']))
{
	$arResult['COLUMNS_IDS'] = $arParams['COLUMNS_IDS'];
}
elseif (isset($arParams['COLUMNS']))
{
	foreach ($arParams['COLUMNS'] as &$column)
	{
		$arResult['COLUMNS_IDS'][] = $column['ID'];
	}
	unset($column);
}
else	// for backward compatibility
{
	$arResult['COLUMNS_IDS'] = array(
		CTaskColumnList::COLUMN_TITLE, CTaskColumnList::COLUMN_DEADLINE,
		CTaskColumnList::COLUMN_RESPONSIBLE, CTaskColumnList::COLUMN_ORIGINATOR,
		CTaskColumnList::COLUMN_GRADE
	);
}

if(is_array($arParams['SYSTEM_COLUMN_IDS'] ?? null))
{
	$arResult['SYSTEM_COLUMN_IDS'] = $arParams['SYSTEM_COLUMN_IDS'];
}
else
{
	$arResult['SYSTEM_COLUMN_IDS'] = array(CTaskColumnList::SYS_COLUMN_CHECKBOX, CTaskColumnList::SYS_COLUMN_COMPLETE); // checkbox & complete columns
}

$oTimer = $arTimer = null;	// we will load timer data on demand, only once
$index = 0;
$arResult['ITEMS'] = array();

if(is_array($arParams['~DATA_COLLECTION']))
{
	foreach ($arParams['~DATA_COLLECTION'] as &$dataItem)
	{
		$arResult['ITEMS'][$index] = array(
			'TASK'             => $dataItem['TASK'],
			'CHILDREN_COUNT'   => $dataItem['CHILDREN_COUNT'],
			'DEPTH'            => isset($dataItem['DEPTH']) ? $dataItem['DEPTH'] : 0,
			'UPDATES_COUNT'    => isset($dataItem['UPDATES_COUNT']) ? $dataItem['UPDATES_COUNT'] : 0,
			'PROJECT_EXPANDED' => isset($dataItem['PROJECT_EXPANDED']) ? $dataItem['PROJECT_EXPANDED'] : true,
			'ALLOWED_ACTIONS'  => null
		);

		if (isset($dataItem['ALLOWED_ACTIONS']))
			$arResult['ITEMS'][$index]['ALLOWED_ACTIONS'] = $dataItem['ALLOWED_ACTIONS'];
		elseif (isset($dataItem['TASK']['META:ALLOWED_ACTIONS']))
			$arResult['ITEMS'][$index]['ALLOWED_ACTIONS'] = $dataItem['TASK']['META:ALLOWED_ACTIONS'];
		elseif ($dataItem['TASK']['ID'])
		{
			$oTask = CTaskItem::getInstanceFromPool($dataItem['TASK']['ID'], $loggedInUser);
			$arResult['ITEMS'][$index]['ALLOWED_ACTIONS'] = $oTask->getAllowedTaskActionsAsStrings();
		}

		if ($dataItem["TASK"]['ALLOW_TIME_TRACKING'] === 'Y')
		{
			if (
				($dataItem['TASK']['TIME_ESTIMATE'] > 0)
				&& ($arResult['ITEMS'][$index]['CURRENT_TASK_SPENT_TIME'] > $dataItem['TASK']['TIME_ESTIMATE']) // seems not to work
			)
			{
				$arResult['ITEMS'][$index]['TASK_TIMER_OVERDUE'] = 'Y';
			}
			else
				$arResult['ITEMS'][$index]['TASK_TIMER_OVERDUE'] = 'N';

			// Lazy load timer data only once
			if ($oTimer === null)
			{
				$oTimer = CTaskTimerManager::getInstance($loggedInUser);	
				$arTimer = $oTimer->getRunningTask(false);	// false => allow use static cache
				$arResult['TIMER'] = $arTimer;
			}

			if (
				($arTimer !== false)
				&& ($arTimer['TASK_ID'] == $dataItem['TASK']['ID'])
			)
			{
				$arResult['ITEMS'][$index]['CURRENT_TASK_TIMER_RUN_FOR_USER'] = 'Y';
			}
			else
				$arResult['ITEMS'][$index]['CURRENT_TASK_TIMER_RUN_FOR_USER'] = 'N';

			if (
				($arResult['ITEMS'][$index]['CURRENT_TASK_TIMER_RUN_FOR_USER'] === 'Y')
				|| $arResult['ITEMS'][$index]['ALLOWED_ACTIONS']['ACTION_START_TIME_TRACKING']
				|| ($arResult['ITEMS'][$index]['TASK']['ALLOW_TIME_TRACKING'] === 'Y')
			)
			{
				$arResult['ITEMS'][$index]['SHOW_TIMER_NODE'] = 'Y';
			}
			else
				$arResult['ITEMS'][$index]['SHOW_TIMER_NODE'] = 'N';
		}

		++$index;
	}
	unset($dataItem);
}

$this->IncludeComponentTemplate();
