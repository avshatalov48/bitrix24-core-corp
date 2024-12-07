<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('ui');
\Bitrix\Main\UI\Extension::load([
	'biconnector.apache-superset-feedback-form',
	'biconnector.apache-superset-dashboard-manager',
	'biconnector.apache-superset-analytics',
]);

/** @var \CMain $APPLICATION */
/** @var array $arResult */

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.buttons',
	'',
	[
		'ID' => 'biconnector_superset_menu',
		'ITEMS' => $arResult['MENU_ITEMS'],
	],
);
