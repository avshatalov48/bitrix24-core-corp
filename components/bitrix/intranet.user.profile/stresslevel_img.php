<?
define("NO_KEEP_STATISTIC", true);
define("BX_STATISTIC_BUFFER_USED", false);
define("NOT_CHECK_PERMISSIONS", true);
//define("PUBLIC_AJAX_MODE", true);
define('BX_SECURITY_SESSION_READONLY', true);

$siteId = (isset($_REQUEST["site"]) && is_string($_REQUEST["site"])) ? trim($_REQUEST["site"]): "";
$siteId = mb_substr(preg_replace("/[^a-z0-9_]/i", "", $siteId), 0, 2);

define("SITE_ID", $siteId);

use Bitrix\Main\Loader;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/bx_root.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (Loader::includeModule('intranet'))
{
	$stressLevelInstance = new \Bitrix\Intranet\Component\UserProfile\StressLevel\Img();
	$image = $stressLevelInstance->getImage([
		'factor' => 4,
	]);

	if($image != null)
	{
		header("Content-Type: image/png");
		header("Cache-Control: no-cache ");
		echo $image;
	}
}

\CMain::finalActions();
die;
?>