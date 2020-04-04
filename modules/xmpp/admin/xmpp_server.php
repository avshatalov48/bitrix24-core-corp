<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xmpp/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xmpp/prolog.php");

IncludeModuleLangFile(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight("xmpp");
if ($POST_RIGHT == "D")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

if (strlen($_REQUEST["server_action"]) > 0 && in_array($_REQUEST["server_action"], array("start", "stop", "query", "clearcache", "dump")))
{
	if (check_bitrix_sessid())
	{
		if ($_REQUEST["server_action"] == "clearcache")
		{
			$arMessage = array(
				"query" => array(
					"." => array("type" => "set"),
					"action" => array("#" => "clearcache"),
				),
			);
			CXMPPUtility::SendToServer($arMessage);
			echo "success";
		}
		elseif ($_REQUEST["server_action"] == "dump")
		{
			$arMessage = array(
				"query" => array(
					"." => array("type" => "set"),
					"action" => array("#" => "dump"),
				),
			);
			CXMPPUtility::SendToServer($arMessage);
			echo "success";
		}
		elseif ($_REQUEST["server_action"] == "query")
		{
			$arMessage = array(
				"query" => array(
					"." => array("type" => "get"),
					"common" => array("#" => ""),
				),
			);
			$arResponce = CXMPPUtility::_SendToServer($arMessage, $errorNo, $errorStr);
			$data = "";
			if ($arResponce && is_array($arResponce))
			{
				$data = CUtil::PhpToJSObject($arResponce);
			}
			else
			{
				global $APPLICATION;
				if (defined("BX_UTF") && BX_UTF)
					$errorStr = $APPLICATION->ConvertCharset($errorStr, "Windows-1251", "UTF-8");
				$data = CUtil::PhpToJSObject(array("error" => array("errorNo" => $errorNo, "errorStr" => $errorStr)));
			}
			echo $data;
		}
		elseif ($_REQUEST["server_action"] == "start")
		{
			$bWindowsHosting = false;
			$strCurrentOS = PHP_OS;
			if (StrToUpper(substr($strCurrentOS, 0, 3)) === "WIN")
			   $bWindowsHosting = true;

			$phpPath = COption::GetOptionString("xmpp", "php_path", $bWindowsHosting ? "../apache/php.exe -c ../apache/php.ini" : "php -c /etc/php.ini");
			$serverPath = COption::GetOptionString("xmpp", "server_path", "./bitrix/modules/xmpp/xmppd.php");
			//$serverLogPath = COption::GetOptionString("xmpp", "server_log_path", "xmppd.log");

			chdir($_SERVER["DOCUMENT_ROOT"]);

			if($phpPath == "../apache/php.exe -c ../apache/php.ini" && !file_exists($_SERVER['DOCUMENT_ROOT']."/../apache/php.exe") && file_exists($_SERVER['DOCUMENT_ROOT']."/../apache2/zendserver/bin/php.exe"))
				$phpPath = "../Apache2/zendserver/bin/php.exe -c ../Apache2/zendserver/etc/php.ini";

			if(trim($phpPath) == "../Apache2/zendserver/bin/php.exe -c ../Apache2/zendserver/etc/php.ini")
			{
				$phpPath = "./Apache2/zendserver/bin/php.exe -c ./Apache2/zendserver/etc/php.ini";
				$serverPath = '"'.rel2abs($_SERVER['DOCUMENT_ROOT'], $serverPath).'"';
				chdir("..");
			}

			$startErrorMessage = "";

			$p = $phpPath." ".($bWindowsHosting ? $serverPath : $_SERVER['DOCUMENT_ROOT'].ltrim($serverPath, '.'))." test_mode";
			if (!$bWindowsHosting)
				$p .= " 2>&1";
			else
				$p = str_replace("/", "\\", $p);
			exec($p, $execOutput, $execReturnVar);
			$s = strtolower(implode("\n", $execOutput));

			if ($execReturnVar == 0)
			{
				if (strlen($s) <= 0)
					$startErrorMessage .= "Unknown error";
				elseif (strpos($s, "server started") === false || strpos($s, "error") !== false)
					$startErrorMessage .= $s;
			}
			else
			{
				$startErrorMessage .= "[".$execReturnVar."] ".$s;
			}

			if (strlen($startErrorMessage) <= 0)
			{
				if ($bWindowsHosting)
				{
					pclose(popen("start ".$phpPath." ".$serverPath.(extension_loaded('bitrix24') ? " bitrix24" : ""), "r"));
				}
				else
				{
					$cmd = 'nohup '.$phpPath.' '.$_SERVER['DOCUMENT_ROOT'].ltrim($serverPath, '.').(extension_loaded('bitrix24') ? " bitrix24" : "").' > /dev/null &';
					exec($cmd, $op);
				}
			}

			if (strlen($startErrorMessage) <= 0)
				echo "success";
			else
				echo $startErrorMessage;

			/*
			$WshShell = new COM("WScript.Shell");
			$oExec = $WshShell->Run("cmd /C ../apache/php.exe xmppd.php", 0, false);
			*/
			/*
			pclose(popen("start ../apache/php.exe xmppd.php", "r"));
			*/
			/*
			exec("/usr/local/bin/php /path/to/script.php > /dev/null &");
			*/
		}
		elseif ($_REQUEST["server_action"] == "stop")
		{
			$arMessage = array(
				"query" => array(
					"." => array("type" => "set"),
					"action" => array("#" => "die"),
				),
			);
			CXMPPUtility::SendToServer($arMessage);
			echo "success";
		}
	}
	die();
}

$APPLICATION->SetTitle(GetMessage("XMPP_AXS_TITLE"));

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("XMPP_AXS_TAB1"), "ICON" => "main_user_edit", "TITLE" => GetMessage("XMPP_AXS_TAB1_TITLE")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>

<script language="JavaScript">
var savedNS;
var stop;

function StartServer()
{
	CHttpRequest.Action = function(result)
	{
		if (result.indexOf('success') == -1)
		{
			CloseWaitWindow();
			document.getElementById('reindex_result_div').innerHTML = "<?= GetMessage('XMPP_AXS_ERR_START') ?>. " + result;
		}
		else
		{
			document.getElementById('reindex_result_div').innerHTML = "<?= GetMessage('XMPP_AXS_SUCC_START') ?>";
			setTimeout("QueryServer();", 4000);
		}
	}

	ShowWaitWindow();
	CHttpRequest.Send('xmpp_server.php?lang=<?echo htmlspecialchars(LANG)?>&server_action=start&<?echo bitrix_sessid_get()?>');
}

function StopServer()
{
	CHttpRequest.Action = function(result)
	{
		CloseWaitWindow();
		if (result.indexOf('success') == -1)
		{
			document.getElementById('reindex_result_div').innerHTML = "<?= GetMessage('XMPP_AXS_ERR_STOP') ?>";
		}
		else
		{
			ShowData(false, "", "");
			document.getElementById('reindex_result_div').innerHTML = "<?= GetMessage('XMPP_AXS_SUCC_STOP') ?>";
		}
	}

	ShowWaitWindow();
	CHttpRequest.Send('xmpp_server.php?lang=<?echo htmlspecialchars(LANG)?>&server_action=stop&<?echo bitrix_sessid_get()?>');
}

function ClearCacheServer()
{
	CHttpRequest.Action = function(result)
	{
		CloseWaitWindow();
		if (result.indexOf('success') == -1)
		{
			document.getElementById('reindex_result_div').innerHTML = "<?= GetMessage('XMPP_AXS_ERR_CLEARCACHE') ?>";
		}
		else
		{
			//ShowData(false, "", "");
			document.getElementById('reindex_result_div').innerHTML = "<?= GetMessage('XMPP_AXS_SUCC_CLEARCACHE') ?>";
		}
	}

	ShowWaitWindow();
	CHttpRequest.Send('xmpp_server.php?lang=<?echo htmlspecialchars(LANG)?>&server_action=clearcache&<?echo bitrix_sessid_get()?>');
}

function DumpServer()
{
	CHttpRequest.Action = function(result)
	{
		CloseWaitWindow();
		if (result.indexOf('success') == -1)
		{
			document.getElementById('reindex_result_div').innerHTML = "Dump error";
		}
		else
		{
			//ShowData(false, "", "");
			document.getElementById('reindex_result_div').innerHTML = "Dump success";
		}
	}

	ShowWaitWindow();
	CHttpRequest.Send('xmpp_server.php?lang=<?echo htmlspecialchars(LANG)?>&server_action=dump&<?echo bitrix_sessid_get()?>');
}

function XPPrepareString(str)
{
	str = str.replace(/^\s+|\s+$/, '');
	str = str.replace(/[\r\n]+/g, "");

	while (str.length > 0 && str.charCodeAt(0) == 65279)
		str = str.substring(1);
	return str;
}

function QueryServer()
{
	CHttpRequest.Action = function(result)
	{
		CloseWaitWindow();
		result = XPPrepareString(result);

		if (result.length > 0)
		{
			eval("arData = " + result);

			if (arData["query"])
			{
				ShowData(true, arData["query"]["common"]["online"]["#"], arData["query"]["common"]["connected"]["#"]);
			}
			else
			{
				ShowData(false, "", "");
				if (arData["error"]["errorStr"].length > 0)
					document.getElementById('reindex_result_div').innerHTML = "<?= GetMessage('XMPP_AXS_ERR_QUERY') ?> [" + arData["error"]["errorNo"] + "] " + arData["error"]["errorStr"];
				else
					document.getElementById('reindex_result_div').innerHTML = "<?= GetMessage('XMPP_AXS_ERR_QUERY1') ?>";
			}
		}
		else
		{
			ShowData(false, "", "");
		}
	}

	ShowWaitWindow();
	CHttpRequest.Send('xmpp_server.php?lang=<?echo htmlspecialchars(LANG)?>&server_action=query&<?echo bitrix_sessid_get()?>');
}

function ShowData(run, online, connected)
{
	document.getElementById('f_server_run').innerHTML = (run ? "<?= GetMessage('XMPP_AXS_YES') ?>" : "<?= GetMessage('XMPP_AXS_NO') ?>");
	document.getElementById('f_users_online').innerHTML = (run ? online : "-");
	document.getElementById('f_users_connected').innerHTML = (run ? connected : "-");

	document.getElementById('start_button').disabled = run;
	document.getElementById('stop_button').disabled = !run;
	document.getElementById('clearcache_button').disabled = !run;
	document.getElementById('dump_button').disabled = !run;
}
</script>

<div id="reindex_result_div" style="margin:0px; font-size:100%; color:#FF0000"></div>

<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?echo htmlspecialchars(LANG)?>" name="fs1">
<?
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
	<tr>
		<td width="40%"><?= GetMessage('XMPP_AXS_F_SERVER_RUN') ?>:</td>
		<td width="60%"><div id="f_server_run"><?= GetMessage('XMPP_AXS_F_SERVER_RUN_VALUE') ?><div></td>
	</tr>
	<tr>
		<td><?= GetMessage('XMPP_AXS_F_USERS_ONLINE') ?>:</td>
		<td><div id="f_users_online">???<div></td>
	</tr>
	<tr>
		<td><?= GetMessage('XMPP_AXS_F_USERS_CONNECTED') ?>:</td>
		<td><div id="f_users_connected">???<div></td>
	</tr>
<?
$tabControl->Buttons();
?>
	<input type="button" id="continue_button" value="<?= GetMessage('XMPP_AXS_ACT_QUERY') ?>" OnClick="QueryServer();">
	<input type="button" id="start_button" value="<?= GetMessage('XMPP_AXS_ACT_START') ?>" OnClick="StartServer();">
	<input type="button" id="stop_button" value="<?= GetMessage('XMPP_AXS_ACT_STOP') ?>" OnClick="StopServer();">
	<input type="button" id="clearcache_button" value="<?= GetMessage('XMPP_AXS_ACT_CLEARCACHE') ?>" OnClick="ClearCacheServer();">
	<input type="button" id="dump_button" value="Dump" OnClick="DumpServer();">
<?
$tabControl->End();
?>
</form>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>