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
	try
	{
		$reflection = new \ReflectionClass($arResult['PAYSYSTEM_HANDLER_CLASS_NAME']);
		$className = $reflection->getName();
	}
	catch (\ReflectionException $ex)
	{
		$className = '';
	}

	$helpdeskCodeMap = [
		\Sale\Handlers\PaySystem\WooppayHandler::class => [
			'TITLE' => Loc::getMessage('SALESCENTER_SP_PAYSYSTEM_WOOPKASSA_LINK_CONNECT'),
			'HREF' => 'https://woopkassa.kz/?utm_source=partner&utm_medium=referral&utm_campaign=bitrix24',
		],
		\Sale\Handlers\PaySystem\RoboxchangeHandler::class => [
			'TITLE' => Loc::getMessage('SALESCENTER_SP_PAYSYSTEM_ROBOXCHANGE_LINK_CONNECT'),
			'HREF' => 'https://partner.robokassa.ru/Reg/Register?PromoCode=01Bitrix&culture=ru',
		],
		\Sale\Handlers\PaySystem\PlatonHandler::class => [
			'TITLE' => Loc::getMessage('SALESCENTER_SP_PAYSYSTEM_PLATON_LINK_CONNECT_SPECIAL_PLAN'),
			'HREF' => 'https://devplaton.com.ua/invoices/1c_bitrix_form/',
		]
	];

	$arResult['ADDITIONAL_LINK_FOR_DESCRIPTION'] = $helpdeskCodeMap[$className] ?? [];

	unset($reflection, $helpdeskCodeMap, $className);
}
