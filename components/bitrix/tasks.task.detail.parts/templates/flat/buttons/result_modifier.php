<?
use \Bitrix\Tasks\Util;
use \Bitrix\Tasks\UI;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$can =& $arParams["TASK"]["ACTION"];
$data =& $arParams["TASK"];
$taskId = intval($arParams["TASK"]["ID"]);

$data["TIME_ESTIMATE"] = intval($data["TIME_ESTIMATE"]);
$data["TIME_ELAPSED"] = intval($data["TIME_ELAPSED"]);

// urls
$arResult['VIEW_URL'] = UI\Task::makeActionUrl($arParams["PATH_TO_TASKS_TASK"], $taskId, 'view');
$arResult['EDIT_URL'] = UI\Task::makeActionUrl($arParams["PATH_TO_TASKS_TASK"], $taskId, 'edit');
$arResult['COPY_URL'] = UI\Task::makeActionUrl($arParams["PATH_TO_TASKS_TASK_COPY"], 0, 'edit');
$arResult['CREATE_SUBTASK_URL'] = UI\Task::makeActionUrl($arParams["PATH_TO_TASKS_TASK_CREATE_SUBTASK"], 0, 'edit');

$arResult['EDIT_URL'] = Util::replaceUrlParameters($arResult['EDIT_URL'], array(
	'BACKURL' => $arResult['VIEW_URL'],
	'SOURCE' => 'view',
), array(), array('encode' => true));
$arResult['COPY_URL'] = Util::replaceUrlParameters($arResult['COPY_URL'], array(
	'SOURCE' => 'view',
), array(), array('encode' => true));
$arResult['CREATE_SUBTASK_URL'] = Util::replaceUrlParameters($arResult['CREATE_SUBTASK_URL'], array(
	'BACKURL' => $arResult['VIEW_URL'],
	'SOURCE' => 'view',
), array(), array('encode' => true));

$classes = array();
if($can["DAYPLAN.TIMER.TOGGLE"])
{
	$classes[] = 'timer-visible';
	$classes[] = 'timer-'.($data["TIMER_IS_RUNNING_FOR_CURRENT_USER"] ? 'pause' : 'start');
}
else
{
	if ($data['ACTION']['PAUSE'])
	{
		$classes[] = 'pause';
	}
	elseif ($data['ACTION']['START'])
	{
		$classes[] = 'start';
	}
}

if ($can["COMPLETE"])
{
	$classes[] = 'complete';
}

if ($can["APPROVE"])
{
	$classes[] = 'approve';
}

if ($can["DISAPPROVE"])
{
	$classes[] = 'disapprove';
}

if ($can["EDIT"] && !$arParams["PUBLIC_MODE"])
{
	$classes[] = 'edit';
}

if ($data["TIME_ESTIMATE"] > 0 && $data["TIME_ELAPSED"] > $data["TIME_ESTIMATE"])
{
	$classes[] = 'timer-overtime';
}

if ($data['TIMER_IS_RUNNING_FOR_CURRENT_USER'])
{
	$classes[] = 'timer-running';
}

if (!$arParams["PUBLIC_MODE"] || $can["RENEW"])
{
	$classes[] = 'more-button';
}

$arResult['CLASSES'] = $classes;