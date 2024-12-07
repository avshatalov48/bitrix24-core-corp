<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\Localization\Loc;
//region TITLE
$APPLICATION->SetPageProperty("title", Loc::getMessage('TASKS_EFFECTIVE_TITLE_FULL'));
$APPLICATION->SetTitle(Loc::getMessage('TASKS_EFFECTIVE_TITLE_SHORT'));
//endregion TITLE

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(__DIR__.'/helper.php');
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

if($helper->checkHasFatals())
{
	return;
}

$arResult['JS_DATA']['show_sl_effective_more']= !CUserOptions::GetOption('spotlight', 'view_date_tasks_sl_effective_more', false);
$arResult['JS_DATA']['text_sl_effective_more']= GetMessage('TASKS_PANEL_TEXT_EFFECTIVE_MORE');
$arResult['JS_DATA']['taskLimitExceeded'] = $arResult['TASK_LIMIT_EXCEEDED'];
$arResult['JS_DATA']['tasksEfficiencyEnabled'] = $arResult['tasksEfficiencyEnabled'];
$arResult['JS_DATA']['pathToTasks'] = str_replace('#user_id#', $arParams['USER_ID'], $arParams['PATH_TO_USER_TASKS']);

$arResult['JS_DATA']['messages']['graph_title_kpi'] = GetMessageJS('TASKS_TITLE_GRAPH_KPI');
$arResult['JS_DATA']['messages']['no_data_text'] = GetMessageJS('TASKS_NO_DATA_TEXT');