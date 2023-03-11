<?php
/**
 * @var CUser $USER
 * @var CMain $APPLICATION
 * @var array $arResult
 */

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\Date;
use Bitrix\Tasks\Internals\Counter\CounterDictionary as TasksCounterDictionary;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
// region for
Main\Localization\Loc::loadMessages(__FILE__);
use \Bitrix\Intranet\UI\LeftMenu;
//Make some preparations. I do not know what it means.
if ($presetId = \CUserOptions::GetOption("intranet", "left_menu_preset"))
{
	\CUserOptions::SetOption("intranet", "left_menu_preset_".SITE_ID, $presetId);
	\CUserOptions::DeleteOption("intranet", "left_menu_preset", false, $USER->GetID());
}
//endregion

$defaultItems = $arResult;
$menuUser = new LeftMenu\User();
$menu = new LeftMenu\Menu($defaultItems, $menuUser);
$activePreset = LeftMenu\Preset\Manager::getPreset();
$menu->applyPreset($activePreset);

$arResult = [
	'IS_ADMIN' => $menuUser->isAdmin(),
	'IS_EXTRANET' => isModuleInstalled("extranet") && SITE_ID    == \COption::GetOptionString("extranet", "extranet_site"),
	'SHOW_PRESET_POPUP' => \COption::GetOptionString("intranet", "show_menu_preset_popup", "N") == "Y",
	'SHOW_SITEMAP_BUTTON' => false,
	'ITEMS' => [
		'show' => $menu->getVisibleItems(),
		'hide' => $menu->getHiddenItems()
	],
	'IS_CUSTOM_PRESET_AVAILABLE' => LeftMenu\Preset\Custom::isAvailable(),
	'CURRENT_PRESET_ID' => $activePreset->getCode(),
	'WORKGROUP_COUNTER_DATA' => [],
];

if ($arResult["IS_EXTRANET"] === false && count($defaultItems) > 0)
{
	$arResult['SHOW_SITEMAP_BUTTON'] = true;
}

if ($menuUser->isAdmin())
{
	$appImport = Option::get("rest", "import_configuration_app", '');
	if ($appImport != '')
	{
		try
		{
			$appList = \Bitrix\Main\Web\Json::decode($appImport);
			$app = array_shift($appList);
			if ($app && Main\Loader::includeModule('rest'))
			{
				$arResult["SHOW_IMPORT_CONFIGURATION"] = 'Y';
				$url = \Bitrix\Rest\Marketplace\Url::getConfigurationImportAppUrl($app);
				$uri = new Bitrix\Main\Web\Uri($url);
				$uri->addParams(
					[
						'create_install' => 'Y'
					]
				);
				$arResult['URL_IMPORT_CONFIGURATION'] = $uri->getUri();
			}
		}
		catch (\Bitrix\Main\ArgumentException $e)
		{
			Option::set("rest", "import_configuration_app", '');
		}
	}
}

$counters = \CUserCounter::GetValues($USER->GetID(), SITE_ID);
$counters = is_array($counters) ? $counters : [];

$workgroupCounterData = [
	'livefeed' => ($counters[\CUserCounter::LIVEFEED_CODE . 'SG0'] ?? ''),
];

if (Loader::includeModule('tasks'))
{
	$tasksCounterInstance = \Bitrix\Tasks\Internals\Counter::getInstance($USER->GetID());

	$workgroupCounterData[TasksCounterDictionary::COUNTER_PROJECTS_MAJOR] = (
		$tasksCounterInstance->get(TasksCounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS)
		+ $tasksCounterInstance->get(TasksCounterDictionary::COUNTER_PROJECTS_TOTAL_COMMENTS)
		+ $tasksCounterInstance->get(TasksCounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED)
		+ $tasksCounterInstance->get(TasksCounterDictionary::COUNTER_PROJECTS_TOTAL_EXPIRED)
	);

	$workgroupCounterData[TasksCounterDictionary::COUNTER_SCRUM_TOTAL_COMMENTS] = $tasksCounterInstance->get(TasksCounterDictionary::COUNTER_SCRUM_TOTAL_COMMENTS);
}

$counters['workgroups'] = array_sum($workgroupCounterData);

$arResult["COUNTERS"] = $counters;
$arResult['WORKGROUP_COUNTER_DATA'] = $workgroupCounterData;

$arResult["GROUPS"] = array();
if (!$arResult["IS_EXTRANET"] && $GLOBALS["USER"]->isAuthorized())
{
	$arResult["GROUPS"] = include(__DIR__."/groups.php");
}

$arResult["IS_PUBLIC_CONVERTED"] = file_exists($_SERVER["DOCUMENT_ROOT"].SITE_DIR."stream/");

//license button
$arResult["SHOW_LICENSE_BUTTON"] = false;
if (
	Main\Loader::includeModule('bitrix24')
	&& !(Main\Loader::includeModule("extranet") && CExtranet::IsExtranetSite())
)
{
	$licenseFamily = \CBitrix24::getLicenseFamily();
	if (!\CBitrix24::isMaximalLicense())
	{
		$arResult["SHOW_LICENSE_BUTTON"] = true;
		$arResult["B24_LICENSE_PATH"] = CBitrix24::PATH_LICENSE_ALL;
		$arResult["LICENSE_BUTTON_COUNTER_URL"] = CBitrix24::PATH_COUNTER;
		$arResult["HOST_NAME"] = defined('BX24_HOST_NAME')? BX24_HOST_NAME: SITE_SERVER_NAME;
		$arResult["IS_DEMO_LICENSE"] = \CBitrix24::getLicenseFamily() === "demo";
		$arResult["DEMO_DAYS"] = "";

		if ($arResult["IS_DEMO_LICENSE"])
		{
			$demoEnd = COption::GetOptionInt('main', '~controller_group_till');

			if ($demoEnd > 0)
			{
				$currentDate = (new Main\Type\DateTime)->getTimestamp();
				$timeUntilEndLicense = $demoEnd - $currentDate;

				if ($timeUntilEndLicense > 86400)
				{
					$arResult["DEMO_DAYS"] = FormatDate("ddiff", $currentDate, $demoEnd + 86400);
				}
				elseif ($timeUntilEndLicense === 86400)
				{
					$arResult["DEMO_DAYS"] = FormatDate("ddiff", $currentDate, $demoEnd);
				}
				elseif ($timeUntilEndLicense < 86400)
				{
					$arResult["DEMO_DAYS"] = Loc::getMessage('MENU_LICENSE_LESS_DAY');
				}
			}
		}
	}
}

$shouldShowWhatsNew = function() {
	if (Loader::includeModule('extranet') && \CExtranet::isExtranetSite())
	{
		return false;
	}

	if (\COption::getOptionString('intranet', 'new_portal_structure', 'N') === 'Y')
	{
		return false;
	}

	$option = \CUserOptions::getOption('intranet', 'left_menu_whats_new_dialog');
	if (isset($option['closed']) && $option['closed'] === 'Y')
	{
		return false;
	}

	$spotlight = new Main\UI\Spotlight('left_menu_whats_new_dialog');
	$spotlight->setUserTimeSpan(3600 * 24 * 7);
	if (ModuleManager::isModuleInstalled('bitrix24'))
	{
		$spotlight->setEndDate(gmmktime(8, 30, 0, 5, 10, 2022));
	}

	return $spotlight->isAvailable();
};

$arResult["SHOW_WHATS_NEW"] = $shouldShowWhatsNew();
