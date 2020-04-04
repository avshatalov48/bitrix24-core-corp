<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/configs/automation/index.php");

$APPLICATION->SetTitle(GetMessage("TITLE"));
?><?
$APPLICATION->IncludeComponent(
	"bitrix:crm.config.automation",
	"",
	Array(
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/crm/configs/automation/",
		"SEF_URL_TEMPLATES" => Array(
			"index" => "index.php",
			"edit" => "#entity#/#category#/",
		),
		"VARIABLE_ALIASES" => Array(
			"index" => Array(),
			"edit" => Array(),
		)
	)
);

?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>