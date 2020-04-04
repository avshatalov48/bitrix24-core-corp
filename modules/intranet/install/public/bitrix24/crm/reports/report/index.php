<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/reports/report/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE")/*"־עקועû"*/);
?><?$APPLICATION->IncludeComponent(
	"bitrix:crm.report",
	"",
	Array(
		"SEF_MODE" => "Y",
		"REPORT_ID" => $_REQUEST["report_id"],
		"SEF_FOLDER" => "/crm/reports/report/",
		"SEF_URL_TEMPLATES" => Array(
			"index" => "index.php",
			"report" => "report/",
			"construct" => "construct/#report_id#/#action#/",
			"show" => "view/#report_id#/"
		),
		"VARIABLE_ALIASES" => Array(
			"index" => Array(),
			"report" => Array(),
			"construct" => Array(),
			"show" => Array(),
		)
	)
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>