<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

Bitrix\Main\Page\Asset::getInstance()->addJs("/bitrix/js/main/hot_keys.js");

$APPLICATION->IncludeComponent(
	"bitrix:crm.admin.page.controller",
	"",
	$arResult['CRM_ADMIN_PAGE_CONTROLLER_PARAMS']
);
?>
<script>
	BX.ready(function() {
		new BX.Sale.ShopPublic();
	});
</script>
