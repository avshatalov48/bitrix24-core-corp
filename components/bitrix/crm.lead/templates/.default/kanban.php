<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

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
use Bitrix\Main\Localization\Loc;
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead.menu/component.php');
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead.list/templates/.default/template.php');

\Bitrix\Crm\Settings\Crm::markAsInitiated();

// if not isset
$arResult['PATH_TO_LEAD_EDIT'] = isset($arResult['PATH_TO_LEAD_EDIT']) ? $arResult['PATH_TO_LEAD_EDIT'] : '';
$arResult['PATH_TO_LEAD_LIST'] = isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '';
$arResult['PATH_TO_LEAD_WIDGET'] = isset($arResult['PATH_TO_LEAD_WIDGET']) ? $arResult['PATH_TO_LEAD_WIDGET'] : '';
$arResult['PATH_TO_LEAD_KANBAN'] = isset($arResult['PATH_TO_LEAD_KANBAN']) ? $arResult['PATH_TO_LEAD_KANBAN'] : '';
$arResult['PATH_TO_LEAD_CALENDAR'] = isset($arResult['PATH_TO_LEAD_CALENDAR']) ? $arResult['PATH_TO_LEAD_CALENDAR'] : '';
$arResult['PATH_TO_LEAD_DEDUPE'] = isset($arResult['PATH_TO_LEAD_DEDUPE']) ? $arResult['PATH_TO_LEAD_DEDUPE'] : '';
$arResult['PATH_TO_LEAD_IMPORT'] = isset($arResult['PATH_TO_LEAD_IMPORT']) ? $arResult['PATH_TO_LEAD_IMPORT'] : '';

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

// main menu
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'LEAD_LIST',
		'ACTIVE_ITEM_ID' => 'LEAD',
		'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_CONTACT_EDIT' => isset($arResult['PATH_TO_CONTACT_EDIT']) ? $arResult['PATH_TO_CONTACT_EDIT'] : '',
		'PATH_TO_DEAL_LIST' => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
		'PATH_TO_DEAL_EDIT' => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
		'PATH_TO_LEAD_LIST' => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
		'PATH_TO_LEAD_EDIT' => isset($arResult['PATH_TO_LEAD_EDIT']) ? $arResult['PATH_TO_LEAD_EDIT'] : '',
		'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
		'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
		'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
		'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
		'PATH_TO_REPORT_LIST' => isset($arResult['PATH_TO_REPORT_LIST']) ? $arResult['PATH_TO_REPORT_LIST'] : '',
		'PATH_TO_DEAL_FUNNEL' => isset($arResult['PATH_TO_DEAL_FUNNEL']) ? $arResult['PATH_TO_DEAL_FUNNEL'] : '',
		'PATH_TO_EVENT_LIST' => isset($arResult['PATH_TO_EVENT_LIST']) ? $arResult['PATH_TO_EVENT_LIST'] : '',
		'PATH_TO_PRODUCT_LIST' => isset($arResult['PATH_TO_PRODUCT_LIST']) ? $arResult['PATH_TO_PRODUCT_LIST'] : ''
	),
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

	// counters
	$this->SetViewTarget('below_pagetitle', 1000);
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.counter.panel',
		'',
		array(
			'ENTITY_TYPE_NAME' => $entityType,
			'EXTRAS' => array(),
			'PATH_TO_ENTITY_LIST' => $arResult['PATH_TO_LEAD_KANBAN']
		)
	);
	$this->EndViewTarget();

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
			'DISABLE_EXPORT' => 'Y'
		),
		$component
	);

	// filter
	$APPLICATION->IncludeComponent(
		'bitrix:crm.kanban.filter',
		'',
		array(
			'ENTITY_TYPE' => $entityType,
			'NAVIGATION_BAR' => array(
				'ITEMS' => array_merge(
					\Bitrix\Crm\Automation\Helper::getNavigationBarItems(\CCrmOwnerType::Lead),
					array(
						array(
							//'icon' => 'kanban',
							'id' => 'kanban',
							'name' => Loc::getMessage('CRM_LEAD_LIST_FILTER_NAV_BUTTON_KANBAN'),
							'active' => 1,
							'url' => $arResult['PATH_TO_LEAD_KANBAN']
						),
						array(
							//'icon' => 'table',
							'id' => 'list',
							'name' => Loc::getMessage('CRM_LEAD_LIST_FILTER_NAV_BUTTON_LIST'),
							'active' => 0,
							'url' => $arResult['PATH_TO_LEAD_LIST']
						)
					),
					(\Bitrix\Crm\Integration\Calendar::isResourceBookingEnabled()
						?
						array(
							array(
								'id' => 'calendar',
								'name' => GetMessage('CRM_LEAD_LIST_FILTER_NAV_BUTTON_CALENDAR'),
								'active' => 0,
								'url' => $arResult['PATH_TO_LEAD_CALENDAR']
							)
						)
						: array()
					)
				),
				'BINDING' => array(
					'category' => 'crm.navigation',
					'name' => 'index',
					'key' => mb_strtolower($arResult['NAVIGATION_CONTEXT_ID'])
				)
			)
		),
		$component,
		array('HIDE_ICONS' => true)
	);

	/*
	$supervisorInv = \Bitrix\Crm\Kanban\SupervisorTable::isSupervisor($entityType) ? 'N' : 'Y';
	CCrmUrlUtil::AddUrlParams(
							CComponentEngine::MakePathFromTemplate(
								$arResult['PATH_TO_LEAD_KANBAN']
							),
							array('supervisor' => $supervisorInv, 'clear_filter' => 'Y')
						)
	 */

	\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();

	$APPLICATION->IncludeComponent(
		'bitrix:crm.kanban',
		'',
		array(
			'ENTITY_TYPE' => $entityType,
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
		),
		$component
	);
}
