<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
$APPLICATION->SetTitle(GetMessage('CRM_LEAD_DEDUPE_WIZARD_PAGE_TITLE'));

if (!(isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y"))
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'LEAD_DEDUPE_WIZARD',
			'ACTIVE_ITEM_ID' => 'LEAD',
			'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_DEAL_WIDGET' => isset($arResult['PATH_TO_DEAL_WIDGET']) ? $arResult['PATH_TO_DEAL_WIDGET'] : '',
			'PATH_TO_DEAL_INDEX' => isset($arResult['PATH_TO_DEAL_INDEX']) ? $arResult['PATH_TO_DEAL_INDEX'] : '',
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
}
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.dedupe.wizard',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'GUID' => 'lead_dedupe_wizard',
			'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
			'PATH_TO_MERGER' => '/crm/lead/merge/',
			'PATH_TO_DEDUPE_LIST' => '/crm/lead/dedupelist/',
			'PATH_TO_ENTITY_LIST' => '/crm/lead/?redirect_to',
		]
	]
);