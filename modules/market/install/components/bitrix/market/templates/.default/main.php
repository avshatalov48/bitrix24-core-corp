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
	'bitrix:market.main',
	'',
	[
		'FROM' => 'main',
		'VARIABLES' => $arResult['VARIABLES'],
	]
);