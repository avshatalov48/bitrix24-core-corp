<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @global \CMain $APPLICATION */
/** @var array $arResult */
/** @var \CrmTerminalPaymentControllerComponent $component */
/** @var \CBitrixComponentTemplate $this */

global $APPLICATION;

$componentParams = [
	'PATH_TO' => $arResult['PATH_TO'],
];

if ($arResult['REQUESTED_PAGE'] === \CrmTerminalPaymentControllerComponent::SALE_SECTION)
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.shop.page.controller',
		'',
		[
			'CONNECT_PAGE' => 'N',
			'ADDITIONAL_PARAMS' => []
		],
		$component
	);
}
elseif ($arResult['REQUESTED_PAGE'] === \CrmTerminalPaymentControllerComponent::CRM_SECTION)
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		[
			'ID' => 'TERMINAL',
			'ACTIVE_ITEM_ID' => 'TERMINAL',
		],
		$component
	);
}

if ($component->isIframeMode())
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.terminal.payment.list',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => $componentParams,
			'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'N',
			'USE_PADDING' => false,
			'USE_UI_TOOLBAR' => 'Y',
			'USE_BACKGROUND_CONTENT' => false,
		]
	);
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.terminal.payment.list',
		'',
		$componentParams
	);
}
