<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

// js/css
$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/bitrix24/crm-entity-show.css');
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-paddings grid-mode pagetitle-toolbar-field-view crm-toolbar');
$asset = Bitrix\Main\Page\Asset::getInstance();
$asset->addJs('/bitrix/js/crm/common.js');

// some common langs
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\UI\NavigationBarPanel;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.invoice.menu/component.php');
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.invoice.list/templates/.default/template.php');

// if not isset
$arResult['PATH_TO_INVOICE_EDIT'] = isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '';
$arResult['PATH_TO_INVOICE_LIST'] = isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '';
$arResult['PATH_TO_INVOICE_KANBAN'] = isset($arResult['PATH_TO_INVOICE_KANBAN']) ? $arResult['PATH_TO_INVOICE_KANBAN'] : '';

// csv and excel delegate to list
$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();
if (in_array($request->get('type'), array('csv', 'excel')))
{
	LocalRedirect(str_replace(
				$arResult['PATH_TO_INVOICE_KANBAN'],
				$arResult['PATH_TO_INVOICE_LIST'],
				$APPLICATION->getCurPageParam()
			), true);
}

// main menu
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'INVOICE_LIST',
		'ACTIVE_ITEM_ID' => 'INVOICE',
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

// chack rights
if (!\CCrmPerms::IsAccessEnabled())
{
	return false;
}

// check accessable
if (!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Invoice))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array());
}
else
{
	$entityType = \CCrmOwnerType::InvoiceName;

	// menu
	$APPLICATION->IncludeComponent(
		'bitrix:crm.invoice.menu',
		'',
		array(
			'PATH_TO_INVOICE_LIST' => $arResult['PATH_TO_INVOICE_LIST'],
			'PATH_TO_INVOICE_EDIT' => $arResult['PATH_TO_INVOICE_EDIT'],
			'PATH_TO_INVOICE_RECUR' => $arResult['PATH_TO_INVOICE_RECUR'],
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
		[
			'ENTITY_TYPE' => $entityType,
			'NAVIGATION_BAR' => (new NavigationBarPanel(CCrmOwnerType::Invoice))
				->setItems([
					NavigationBarPanel::ID_KANBAN,
					NavigationBarPanel::ID_LIST
				], NavigationBarPanel::ID_KANBAN)
				->setBinding($arResult['NAVIGATION_CONTEXT_ID'])
				->get(),
		],
		$component,
		['HIDE_ICONS' => true]
	);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.kanban',
		'',
		array(
			'ENTITY_TYPE' => $entityType
		),
		$component
	);
}
