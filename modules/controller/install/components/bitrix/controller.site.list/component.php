<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @var CDatabase $DB */
/** @var CUser $USER */
/** @var CMain $APPLICATION */

//Check if user have rights to see and login
if (!is_array($arParams['GROUP_PERMISSIONS']))
{
	$arParams['GROUP_PERMISSIONS'] = [1];
}

$bUSER_HAVE_ACCESS = false;

if (isset($GLOBALS['USER']) && is_object($GLOBALS['USER']))
{
	$MOD_RIGHT = CMain::GetGroupRight('controller');
	if ($MOD_RIGHT >= 'V')
	{
		$bUSER_HAVE_ACCESS = true;
	}
	else
	{
		$arRIGHTS = CMain::GetUserRoles('controller');
		if (in_array('L', $arRIGHTS, true))
		{
			$bUSER_HAVE_ACCESS = true;
		}
	}
}

if (!$bUSER_HAVE_ACCESS)
{
	return;
}

//Set menu title from parameter or from lang file
if (isset($arParams['TITLE']))
{
	$arParams['TITLE'] = trim($arParams['TITLE']);
	if ($arParams['TITLE'] == '')
	{
		$arParams['TITLE'] = GetMessage('CC_BCSL_TITLE_DEFAULT');
	}
}
else
{
	$arParams['TITLE'] = GetMessage('CC_BCSL_TITLE_DEFAULT');
}

//Component execution with cache support
if ($this->startResultCache())
{
	if (!CModule::IncludeModule('controller'))
	{
		$this->abortResultCache();
		ShowError(GetMessage('CC_BCSL_MODULE_NOT_INSTALLED'));
		return;
	}

	$arResult['TITLE'] = $arParams['TITLE'];
	$arResult['ITEMS'] = [];
	$arResult['MENU_ITEMS'] = [];

	$arFilter = [
		'=ACTIVE' => 'Y',
		'=DISCONNECTED' => 'N',
	];
	if (isset($arParams['GROUP']) && is_array($arParams['GROUP']) && count($arParams['GROUP']))
	{
		$arFilter['=CONTROLLER_GROUP_ID'] = $arParams['GROUP'];
	}

	$rsMembers = CControllerMember::GetList(['ID' => 'ASC'], $arFilter);
	while ($arMember = $rsMembers->GetNext())
	{
		$arResult['ITEMS'][] = $arMember;
		$arResult['MENU_ITEMS'][] = [
			'ICONCLASS' => 'site-list-icon',
			'TEXT' => $arMember['NAME'],
			'ONCLICK' => 'window.location = \'/bitrix/admin/controller_goto.php?member=' . $arMember['ID'] . '&lang=' . LANGUAGE_ID . '\';',
			'TITLE' => $arMember['URL'],
		];
	}

	$this->setResultCacheKeys([]);
	$this->includeComponentTemplate();
}

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/utils.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/popup_menu.js');
