<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if (SITE_TEMPLATE_ID != "landing24")
{
	$APPLICATION->IncludeComponent("bitrix:crm.shop.page.controller", "", array(
		"CONNECT_PAGE" => "N",
		"ADDITIONAL_PARAMS" => array(
			"stores" => array(
				"IS_ACTIVE" => true
			)
		)
	));
}

$APPLICATION->IncludeComponent(
	"bitrix:landing.start",
	".default",
	array(
		"SEF_FOLDER" => "/shop/stores/",
		"SEF_MODE" => "Y",
		"COMPONENT_TEMPLATE" => ".default",
		"TYPE" => "STORE",
		"EDIT_FULL_PUBLICATION" => "Y",
		"SEF_URL_TEMPLATES" => array(
			"sites" => "",
			"site_show" => "site/#site_show#/",
			"site_edit" => "site/edit/#site_edit#/",
			"landing_edit" => "site/#site_show#/#landing_edit#/",
			"landing_view" => "site/#site_show#/view/#landing_edit#/",
			"domains" => "domains/",
			"domain_edit" => "domain/edit/#domain_edit#/",
		)
	),
	false
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>