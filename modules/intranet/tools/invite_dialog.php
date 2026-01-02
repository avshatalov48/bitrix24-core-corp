<?
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
define("CONFIRM_PAGE", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if (\Bitrix\Intranet\CurrentUser::get()->isAuthorized())
{
	LocalRedirect('/');
}

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
?>
