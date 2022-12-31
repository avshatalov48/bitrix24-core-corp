<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

Bitrix\Crm\Settings\Crm::markAsInitiated();

$categoryID = isset($arResult['VARIABLES']['category_id'])
	? (int)$arResult['VARIABLES']['category_id']
	: -1;
$isSlider = ($_REQUEST['IFRAME'] == 'Y' && $_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER');
if (!$isSlider)
{
	/** @var CMain $APPLICATION */
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'DEAL_LIST',
			'ACTIVE_ITEM_ID' => 'DEAL',
			'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT' => isset($arResult['PATH_TO_CONTACT_EDIT']) ? $arResult['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_WIDGET' => isset($arResult['PATH_TO_DEAL_WIDGET']) ? $arResult['PATH_TO_DEAL_WIDGET'] : '',
			'PATH_TO_DEAL_LIST' => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_EDIT' => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
			'PATH_TO_DEAL_CATEGORY' => isset($arResult['PATH_TO_DEAL_CATEGORY']) ? $arResult['PATH_TO_DEAL_CATEGORY'] : '',
			'PATH_TO_DEAL_WIDGETCATEGORY' => isset($arResult['PATH_TO_DEAL_WIDGETCATEGORY']) ? $arResult['PATH_TO_DEAL_WIDGETCATEGORY'] : '',
			'PATH_TO_LEAD_LIST' => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_LEAD_EDIT' => isset($arResult['PATH_TO_LEAD_EDIT']) ? $arResult['PATH_TO_LEAD_EDIT'] : '',
			'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
			'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
			'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
			'PATH_TO_ORDER_LIST' => isset($arResult['PATH_TO_ORDER_LIST']) ? $arResult['PATH_TO_ORDER_LIST'] : '',
			'PATH_TO_ORDER_EDIT' => isset($arResult['PATH_TO_ORDER_EDIT']) ? $arResult['PATH_TO_ORDER_EDIT'] : '',
			'PATH_TO_REPORT_LIST' => isset($arResult['PATH_TO_REPORT_LIST']) ? $arResult['PATH_TO_REPORT_LIST'] : '',
			'PATH_TO_DEAL_FUNNEL' => isset($arResult['PATH_TO_DEAL_FUNNEL']) ? $arResult['PATH_TO_DEAL_FUNNEL'] : '',
			'PATH_TO_EVENT_LIST' => isset($arResult['PATH_TO_EVENT_LIST']) ? $arResult['PATH_TO_EVENT_LIST'] : '',
			'PATH_TO_PRODUCT_LIST' => isset($arResult['PATH_TO_PRODUCT_LIST']) ? $arResult['PATH_TO_PRODUCT_LIST'] : '',
			//'COUNTER_EXTRAS' => array('DEAL_CATEGORY_ID' => $categoryID)
		),
		$component
	);
}

