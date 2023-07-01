<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa\Driver;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/rpa/.sub.menu_ext.php');

$rpaEnabled = Loader::includeModule('rpa') && Driver::getInstance()->isEnabled();
if (!$rpaEnabled)
{
	return;
}

$tasksCounter = 0;
$taskManager = Driver::getInstance()->getTaskManager();
if($taskManager)
{
	$tasksCounter = $taskManager->getUserTotalIncompleteCounter();
}

$aMenuLinks = [
	[
		Loc::getMessage('RPA_SUB_MENU_TASKS_2'),
		'/rpa/tasks/',
		[],
		[
			'menu_item_id' => 'rpa-top-panel-tasks',
			'counter_num' => $tasksCounter,
			'counter_id' => 'rpa_tasks',
		],
	],
	[
		Loc::getMessage('RPA_SUB_MENU_PROCESSES_1'),
		'/rpa/',
		[],
		[
			'menu_item_id' => 'rpa-top-panel-main-section',
		],
		'',
	],
];