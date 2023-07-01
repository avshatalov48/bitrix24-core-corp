<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;


Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/bizproc/.left.menu_ext.php');

$tasksCount = (int)CUserCounter::getValue($GLOBALS['USER']->getID(), 'bp_tasks');
$aMenuLinks = [
	[
		GetMessage('MENU_BIZPROC_TASKS_1'),
		'/company/personal/bizproc/',
		[],
		[
			'counter_id' => 'bp_tasks',
			'counter_num' => $tasksCount,
			'menu_item_id' => 'menu_bizproc'
		],
		'',
	],
];

if (Loader::includeModule('lists') && CLists::isFeatureEnabled())
{
	$aMenuLinks[] = [
		GetMessage('MENU_PROCESS_STREAM2'),
		'/bizproc/processes/',
		[],
		['menu_item_id' => 'menu_processes'],
		'',
	];

	$aMenuLinks[] = [
		GetMessage('MENU_MY_PROCESS_1'),
		'/company/personal/processes/',
		[],
		['menu_item_id' => 'menu_my_processes'],
		'',
	];
}

$aMenuLinks[] = [
	GetMessage('MENU_BIZPROC_ACTIVE'),
	'/bizproc/bizproc/',
	[],
	['menu_item_id' => 'menu_bizproc_active'],
	'',
];

if (Loader::includeModule('crm'))
{
	$aMenuLinks[] = [
		GetMessage('MENU_BIZPROC_CRM'),
		'/crm/configs/bp/',
		[],
		['menu_item_id' => 'menu_bizproc_crm'],
		'',
	];
}

if (Loader::includeModule('disk'))
{
	$aMenuLinks[] = [
		GetMessage('MENU_BIZPROC_DISK'),
		'/docs/path/',
		[],
		['menu_item_id' => 'menu_bizproc_disk'],
		'',
	];
}