if(!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Deal))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array());
}
else
{
	$isBitrix24Template = SITE_TEMPLATE_ID === 'bitrix24';

	if ($arResult['IS_RECURRING'] !== 'Y')
	{
		$APPLICATION->IncludeComponent(
			'bitrix:crm.entity.counter.panel',
			'',
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::DealName,
				'EXTRAS' => array('DEAL_CATEGORY_ID' => $categoryID),
				'PATH_TO_ENTITY_LIST' =>
					$categoryID < 0
						? $arResult['PATH_TO_DEAL_LIST']
						: CComponentEngine::makePathFromTemplate(
						$arResult['PATH_TO_DEAL_CATEGORY'],
						array('category_id' => $categoryID)
					)
			)
		);
	}

	if($isBitrix24Template)
	{
		$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
		$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'crm-toolbar-modifier');
	}

	$APPLICATION->ShowViewContent('crm-grid-filter');

	if (!$isSlider)
	{
		$APPLICATION->IncludeComponent(
			'bitrix:crm.dedupe.autosearch',
			'',
			[
				'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
				'PATH_TO_MERGE' => $arResult['PATH_TO_COMPANY_MERGE'],
				'PATH_TO_DEDUPELIST' => $arResult['PATH_TO_COMPANY_DEDUPELIST']
			],
			$component,
			['HIDE_ICONS' => 'Y']
		);

		$APPLICATION->IncludeComponent(
			'bitrix:crm.dedupe.autosearch',
			'',
			[
				'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
				'PATH_TO_MERGE' => $arResult['PATH_TO_CONTACT_MERGE'],
				'PATH_TO_DEDUPELIST' => $arResult['PATH_TO_CONTACT_DEDUPELIST']
			],
			$component,
			['HIDE_ICONS' => 'Y']
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.deal.menu',
		'',
		[
			'PATH_TO_DEAL_LIST' => $arResult['PATH_TO_DEAL_LIST'],
			'PATH_TO_DEAL_SHOW' => $arResult['PATH_TO_DEAL_SHOW'],
			'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'],
			'PATH_TO_DEAL_FUNNEL' => $arResult['PATH_TO_DEAL_FUNNEL'],
			'PATH_TO_DEAL_IMPORT' => $arResult['PATH_TO_DEAL_IMPORT'],
			'PATH_TO_DEAL_RECUR' => $arResult['PATH_TO_DEAL_RECUR'],
			'PATH_TO_DEAL_RECUR_CATEGORY' => $arResult['PATH_TO_DEAL_RECUR_CATEGORY'],
			'IS_RECURRING' => $arResult['IS_RECURRING'],
			'ELEMENT_ID' => 0,
			'CATEGORY_ID' => $categoryID,
			'TYPE' => 'list',
			'IN_SLIDER' => $isSlider ? 'Y' : 'N',
		],
		$component
	);

	if(!$isSlider)
	{
		$catalogPath = ($arResult['IS_RECURRING'] !== 'Y')
			? $arResult['PATH_TO_DEAL_CATEGORY']
			: $arResult['PATH_TO_DEAL_RECUR_CATEGORY'];

		$APPLICATION->IncludeComponent(
			'bitrix:crm.deal_category.panel',
			$isBitrix24Template ? 'tiny' : '',
			[
				'PATH_TO_DEAL_LIST' => $arResult['PATH_TO_DEAL_LIST'],
				'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'],
				'PATH_TO_DEAL_CATEGORY' => $catalogPath,
				'PATH_TO_DEAL_CATEGORY_LIST' => $arResult['PATH_TO_DEAL_CATEGORY_LIST'],
				'PATH_TO_DEAL_CATEGORY_EDIT' => $arResult['PATH_TO_DEAL_CATEGORY_EDIT'],
				'CATEGORY_ID' => $categoryID
			],
			$component
		);
	}

	if(\Bitrix\Main\ModuleManager::isModuleInstalled('rest'))
	{
		$APPLICATION->IncludeComponent(
			'bitrix:app.placement',
			'menu',
			array(
				'PLACEMENT' => "CRM_DEAL_LIST_MENU",
				"PLACEMENT_OPTIONS" => array(),
				'INTERFACE_EVENT' => 'onCrmDealListInterfaceInit',
				'MENU_EVENT_MODULE' => 'crm',
				'MENU_EVENT' => 'onCrmDealListItemBuildMenu',
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.deal.list',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'DEAL_COUNT' => '20',
				'IS_RECURRING' => $arResult['IS_RECURRING'],
				'PATH_TO_DEAL_RECUR_SHOW' => $arResult['PATH_TO_DEAL_RECUR_SHOW'],
				'PATH_TO_DEAL_RECUR' => $arResult['PATH_TO_DEAL_RECUR'],
				'PATH_TO_DEAL_RECUR_EDIT' => $arResult['PATH_TO_DEAL_RECUR_EDIT'],
				'PATH_TO_DEAL_LIST' => $arResult['PATH_TO_DEAL_LIST'],
				'PATH_TO_DEAL_SHOW' => $arResult['PATH_TO_DEAL_SHOW'],
				'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'],
				'PATH_TO_DEAL_DETAILS' => $arResult['PATH_TO_DEAL_DETAILS'],
				'PATH_TO_DEAL_WIDGET' => $arResult['PATH_TO_DEAL_WIDGET'],
				'PATH_TO_DEAL_KANBAN' => $arResult['PATH_TO_DEAL_KANBAN'],
				'PATH_TO_DEAL_CALENDAR' => $arResult['PATH_TO_DEAL_CALENDAR'],
				'PATH_TO_DEAL_CATEGORY' => $arResult['PATH_TO_DEAL_CATEGORY'],
				'PATH_TO_DEAL_MERGE' => $arResult['PATH_TO_DEAL_MERGE'],
				'PATH_TO_DEAL_RECUR_CATEGORY' => $arResult['PATH_TO_DEAL_RECUR_CATEGORY'],
				'PATH_TO_DEAL_WIDGETCATEGORY' => $arResult['PATH_TO_DEAL_WIDGETCATEGORY'],
				'PATH_TO_DEAL_KANBANCATEGORY' => $arResult['PATH_TO_DEAL_KANBANCATEGORY'],
				'PATH_TO_DEAL_CALENDARCATEGORY' => $arResult['PATH_TO_DEAL_CALENDARCATEGORY'],
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'],
				'GRID_ID_SUFFIX' => (new \Bitrix\Crm\Component\EntityList\GridId(CCrmOwnerType::Deal))
					->getDefaultSuffix($categoryID),
				'DISABLE_NAVIGATION_BAR' => ($arResult['IS_RECURRING'] === 'Y' && $isSlider) ? 'Y' : 'N',
				'CATEGORY_ID' => $categoryID
			],
			'USE_UI_TOOLBAR' => 'Y',
		]
	);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.deal.checker',
		'',
		['CATEGORY_ID' => $categoryID],
		null,
		['HIDE_ICONS' => 'Y']
	);
}
?>
