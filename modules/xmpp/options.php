<?
$module_id = "xmpp";
if ($USER->IsAdmin() && (CModule::IncludeModule("xmpp"))):

IncludeModuleLangFile(__FILE__);
CModule::IncludeModule('iblock');

if($REQUEST_METHOD=="POST" && strlen($Update)>0 && check_bitrix_sessid())
{
	COption::SetOptionString("xmpp", "domain_name", $domain_name);
	COption::SetOptionString("xmpp", "listen_domain", $listen_domain);
	COption::SetOptionString("xmpp", "domain_lang", $domain_lang);
	COption::SetOptionString("xmpp", "php_path", $php_path);
	//COption::SetOptionString("xmpp", "server_path", $server_path);
	COption::SetOptionString("xmpp", "log_level", $log_level);
	COption::SetOptionString("xmpp", "start_ssl", StrToUpper($start_ssl));
	COption::SetOptionInt("xmpp", 'iblock_presence', $_REQUEST['iblock_presence']);
	COption::SetOptionString("xmpp", "sonet_sender_type", $sonet_sender_type);
	COption::SetOptionString("xmpp", "sonet_jid", $sonet_jid);	
	COption::SetOptionString("xmpp", "sonet_uid", $sonet_uid);
	COption::SetOptionString("xmpp", "name_template", $name_template);
}

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "vote_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
);
if (IsModuleInstalled("socialnetwork"))
	$aTabs[] = array("DIV" => "edit3", "TAB" => GetMessage("XMPP_TAB_SONET"), "ICON" => "vote_settings", "TITLE" => GetMessage("XMPP_TAB_TITLE_SONET"));

$aTabs[] = array("DIV" => "edit2", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "vote_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS"));

$tabControl = new CAdminTabControl("tabControl", $aTabs);
?>
<?
$tabControl->Begin();
?><form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?=LANGUAGE_ID?>" id="FORMACTION"><?
?><?=bitrix_sessid_post()?><?
$tabControl->BeginNextTab();
?>

	<tr>
		<td width="50%"><?= GetMessage("XMPP_OPT_DOMAIN") ?>:</td>
		<td width="50%">
			<?$val = COption::GetOptionString("xmpp", "domain_name", "");?>
			<input type="text" size="35" maxlength="255" value="<?=htmlspecialcharsbx($val)?>" name="domain_name"></td>
	</tr>
	<tr>
		<td width="50%"><?= GetMessage("XMPP_OPT_LISTEN_DOMAIN") ?><span class="required"><sup>1</sup></span>:</td>
		<td width="50%">
			<?$val = COption::GetOptionString("xmpp", "listen_domain", "0.0.0.0");?>
			<input type="text" size="35" maxlength="255" value="<?=htmlspecialcharsbx($val)?>" name="listen_domain"></td>
	</tr>
	<tr>
		<td width="50%"><?= GetMessage("XMPP_OPT_LANG") ?>:</td>
		<td width="50%">
			<?$val = COption::GetOptionString("xmpp", "domain_lang", "en");?>
			<input type="text" size="35" maxlength="255" value="<?=htmlspecialcharsbx($val)?>" name="domain_lang"></td>
	</tr>
	<tr>
		<td width="50%"><?= GetMessage("XMPP_OPT_PHP_PATH") ?>:</td>
		<td width="50%">
			<?$val = COption::GetOptionString("xmpp", "php_path", (StrToUpper(substr(PHP_OS, 0, 3)) === "WIN") ? "../apache/php.exe -c ../apache/php.ini" : "php -c /etc/php.ini");?>
			<input type="text" size="35" maxlength="255" value="<?=htmlspecialcharsbx($val)?>" name="php_path"></td>
	</tr>
	<!--tr>
		<td valign="top"  width="50%"><?= GetMessage("XMPP_OPT_XMPP_PATH") ?>:</td>
		<td valign="middle" width="50%">
			<?$val = COption::GetOptionString("xmpp", "server_path", "./bitrix/modules/xmpp/xmppd.php");?>
			<input type="text" size="35" maxlength="255" value="<?=htmlspecialcharsbx($val)?>" name="server_path"></td>
	</tr-->
	<tr>
		<td width="50%"><?= GetMessage("XMPP_OPT_LOG_LEVEL") ?>:</td>
		<td width="50%">
			<?$val = intval(COption::GetOptionString("xmpp", "log_level", "4"));?>
			<select name="log_level">
				<?for ($i = 0; $i < 11; $i++):?>
					<option value="<?= $i ?>"<?= (($i == $val) ? " selected" : "")?>><?= $i ?></option>
				<?endfor;?>
			</select>
	</tr>
	<tr>
		<td width="50%"><?= GetMessage("XMPP_OPT_SSL") ?><span class="required"><sup>2</sup></span>:</td>
		<td width="50%">
			<?$val = StrToUpper(COption::GetOptionString("xmpp", "start_ssl", "N"));?>
			<select name="start_ssl">
				<option value="N"<?= (("N" == $val) ? " selected" : "")?>><?= GetMessage("XMPP_OPT_NO") ?></option>
				<option value="Y"<?= (("Y" == $val) ? " selected" : "")?>><?= GetMessage("XMPP_OPT_YES") ?></option>
			</select>
	</tr>
	<tr>
		<td width="50%"><?= GetMessage("XMPP_OPT_PRESENCE") ?>:</td>
		<td width="50%">
			<?$val = COption::GetOptionInt("xmpp", "iblock_presence");?>
			<select name="iblock_presence">
				<option value="0"></option>
				<?
				$dbIBlock = CIBlock::GetList(array('NAME' => 'ASC', 'CODE' => 'ASC'), array('ACTIVE' => 'Y'));
				while ($arIBlock = $dbIBlock->Fetch())
				{
					?><option value="<?= $arIBlock['ID'] ?>"<?= (($arIBlock['ID'] == $val) ? " selected" : "")?>><?= ($arIBlock['CODE'] ? '['.$arIBlock['CODE'].'] ' : '').$arIBlock['NAME'] ?></option><?
				}
				?>
			</select>
	</tr>
	<tr>
		<td width="50%"><?= GetMessage("XMPP_NAME_TEMPLATE") ?>:</td>
		<td width="50%">
			<?$curVal = str_replace(array("#NOBR#","#/NOBR#"), array("",""), COption::GetOptionString("xmpp", "name_template", "#LAST_NAME# #NAME#"));?>
			<select name="name_template">
				<?
				$arNameTemplates = CSite::GetNameTemplates();
				foreach ($arNameTemplates as $template => $phrase)
				{
					$template = str_replace(array("#NOBR#","#/NOBR#"), array("",""), $template);
					?><option value="<?= $template?>" <?=(($template == $curVal) ? " selected" : "")?> ><?= $phrase?></option><?
				}
				?>
			</select>
	</tr>
