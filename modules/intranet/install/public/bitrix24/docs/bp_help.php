<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/docs/bp_help.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));
echo GetMessage("CONTENT");?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>