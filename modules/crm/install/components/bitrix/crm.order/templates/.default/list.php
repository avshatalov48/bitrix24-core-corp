<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/** @var CMain $APPLICATION */

if(!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Order))
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

	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.counter.panel',
		'',
		array('ENTITY_TYPE_NAME' => CCrmOwnerType::OrderName)
	);

	if($isBitrix24Template)
	{
		$this->EndViewTarget();
	}

	$APPLICATION->ShowViewContent('crm-grid-filter');
	
	$APPLICATION->IncludeComponent(
		'bitrix:crm.order.menu',
		'',
		array(
			'PATH_TO_ORDER_LIST' => $arResult['PATH_TO_ORDER_LIST'],
			'PATH_TO_ORDER_SHOW' => $arResult['PATH_TO_ORDER_SHOW'],
			'PATH_TO_ORDER_EDIT' => $arResult['PATH_TO_ORDER_EDIT'],
			'PATH_TO_ORDER_IMPORT' => $arResult['PATH_TO_ORDER_IMPORT'],
			'PATH_TO_ORDER_PAYMENT' => $arResult['PATH_TO_ORDER_PAYMENT'],
			'ELEMENT_ID' => $arResult['VARIABLES']['order_id'],
			'TYPE' => 'list'
		),
		$component
	);

	/*
	if(\Bitrix\Main\ModuleManager::isModuleInstalled('rest'))
	{
		$APPLICATION->IncludeComponent(
			'bitrix:app.placement',
			'menu',
			array(
				'PLACEMENT' => "CRM_ORDER_LIST_MENU",
				"PLACEMENT_OPTIONS" => array(),
				'INTERFACE_EVENT' => 'onCrmOrderListInterfaceInit',
				'MENU_EVENT_MODULE' => 'crm',
				'MENU_EVENT' => 'onCrmOrderListItemBuildMenu',
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}
	*/

	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.order.list',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'ORDER_COUNT' => '20',
				'PATH_TO_ORDER_SHOW' => $arResult['PATH_TO_ORDER_SHOW'],
				'PATH_TO_ORDER_EDIT' => $arResult['PATH_TO_ORDER_EDIT'],
				'PATH_TO_ORDER_KANBAN' => $arResult['PATH_TO_ORDER_KANBAN'],
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'],
			]
		],
		$component
	);
}
?>