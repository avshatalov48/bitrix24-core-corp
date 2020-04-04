<?

use Bitrix\FaceId\FaceId;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/intranet/public_bitrix24/timeman/.left.menu_ext.php");

$licenseType = "";
if (Loader::includeModule("bitrix24"))
{
	$licenseType = CBitrix24::getLicenseType();
}

$isTimemanInstalled = ModuleManager::isModuleInstalled("timeman");

$aMenuLinks = [
	[
		Loc::getMessage("MENU_ABSENCE"),
		"/timeman/",
		[],
		["menu_item_id" => "menu_absence"],
		"",
	],
];

if (!(!$isTimemanInstalled && in_array($licenseType, ["company", "edu", "nfr"])))
{
	$aMenuLinks[] = [
		Loc::getMessage("MENU_TIMEMAN"),
		"/timeman/timeman.php",
		[],
		["menu_item_id" => "menu_timeman"],
		"",
	];
}

if (ModuleManager::isModuleInstalled("faceid") && Loader::includeModule('faceid') && FaceId::isAvailable())
{
	$aMenuLinks[] = [
		'Bitrix24.Time',
		"/timeman/bitrix24time.php",
		[],
		["menu_item_id" => "menu_bitrix24time"],
		"",
	];
}

if (!(!$isTimemanInstalled && in_array($licenseType, ["company", "edu", "nfr"])))
{
	$aMenuLinks[] = [
		Loc::getMessage("MENU_WORK_REPORT"),
		"/timeman/work_report.php",
		[],
		["menu_item_id" => "menu_work_report"],
		"",
	];
	if (Loader::includeModule('timeman'))
	{
		global $USER;
		$permissionsManager = \Bitrix\Timeman\Service\DependencyManager::getInstance()->getUserPermissionsManager($USER);
		if ($permissionsManager->canReadSchedules())
		{
			$aMenuLinks[] = [
				Loc::getMessage("MENU_SCHEDULES"),
				"/timeman/schedules/",
				[],
				["menu_item_id" => "menu_schedules_list"],
				"",
			];
		}

		if ($permissionsManager->canUpdateSettings())
		{
			$aMenuLinks[] = [
				Loc::getMessage("MENU_WORKTIME_SETTINGS_PERMISSIONS"),
				"/timeman/settings/permissions/",
				[],
				["menu_item_id" => "menu_worktime_settings_permissions"],
				"",
			];
		}
	}
}

if (!(!ModuleManager::isModuleInstalled("meeting") && in_array($licenseType, ["company", "edu", "nfr"])))
{
	$aMenuLinks[] = [
		Loc::getMessage("MENU_MEETING"),
		"/timeman/meeting/",
		[],
		["menu_item_id" => "menu_meeting"],
		"",
	];
}