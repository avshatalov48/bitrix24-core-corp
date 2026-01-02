<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \CMain $APPLICATION */
/** @var array $arResult */
/** @var array $arParams */

if ($arParams['MENU_MODE'] ?? false)
{
	return;
}

\Bitrix\Main\Loader::includeModule('ui');
\Bitrix\Main\UI\Extension::load([
	'biconnector.apache-superset-feedback-form',
	'biconnector.apache-superset-dashboard-manager',
	'biconnector.apache-superset-analytics',
]);

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.buttons',
	'',
	[
		'ID' => 'biconnector_superset_menu',
		'ITEMS' => $arResult['MENU_ITEMS'],
		'THEME' => defined('AIR_SITE_TEMPLATE') ? 'air' : null,
	],
);
