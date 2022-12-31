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

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.order.list/templates/.default/template.php');

// if not isset
$arResult['PATH_TO_INVOICE_EDIT'] = isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '';
$arResult['PATH_TO_INVOICE_LIST'] = isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '';
$arResult['PATH_TO_INVOICE_WIDGET'] = isset($arResult['PATH_TO_INVOICE_WIDGET']) ? $arResult['PATH_TO_INVOICE_WIDGET'] : '';
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

// chack rights
if (!\CCrmPerms::IsAccessEnabled())
{
	return false;
}

// check accessable
if (!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Order))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array());
}
else
{
	$entityType = \CCrmOwnerType::OrderName;

	// counters stub
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.counter.panel',
		'',
		array('ENTITY_TYPE_NAME' => $entityType)
	);

	// menu
	$APPLICATION->IncludeComponent(
		'bitrix:crm.order.menu',
		'',
		array(
			'PATH_TO_ORDER_LIST' => $arResult['PATH_TO_ORDER_LIST'],
			'PATH_TO_ORDER_DETAILS' => $arResult['PATH_TO_ORDER_DETAILS'],
			'ELEMENT_ID' => 0,
			'TYPE' => 'kanban',
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
			'NAVIGATION_BAR' => (new NavigationBarPanel(CCrmOwnerType::Order))
				->setItems([
					NavigationBarPanel::ID_AUTOMATION,
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
			'ENTITY_TYPE' => $entityType,
			'SHOW_ACTIVITY' => 'Y',
			'PATH_TO_ORDER_SHIPMENT_DETAILS' => $arResult['PATH_TO_ORDER_SHIPMENT_DETAILS'],
			'PATH_TO_ORDER_PAYMENT_DETAILS' => $arResult['PATH_TO_ORDER_PAYMENT_DETAILS'],
			'PATH_TO_BUYER_PROFILE' => $arResult['PATH_TO_BUYER_PROFILE'],
		),
		$component
	);
}
