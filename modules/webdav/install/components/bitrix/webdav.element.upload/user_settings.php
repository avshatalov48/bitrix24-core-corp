<?define("STOP_STATISTICS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
// **************************************************************************************
if(!function_exists("__UnEscape"))
{
	function __UnEscape(&$item, $key)
	{
		if(is_array($item))
			array_walk($item, '__UnEscape');
		else
		{
			if(strpos($item, "%u") !== false)
				$item = $GLOBALS["APPLICATION"]->UnJSEscape($item);
		}
	}
}

array_walk($_REQUEST, '__UnEscape');
if (check_bitrix_sessid() && $GLOBALS["USER"]->IsAuthorized())
{
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".strToLower($GLOBALS["DB"]->type)."/favorites.php");
	
	$UploadViewMode = CUserOptions::GetOption("webdav", "upload_settings", '');
	if (CheckSerializedData($UploadViewMode))
		$UploadViewMode = @unserialize($UploadViewMode);

	if (!is_array($UploadViewMode))
		$UploadViewMode = array();
	if ($_REQUEST["save"] == "view_mode")
	{
		$original_data = $UploadViewMode["view_mode"];
		
		if (!empty($_REQUEST["view_mode"]))
		{
			$UploadViewMode["view_mode"] = (empty($_REQUEST["view_mode"]) ? "applet" : strToLower($_REQUEST["view_mode"]));
		}
		else //used as switcher. Not right.
		{
			$UploadViewMode["view_mode"] = (empty($UploadViewMode["view_mode"]) ? "applet" : $UploadViewMode["view_mode"]);
			$UploadViewMode["view_mode"] = ($UploadViewMode["view_mode"] == "applet" ? "form" : "applet");
		}
		
		if ($original_data != $UploadViewMode["view_mode"])
			CUserOptions::SetOption("webdav", "upload_settings", serialize($UploadViewMode));
	}
}
?>