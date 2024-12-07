<?
define("STOP_STATISTICS", true);
define('NO_AGENT_CHECK', true);
define("DisableEventsCheck", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!CModule::IncludeModule('crm'))
	die('CRM module is not installed');

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!(CCrmDeal::CheckCreatePermission($userPermissions, 0)
	&& CCrmDeal::CheckUpdatePermission(0, $userPermissions, 0)))
{
	die("Permission denied");
}

$externalSaleId = 0;

/*
if (isset($_SERVER["REQUEST_URI"]) && strlen($_SERVER["REQUEST_URI"]) > 0)
	$path = substr($_SERVER["REQUEST_URI"], strlen("/bitrix/tools/crm_sale_proxy.php"));
else
	$path = $_SERVER["PATH_INFO"].((isset($_SERVER["QUERY_STRING"]) && strlen($_SERVER["QUERY_STRING"]) > 0) ? "?".$_SERVER["QUERY_STRING"] : "");
*/
$path = $_SERVER["QUERY_STRING"];
$path = preg_replace("/%0D|%0A|\r|\n/i", "", $path);

if (isset($_REQUEST["__BX_CRM_QUERY_STRING_PREFIX"]))
{
	$prefix = $_REQUEST["__BX_CRM_QUERY_STRING_PREFIX"];
	$prefix = preg_replace("/%0D|%0A|\r|\n/i", "", $prefix);
	if (mb_substr($prefix, 0, mb_strlen("/bitrix/tools/crm_sale_proxy.php?")) == "/bitrix/tools/crm_sale_proxy.php?")
		$prefix = mb_substr($prefix, mb_strlen("/bitrix/tools/crm_sale_proxy.php?"));
	if (mb_substr($path, 0, mb_strlen($prefix)) != $prefix)
		$path = $prefix.$path;
}

$path = ltrim($path, "/");
if (($pos = mb_strpos($path, "/")) !== false)
{
	$externalSaleId = intval(mb_substr($path, 0, $pos));
	$path = mb_substr($path, $pos);
}

$proxy = new CCrmExternalSaleProxy($externalSaleId);
if (!$proxy->IsInitialized())
	die("External site is not found");

$arPath = parse_url($path);
if (CHTTP::isPathTraversalUri($arPath["path"]))
	die("Traversal paths are not permitted.");

$pathRegexs = array(
	"/^\/bitrix\/admin\/[a-z0-9_.-]+\.php$/i",
	"/^\/bitrix\/tools(?:\/[^\/]+)*\/[a-z0-9_.-]+\.php$/i",
	"/^\/bitrix\/components\/bitrix\/(?:[a-z0-9_.-]+\/)+[a-z0-9_.-]+\.(?:php|js|css)$/i",
	"/^\/bitrix\/js\/(?:[a-z0-9_.-]+\/)+[a-z0-9_.-]+\.(?:js|css)$/i"
);

$isPermitted = false;
foreach ($pathRegexs as $regex)
{
	if(preg_match($regex, $arPath["path"]) === 1)
	{
		$isPermitted = true;
		break;
	}
}
if (!$isPermitted)
	die("Page is not found");

$path = $arPath["path"]."?".$arPath["query"];
$request = array(
	"METHOD" => $_SERVER["REQUEST_METHOD"],
	"PATH" => $path,
	"HEADERS" => array(),
	"BODY" => array()
);
$request["PATH"] = str_replace("CRM_MANAGER_USER_ID", "CMUI", $request["PATH"]);

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	$request["BODY"] = $_POST;

	$request["BODY"]["CRM_MANAGER_USER_ID"] = intval($USER->GetID());
	$request["BODY"]["bxpublic"] = "Y";
	$request["BODY"]["nocdn"] = "Y";
	$request["BODY"]["externalcontext"] = "crm";

	if ($_SERVER['HTTP_BX_AJAX'] || (isset($request["BODY"]["AlreadyUTF8Request"]) && ($request["BODY"]["AlreadyUTF8Request"] == "Y")))
	{
		$request["UTF"] = true;
		unset($request["BODY"]["AlreadyUTF8Request"]);
	}
}
else
{
	$request["PATH"] .= ((mb_strpos($request["PATH"], "?") !== false) ? "&" : "?")."CRM_MANAGER_USER_ID=".intval($USER->GetID())."&bxpublic=Y&nocdn=Y&externalcontext=crm";
}

$response = $proxy->Send($request);
if ($response == null)
	die("Communication error");

$body = $response["BODY"];

if (isset($response["CONTENT"]["ENCODING"]) && (in_array($response["CONTENT"]["TYPE"], array("text/xml", "application/xml", "text/html", "application/x-javascript"))))
{
	$utf8Encoding = (mb_strtoupper($response["CONTENT"]["ENCODING"]) == "UTF-8");
	if (!$utf8Encoding)
		$body = \Bitrix\Main\Text\Encoding::convertEncoding($body, $response["CONTENT"]["ENCODING"], "UTF-8");
}

$body = preg_replace(
	"#(\"|')(/bitrix/([a-z0-9_.-]+/)*?([a-z0-9_.-]+?\.(gif|png)))#i",
	"$1".($proxy->GetUrl())."$2",
	$body
);

$body = preg_replace(
	"#(\"|')(/bitrix/([a-z0-9_.-]+/)*?(sale\.css))#i",
	"$1".($proxy->GetUrl())."$2",
	$body
);

$body = preg_replace(
	"#(\"|')(/upload/([%a-z0-9_.-]+/)*?([%a-z0-9_.-]+?\.([a-z0-9]+)))#i",
	"$1".($proxy->GetUrl())."$2",
	$body
);

$body = preg_replace(
	"#(<a\s[^>]*?)(href\s*=\s*(\"|'))(/bitrix/([a-z0-9_.-]+/)*([a-z0-9_.-]+\.php)(?<!sale_order_edit\.php|ajax\.php|sale_order_new\.php|sale_order_detail\.php|sale_order_print\.php|sale_print\.php|sale_product_search\.php|user_search\.php))#i",
	"$1target=\"_blank\" $2".($proxy->GetUrl())."$4",
	$body
);

$body = preg_replace(
    "#(\"|')(/bitrix/([a-z0-9_.-]+/)*([a-z0-9_.-]+\.(?:php)))#i",
    "$1/bitrix/tools/crm_sale_proxy.php?".$externalSaleId."$2",
    $body
);

$body = preg_replace(
	"#(<a\s[^>]*?)(href\s*=\s*(\"|'))(?!/bitrix/)(/([a-z0-9_.-]+/)*([a-z0-9_.-]+)/?)(\"|')#i",
	"$1target=\"_blank\" $2".($proxy->GetUrl())."$4$5",
	$body
);

$body = str_replace(
	'class="adm-filter-tab adm-filter-add-tab"',
	'class="adm-filter-tab adm-filter-add-tab" style="display:none;"',
	$body
);

if (mb_strpos($arPath["path"], '.css') !== false)
{
	header('Content-Type: text/css');
}
elseif (mb_strpos($arPath["path"], '.js') !== false)
{
	header('Content-Type: application/x-javascript');
}

echo $body;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");