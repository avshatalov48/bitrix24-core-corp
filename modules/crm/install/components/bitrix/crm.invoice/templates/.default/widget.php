<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$APPLICATION->SetTitle(GetMessage('CRM_INVOICE_WGT_PAGE_TITLE_SHORT'));
\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'INVOICE_WIDGET',
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

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$isSupervisor = CCrmPerms::IsAdmin($currentUserID)
	|| Bitrix\Crm\Integration\IntranetManager::isSupervisor($currentUserID);

if($isSupervisor && isset($_REQUEST['super']))
{
	$isSupervisor = mb_strtoupper($_REQUEST['super']) === 'Y';
}


?><div class="bx-crm-view"><?
	$APPLICATION->IncludeComponent(
		'bitrix:crm.widget_panel',
		'',
		array(
			'GUID' => 'invoice_widget',
			'ENTITY_TYPES' => array(CCrmOwnerType::InvoiceName, CCrmOwnerType::DealName),
			'LAYOUT' => 'L50R50',
			'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'],
			'PATH_TO_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_WIDGET' => isset($arResult['PATH_TO_INVOICE_WIDGET']) ? $arResult['PATH_TO_INVOICE_WIDGET'] : '',
			'PATH_TO_KANBAN' => isset($arResult['PATH_TO_INVOICE_KANBAN']) ? $arResult['PATH_TO_INVOICE_KANBAN'] : '',
			'PATH_TO_DEMO_DATA' => $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.invoice/templates/.default/widget',
			'IS_SUPERVISOR' => $isSupervisor,
			'ROWS' => $rowData,
			'NAVIGATION_COUNTER' => CCrmInvoice::GetCounterValue(),
			'DEMO_TITLE' => GetMessage('CRM_INVOICE_WGT_DEMO_TITLE'),
			'DEMO_CONTENT' => GetMessage(
				'CRM_INVOICE_WGT_DEMO_CONTENT',
				array(
					'#URL#' => CCrmOwnerType::GetEditUrl(CCrmOwnerType::Invoice, 0, false),
					'#CLASS_NAME#' => 'crm-widg-white-link'
				)
			)
		)
	);
?></div>
