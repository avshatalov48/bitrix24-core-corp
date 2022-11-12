<?
define("NO_KEEP_STATISTIC", true);
define("BX_STATISTIC_BUFFER_USED", false);
define("NO_LANG_FILES", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_PUBLIC_TOOLS", true);

$site_id = isset($_REQUEST["site"]) && is_string($_REQUEST["site"]) ? trim($_REQUEST["site"]) : "";
$site_id = mb_substr(preg_replace("/[^a-z0-9_]/i", "", $site_id), 0, 2);

define("SITE_ID", $site_id);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/bx_root.php");

$action = isset($_REQUEST["action"]) && is_string($_REQUEST["action"]) ? trim($_REQUEST["action"]) : "";

$lng = isset($_REQUEST["lang"]) && is_string($_REQUEST["lang"]) ? trim($_REQUEST["lang"]) : "";
$lng = mb_substr(preg_replace("/[^a-z0-9_]/i", "", $lng), 0, 2);

$ls = isset($_REQUEST["ls"]) && is_string($_REQUEST["ls"]) ? trim($_REQUEST["ls"]) : "";
$ls_arr = isset($_REQUEST["ls_arr"])? $_REQUEST["ls_arr"]: "";

$as = isset($_REQUEST["as"]) ? intval($_REQUEST["as"]) : 58;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$rsSite = CSite::GetByID($site_id);
if ($arSite = $rsSite->Fetch())
{
	define("LANGUAGE_ID", $arSite["LANGUAGE_ID"]);
}
else
{
	define("LANGUAGE_ID", "en");
}

__IncludeLang(__DIR__."/lang/".$lng."/ajax.php");

if(CModule::IncludeModule("socialnetwork"))
{
	$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin();

	// write and close session to prevent lock;
	session_write_close();

	$arResult = array();

	if (!$GLOBALS["USER"]->IsAuthorized())
		$arResult[0] = "*";
	elseif (!check_bitrix_sessid())
		$arResult[0] = "*";
	elseif ($action == "change_favorites")
	{
		$log_id = $_REQUEST["log_id"];
		if ($arLog = CSocNetLog::GetByID($log_id))
		{
			if ($strRes = CSocNetLogFavorites::Change($GLOBALS["USER"]->GetID(), $log_id))
			{
				if ($strRes == "Y")
				{
					if (method_exists('\Bitrix\Socialnetwork\ComponentHelper','userLogSubscribe'))
					{
						\Bitrix\Socialnetwork\ComponentHelper::userLogSubscribe(array(
							'logId' => $log_id,
							'userId' => $GLOBALS["USER"]->GetID(),
							'typeList' => array(
								'FOLLOW',
								'COUNTER_COMMENT_PUSH'
							)
						));
					}
					else
					{
						CSocNetLogFollow::Set($GLOBALS["USER"]->GetID(), "L".$log_id, "Y");
					}
				}
				$arResult["SUCCESS"] = "Y";
			}
			else
			{
				$arResult["SUCCESS"] = "N";
			}
		}
		else
		{
			$arResult["SUCCESS"] = "N";
		}
	}
	elseif ($action == "change_follow")
	{
		$log_id = intval($_REQUEST["log_id"]);

		if ($log_id > 0)
		{
			if (
				$_REQUEST["follow"] == "Y"
				&& method_exists('\Bitrix\Socialnetwork\ComponentHelper','userLogSubscribe')
			)
			{
				$strRes = \Bitrix\Socialnetwork\ComponentHelper::userLogSubscribe(array(
					'logId' => $log_id,
					'userId' => $GLOBALS["USER"]->GetID(),
					'typeList' => array(
						'FOLLOW',
						'COUNTER_COMMENT_PUSH'
					)
				));
			}
			else
			{
				$strRes = CSocNetLogFollow::Set($GLOBALS["USER"]->GetID(), "L".$log_id, ($_REQUEST["follow"] == "Y" ? "Y" : "N"));
			}
			$arResult["SUCCESS"] = ($strRes ? "Y" : "N");
		}
		else
			$arResult["SUCCESS"] = "N";
	}
	elseif ($action == "change_follow_default")
	{
		$arResult["SUCCESS"] = (
			CSocNetLogFollow::Set($GLOBALS["USER"]->GetID(), "**", $_POST["value"] == "Y" ? "Y" : "N") 
				? "Y" 
				: "N"
		);
	}
	elseif ($action == "change_expert_mode")
	{
		\Bitrix\Socialnetwork\LogViewTable::set($GLOBALS["USER"]->GetID(), 'tasks', ($_POST["value"] == "Y" ? "N" : "Y"));
		$arResult["SUCCESS"] = 'Y';
	}
	elseif ($action == "log_error")
	{
		$message = trim($_REQUEST["message"]);
		$url = trim($_REQUEST["url"]);
		$linenumber = intval($_REQUEST["linenumber"]);
		if (!IsModuleInstalled("bitrix24"))
		{
			AddMessage2Log("Mobile Livefeed javascript error:\nMessage: ".$message."\nURL: ".$url."\nLine number: ".$linenumber."\nUser ID: ".$GLOBALS["USER"]->GetID());
		}
	}

	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJSObject($arResult);
}

define('PUBLIC_AJAX_MODE', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
die();
?>