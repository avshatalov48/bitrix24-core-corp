<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
	die();

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
	die("Permission denied");
if (!check_bitrix_sessid())
	die("ER".GetMessage("BPWC_WNC_EMPTY_SESSID")." [bsid=".bitrix_sessid().";]");

$errorMessage = "";
if (strlen($_POST["LOGIN"]) <= 0)
	$errorMessage .= GetMessage("BPWC_WNC_EMPTY_LOGIN")."<br>";
if (strlen($_POST["PASSWORD"]) <= 0)
	$errorMessage .= GetMessage("BPWC_WNC_EMPTY_PASSWORD")."<br>";
if (strlen($_POST["SERVER"]) <= 0)
	$errorMessage .= GetMessage("BPWC_WNC_EMPTY_URL")."<br>";

$cnt = CCrmExternalSale::Count();

$arLimitationSettings = CCrmExternalSale::GetLimitationSettings();
if ($arLimitationSettings["MAX_SHOPS"] > 0)
{
	if ($cnt >= $arLimitationSettings["MAX_SHOPS"])
		$errorMessage .= GetMessage("BPWC_WNC_MAX_SHOPS")."<br>";
}

if (strlen($errorMessage) <= 0)
{
	$arCrmUrl = parse_url($_POST["SERVER"]);

	$crmUrlScheme = strtolower($arCrmUrl["scheme"]);
	if ($crmUrlScheme != "https")
		$crmUrlScheme = "http";
	$crmUrlHost = $arCrmUrl["host"];
	$crmUrlPort = intval($arCrmUrl["port"]);
	if ($crmUrlPort <= 0)
		$crmUrlPort = ($crmUrlScheme == "http") ? 80 : 443;

	$arFields = array(
		"ACTIVE" => "Y",
		"LOGIN" => $_POST["LOGIN"],
		"PASSWORD" => $_POST["PASSWORD"],
		"NAME" => (strlen($_POST["SITE_NAME"]) > 0) ? $_POST["SITE_NAME"] : false,
		"IMPORT_SIZE" => 10,
		"IMPORT_PERIOD" => 7,
		"IMPORT_PREFIX" => "EShop".$cnt,
		"IMPORT_ERRORS" => 0,
		"IMPORT_PUBLIC" => "Y",
		"IMPORT_PROBABILITY" => 20,
		"SCHEME" => $crmUrlScheme,
		"SERVER" => $crmUrlHost,
		"PORT" => $crmUrlPort,
	);

	if (!defined("BX_UTF"))
	{
		if ($arFields["NAME"])
			$arFields["NAME"] = CharsetConverter::ConvertCharset($arFields["NAME"], "UTF-8", SITE_CHARSET);
	}

	$res = CCrmExternalSale::Add($arFields);

	if (!$res)
	{
		if ($ex = $GLOBALS["APPLICATION"]->GetException())
			$errorMessage .= $ex->GetString().".<br>";
		else
			$errorMessage .= "Unknown error."."<br>";
	}
}

if (strlen($errorMessage) <= 0)
	echo "OK"."/crm/configs/external_sale/?do_show_wizard=Y&do_show_wizard_id=".intval($res);
else
	echo "ER".$errorMessage;

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>