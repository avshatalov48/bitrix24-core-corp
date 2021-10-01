<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\FaceId\FaceId;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Timeman\Monitor\Config;
use Bitrix\Bitrix24\Feature;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/timeman/.left.menu_ext.php');

$hasTimemanFeature = false;

if (Loader::includeModule('bitrix24'))
{
	$hasTimemanFeature = Feature::isFeatureEnabled('timeman');
}

$aMenuLinks = [
	[
		Loc::getMessage('MENU_ABSENCE'),
		'/timeman/',
		[],
		['menu_item_id' => 'menu_absence'],
		'',
	],
];

$workTimeUrl = 'https://helpdesk.bitrix24.ru/open/1429531/';

if ($hasTimemanFeature)
{
	$workTimeUrl = '/timeman/timeman.php';
}

$aMenuLinks[] = [
	Loc::getMessage('MENU_TIMEMAN'),
	$workTimeUrl,
	[],
	['menu_item_id' => 'menu_timeman'],
	'',
];

if (ModuleManager::isModuleInstalled('faceid') && Loader::includeModule('faceid') && FaceId::isAvailable())
{
	$aMenuLinks[] = [
		'Bitrix24.Time',
		'/timeman/bitrix24time.php',
		[],
		['menu_item_id' => 'menu_bitrix24time'],
		'',
	];
}

if (
	Loader::includeModule('timeman')
	&& class_exists('\Bitrix\Timeman\Monitor\Config')
	&& method_exists('\Bitrix\Timeman\Monitor\Config', 'isAvailable')
	&& Config::isAvailable()
)
{
	$aMenuLinks[] = [
		Loc::getMessage('MENU_MONITOR_REPORT'),
		'/timeman/monitor_report.php',
		[],
		['menu_item_id' => 'menu_pwt_report'],
		'',
	];
}

$workReportUrl = 'https://helpdesk.bitrix24.ru/open/5391955/';
$workSchedulesUrl = 'https://helpdesk.bitrix24.ru/open/9631501/';

$permissionsMenu = [];

if ($hasTimemanFeature)
{
	$workReportUrl = '/timeman/work_report.php';

	if (Loader::includeModule('timeman'))
	{
		global $USER;
		$permissionsManager = \Bitrix\Timeman\Service\DependencyManager::getInstance()
			->getUserPermissionsManager($USER)
		;

		if ($permissionsManager->canReadSchedules())
		{
			$workSchedulesUrl = '/timeman/schedules/';
		}

		if ($permissionsManager->canUpdateSettings())
		{
			$permissionsMenu = [
				Loc::getMessage('MENU_WORKTIME_SETTINGS_PERMISSIONS'),
				'/timeman/settings/permissions/',
				[],
				['menu_item_id' => 'menu_worktime_settings_permissions'],
				'',
			];
		}
	}
}

$aMenuLinks[] = [
	Loc::getMessage('MENU_WORK_REPORT'),
	$workReportUrl,
	[],
	['menu_item_id' => 'menu_work_report'],
	'',
];

$aMenuLinks[] = [
	Loc::getMessage('MENU_SCHEDULES'),
	$workSchedulesUrl,
	[],
	['menu_item_id' => 'menu_schedules_list'],
	'',
];

if ($permissionsMenu)
{
	$aMenuLinks[] = $permissionsMenu;
}

if (!(!ModuleManager::isModuleInstalled('meeting') && $hasTimemanFeature))
{
	$aMenuLinks[] = [
		Loc::getMessage('MENU_MEETING'),
		'/timeman/meeting/',
		[],
		['menu_item_id' => 'menu_meeting'],
		'',
	];
}