<?$tabControl->BeginNextTab();?>

	<tr>
		<td width="50%"><?= GetMessage("XMPP_OPT_SONET_UID") ?>:</td>
		<td width="50%">
			<?$val = COption::GetOptionString("xmpp", "sonet_sender_type", "jid");?>
			<input type="radio" name="sonet_sender_type" id="sonet_sender_type_jid" value="jid" OnClick="manageSonetSenderType('jid')"<?=$val=="jid"?" checked":""?>><label for="sonet_sender_type_jid"><?=GetMessage("XMPP_OPT_SONET_TYPE_JID")?></label><br>
			<input type="radio" name="sonet_sender_type" id="sonet_sender_type_uid" value="uid" OnClick="manageSonetSenderType('uid')"<?=$val=="uid"?" checked":""?>><label for="sonet_sender_type_uid"><?=GetMessage("XMPP_OPT_SONET_TYPE_UID")?></label><br>
		</td>
	</tr>	
	<tr>
		<td width="50%"><?= GetMessage("XMPP_OPT_SONET_JID") ?>:</td>
		<td width="50%">
			<?$val = COption::GetOptionString("xmpp", "sonet_jid", "admin@".$_SERVER["SERVER_NAME"]);?>
			<input type="text" size="35" maxlength="255" value="<?=htmlspecialcharsbx($val)?>" name="sonet_jid" id="sonet_jid"></td>
	</tr>
	<tr>
		<td width="50%"><?= GetMessage("XMPP_OPT_SONET_UID") ?>:</td>
		<td width="50%">
			<?$val = COption::GetOptionString("xmpp", "sonet_uid", "");?>
			<input type="text" size="5" maxlength="10" value="<?=htmlspecialcharsbx($val)?>" name="sonet_uid" id="sonet_uid"></td>
	</tr>	
<?$tabControl->EndTab();?>	
	<script language="JavaScript">
		manageSonetSenderType(false);
		function manageSonetSenderType(what)
		{
			var jid = document.getElementsByName('sonet_jid')[0];
			var uid = document.getElementsByName('sonet_uid')[0];
			if(what==false)
			{
				var radio = document.getElementsByName('sonet_sender_type');
				for(var i=0;i<radio.length;i++)
					if(radio[i].checked)
						what=radio[i].value;
			}
			jid.disabled = what != 'jid';
			uid.disabled = what != 'uid';
		}
	</script>
	
<?$tabControl->BeginNextTab();?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
<?$tabControl->Buttons();?>
<input type="hidden" name="Update" value="Y">
<input <?if (!$USER->IsAdmin()) echo "disabled" ?> class="adm-btn-save" type="submit" name="Update" value="<?= GetMessage("XMPP_OPT_ACT_APPLY") ?>">
<input type="reset" name="reset" value="<?= GetMessage("XMPP_OPT_ACT_DEFAULT") ?>">
<?$tabControl->End();?>
</form>

<?echo BeginNote();?>
<span class="required"><sup>1</sup></span> <?echo GetMessage("XMPP_OPT_NOTE_1")?><br>
<span class="required"><sup>2</sup></span> <?echo GetMessage("XMPP_OPT_NOTE_2")?>
<?echo EndNote();?>
<?endif;?>
