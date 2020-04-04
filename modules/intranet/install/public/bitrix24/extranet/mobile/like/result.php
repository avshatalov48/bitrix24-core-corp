<?
require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?><?
	$APPLICATION->IncludeComponent("bitrix:rating.vote.result", "mobile", array(
		"PATH_TO_USER_PROFILE" => SITE_DIR."mobile/users/?user_id=#user_id#"
	),
	false,
	array("HIDE_ICONS" => "Y")
);?><?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")
?>