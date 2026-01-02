<?php
/**
 * @var CUser $USER
 * @var CMain $APPLICATION
 * @var array $arResult
 */

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

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
$isCollaber = Loader::includeModule('extranet')
	&& \Bitrix\Extranet\Service\ServiceContainer::getInstance()->getCollaberService()->isCollaberById(\Bitrix\Intranet\CurrentUser::get()->getId());
$activePreset = LeftMenu\Preset\Manager::getPreset($isCollaber ? 'collab' : null);
$menu->applyPreset($activePreset);
$visibleItems = $menu->getVisibleItems();
$firstPageChanger = ToolsManager::getInstance()->getFirstPageChanger();

if ($firstPageChanger->checkNeedChanges())
{
	$firstPageChanger->changeForCurrentUser($visibleItems);
}

$arResult = [
	'IS_ADMIN' => $menuUser->isAdmin(),
	'IS_EXTRANET' => isModuleInstalled("extranet") && SITE_ID === \COption::GetOptionString("extranet", "extranet_site"),
	'SHOW_PRESET_POPUP' => \COption::GetOptionString("intranet", "show_menu_preset_popup", "N") == "Y" && $menuUser->isAdmin(),
	'SHOW_SITEMAP_BUTTON' => false,
	'ITEMS' => [
		'show' => $visibleItems,
		'hide' => $menu->getHiddenItems()
	],
	'IS_CUSTOM_PRESET_AVAILABLE' => LeftMenu\Preset\Custom::isAvailable(),
	'CURRENT_PRESET_ID' => $activePreset->getCode(),
	'PRESET_TOOLS_AVAILABILITY' => [
		'crm' => ToolsManager::getInstance()->checkAvailabilityByToolId('crm'),
		'tasks' => ToolsManager::getInstance()->checkAvailabilityByToolId('tasks'),
		'sites' => ToolsManager::getInstance()->checkAvailabilityByToolId('sites'),
		'social' => ToolsManager::getInstance()->checkAvailabilityByToolId('team_work')
	],
	'SETTINGS_PATH' => \Bitrix\Intranet\Portal::getInstance()->getSettings()->getSettingsUrl(),
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
$arResult["COUNTERS"] = $counters;

$arResult["GROUP_COUNT"] = 0;
if (!$arResult["IS_EXTRANET"] && $GLOBALS["USER"]->isAuthorized())
{
	$arResult["GROUP_COUNT"] = count(\Bitrix\Intranet\Controller\LeftMenu::getMyGroups());
}

//license button
$arResult["SHOW_LICENSE_BUTTON"] = false;

if (
	Main\Loader::includeModule('bitrix24')
	&& !(Main\Loader::includeModule("extranet") && CExtranet::IsExtranetSite())
)
{
	$arResult["SHOW_LICENSE_BUTTON"] = \CBitrix24::getLicenseFamily() !== 'ent';
	$arResult["B24_LICENSE_PATH"] = \CBitrix24::PATH_LICENSE_ALL . '?analyticsLabel[leftMenu]=Y&analyticsLabel[licenseType]=' . \CBitrix24::getLicenseType();
}
