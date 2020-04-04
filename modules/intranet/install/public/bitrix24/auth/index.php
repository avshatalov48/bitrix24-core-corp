<?
define("NEED_AUTH", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/auth/index.php");

if (is_string($_REQUEST["backurl"]) && strpos($_REQUEST["backurl"], "/") === 0)
{
	Bitrix\Intranet\Composite\CacheProvider::deleteUserCache();
	LocalRedirect($_REQUEST["backurl"]);
}

$APPLICATION->SetTitle(GetMessage("BITRIX24_AUTH_TITLE"));
?>
<p><?=GetMessage("BITRIX24_AUTH_DESCRIPTION")?></p>

<p><a href="<?=SITE_DIR?>"><?=GetMessage("BITRIX24_AUTH_BACK_URL")?></a></p>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>