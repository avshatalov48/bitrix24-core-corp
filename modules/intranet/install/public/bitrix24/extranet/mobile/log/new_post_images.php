<?
require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?><?$APPLICATION->IncludeComponent("bitrix:mobile.file.list", "", array(
		"THUMBNAIL_RESIZE_METHOD" => "EXACT",
		"THUMBNAIL_SIZE" => 100,
		"POST_ID" => (isset($_REQUEST["post_id"]) && intval($_REQUEST["post_id"]) > 0 ? $_REQUEST["post_id"] : 0)
	),
	false,
	Array("HIDE_ICONS" => "Y")
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")?>