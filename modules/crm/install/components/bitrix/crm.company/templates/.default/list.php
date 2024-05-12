<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$cpID = 'COMPANY_LIST';
$cpActiveItemID = 'COMPANY';
$isMyCompanyMode = isset($arResult['MYCOMPANY_MODE']) && $arResult['MYCOMPANY_MODE'] === 'Y';
if ($isMyCompanyMode)
{
	$cpID = 'MYCOMPANY_LIST';
	$cpActiveItemID = 'MY_COMPANY';
}
$categoryId = (int)($arResult['VARIABLES']['category_id'] ?? 0);
$isSlider = isset($_REQUEST['IFRAME'], $_REQUEST['IFRAME_TYPE'])
	&& $_REQUEST['IFRAME'] === 'Y'
	&& $_REQUEST['IFRAME_TYPE'] === 'SIDE_SLIDER';

$pathToList = $categoryId > 0
	? CComponentEngine::MakePathFromTemplate(
		$arResult['PATH_TO_COMPANY_CATEGORY'] ?? '',
		['category_id' => $categoryId]
	)
	: $arResult['PATH_TO_COMPANY_LIST'] ?? '';

$analytics = [
	'c_section' => $isMyCompanyMode ? \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_MYCOMPANY : \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_COMPANY,
	'c_sub_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_LIST,
];

if (!$isSlider)
{
	/** @var CMain $APPLICATION */
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		[
			'ID' => $cpID,
			'ACTIVE_ITEM_ID' => CCrmComponentHelper::getMenuActiveItemId($cpActiveItemID, $categoryId),
			'PATH_TO_COMPANY_LIST' => (isset($arResult['PATH_TO_COMPANY_LIST']) && !$isMyCompanyMode) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => (isset($arResult['PATH_TO_COMPANY_EDIT']) && !$isMyCompanyMode) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
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
			'MYCOMPANY_MODE' => $isMyCompanyMode ? 'Y' : 'N',
			'PATH_TO_COMPANY_WIDGET' => $arResult['PATH_TO_COMPANY_WIDGET'] ?? '',
			'PATH_TO_COMPANY_PORTRAIT' => $arResult['PATH_TO_COMPANY_PORTRAIT'] ?? '',
			'ANALYTICS' => $analytics,
		],
		$component
	);
}

if (!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Company))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array());
}
else
{
	if (!$isMyCompanyMode)
	{
		$APPLICATION->IncludeComponent(
			'bitrix:crm.entity.counter.panel',
			'',
			[
				'ENTITY_TYPE_NAME' => CCrmOwnerType::CompanyName,
				'EXTRAS' => [
					'CATEGORY_ID' => $categoryId,
				],
				'PATH_TO_ENTITY_LIST' => $pathToList,
			]
		);
	}

	$APPLICATION->ShowViewContent('crm-grid-filter');

	if (!$isSlider)
	{
		$APPLICATION->IncludeComponent(
			'bitrix:crm.dedupe.autosearch',
			'',
			[
				'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
				'PATH_TO_MERGE' => $arResult['PATH_TO_COMPANY_MERGE'] ?? '',
				'PATH_TO_DEDUPELIST' => $arResult['PATH_TO_COMPANY_DEDUPELIST'] ?? '',
			],
			$component,
			['HIDE_ICONS' => 'Y']
		);
	}
	$APPLICATION->IncludeComponent(
		'bitrix:crm.company.menu',
		'',
		[
			'PATH_TO_COMPANY_LIST' => $arResult['PATH_TO_COMPANY_LIST'] ?? '',
			'PATH_TO_COMPANY_SHOW' => $arResult['PATH_TO_COMPANY_SHOW'] ?? '',
			'PATH_TO_COMPANY_EDIT' => $arResult['PATH_TO_COMPANY_EDIT'] ?? '',
			'PATH_TO_COMPANY_IMPORT' => $arResult['PATH_TO_COMPANY_IMPORT'] ?? '',
			'PATH_TO_COMPANY_DEDUPE' => $arResult['PATH_TO_COMPANY_DEDUPE'] ?? '',
			'PATH_TO_COMPANY_DEDUPEWIZARD' => $arResult['PATH_TO_COMPANY_DEDUPEWIZARD'] ?? '',
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'] ?? '',
			'ELEMENT_ID' => $arResult['VARIABLES']['company_id'] ?? null,
			'CATEGORY_ID' => $categoryId,
			'TYPE' => 'list',
			'MYCOMPANY_MODE' => $isMyCompanyMode ? 'Y' : 'N',
			'IN_SLIDER' => $isSlider ? 'Y' : 'N',
			'ANALYTICS' => $analytics,
		],
		$component
	);

	if (\Bitrix\Main\ModuleManager::isModuleInstalled('rest'))
	{
		$APPLICATION->IncludeComponent(
			'bitrix:app.placement',
			'menu',
			[
				'PLACEMENT' => "CRM_COMPANY_LIST_MENU",
				"PLACEMENT_OPTIONS" => [],
				'INTERFACE_EVENT' => 'onCrmCompanyMenuInterfaceInit',
				'MENU_EVENT_MODULE' => 'crm',
				'MENU_EVENT' => 'onCrmCompanyListItemBuildMenu',
			],
			null,
			['HIDE_ICONS' => 'Y']
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.company.list',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'CATEGORY_ID' => $categoryId,
				'GRID_ID_SUFFIX' => (new \Bitrix\Crm\Component\EntityList\GridId(CCrmOwnerType::Company))
					->getDefaultSuffix($categoryId),
				'COMPANY_COUNT' => '20',
				'PATH_TO_COMPANY_LIST' => $pathToList,
				'PATH_TO_COMPANY_SHOW' => $arResult['PATH_TO_COMPANY_SHOW'] ?? '',
				'PATH_TO_COMPANY_EDIT' => $arResult['PATH_TO_COMPANY_EDIT'] ?? '',
				'PATH_TO_COMPANY_WIDGET' => $arResult['PATH_TO_COMPANY_WIDGET'] ?? '',
				'PATH_TO_CONTACT_EDIT' => $arResult['PATH_TO_CONTACT_EDIT'] ?? '',
				'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'] ?? '',
				'PATH_TO_COMPANY_MERGE' => $arResult['PATH_TO_COMPANY_MERGE'] ?? '',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'] ?? '',
				'MYCOMPANY_MODE' => $isMyCompanyMode ? 'Y' : 'N',
				'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'] ?? null,
				'ANALYTICS' => $analytics,
			],
			'USE_PADDING' => false,
			'CLOSE_AFTER_SAVE' => true,
			'RELOAD_PAGE_AFTER_SAVE' => false,
			'USE_LINK_TARGETS_REPLACING' => true,
			'USE_UI_TOOLBAR' => 'Y',
		]
	);
}
