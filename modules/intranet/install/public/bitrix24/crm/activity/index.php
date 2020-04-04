<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/activity/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));
if (CModule::IncludeModule("crm")):
	$APPLICATION->IncludeComponent("bitrix:crm.activity",
		"",
		array(
			'SEF_MODE' => 'Y',
			"PATH_TO_CONTACT_SHOW" => "/crm/contact/show/#contact_id#/",
			"PATH_TO_CONTACT_EDIT" => "/crm/contact/edit/#contact_id#/",
			"PATH_TO_COMPANY_SHOW" => "/crm/company/show/#company_id#/",
			"PATH_TO_COMPANY_EDIT" => "/crm/company/edit/#company_id#/",
			"PATH_TO_DEAL_SHOW" => "/crm/deal/show/#deal_id#/",
			"PATH_TO_DEAL_EDIT" => "/crm/deal/edit/#deal_id#/",
			"PATH_TO_USER_PROFILE" => "/company/personal/user/#user_id#/",
			"ELEMENT_ID" => $_REQUEST["id"],
			"SEF_FOLDER" => "/crm/activity/",
			"SEF_URL_TEMPLATES" => Array(
				'index' => 'index.php',
				'list' => '',
				'widget' => 'widget/',
			),
			"VARIABLE_ALIASES" => Array(
				"index" => Array(),
				"list" => Array(),
				"widget" => Array(),
			)
		),
		false
	);
endif;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>
