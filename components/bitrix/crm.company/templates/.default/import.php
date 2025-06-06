<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$cpID = 'COMPANY_LIST';
$cpActiveItemID = 'COMPANY';
if ($arResult['MYCOMPANY_MODE'] === 'Y')
{
	$cpID = 'MYCOMPANY_LIST';
	$cpActiveItemID = 'MY_COMPANY';
}
$isMyCompanyMode = (isset($arResult['MYCOMPANY_MODE']) && $arResult['MYCOMPANY_MODE'] === 'Y');

global $APPLICATION;

$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => $cpID,
		'ACTIVE_ITEM_ID' => CCrmComponentHelper::getMenuActiveItemId($cpActiveItemID, $_GET['category_id']),
		'PATH_TO_COMPANY_LIST' => (isset($arResult['PATH_TO_COMPANY_LIST']) && !$isMyCompanyMode) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => (isset($arResult['PATH_TO_COMPANY_EDIT']) && !$isMyCompanyMode) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
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
		'MYCOMPANY_MODE' => ($arResult['MYCOMPANY_MODE'] === 'Y' ? 'Y' : 'N'),
	),
	$component
);

$APPLICATION->IncludeComponent(
	'bitrix:crm.company.menu',
	'',
	[
		'PATH_TO_COMPANY_LIST' => $arResult['PATH_TO_COMPANY_LIST'],
		'PATH_TO_COMPANY_SHOW' => $arResult['PATH_TO_COMPANY_SHOW'],
		'PATH_TO_COMPANY_EDIT' => $arResult['PATH_TO_COMPANY_EDIT'],
		'PATH_TO_COMPANY_IMPORT' => $arResult['PATH_TO_COMPANY_IMPORT'],
		'ELEMENT_ID' => $arResult['VARIABLES']['company_id'],
		'TYPE' => 'import',
		'MYCOMPANY_MODE' => ($arResult['MYCOMPANY_MODE'] === 'Y' ? 'Y' : 'N'),
	],
	$component
);

$APPLICATION->IncludeComponent(
	'bitrix:crm.company.import',
	'',
	[
		'PATH_TO_COMPANY_LIST' => $arResult['PATH_TO_COMPANY_LIST'],
		'PATH_TO_COMPANY_IMPORT' => $arResult['PATH_TO_COMPANY_IMPORT'],
		'MYCOMPANY_MODE' => ($arResult['MYCOMPANY_MODE'] === 'Y' ? 'Y' : 'N'),
	],
	$component
);

$APPLICATION->SetTitle(\Bitrix\Main\Localization\Loc::getMessage('CRM_COMPANY_NAV_TITLE_LIST'));