<?
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

if (
	isset($_GET["user_id"])
	&& isset($_GET["checkword"])
)
{
	define("CONFIRM_PAGE", true);
	define("NOT_CHECK_PERMISSIONS", true);
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
	$APPLICATION->SetTitle(GetMessage("BX24_INVITE_DIALOG_CONF_PAGE_TITLE"));

	$APPLICATION->IncludeComponent(
		"bitrix:system.auth.initialize",
		"",
		array(
			"CHECKWORD_VARNAME"=>"checkword",
			"USERID_VARNAME"=>"user_id",
			"AUTH_URL"=>"#SITE_DIR#auth.php",
		),
		false
	);

	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
	die();
}

define("PUBLIC_AJAX_MODE", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$APPLICATION->IncludeComponent("bitrix:intranet.invite.dialog", "", array());

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
?>
