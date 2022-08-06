<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

//show the crm type popup (with or without leads)
if (!\Bitrix\Crm\Settings\LeadSettings::isEnabled())
{
	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');
	?><script><?=\Bitrix\Crm\Settings\LeadSettings::showCrmTypePopup();?></script><?
}

if(!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Lead))
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

	$APPLICATION->ShowViewContent('crm-grid-filter');

	if(\Bitrix\Main\ModuleManager::isModuleInstalled('rest'))
	{
		$APPLICATION->IncludeComponent(
			'bitrix:app.placement',
			'menu',
			array(
				'PLACEMENT' => "CRM_LEAD_LIST_MENU",
				"PLACEMENT_OPTIONS" => array(),
				'INTERFACE_EVENT' => 'onCrmLeadListInterfaceInit',
				'MENU_EVENT_MODULE' => 'crm',
				'MENU_EVENT' => 'onCrmLeadListItemBuildMenu',
			),
			null,
			array('HIDE_ICONS' => 'Y')
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
				'GRID_ID_SUFFIX' => 'ANALYTIC_REPORT',
				'PATH_TO_LEAD_SHOW' => $arResult['PATH_TO_LEAD_SHOW'],
				'PATH_TO_LEAD_EDIT' => $arResult['PATH_TO_LEAD_EDIT'],
				'PATH_TO_LEAD_CONVERT' => $arResult['PATH_TO_LEAD_CONVERT'],
				'PATH_TO_LEAD_WIDGET' => $arResult['PATH_TO_LEAD_WIDGET'],
				'PATH_TO_LEAD_KANBAN' => $arResult['PATH_TO_LEAD_KANBAN'],
				'PATH_TO_LEAD_CALENDAR' => $arResult['PATH_TO_LEAD_CALENDAR'],
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'],
				'DISABLE_NAVIGATION_BAR' => 'Y',
				'HIDE_FILTER' => !$applyFilter
			],
			'USE_UI_TOOLBAR' => 'Y',
		]
	);
}
?>
