<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle(GetMessage("TITLE"));

$APPLICATION->IncludeComponent("bitrix:crm.shop.page.controller", "", array(
	"CONNECT_PAGE" => "N",
	"ADDITIONAL_PARAMS" => array(
		"orders" => array(
			"IS_ACTIVE" => true
		)
	)
));

$APPLICATION->IncludeComponent(
	"bitrix:crm.order",
	"",
	array(
		"SEF_MODE" => "Y",
		"PATH_TO_CONTACT_SHOW" => SITE_DIR . "crm/contact/show/#contact_id#/",
		"PATH_TO_CONTACT_EDIT" => SITE_DIR . "crm/contact/edit/#contact_id#/",
		"PATH_TO_COMPANY_SHOW" => SITE_DIR . "crm/company/show/#company_id#/",
		"PATH_TO_COMPANY_EDIT" => SITE_DIR . "crm/company/edit/#company_id#/",
		"PATH_TO_DEAL_SHOW" => SITE_DIR . "crm/deal/show/#deal_id#/",
		"PATH_TO_DEAL_EDIT" => SITE_DIR . "crm/deal/edit/#deal_id#/",
		"PATH_TO_INVOICE_SHOW" => SITE_DIR . "crm/invoice/show/#invoice_id#/",
		"PATH_TO_INVOICE_EDIT" => SITE_DIR . "crm/invoice/edit/#invoice_id#/",
		"PATH_TO_LEAD_SHOW" => SITE_DIR . "crm/lead/show/#lead_id#/",
		"PATH_TO_LEAD_EDIT" => SITE_DIR . "crm/lead/edit/#lead_id#/",
		"PATH_TO_LEAD_CONVERT" => SITE_DIR . "crm/lead/convert/#lead_id#/",
		"PATH_TO_PRODUCT_EDIT" => SITE_DIR . "crm/product/edit/#product_id#/",
		"PATH_TO_PRODUCT_SHOW" => SITE_DIR . "crm/product/show/#product_id#/",
		"PATH_TO_USER_PROFILE" => SITE_DIR . "company/personal/user/#user_id#/",
		"ELEMENT_ID" => $_REQUEST["order_id"] ?? '',
		"SEF_FOLDER" => "/shop/orders/",
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
	)
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
