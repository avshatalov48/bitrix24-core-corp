<?
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################

require_once(dirname(__FILE__)."/../../main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xmpp/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xmpp/prolog.php");

$POST_RIGHT = $APPLICATION->GetGroupRight("xmpp");
if ($POST_RIGHT == "D")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile(__FILE__);

$APPLICATION->SetTitle(GetMessage("STANZA_PAGE_TITLE"));

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>

<?
$aTabs = array(
	array("DIV" => "tab1", "TAB" => GetMessage("STANZA_TAB"), "TITLE" => GetMessage("STANZA_TAB_TITLE")),
);
$editTab = new CAdminTabControl("editTab", $aTabs);

?>
<form name="form1" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?=LANG?>" method="POST">
	<?=bitrix_sessid_post()?>
	<?
	$editTab->Begin();
	$editTab->BeginNextTab();
	?>
	<tr valign="top">
		<td width="100%" colspan="2">
			<select name="samples" onchange="document.form1.stanza.value = document.form1.stanza.value + this[this.selectedIndex].value">
				<option value='<message type="chat" from="user@192.168.0.8/Work" to="admin@192.168.0.8/Psi" id="aabda"><body>Hi!</body><active xmlns="http://jabber.org/protocol/chatstates"/></message>'>Message</option>
				<option value='<presence from="admin@192.168.0.8/Psi" to="user@192.168.0.8/Work"><priority>5</priority></presence>'>Presence 1</option>
				<option value='<presence from="admin@192.168.0.8/Psi"><priority>5</priority></presence>'>Presence 2</option>
				<option value='<iq type="get" to="admin@192.168.0.8" id="aabaa"><vCard xmlns="vcard-temp" version="2.0" prodid="-//HandGen//NONSGML vGen v1.0//EN" /></iq>'>vCard</option>
			</select>
		</td>
	</tr>
	<tr valign="top">
		<td width="100%" colspan="2">
			<input type="hidden" name="lang" value="<?=LANG?>">
			<textarea cols="60" name="stanza" id="stanza" rows="15" wrap="OFF" style="width:100%;"><? echo htmlspecialchars($stanza); ?></textarea><br />
		</td>
	</tr>
	<?$editTab->Buttons();
	?>
	<input type="submit" accesskey="x" name="execute" value="<?echo GetMessage("STANZA_EXECUTE")?>">
	<input type="reset" value="<?echo GetMessage("STANZA_RESET")?>">
	<?
	$editTab->End();
	?>
</form>
<?
if ($_SERVER["REQUEST_METHOD"] == "POST" && $stanza<>"" && check_bitrix_sessid())
{
	$errorNo = "";
	$errorStr = "";

	$first = getmicrotime();

	$result = CXMPPUtility::_SendToServerXML($stanza, $errorNo, $errorStr);

	$exec_time = Round(getmicrotime() - $first, 5);

	if ($result !== false)
	{
		$strResult = GetMessage("STANZA_SUCCESS_EXECUTE");
		$strMessage = GetMessage("STANZA_EXEC_TIME")."<b>".$exec_time."</b> ".GetMessage("STANZA_SEC");
		$strData = HtmlSpecialChars($result);
	}
	else
	{
		$strResult = GetMessage("STANZA_QUERY_ERROR_1");
		$strMessage = GetMessage("STANZA_EXEC_TIME")."<b>".$exec_time."</b> ".GetMessage("STANZA_SEC");
		$strData = "[".$errorNo."] ".$errorStr;
	}

	echo "<div style=\"font-size:70%\">";
	echo "<b>".$strResult."</b><br /><br />";
	echo $strData."<br /><br />";
	echo $strMessage."<br /><br />";
	echo "</div>";
}
?>
<?require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");?>