<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

\Bitrix\Main\UI\Extension::load(['ui.design-tokens']);

$APPLICATION->includeComponent(
	'bitrix:market.install',
	'',
	[
		'APP_CODE' => $arResult['VARIABLES']['appCode'],
		'VERSION' => $arResult['VARIABLES']['version'],
		'INSTALL_HASH' => $arResult['VARIABLES']['installHash'],
		'CHECK_HASH' => $arResult['VARIABLES']['checkHash'],
		'IFRAME' => 'Y',
	],
	$component,
	['HIDE_ICONS' => 'Y']
);



