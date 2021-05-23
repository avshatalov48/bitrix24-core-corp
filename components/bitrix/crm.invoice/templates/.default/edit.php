<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/** @var CMain $APPLICATION */
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'INVOICE_EDIT',
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
		'PATH_TO_INVOICE_RECUR' => isset($arResult['PATH_TO_INVOICE_RECUR']) ? $arResult['PATH_TO_INVOICE_RECUR'] : '',
		'PATH_TO_REPORT_LIST' => isset($arResult['PATH_TO_REPORT_LIST']) ? $arResult['PATH_TO_REPORT_LIST'] : '',
		'PATH_TO_DEAL_FUNNEL' => isset($arResult['PATH_TO_DEAL_FUNNEL']) ? $arResult['PATH_TO_DEAL_FUNNEL'] : '',
		'PATH_TO_EVENT_LIST' => isset($arResult['PATH_TO_EVENT_LIST']) ? $arResult['PATH_TO_EVENT_LIST'] : '',
		'PATH_TO_PRODUCT_LIST' => isset($arResult['PATH_TO_PRODUCT_LIST']) ? $arResult['PATH_TO_PRODUCT_LIST'] : ''
	),
	$component
);

if(!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Invoice))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array());
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.invoice.menu',
		'',
		array(
			'PATH_TO_INVOICE_LIST' => $arResult['PATH_TO_INVOICE_LIST'],
			'PATH_TO_INVOICE_SHOW' => $arResult['PATH_TO_INVOICE_SHOW'],
			'PATH_TO_INVOICE_EDIT' => $arResult['PATH_TO_INVOICE_EDIT'],
			'ELEMENT_ID' => $arResult['VARIABLES']['invoice_id'],
			'IS_RECURRING' => $arResult['IS_RECURRING'],
			'PATH_TO_INVOICE_RECUR_EDIT' => $arResult['PATH_TO_INVOICE_RECUR_EDIT'],
			'PATH_TO_INVOICE_RECUR_SHOW' => $arResult['PATH_TO_INVOICE_RECUR_SHOW'],
			'PATH_TO_INVOICE_RECUR' => $arResult['PATH_TO_INVOICE_RECUR'],
			'TYPE' => 'edit'
		),
		$component
	);
	$APPLICATION->IncludeComponent(
		'bitrix:crm.invoice.edit',
		'',
		array(
			'PATH_TO_INVOICE_SHOW' => $arResult['PATH_TO_INVOICE_SHOW'],
			'PATH_TO_INVOICE_LIST' => $arResult['PATH_TO_INVOICE_LIST'],
			'PATH_TO_INVOICE_EDIT' => $arResult['PATH_TO_INVOICE_EDIT'],
			'PATH_TO_INVOICE_RECUR' => $arResult['PATH_TO_INVOICE_RECUR'],
			'PATH_TO_INVOICE_RECUR_EDIT' => $arResult['PATH_TO_INVOICE_RECUR_EDIT'],
			'PATH_TO_INVOICE_RECUR_SHOW' => $arResult['PATH_TO_INVOICE_RECUR_SHOW'],
			'PATH_TO_CONTACT_SHOW' => $arResult['PATH_TO_CONTACT_SHOW'],
			'PATH_TO_COMPANY_SHOW' => $arResult['PATH_TO_COMPANY_SHOW'],
			'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE'],
			'PATH_TO_PRODUCT_EDIT' => $arResult['PATH_TO_PRODUCT_EDIT'],
			'PATH_TO_PRODUCT_SHOW' => $arResult['PATH_TO_PRODUCT_SHOW'],
			'ELEMENT_ID' => $arResult['VARIABLES']['invoice_id'],
			'IS_RECURRING' => $arResult['IS_RECURRING'],
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
		),
		$component
	);
}