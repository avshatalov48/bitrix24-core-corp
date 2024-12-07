<?php
/**
 * @var CUser $USER
 * @var CMain $APPLICATION
 * @var array $arResult
 */

use Bitrix\Main;
use \Bitrix\Intranet\UI\LeftMenu;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\Counter\CounterDictionary as TasksCounterDictionary;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Main\Localization\Loc::loadMessages(__FILE__);

$defaultItems = $arResult;
$menuUser = new LeftMenu\User();
$menu = new LeftMenu\Menu($defaultItems, $menuUser);
$activePreset = LeftMenu\Preset\Manager::getPreset();
$menu->applyPreset($activePreset);
$visibleItems = $menu->getVisibleItems();

$openItems = [];
$hiddenItems = [];
$isOpen = true;
foreach ($visibleItems as $item)
{
	if (isset($item['IS_GROUP']) && $item['IS_GROUP'] === 'Y')
	{
		continue;
	}

	if ($isOpen)
	{
		$openItems[] = $item;

		if (count($openItems) === 16)
		{
			$isOpen = false;
		}
	}
	else
	{
		$hiddenItems[] = $item;
	}
}

$arResult = [
	'ITEMS' => [
		'open' => $openItems,
		'hidden' => $hiddenItems,
	]
];

$counters = \CUserCounter::GetValues($USER->GetID(), SITE_ID);
$counters = is_array($counters) ? $counters : [];

$workgroupCounterData = [
	'livefeed' => (int)($counters[\CUserCounter::LIVEFEED_CODE . 'SG0'] ?? 0),
];

if (Loader::includeModule('tasks'))
{
	$tasksCounterInstance = \Bitrix\Tasks\Internals\Counter::getInstance($USER->GetID());

	$workgroupCounterData[TasksCounterDictionary::COUNTER_PROJECTS_MAJOR] = (int)(
		$tasksCounterInstance->get(TasksCounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS)
		+ $tasksCounterInstance->get(TasksCounterDictionary::COUNTER_PROJECTS_TOTAL_COMMENTS)
		+ $tasksCounterInstance->get(TasksCounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED)
		+ $tasksCounterInstance->get(TasksCounterDictionary::COUNTER_PROJECTS_TOTAL_EXPIRED)
	);

	$workgroupCounterData[TasksCounterDictionary::COUNTER_SCRUM_TOTAL_COMMENTS] = (int)$tasksCounterInstance->get(TasksCounterDictionary::COUNTER_SCRUM_TOTAL_COMMENTS);
}

$counters['workgroups'] = array_sum($workgroupCounterData);

$arResult["COUNTERS"] = $counters;
$arResult['WORKGROUP_COUNTER_DATA'] = $workgroupCounterData;