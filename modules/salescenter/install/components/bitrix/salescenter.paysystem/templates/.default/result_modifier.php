<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/**
 * @var array $arResult
 */

Loc::loadMessages(__FILE__);

$arResult['ADDITIONAL_LINK_FOR_DESCRIPTION'] = [];
if (!empty($arResult['PAYSYSTEM_HANDLER_CLASS_NAME']))
{
	$className = $arResult['PAYSYSTEM_HANDLER_CLASS_NAME'];

	$helpdeskCodeMap = [
		\Sale\Handlers\PaySystem\WooppayHandler::class => [
			'TITLE' => Loc::getMessage('SALESCENTER_SP_PAYSYSTEM_WOOPKASSA_LINK_CONNECT'),
			'HREF' => 'https://woopkassa.kz/?utm_source=partner&utm_medium=referral&utm_campaign=bitrix24',
		],
		\Sale\Handlers\PaySystem\PlatonHandler::class => [
			'TITLE' => Loc::getMessage('SALESCENTER_SP_PAYSYSTEM_PLATON_LINK_CONNECT_SPECIAL_PLAN'),
			'HREF' => 'https://devplaton.com.ua/invoices/1c_bitrix_form/',
		]
	];
	$arResult['ADDITIONAL_LINK_FOR_DESCRIPTION'] = $helpdeskCodeMap[$className] ?? [];


	$settingsCodeMap = [
		'DEFAULT' => Loc::getMessage('SALESCENTER_SP_SETTINGS_FORM'),
		\Sale\Handlers\PaySystem\RoboxchangeHandler::class => [
			'NEW' => Loc::getMessage('SALESCENTER_SP_ROBOKASSA_SETTINGS_FORM_NEW'),
			'EXIST' => Loc::getMessage('SALESCENTER_SP_ROBOKASSA_SETTINGS_FORM_EXIST')
		],
	];
	$arResult['SETTINGS_FORM_LINK_NAME_CODE_MAP'] = $settingsCodeMap;

	if (isset($settingsCodeMap[$className]))
	{
		$isPaySystemSettingsExists =
			$arResult['PAY_SYSTEM_ROBOKASSA_SETTINGS']['IS_SUPPORT_SETTINGS']
			&& $arResult['PAY_SYSTEM_ROBOKASSA_SETTINGS']['IS_SETTINGS_EXISTS']
		;

		$arResult['SETTINGS_FORM_LINK_NAME'] =
			$isPaySystemSettingsExists
				? $settingsCodeMap[$className]['EXIST']
				: $settingsCodeMap[$className]['NEW'];
	}
	else
	{
		$arResult['SETTINGS_FORM_LINK_NAME'] = $settingsCodeMap['DEFAULT'];
	}

	unset($reflection, $helpdeskCodeMap, $className, $isPaySystemSettingsExists);
}
