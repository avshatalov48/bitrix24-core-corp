<?php

use Bitrix\Intranet\Binding\Marketplace;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Rest\AppTable;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rest/install/public/marketplace/.left.menu_ext.php");

$arMenu = [];

$extranetSite = (
	Loader::includeModule('extranet')
	&& \CExtranet::isExtranetSite(SITE_ID)
);

$marketDir = Marketplace::getMainDirectory();
if (mb_substr($marketDir, 0, 1) == '/') {
	$marketDir = mb_substr($marketDir, 1);
}

if(
	!$extranetSite
	&& (
		SITE_TEMPLATE_ID == 'bitrix24'
		|| ModuleManager::isModuleInstalled('bitrix24')
	)
) {
	$arMenu[] = [
		GetMessage("MENU_MARKET_ALL"),
		SITE_DIR . $marketDir,
		[],
		["menu_item_id" => "menu_market"],
		""
	];
}

if(
	!$extranetSite
	&& CModule::IncludeModule("rest")
)
{
	if (CRestUtil::isAdmin())
	{
		$arMenu[] = [
			GetMessage("MENU_MARKET_INSTALLED"),
			SITE_DIR . $marketDir . "installed/",
			[],
			["menu_item_id" => "menu_market_installed"],
			""
		];
	}

	global $USER;
	$arUserGroupCode = $USER->GetAccessCodes();

	$arMenuApps = [];
	$dbApps = AppTable::getList(
		[
			'order' => [
				"ID" => "ASC"
			],
			'filter' => [
				"=ACTIVE" => AppTable::ACTIVE
			],
			'select' => [
				'ID',
				'CODE',
				'CLIENT_ID',
				'STATUS',
				'ACTIVE',
				'ACCESS',
				'MENU_NAME' => 'LANG.MENU_NAME',
				'MENU_NAME_DEFAULT' => 'LANG_DEFAULT.MENU_NAME',
				'MENU_NAME_LICENSE' => 'LANG_LICENSE.MENU_NAME',
			],
		]
	);
	foreach ($dbApps->fetchCollection() as $app)
	{
		$arApp = [
			'ID' => $app->getId(),
			'CODE' => $app->getCode(),
			'ACTIVE' => $app->getActive(),
			'CLIENT_ID' => $app->getClientId(),
			'ACCESS' => $app->getAccess(),
			'MENU_NAME' => !is_null($app->getLang()) ? $app->getLang()->getMenuName() : '',
			'MENU_NAME_DEFAULT' => !is_null($app->getLangDefault()) ? $app->getLangDefault()->getMenuName() : '',
			'MENU_NAME_LICENSE' => !is_null($app->getLangLicense()) ? $app->getLangLicense()->getMenuName() : ''
		];

		if($arApp['CODE'] === CRestUtil::BITRIX_1C_APP_CODE)
		{
			continue;
		}

		$lang = in_array(LANGUAGE_ID, ["ru", "en", "de"]) ? LANGUAGE_ID : Loc::getDefaultLang(LANGUAGE_ID);

		if ($arApp["MENU_NAME"] === '' && $arApp['MENU_NAME_DEFAULT'] === '' && $arApp['MENU_NAME_LICENSE'] === '')
		{
			$app->fillLangAll();
			if (!is_null($app->getLangAll()))
			{
				$langList = [];
				foreach ($app->getLangAll() as $appLang)
				{
					if ($appLang->getMenuName() !== '')
					{
						$langList[$appLang->getLanguageId()] = $appLang->getMenuName();
					}
				}

				if (isset($langList[$lang]) && $langList[$lang])
				{
					$arApp["MENU_NAME"] = $langList[$lang];
				}
				elseif (isset($langList['en']) && $langList['en'])
				{
					$arApp["MENU_NAME"] = $langList['en'];
				}
				elseif (!empty($langList))
				{
					$arApp["MENU_NAME"] = reset($langList);
				}
			}
		}

		if($arApp["MENU_NAME"] <> '' || $arApp['MENU_NAME_DEFAULT'] <> '' || $arApp['MENU_NAME_LICENSE'] <> '')
		{
			$appRightAvailable = false;
			if(CRestUtil::isAdmin())
			{
				$appRightAvailable = true;
			}
			elseif(!empty($arApp["ACCESS"]))
			{
				$rights = explode(",", $arApp["ACCESS"]);
				foreach($rights as $rightID)
				{
					if(in_array($rightID, $arUserGroupCode))
					{
						$appRightAvailable = true;
						break;
					}
				}
			}
			else
			{
				$appRightAvailable = true;
			}

			if($appRightAvailable)
			{
				$appName = $arApp["MENU_NAME"];

				if($appName == '')
				{
					$appName = $arApp['MENU_NAME_DEFAULT'];
				}

				if($appName == '')
				{
					$appName = $arApp['MENU_NAME_LICENSE'];
				}

				$arMenuApps[] = [
					htmlspecialcharsbx($appName),
					CRestUtil::getApplicationPage($arApp['ID'], 'ID', $arApp),
					[
						CRestUtil::getApplicationPage($arApp['ID'], 'CODE', $arApp),
						CRestUtil::getApplicationPage($arApp['ID'], 'CLIENT_ID', $arApp),
					],
					["is_application" => "Y", "app_id" => $arApp["ID"]],
					""
				];
			}
		}
	}

	if ($USER->IsAuthorized())
	{
		$urlDevOps = \Bitrix\Rest\Url\DevOps::getInstance()->getIndexUrl();
		$arMenu[] = [
			GetMessage("REST_MENU_MARKET_DEVOPS"),
			$urlDevOps,
			[],
			[
				"menu_item_id" => "menu_marketplace_hook"
			],
			"",
		];
	}

	$arMenu = array_merge($arMenu, $arMenuApps);
}


$aMenuLinks = array_merge($arMenu, $aMenuLinks);