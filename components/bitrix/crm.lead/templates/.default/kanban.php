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

// js/css
$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/bitrix24/crm-entity-show.css');
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-paddings grid-mode pagetitle-toolbar-field-view crm-toolbar');
$asset = Bitrix\Main\Page\Asset::getInstance();
$asset->addJs('/bitrix/js/crm/common.js');

// some common langs
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\Kanban\ViewMode;
use Bitrix\Crm\UI\NavigationBarPanel;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead.menu/component.php');
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead.list/templates/.default/template.php');

\Bitrix\Crm\Settings\Crm::markAsInitiated();

// if not isset
$arResult['PATH_TO_LEAD_EDIT'] = ($arResult['PATH_TO_LEAD_EDIT'] ?? '');
$arResult['PATH_TO_LEAD_LIST'] = ($arResult['PATH_TO_LEAD_LIST'] ?? '');
$arResult['PATH_TO_LEAD_WIDGET'] = ($arResult['PATH_TO_LEAD_WIDGET'] ?? '');
$arResult['PATH_TO_LEAD_KANBAN'] = ($arResult['PATH_TO_LEAD_KANBAN'] ?? '');
$arResult['PATH_TO_LEAD_ACTIVITY'] = ($arResult['PATH_TO_LEAD_ACTIVITY'] ?? '');
$arResult['PATH_TO_LEAD_CALENDAR'] = ($arResult['PATH_TO_LEAD_CALENDAR'] ?? '');
$arResult['PATH_TO_LEAD_DEDUPE'] = ($arResult['PATH_TO_LEAD_DEDUPE'] ?? '');
$arResult['PATH_TO_LEAD_IMPORT'] = ($arResult['PATH_TO_LEAD_IMPORT'] ?? '');

// csv and excel delegate to list
$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();
if (in_array($request->get('type'), array('csv', 'excel')))
{
	LocalRedirect(str_replace(
				$arResult['PATH_TO_LEAD_KANBAN'],
				$arResult['PATH_TO_LEAD_LIST'],
				$APPLICATION->getCurPageParam()
			), true);
}

$kanbanViewMode = $arResult['KANBAN_VIEW_MODE'] ?? null;
$subSection = (
	$kanbanViewMode === ViewMode::MODE_ACTIVITIES
		? Dictionary::SUB_SECTION_ACTIVITIES
		: Dictionary::SUB_SECTION_KANBAN
);

$analytics = [
	'c_section' => Dictionary::SECTION_LEAD,
	'c_sub_section' => $subSection,
];

// main menu
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	[
		'ID' => 'LEAD_LIST',
		'ACTIVE_ITEM_ID' => \CCrmOwnerType::LeadName,
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
		'ANALYTICS' => $analytics,
	],
	$component
);

// check rights
if (!\CCrmPerms::IsAccessEnabled())
{
	return false;
}

// check accessable
if (!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Lead))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array());
}
else
{
	$entityType = \CCrmOwnerType::LeadName;

	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.counter.panel',
		'',
		array(
			'ENTITY_TYPE_NAME' => $entityType,
			'EXTRAS' => array(),
			'PATH_TO_ENTITY_LIST' => $arResult['PATH_TO_LEAD_KANBAN']
		)
	);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.dedupe.autosearch',
		'',
		array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
			'PATH_TO_MERGE' => $arResult['PATH_TO_LEAD_MERGE'],
			'PATH_TO_DEDUPELIST' => $arResult['PATH_TO_LEAD_DEDUPELIST']
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);

	// menu
	$APPLICATION->IncludeComponent(
		'bitrix:crm.lead.menu',
		'',
		array(
			'PATH_TO_LEAD_LIST' => $arResult['PATH_TO_LEAD_LIST'],
			'PATH_TO_LEAD_EDIT' => $arResult['PATH_TO_LEAD_EDIT'],
			'PATH_TO_LEAD_DEDUPE' => $arResult['PATH_TO_LEAD_DEDUPE'],
			'PATH_TO_LEAD_DEDUPEWIZARD' => $arResult['PATH_TO_LEAD_DEDUPEWIZARD'],
			'PATH_TO_LEAD_IMPORT' => $arResult['PATH_TO_LEAD_IMPORT'],
			'ELEMENT_ID' => 0,
			'TYPE' => 'list',
			'DISABLE_EXPORT' => 'Y',
			'ANALYTICS' => $analytics,
		),
		$component
	);

	// filter
	$activeItemId = (
		$kanbanViewMode === ViewMode::MODE_ACTIVITIES
			? NavigationBarPanel::ID_ACTIVITY
			: NavigationBarPanel::ID_KANBAN
	);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.kanban.filter',
		'',
		[
			'ENTITY_TYPE' => $entityType,
			'NAVIGATION_BAR' => (new NavigationBarPanel(CCrmOwnerType::Lead))
				->setItems([
					NavigationBarPanel::ID_KANBAN,
					NavigationBarPanel::ID_LIST,
					NavigationBarPanel::ID_ACTIVITY,
					NavigationBarPanel::ID_CALENDAR,
					NavigationBarPanel::ID_AUTOMATION
				], $activeItemId)
				->setBinding($arResult['NAVIGATION_CONTEXT_ID'])
				->get(),
		],
		$component,
		[
			'HIDE_ICONS' => true,
		]
	);

	\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();

	$viewMode = ($kanbanViewMode ?? ViewMode::MODE_STAGES);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.kanban',
		'',
		[
			'ENTITY_TYPE' => $entityType,
			'VIEW_MODE' => $viewMode,
			'USE_ITEM_PLANNER' => ($kanbanViewMode === ViewMode::MODE_ACTIVITIES ? 'Y' : 'N'),
			'SHOW_ACTIVITY' => 'Y',
			'PATH_TO_IMPORT' => $arResult['PATH_TO_LEAD_IMPORT'],
			'PATH_TO_MERGE' => $arResult['PATH_TO_LEAD_MERGE'],
			'HEADERS_SECTIONS' => [
				[
					'id'=> CCrmOwnerType::LeadName,
					'name' => Loc::getMessage('CRM_COMMON_LEAD'),
					'default' => true,
					'selected' => true,
				],
			],
			'EXTRA' => [
				'ANALYTICS' => [
					'c_section' => Dictionary::SECTION_LEAD,
					'c_sub_section' => $subSection,
				],
			],
		],
		$component
	);
}
