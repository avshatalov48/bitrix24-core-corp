<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:crm.lead.rest", "", Array()
);
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>