<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/openlines/index.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");

$APPLICATION->SetTitle(GetMessage("OL_PAGE_STATISTICS_TITLE_SHORT"));
?>
<?//LocalRedirect('/openlines/list/');?>
<?$APPLICATION->IncludeComponent("bitrix:imopenlines.reportboard", "", array());?>
<?//$APPLICATION->IncludeComponent("bitrix:imopenlines.lines", "", array());?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
