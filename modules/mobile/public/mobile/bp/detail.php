<?
require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>
<?$APPLICATION->IncludeComponent("bitrix:bizproc.task", "mobile", array(
	"TASK_ID" => $_GET["task_id"]
))?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")?>