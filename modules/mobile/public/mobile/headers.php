<?
define('BX_MOBILE', true);
define("BX_SKIP_SESSION_EXPAND", true);
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);
define('BX_PULL_SKIP_LS', true);
//define('BX_PULL_SKIP_WEBSOCKET', true);
define("BX_PULL_COMMAND_PATH", "/mobile/ajax.php?mobile_action=pull");
if (!defined('BX_DONT_SKIP_PULL_INIT'))
{
	define("BX_PULL_SKIP_INIT", true);
}
	
if (isset($_REQUEST["mobile_action"]) && $_REQUEST["mobile_action"] == "checkout")
{
	define("EXTRANET_NO_REDIRECT", true);
}

if (isset($_REQUEST["manifest_id"]))
{
	define('BX_SECURITY_SESSION_READONLY', true);
}

?>