<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$categoryId = (int)($arResult['VARIABLES']['category_id'] ?? 0);
$pathToList = $categoryId > 0
	? CComponentEngine::MakePathFromTemplate(
		$arResult['PATH_TO_CONTACT_CATEGORY'],
		['category_id' => $categoryId]
	)
	: $arResult['PATH_TO_CONTACT_LIST']
;

$isSlider = isset($_REQUEST['IFRAME'], $_REQUEST['IFRAME_TYPE'])
	&& $_REQUEST['IFRAME'] === 'Y'
	&& $_REQUEST['IFRAME_TYPE'] === 'SIDE_SLIDER';
if (!$isSlider)
{
	/** @var CMain $APPLICATION */
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		[
			'ID' => 'CONTACT_LIST',
			'ACTIVE_ITEM_ID' => CCrmComponentHelper::getMenuActiveItemId(CCrmOwnerType::ContactName, $categoryId),
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
			'PATH_TO_INVOICE_LIST' => $arResult['PATH_TO_INVOICE_LIST'] ?? '',
			'PATH_TO_INVOICE_EDIT' => $arResult['PATH_TO_INVOICE_EDIT'] ?? '',
			'PATH_TO_REPORT_LIST' => $arResult['PATH_TO_REPORT_LIST'] ?? '',
			'PATH_TO_DEAL_FUNNEL' => $arResult['PATH_TO_DEAL_FUNNEL'] ?? '',
			'PATH_TO_EVENT_LIST' => $arResult['PATH_TO_EVENT_LIST'] ?? '',
			'PATH_TO_PRODUCT_LIST' => $arResult['PATH_TO_PRODUCT_LIST'] ?? '',
			'PATH_TO_CONTACT_WIDGET' => $arResult['PATH_TO_CONTACT_WIDGET'] ?? '',
			'PATH_TO_CONTACT_PORTRAIT' => $arResult['PATH_TO_CONTACT_PORTRAIT'] ?? '',
		],
		$component
	);
}

if (!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Contact))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', []);
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.counter.panel',
		'',
		[
			'ENTITY_TYPE_NAME' => CCrmOwnerType::ContactName,
			'EXTRAS' => [
				'CATEGORY_ID' => $categoryId,
			],
			'PATH_TO_ENTITY_LIST' => $pathToList,
		]
	);

	$APPLICATION->ShowViewContent('crm-grid-filter');

	if (!$isSlider)
	{
		$APPLICATION->IncludeComponent(
			'bitrix:crm.dedupe.autosearch',
			'',
			[
				'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
				'PATH_TO_MERGE' => $arResult['PATH_TO_CONTACT_MERGE'],
				'PATH_TO_DEDUPELIST' => $arResult['PATH_TO_CONTACT_DEDUPELIST'],
			],
			$component,
			['HIDE_ICONS' => 'Y']
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.contact.menu',
		'',
		[
			'PATH_TO_CONTACT_LIST' => $arResult['PATH_TO_CONTACT_LIST'] ?? '',
			'PATH_TO_CONTACT_SHOW' => $arResult['PATH_TO_CONTACT_SHOW'] ?? '',
			'PATH_TO_CONTACT_EDIT' => $arResult['PATH_TO_CONTACT_EDIT'] ?? '',
			'PATH_TO_CONTACT_IMPORT' => $arResult['PATH_TO_CONTACT_IMPORT'] ?? '',
			'PATH_TO_CONTACT_IMPORTVCARD' => $arResult['PATH_TO_CONTACT_IMPORTVCARD'] ?? '',
			'PATH_TO_CONTACT_DEDUPE' => $arResult['PATH_TO_CONTACT_DEDUPE'] ?? '',
			'PATH_TO_CONTACT_DEDUPEWIZARD' => $arResult['PATH_TO_CONTACT_DEDUPEWIZARD'] ?? '',
			'ELEMENT_ID' => $arResult['VARIABLES']['contact_id'] ?? null,
			'CATEGORY_ID' => $categoryId,
			'TYPE' => 'list',
			'IN_SLIDER' => $isSlider ? 'Y' : 'N',
		],
		$component
	);

	if (\Bitrix\Main\ModuleManager::isModuleInstalled('rest'))
	{
		$APPLICATION->IncludeComponent(
			'bitrix:app.placement',
			'menu',
			[
				'PLACEMENT' => "CRM_CONTACT_LIST_MENU",
				"PLACEMENT_OPTIONS" => [],
				'INTERFACE_EVENT' => 'onCrmContactMenuInterfaceInit',
				'MENU_EVENT_MODULE' => 'crm',
				'MENU_EVENT' => 'onCrmContactListItemBuildMenu',
			],
			null,
			['HIDE_ICONS' => 'Y']
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.contact.list',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'CATEGORY_ID' => $categoryId,
				'GRID_ID_SUFFIX' => (new \Bitrix\Crm\Component\EntityList\GridId(CCrmOwnerType::Contact))
					->getDefaultSuffix($categoryId),
				'CONTACT_COUNT' => '20',
				'PATH_TO_CONTACT_LIST' => $pathToList,
				'PATH_TO_CONTACT_SHOW' => $arResult['PATH_TO_CONTACT_SHOW'],
				'PATH_TO_CONTACT_EDIT' => $arResult['PATH_TO_CONTACT_EDIT'],
				'PATH_TO_CONTACT_WIDGET' => $arResult['PATH_TO_CONTACT_WIDGET'],
				'PATH_TO_COMPANY_SHOW' => $arResult['PATH_TO_COMPANY_SHOW'],
				'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'],
				'PATH_TO_CONTACT_MERGE' => $arResult['PATH_TO_CONTACT_MERGE'],
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'],
			],
			'USE_UI_TOOLBAR' => 'Y',
		],
		$component
	);
}
