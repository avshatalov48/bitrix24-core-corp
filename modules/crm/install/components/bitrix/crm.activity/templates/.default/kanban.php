<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Kanban\ViewMode;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UI\NavigationBarPanel;
use Bitrix\Main\UI\Extension;

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$bodyClassValue = ($bodyClass ? $bodyClass . ' ' : '') . 'no-paddings grid-mode pagetitle-toolbar-field-view crm-toolbar';
$APPLICATION->SetPageProperty('BodyClass', $bodyClassValue);

Extension::load(['crm_common']);

if ($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		[
			'ID' => 'ACTIVITY_LIST',
			'ACTIVE_ITEM_ID' => \CCrmOwnerType::ActivityName,
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
			'PATH_TO_PRODUCT_LIST' => $arResult['PATH_TO_PRODUCT_LIST'] ?? ''
		],
		$component
	);
}

if (!\CCrmPerms::IsAccessEnabled())
{
	return false;
}

$entityType = \CCrmOwnerType::ActivityName;

$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.counter.panel',
	'',
	[
		'ENTITY_TYPE_NAME' => $entityType,
		'EXTRAS' => [],
		'PATH_TO_ENTITY_LIST' => $arResult['PATH_TO_ACTIVITY_KANBAN'] ?? '',
	]
);

$viewMode = ViewMode::MODE_ACTIVITIES;
$APPLICATION->IncludeComponent(
	'bitrix:crm.kanban.filter',
	'',
	[
		'VIEW_MODE' => $viewMode,
		'ENTITY_TYPE' => $entityType,
		'CUSTOM_SECTION_CODE' => $arResult['CUSTOM_SECTION_CODE'],
		'NAVIGATION_BAR' => (new NavigationBarPanel(CCrmOwnerType::Activity))
			->setCustomSectionCode($arResult['CUSTOM_SECTION_CODE'])
			->setItems([
				NavigationBarPanel::ID_KANBAN,
				NavigationBarPanel::ID_LIST,
				//NavigationBarPanel::ID_REPORTS,
			], NavigationBarPanel::ID_KANBAN)
			->setBinding($arResult['NAVIGATION_CONTEXT_ID'])
			->get(),
	],
	$component,
	[
		'HIDE_ICONS' => true,
	]
);

Container::getInstance()->getLocalization()->loadMessages();

$APPLICATION->IncludeComponent(
	'bitrix:crm.kanban',
	'',
	[
		'ENTITY_TYPE' => $entityType,
		'VIEW_MODE' => $viewMode,
		'SHOW_ACTIVITY' => 'N',
		'SKIP_COLUMN_COUNT_CHECK' => 'Y',
		'USE_PUSH_CRM' => 'N',
		'EXTRA' => [
			'CUSTOM_SECTION_CODE' => $arResult['CUSTOM_SECTION_CODE'],
		],
	],
	$component
);
