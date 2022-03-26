<?php
/**
 * @global \CMain $APPLICATION
 */
define('CONFIRM_PAGE', true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/extranet/confirm/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));
?>
<?php
$APPLICATION->IncludeComponent(
	"bitrix:system.auth.initialize",
	"",
	Array(
		"CHECKWORD_VARNAME"=>"checkword",
		"USERID_VARNAME"=>"user_id",
		"AUTH_URL"=> SITE_DIR."auth.php",
	),
false
);?>
<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");