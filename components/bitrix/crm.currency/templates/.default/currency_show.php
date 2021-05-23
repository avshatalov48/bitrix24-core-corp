<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'CURRENCY_SHOW',
		'ACTIVE_ITEM_ID' => '',
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

$APPLICATION->IncludeComponent(
	'bitrix:crm.currency.menu',
	'',
	array(
		'PATH_TO_CURRENCY_LIST' => $arResult['PATH_TO_CURRENCY_LIST'],
		'PATH_TO_CURRENCY_SHOW' => $arResult['PATH_TO_CURRENCY_SHOW'],
		'PATH_TO_CURRENCY_EDIT' => $arResult['PATH_TO_CURRENCY_EDIT'],
		'PATH_TO_CURRENCY_ADD' => $arResult['PATH_TO_CURRENCY_ADD'],
		'CURRENCY_ID' => $arResult['VARIABLES']['currency_id'],
		'TYPE' => 'show'
	),
	$component
);

$APPLICATION->IncludeComponent(
	'bitrix:crm.currency.show',
	'', 
	array(
		'PATH_TO_CURRENCY_LIST' => $arResult['PATH_TO_CURRENCY_LIST'],
		'PATH_TO_CURRENCY_SHOW' => $arResult['PATH_TO_CURRENCY_SHOW'],
		'PATH_TO_CURRENCY_EDIT' => $arResult['PATH_TO_CURRENCY_EDIT'],
		'PATH_TO_CURRENCY_ADD' => $arResult['PATH_TO_CURRENCY_ADD'],
		'CURRENCY_ID' => $arResult['VARIABLES']['currency_id']
	),
	$component
);