<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$categoryID = isset($arResult['VARIABLES']['category_id']) ? (int)$arResult['VARIABLES']['category_id'] : -1;

if(!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Deal))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array());
}
else
{
	$isBitrix24Template = SITE_TEMPLATE_ID === 'bitrix24';
	if($isBitrix24Template)
	{
		$this->SetViewTarget('below_pagetitle', 0);
	}

	$applyFilter = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('apply_filter') === 'Y';

	if($isBitrix24Template)
	{
		$this->EndViewTarget();
	}

	if($isBitrix24Template)
	{
		$this->SetViewTarget('inside_pagetitle', 100);
	}
	$catalogPath = ($arResult['IS_RECURRING'] !== 'Y') ? $arResult['PATH_TO_DEAL_CATEGORY'] : $arResult['PATH_TO_DEAL_RECUR_CATEGORY'];

	if(SITE_TEMPLATE_ID === 'bitrix24')
	{
		$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
		$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'crm-toolbar-modifier');
	}

	if($isBitrix24Template)
	{
		$this->SetViewTarget('inside_pagetitle', 100);
	}

	if($isBitrix24Template)
	{
		$this->EndViewTarget();
	}
	$APPLICATION->ShowViewContent('crm-grid-filter');

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
				'PATH_TO_DEAL_RECUR_CATEGORY' => $arResult['PATH_TO_DEAL_RECUR_CATEGORY'],
				'PATH_TO_DEAL_WIDGETCATEGORY' => $arResult['PATH_TO_DEAL_WIDGETCATEGORY'],
				'PATH_TO_DEAL_KANBANCATEGORY' => $arResult['PATH_TO_DEAL_KANBANCATEGORY'],
				'PATH_TO_DEAL_CALENDARCATEGORY' => $arResult['PATH_TO_DEAL_CALENDARCATEGORY'],
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'],
				'DISABLE_NAVIGATION_BAR' => 'Y',
				'GRID_ID_SUFFIX' => $categoryID >= 0 ? "C_{$categoryID}" : '',
				'CATEGORY_ID' => $categoryID,
				'HIDE_FILTER' => !$applyFilter
			],
			'USE_UI_TOOLBAR' => 'Y',
		]
	);
}
?>
