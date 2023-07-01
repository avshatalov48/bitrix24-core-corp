<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CMain $APPLICATION */

if (!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Order))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', []);
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.counter.panel',
		'',
		array('ENTITY_TYPE_NAME' => CCrmOwnerType::OrderName)
	);

	$APPLICATION->ShowViewContent('crm-grid-filter');

	$APPLICATION->IncludeComponent(
		'bitrix:crm.order.menu',
		'',
		array(
			'PATH_TO_ORDER_LIST' => $arResult['PATH_TO_ORDER_LIST'] ?? '',
			'PATH_TO_ORDER_SHOW' => $arResult['PATH_TO_ORDER_SHOW'] ?? '',
			'PATH_TO_ORDER_EDIT' => $arResult['PATH_TO_ORDER_EDIT'] ?? '',
			'PATH_TO_ORDER_IMPORT' => $arResult['PATH_TO_ORDER_IMPORT'] ?? '',
			'PATH_TO_ORDER_PAYMENT' => $arResult['PATH_TO_ORDER_PAYMENT'] ?? '',
			'ELEMENT_ID' => $arResult['VARIABLES']['order_id'] ?? null,
			'TYPE' => 'list'
		),
		$component
	);

	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.order.list',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'ORDER_COUNT' => '20',
				'PATH_TO_ORDER_SHOW' => $arResult['PATH_TO_ORDER_SHOW'] ?? '',
				'PATH_TO_ORDER_EDIT' => $arResult['PATH_TO_ORDER_EDIT'] ?? '',
				'PATH_TO_ORDER_KANBAN' => $arResult['PATH_TO_ORDER_KANBAN'] ?? '',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'] ?? '',
				'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'] ?? null,
				'BUILDER_CONTEXT' => $arParams['BUILDER_CONTEXT'] ?? ''
			],
			'USE_UI_TOOLBAR' => 'Y',
		],
		$component
	);
}
