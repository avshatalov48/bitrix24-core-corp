<?
require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>
<?$APPLICATION->IncludeComponent("bitrix:bizproc.task.list", "mobile", array())?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")?>