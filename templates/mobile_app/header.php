<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var $APPLICATION CMain
 **/

use Bitrix\Main\Page\AssetShowTargetType;

$platform = "android";
if (CModule::IncludeModule("mobileapp"))
{
	if (!defined("SKIP_MOBILEAPP_INIT"))
	{
		CMobile::Init();
	}
	$platform = CMobile::$platform;
}
else
{
	die();
}

\Bitrix\Main\Data\AppCacheManifest::getInstance()->setManifestCheckFile(SITE_DIR . "mobile/");
if (CModule::IncludeModule("mobile"))
{
	CJSCore::Init(array("mobile_ui"));
}

$mobileContext = new \Bitrix\Mobile\Context();
$moduleVersion = $mobileContext->version;

$APPLICATION->IncludeComponent("bitrix:mobile.data", "", array(
	"START_PAGE" => SITE_DIR . "mobile/index.php?version=" . $moduleVersion,
	"MENU_PAGE" => SITE_DIR . "mobile/left.php?version=" . $moduleVersion,
), false, array("HIDE_ICONS" => "Y"));
?><!DOCTYPE html>
<html<? $APPLICATION->ShowProperty("manifest"); ?> class="<?= $platform; ?> light">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=<?= SITE_CHARSET ?>"/>
	<meta name="format-detection" content="telephone=no">
	<script>
		if (typeof Application["getThemeId"] === "function") {
			document.documentElement.className = Application.getThemeId();
		}
	</script>
	<?
	$APPLICATION->ShowHead();
	$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH . (defined('MOBILE_TEMPLATE_CSS') ? MOBILE_TEMPLATE_CSS : "/themes.css"));
	$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH . (defined('MOBILE_TEMPLATE_CSS') ? MOBILE_TEMPLATE_CSS : "/themes_extra.css"));
	if (!defined("BX_DONT_INCLUDE_MOBILE_TEMPLATE_CSS"))
	{
		if (!defined('MOBILE_TEMPLATE_CSS'))
		{
			\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
		}

		$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH . (defined('MOBILE_TEMPLATE_CSS') ? MOBILE_TEMPLATE_CSS : "/common_styles.css"));
	}

	if ($USER->IsAuthorized())
	{
		\Bitrix\Main\Page\Asset::getInstance()->addString(
			"<script>(window.BX||top.BX).message({ 'USER_ID': '" . $USER->GetID() . "'});</script>",
			$unique = false,
			\Bitrix\Main\Page\AssetLocation::AFTER_JS,
			\Bitrix\Main\Page\AssetMode::ALL
		);
	}

	CJSCore::Init(array('ajax', 'mobile_tools'));
	?>
	<title><? $APPLICATION->ShowTitle() ?></title>
</head>
<body class="<? $APPLICATION->ShowProperty("BodyClass"); ?>"><?
?>
<script>
	BX.message({
		MobileSiteDir: '<?=CUtil::JSEscape(htmlspecialcharsbx(SITE_DIR))?>'
	});
	let id = document.documentElement.className
	let toggleTheme = () => {
		let value = id !== "dark" ? "dark" : "light"
		let removeValue = value === "dark" ? "light" : "dark"
		document.documentElement.classList.remove(removeValue)
		document.documentElement.classList.add(value)
		id = value
	}
</script>