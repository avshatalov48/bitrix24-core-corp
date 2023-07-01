<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;

/** @global \CMain $APPLICATION */
/** @var array $arResult */
/** @var \CatalogStoreDocumentControllerComponent $component */
/** @var \CBitrixComponentTemplate $this */

$paymentId = (int)($arResult['VARIABLES']['PAYMENT_ID'] ?? 0);

global $APPLICATION;

Main\UI\Extension::load('ui.notification');

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.terminal.payment.detail',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'ID' => $paymentId,
		],
		'USE_BACKGROUND_CONTENT' => false,
		'USE_UI_TOOLBAR' => 'Y',
		'USE_TOP_MENU' => false,
		'USE_PADDING' => false,
		'PAGE_MODE' => false,
	]
);
