<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;

$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'COMPANY_PORTRAIT',
		'ACTIVE_ITEM_ID' => 'COMPANY',
		'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_COMPANY_WIDGET' => isset($arResult['PATH_TO_COMPANY_WIDGET']) ? $arResult['PATH_TO_COMPANY_WIDGET'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_CONTACT_WIDGET' => isset($arResult['PATH_TO_CONTACT_WIDGET']) ? $arResult['PATH_TO_CONTACT_WIDGET'] : '',
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

if(!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Company))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array());
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.company.menu',
		'',
		array(
			'PATH_TO_COMPANY_LIST' => $arResult['PATH_TO_COMPANY_LIST'],
			'PATH_TO_COMPANY_SHOW' => $arResult['PATH_TO_COMPANY_SHOW'],
			'PATH_TO_COMPANY_EDIT' => $arResult['PATH_TO_COMPANY_EDIT'],
			'PATH_TO_COMPANY_IMPORT' => $arResult['PATH_TO_COMPANY_IMPORT'],
			'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'],
			'PATH_TO_CONTACT_EDIT' => $arResult['PATH_TO_CONTACT_EDIT'],
			'PATH_TO_COMPANY_PORTRAIT' => $arResult['PATH_TO_COMPANY_PORTRAIT'],
			'ELEMENT_ID' => $arResult['VARIABLES']['company_id'],
			'TYPE' => 'portrait'
		),
		$component
	);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.client.portrait',
		'',
		array(
			'PATH_TO_COMPANY_SHOW' => $arResult['PATH_TO_COMPANY_SHOW'],
			'PATH_TO_COMPANY_LIST' => $arResult['PATH_TO_COMPANY_LIST'],
			'PATH_TO_COMPANY_EDIT' => $arResult['PATH_TO_COMPANY_EDIT'],
			'PATH_TO_LEAD_EDIT' => $arResult['PATH_TO_LEAD_EDIT'],
			'PATH_TO_LEAD_SHOW' => $arResult['PATH_TO_LEAD_SHOW'],
			'PATH_TO_LEAD_CONVERT' => $arResult['PATH_TO_LEAD_CONVERT'],
			'PATH_TO_CONTACT_EDIT' => $arResult['PATH_TO_CONTACT_EDIT'],
			'PATH_TO_CONTACT_SHOW' => $arResult['PATH_TO_CONTACT_SHOW'],
			'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'],
			'PATH_TO_DEAL_SHOW' => $arResult['PATH_TO_DEAL_SHOW'],
			'PATH_TO_REQUISITE_EDIT' => $arResult['PATH_TO_REQUISITE_EDIT'],
			'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE'],
			'PATH_TO_COMPANY_PORTRAIT' => $arResult['PATH_TO_COMPANY_PORTRAIT'],
			'ELEMENT_ID' => $arResult['VARIABLES']['company_id'],
			'ELEMENT_TYPE' => CCrmOwnerType::Company,
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
		),
		$component
	);
	
}
?>