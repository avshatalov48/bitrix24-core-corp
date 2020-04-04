<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(!$GLOBALS['USER']->IsAdmin())
	$APPLICATION->AuthForm("");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/updates/backup.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");

$APPLICATION->SetTitle(GetMessage("BACKUP_TITLE"));
?>
<?$APPLICATION->IncludeComponent("bitrix:intranet.backup", "", array());?>
<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>