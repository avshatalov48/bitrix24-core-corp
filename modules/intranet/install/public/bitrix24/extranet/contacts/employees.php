<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/extranet/contacts/employees.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));

LocalRedirect(SITE_DIR.'contacts/?apply_filter=Y&EXTRANET=N');
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>