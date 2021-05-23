<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Context;

global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'WEBFORM_LIST',
		'ACTIVE_ITEM_ID' => 'WEBFORM',
		'NAME_TEMPLATE' => isset($arResult['NAME_TEMPLATE']) ? $arResult['NAME_TEMPLATE'] : '',
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

$containerNodeId = Context::getCurrent()->getRequest()->get('containerNodeId');
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	array(
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.ads.leadads',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'PATH_TO_WEB_FORM_LIST' => $arResult['PATH_TO_WEB_FORM_LIST'],
			'PATH_TO_WEB_FORM_EDIT' => $arResult['PATH_TO_WEB_FORM_EDIT'],
			'PATH_TO_WEB_FORM_FILL' => $arResult['PATH_TO_WEB_FORM_FILL'],
			'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE'],

			'ACCOUNT_ID' => null,
			'FORM_ID' => null,
			'CRM_FORM_ID' => $arResult['VARIABLES']['id'],
			'PROVIDER_TYPE' => Context::getCurrent()->getRequest()->get('type'),
		],
		//'USE_PADDING' => false,
		//'CLOSE_AFTER_SAVE' => false,
		//'RELOAD_PAGE_AFTER_SAVE' => false,
		//'USE_LINK_TARGETS_REPLACING' => true,
	)
);