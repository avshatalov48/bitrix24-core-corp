<?php
/**
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\BIConnector;
use Bitrix\Main\Type;

if (!\Bitrix\Main\Loader::includeModule('biconnector'))
{
	ShowError(Loc::getMessage('CC_BBD_ERROR_INCLUDE_MODULE'));
	return;
}

$filter = [
	'=ID' => $arParams['DASHBOARD_ID'],
];

if (!$USER->CanDoOperation('biconnector_dashboard_view'))
{
	$filter['=PERMISSION.USER_ID'] = $USER->GetID();
}

$arResult = \Bitrix\BIConnector\DashboardTable::getList([
	'filter' => $filter,
])->fetch();

if ($arResult && $arParams['SET_TITLE'] == 'Y')
{
	$APPLICATION->SetTitle(htmlspecialcharsEx($arResult['NAME']));
	BIConnector\DashboardTable::update(
		$arParams['DASHBOARD_ID'],
		[
			'LAST_VIEW_BY' => (int)$USER->GetID(),
			'DATE_LAST_VIEW' => new Type\DateTime(),
		]
	);
}

$this->includeComponentTemplate();
