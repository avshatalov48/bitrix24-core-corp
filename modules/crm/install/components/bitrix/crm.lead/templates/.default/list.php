<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

//show the crm type popup (with or without leads)
if (!\Bitrix\Crm\Settings\LeadSettings::isEnabled())
{
	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');
	?><script><?=\Bitrix\Crm\Settings\LeadSettings::showCrmTypePopup();?></script><?
}

\Bitrix\Crm\Settings\Crm::markAsInitiated();

$isSlider = (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y');
if (!$isSlider)
{
	/** @var CMain $APPLICATION */
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		[
			'ID' => 'LEAD_LIST',
			'ACTIVE_ITEM_ID' => 'LEAD',
			'PATH_TO_COMPANY_LIST' => $arResult['PATH_TO_COMPANY_LIST'] ?? '',
			'PATH_TO_COMPANY_EDIT' => $arResult['PATH_TO_COMPANY_EDIT'] ?? '',
			'PATH_TO_CONTACT_LIST' => $arResult['PATH_TO_CONTACT_LIST'] ?? '',
			'PATH_TO_CONTACT_EDIT' => $arResult['PATH_TO_CONTACT_EDIT'] ?? '',
			'PATH_TO_DEAL_LIST' => $arResult['PATH_TO_DEAL_LIST'] ?? '',
			'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'] ?? '',
			'PATH_TO_LEAD_LIST' => $arResult['PATH_TO_LEAD_LIST'] ?? '',
			'PATH_TO_LEAD_EDIT' => $arResult['PATH_TO_LEAD_EDIT'] ?? '',
			'PATH_TO_QUOTE_LIST' => $arResult['PATH_TO_QUOTE_LIST'] ?? '',
			'PATH_TO_QUOTE_EDIT' => $arResult['PATH_TO_QUOTE_EDIT'] ?? '',
			'PATH_TO_ORDER_LIST' => $arResult['PATH_TO_ORDER_LIST'] ?? '',
			'PATH_TO_ORDER_EDIT' => $arResult['PATH_TO_ORDER_EDIT'] ?? '',
			'PATH_TO_INVOICE_LIST' => $arResult['PATH_TO_INVOICE_LIST'] ?? '',
			'PATH_TO_INVOICE_EDIT' => $arResult['PATH_TO_INVOICE_EDIT'] ?? '',
			'PATH_TO_REPORT_LIST' => $arResult['PATH_TO_REPORT_LIST'] ?? '',
			'PATH_TO_DEAL_FUNNEL' => $arResult['PATH_TO_DEAL_FUNNEL'] ?? '',
			'PATH_TO_EVENT_LIST' => $arResult['PATH_TO_EVENT_LIST'] ?? '',
			'PATH_TO_PRODUCT_LIST' => $arResult['PATH_TO_PRODUCT_LIST'] ?? ''
		],
		$component
	);
}

if(!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Lead))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array());
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.counter.panel',
		'',
		[
			'ENTITY_TYPE_NAME' => CCrmOwnerType::LeadName,
			'EXTRAS' => [],
			'PATH_TO_ENTITY_LIST' => $arResult['PATH_TO_LEAD_LIST']
		]
	);

	$APPLICATION->ShowViewContent('crm-grid-filter');

	if (!$isSlider)
	{
		$APPLICATION->IncludeComponent(
			'bitrix:crm.dedupe.autosearch',
			'',
			[
				'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
				'PATH_TO_MERGE' => $arResult['PATH_TO_LEAD_MERGE'],
				'PATH_TO_DEDUPELIST' => $arResult['PATH_TO_LEAD_DEDUPELIST']
			],
			$component,
			['HIDE_ICONS' => 'Y']
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.lead.menu',
		'',
		[
			'PATH_TO_LEAD_LIST' => $arResult['PATH_TO_LEAD_LIST'] ?? '',
			'PATH_TO_LEAD_SHOW' => $arResult['PATH_TO_LEAD_SHOW'] ?? '',
			'PATH_TO_LEAD_EDIT' => $arResult['PATH_TO_LEAD_EDIT'] ?? '',
			'PATH_TO_LEAD_CONVERT' => $arResult['PATH_TO_LEAD_CONVERT'] ?? '',
			'PATH_TO_LEAD_IMPORT' => $arResult['PATH_TO_LEAD_IMPORT'] ?? '',
			'PATH_TO_LEAD_DEDUPE' => $arResult['PATH_TO_LEAD_DEDUPE'] ?? '',
			'PATH_TO_LEAD_DEDUPEWIZARD' => $arResult['PATH_TO_LEAD_DEDUPEWIZARD'] ?? '',
			'ELEMENT_ID' => (int)($arResult['VARIABLES']['lead_id'] ?? 0),
			'TYPE' => 'list',
			'IN_SLIDER' => $isSlider ? 'Y' : 'N',
		],
		$component
	);

	if(\Bitrix\Main\ModuleManager::isModuleInstalled('rest'))
	{
		$APPLICATION->IncludeComponent(
			'bitrix:app.placement',
			'menu',
			[
				'PLACEMENT' => "CRM_LEAD_LIST_MENU",
				"PLACEMENT_OPTIONS" => [],
				'INTERFACE_EVENT' => 'onCrmLeadListInterfaceInit',
				'MENU_EVENT_MODULE' => 'crm',
				'MENU_EVENT' => 'onCrmLeadListItemBuildMenu',
			],
			null,
			['HIDE_ICONS' => 'Y']
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.lead.list',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'LEAD_COUNT' => '20',
				'PATH_TO_LEAD_SHOW' => $arResult['PATH_TO_LEAD_SHOW'],
				'PATH_TO_LEAD_EDIT' => $arResult['PATH_TO_LEAD_EDIT'],
				'PATH_TO_LEAD_CONVERT' => $arResult['PATH_TO_LEAD_CONVERT'],
				'PATH_TO_LEAD_WIDGET' => $arResult['PATH_TO_LEAD_WIDGET'],
				'PATH_TO_LEAD_KANBAN' => $arResult['PATH_TO_LEAD_KANBAN'],
				'PATH_TO_LEAD_CALENDAR' => $arResult['PATH_TO_LEAD_CALENDAR'],
				'PATH_TO_LEAD_MERGE' => $arResult['PATH_TO_LEAD_MERGE'],
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID']
			],
			'USE_UI_TOOLBAR' => 'Y',
		]
	);
}
