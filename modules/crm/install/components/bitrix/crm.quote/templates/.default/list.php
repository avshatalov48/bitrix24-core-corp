<?php

use Bitrix\Main\ModuleManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CMain $APPLICATION */

$isSlider = isset($_REQUEST['IFRAME'], $_REQUEST['IFRAME_TYPE'])
	&& $_REQUEST['IFRAME'] === 'Y'
	&& $_REQUEST['IFRAME_TYPE'] === 'SIDE_SLIDER';

if (!$isSlider)
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		[
			'ID' => 'QUOTE_LIST',
			'ACTIVE_ITEM_ID' => 'QUOTE',
			'PATH_TO_COMPANY_LIST' => $arResult['PATH_TO_COMPANY_LIST'] ?? '',
			'PATH_TO_COMPANY_EDIT' => $arResult['PATH_TO_COMPANY_EDIT'] ?? '',
			'PATH_TO_CONTACT_LIST' => $arResult['PATH_TO_CONTACT_LIST'] ?? '',
			'PATH_TO_CONTACT_EDIT' => $arResult['PATH_TO_CONTACT_EDIT'] ?? '',
			'PATH_TO_LEAD_LIST' => $arResult['PATH_TO_LEAD_LIST'] ?? '',
			'PATH_TO_LEAD_EDIT' => $arResult['PATH_TO_LEAD_EDIT'] ?? '',
			'PATH_TO_DEAL_LIST' => $arResult['PATH_TO_DEAL_LIST'] ?? '',
			'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'] ?? '',
			'PATH_TO_QUOTE_LIST' => $arResult['PATH_TO_QUOTE_LIST'] ?? '',
			'PATH_TO_QUOTE_EDIT' => $arResult['PATH_TO_QUOTE_EDIT'] ?? '',
			'PATH_TO_ORDER_LIST' => $arResult['PATH_TO_ORDER_LIST'] ?? '',
			'PATH_TO_QUOTE_DETAILS' => $arResult['PATH_TO_QUOTE_DETAILS'] ?? '',
			'PATH_TO_ORDER_EDIT' => $arResult['PATH_TO_ORDER_EDIT'] ?? '',
			'PATH_TO_INVOICE_LIST' => $arResult['PATH_TO_INVOICE_LIST'] ?? '',
			'PATH_TO_INVOICE_EDIT' => $arResult['PATH_TO_INVOICE_EDIT'] ?? '',
			'PATH_TO_REPORT_LIST' => $arResult['PATH_TO_REPORT_LIST'] ?? '',
			'PATH_TO_DEAL_FUNNEL' => $arResult['PATH_TO_DEAL_FUNNEL'] ?? '',
			'PATH_TO_EVENT_LIST' => $arResult['PATH_TO_EVENT_LIST'] ?? '',
			'PATH_TO_PRODUCT_LIST' => $arResult['PATH_TO_PRODUCT_LIST'] ?? '',
		],
		$component
	);
}

if (!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Quote))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', []);
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.counter.panel',
		'',
		[
			'ENTITY_TYPE_NAME' => CCrmOwnerType::QuoteName,
			'EXTRAS' => [],
			'PATH_TO_ENTITY_LIST' => $arResult['PATH_TO_QUOTE_LIST'] ?? ''
		]
	);

	$APPLICATION->ShowViewContent('crm-grid-filter');

	$APPLICATION->IncludeComponent(
		'bitrix:crm.quote.menu',
		'',
		[
			'PATH_TO_QUOTE_LIST' => $arResult['PATH_TO_QUOTE_LIST'] ?? '',
			'PATH_TO_QUOTE_SHOW' => $arResult['PATH_TO_QUOTE_SHOW'] ?? '',
			'PATH_TO_QUOTE_EDIT' => $arResult['PATH_TO_QUOTE_EDIT'] ?? '',
			'PATH_TO_QUOTE_DETAILS' => $arResult['PATH_TO_QUOTE_DETAILS'] ?? '',
			'PATH_TO_QUOTE_IMPORT' => $arResult['PATH_TO_QUOTE_IMPORT'] ?? '',
			'PATH_TO_QUOTE_PAYMENT' => $arResult['PATH_TO_QUOTE_PAYMENT'] ?? '',
			'ELEMENT_ID' => $arResult['VARIABLES']['quote_id'] ?? null,
			'TYPE' => 'list',
			'IN_SLIDER' => $isSlider ? 'Y' : 'N',
		],
		$component
	);

	if (ModuleManager::isModuleInstalled('rest'))
	{
		$APPLICATION->IncludeComponent(
			'bitrix:app.placement',
			'menu',
			[
				'PLACEMENT' => 'CRM_QUOTE_LIST_MENU',
				'PLACEMENT_OPTIONS' => [],
				'INTERFACE_EVENT' => 'onCrmQuoteListInterfaceInit',
				'MENU_EVENT_MODULE' => 'crm',
				'MENU_EVENT' => 'onCrmQuoteListItemBuildMenu',
			],
			null,
			['HIDE_ICONS' => 'Y']
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.quote.list',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'QUOTE_COUNT' => '20',
				'PATH_TO_QUOTE_DETAILS' => $arResult['PATH_TO_QUOTE_DETAILS'] ?? '',
				'PATH_TO_QUOTE_SHOW' => $arResult['PATH_TO_QUOTE_SHOW'] ?? '',
				'PATH_TO_QUOTE_EDIT' => $arResult['PATH_TO_QUOTE_EDIT'] ?? '',
				'PATH_TO_QUOTE_KANBAN' => $arResult['PATH_TO_QUOTE_KANBAN'] ?? '',
				'PATH_TO_QUOTE_DEADLINES' => $arResult['PATH_TO_QUOTE_DEADLINES'] ?? '',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'] ?? '',
				'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'] ?? null,
			],
			'USE_UI_TOOLBAR' => 'Y',
		]
	);
}
