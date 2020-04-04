<?php

$siteId = '';
if(isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);
}

if($siteId)
{
	define('SITE_ID', $siteId);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.order',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			"SEF_MODE" => "Y",
			"PATH_TO_CONTACT_SHOW" => "/crm/contact/show/#contact_id#/",
			"PATH_TO_CONTACT_EDIT" => "/crm/contact/edit/#contact_id#/",
			"PATH_TO_COMPANY_SHOW" => "/crm/company/show/#company_id#/",
			"PATH_TO_COMPANY_EDIT" => "/crm/company/edit/#company_id#/",
			"PATH_TO_DEAL_SHOW" => "/crm/deal/show/#deal_id#/",
			"PATH_TO_DEAL_EDIT" => "/crm/deal/edit/#deal_id#/",
			"PATH_TO_INVOICE_SHOW" => "/crm/invoice/show/#invoice_id#/",
			"PATH_TO_INVOICE_EDIT" => "/crm/invoice/edit/#invoice_id#/",
			"PATH_TO_LEAD_SHOW" => "/crm/lead/show/#lead_id#/",
			"PATH_TO_LEAD_EDIT" => "/crm/lead/edit/#lead_id#/",
			"PATH_TO_LEAD_CONVERT" => "/crm/lead/convert/#lead_id#/",
			"PATH_TO_PRODUCT_EDIT" => "/crm/product/edit/#product_id#/",
			"PATH_TO_PRODUCT_SHOW" => "/crm/product/show/#product_id#/",
			"PATH_TO_USER_PROFILE" => "/company/personal/user/#user_id#/",
			"ELEMENT_ID" => $_REQUEST["order_id"],
			"SEF_FOLDER" => "/salescenter/orders/",
			"SEF_URL_TEMPLATES" => array(
				"index" => "index.php",
				"list" => "list/",
				"edit" => "edit/#order_id#/",
				"show" => "show/#order_id#/"
			),
			"VARIABLE_ALIASES" => array(
				"index" => array(),
				"list" => array(),
				"edit" => array(),
				"show" => array(),
			)
		],
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');