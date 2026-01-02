<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (\Bitrix\Main\Loader::includeModule('ui'))
{
	\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
}

$arResult["CURRENT_LANG"] = in_array(LANGUAGE_ID, array("ru", "en", "de", "ua")) ? LANGUAGE_ID : \Bitrix\Main\Localization\Loc::getDefaultLang(LANGUAGE_ID);
$arResult["IS_LEAD_ENABLED"] = \Bitrix\Crm\Settings\LeadSettings::isEnabled();

$arResult["IS_CRM_ADMIN"] = \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->isCrmAdmin();

if (isset($arParams['SHOW_CONVERTER_CONFIG']))
{
	$componentPage = 'converter';
	$arResult["CONVERTER_CONFIG"] = \Bitrix\Crm\Settings\LeadSettings::getCurrent()->getFreeModeConverterConfig();
	$arResult['DEAL_CATEGORIES'] = \Bitrix\Crm\Category\DealCategory::getSelectListItems();
}

$this->IncludeComponentTemplate($componentPage);
