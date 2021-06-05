<?
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2002-2012 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');

$module_id = "ldap";
CModule::IncludeModule($module_id);

$MOD_RIGHT = $APPLICATION->GetGroupRight($module_id);
if($MOD_RIGHT>="R"):

$arAllLdapServers = array(0 => Loc::getMessage('LDAP_NOT_USE_DEFAULT_NTLM_SERVER'));
$rsLdapServers = CLdapServer::GetList();

while($arLdapServer = $rsLdapServers->Fetch())
{
	$arAllLdapServers[$arLdapServer['ID']] = $arLdapServer['NAME'];
}

// get current NTLM user login for displaying later
$ntlmVarname = COption::GetOptionString($module_id, 'ntlm_varname', 'REMOTE_USER');

if (array_key_exists($ntlmVarname,$_SERVER) && trim($_SERVER[$ntlmVarname])!='')
{
	$currentUserNTLMMsg = htmlspecialcharsbx($_SERVER[$ntlmVarname]);
}
else
{
	$currentUserNTLMMsg = Loc::getMessage("LDAP_CURRENT_USER_ABS");
}


// set up form
$arAllOptions =	Array(
		//Array("group_limit", Loc::getMessage('LDAP_OPTIONS_GROUP_LIMIT'), 0, Array("text", 5)),
		Array("default_email", Loc::getMessage('LDAP_OPTIONS_DEFAULT_EMAIL'), "no@email", Array("text")),
		Array("use_ntlm", Loc::getMessage('LDAP_OPTIONS_USE_NTLM'), "N", Array("checkbox")),
		Array("use_ntlm_login", Loc::getMessage('LDAP_CURRENT_USER'), $currentUserNTLMMsg, Array("statictext")),
		Array("ntlm_varname", Loc::getMessage('LDAP_OPTIONS_NTLM_VARNAME'), "REMOTE_USER", Array("text", 20)),
		Array("ntlm_default_server", Loc::getMessage('LDAP_DEFAULT_NTLM_SERVER'), "0", Array("selectbox", $arAllLdapServers)),
		Array("add_user_when_auth", Loc::getMessage("LDAP_OPTIONS_NEW_USERS"), "Y", Array("checkbox")),
		Array("ntlm_auth_without_prefix", Loc::getMessage("LDAP_WITHOUT_PREFIX"), "Y", Array("checkbox")),
		Array("ldap_create_duplicate_login_user", Loc::getMessage("LDAP_DUPLICATE_LOGIN_USER"), "Y", Array("checkbox")),
		Loc::getMessage("LDAP_BITRIXVM_BLOCK"),
		Array("bitrixvm_auth_support", Loc::getMessage("LDAP_BITRIXVM_SUPPORT"), "N", Array("checkbox")),
		Array("bitrixvm_auth_net", Loc::getMessage('LDAP_BITRIXVM_NET'), "", Array("textarea")),
	);

if($MOD_RIGHT>="W"):

	if ($REQUEST_METHOD=="GET" && $RestoreDefaults <> '' && check_bitrix_sessid())
	{
		COption::RemoveOption($module_id);
		$z = CGroup::GetList("id", "asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
		while($zr = $z->Fetch())
			$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
	}

	if($REQUEST_METHOD=="POST" && $Update <> '' && check_bitrix_sessid())
	{
		if($_POST['bitrixvm_auth_net'] && !preg_match("#(\d{1,3}\.){3,3}(\d{1,3})/(\d{1,3}\.){3,3}(\d{1,3})#",$_POST['bitrixvm_auth_net']) && !preg_match("#(\d{1,3}\.){3,3}(\d{1,3})/(\d{1,3})#",$_POST['bitrixvm_auth_net']))
			CAdminMessage::ShowMessage(Loc::getMessage('LDAP_WRONG_NET_MASK'));

		foreach($arAllOptions as $option)
		{
			if(!is_array($option))
				continue;

			$name = $option[0];
			$val = ${$name};
			if($option[3][0] == "checkbox" && $val != "Y")
				$val = "N";
			if($option[3][0] == "multiselectbox")
				$val = @implode(",", $val);

			COption::SetOptionString($module_id, $name, $val, $option[1]);
		}
		if ($_POST['use_ntlm'] == 'Y')
		{
			RegisterModuleDependences('main', 'OnBeforeProlog', 'ldap', 'CLDAP', 'NTLMAuth', 40);
		}
		else
		{
			UnRegisterModuleDependences('main', 'OnBeforeProlog', 'ldap', 'CLDAP', 'NTLMAuth');
		}

		if ($_POST['bitrixvm_auth_support'] == 'Y')
			CLdapUtil::SetBitrixVMAuthSupport();
		else
			CLdapUtil::UnSetBitrixVMAuthSupport();
	}

endif; //if($MOD_RIGHT>="W"):

$arAllOptions[] = Array("bitrixvm_auth_hint", "", BeginNote().Loc::getMessage("LDAP_BITRIXVM_HINT").EndNote(), Array("statichtml", ""));

$aTabs = array(
	array("DIV" => "edit1", "TAB" => Loc::getMessage("MAIN_TAB_SET"), "ICON" => "ldap_settings", "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_SET")),
	array("DIV" => "edit2", "TAB" => Loc::getMessage("MAIN_TAB_RIGHTS"), "ICON" => "ldap_settings", "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_RIGHTS")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

?>
<?
$tabControl->Begin();
?><form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?=LANGUAGE_ID?>" name="ldap_settings">
<?$tabControl->BeginNextTab();?>
<?__AdmSettingsDrawList("ldap", $arAllOptions);?>
<?
$tabControl->BeginNextTab();?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
<?$tabControl->Buttons();?>
<script language="JavaScript">
function RestoreDefaults()
{
	if(confirm('<?echo AddSlashes(Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>'))
		window.location = "<?echo $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANG?>&mid=<?echo urlencode($mid)."&".bitrix_sessid_get();?>";
}
</script>
<input type="submit" name="Update" <?if ($MOD_RIGHT<"W") echo "disabled" ?> value="<?echo Loc::getMessage("LDAP_OPTIONS_SAVE")?>">
<input type="reset" name="reset" value="<?echo Loc::getMessage("LDAP_OPTIONS_RESET")?>">
<input type="hidden" name="Update" value="Y">
<?=bitrix_sessid_post();?>
<input type="button" <?if ($MOD_RIGHT<"W") echo "disabled" ?> title="<?echo Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="RestoreDefaults();" value="<?echo Loc::getMessage("MAIN_RESTORE_DEFAULTS")?>">
<?$tabControl->End();?>
</form>
<?endif;

echo BeginNote();
echo Loc::getMessage("LDAP_OPTIONS_USE_NTLM_MSG");
echo EndNote();

?>
