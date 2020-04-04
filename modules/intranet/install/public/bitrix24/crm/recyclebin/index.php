<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/recyclebin/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));
$APPLICATION->IncludeComponent(
	"bitrix:crm.recyclebin.list",
	"",
	array("PATH_TO_USER_PROFILE" => "/company/personal/user/#user_id#/")
);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>