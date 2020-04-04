<?php
if(!$USER->IsAdmin())
	return;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/voximplant/options.php');

CModule::IncludeModule('voximplant');

$aTabs = array(
	array(
		"DIV" => "edit1", "TAB" => GetMessage("VI_TAB_SETTINGS"), "ICON" => "voximplant_config", "TITLE" => GetMessage("VI_TAB_TITLE_SETTINGS_2"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

$ViAccount = new CVoxImplantAccount();
$ViAccount->UpdateAccountInfo();

if ($ViAccount->GetError()->error)
{
	$accountName = '-';
	$accountBalance = '-';
	if ($ViAccount->GetError()->code == 'LICENCE_ERROR')
	{
		$errorMessage = GetMessage('VI_ACCOUNT_ERROR_LICENSE');
	}
	else
	{
		$errorMessage = GetMessage('VI_ACCOUNT_ERROR');
	}
}
else
{
	$accountName = $ViAccount->GetAccountName();
	$accountBalance = $ViAccount->GetAccountBalance().' '.$ViAccount->GetAccountCurrency();
	$errorMessage = '';
}
if(strlen($_POST['Update'])>0 && check_bitrix_sessid())
{
	if (strlen($_POST['PUBLIC_URL']) > 0 && strlen($_POST['PUBLIC_URL']) < 12)
	{
		$errorMessage = GetMessage('VI_ACCOUNT_ERROR_PUBLIC');
	}
	else if(strlen($_POST['Update'])>0)
	{
		COption::SetOptionString("voximplant", "portal_url", $_POST['PUBLIC_URL']);
		COption::SetOptionString("voximplant", "debug", isset($_POST['DEBUG_MODE']));

		$viHttp = new CVoxImplantHttp();
		$viHttp->ClearConfigCache();

		if(strlen($Update)>0 && strlen($_REQUEST["back_url_settings"])>0)
		{
			LocalRedirect($_REQUEST["back_url_settings"]);
		}
		else
		{
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
		}
	}
}
?>
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?echo LANG?>">
<?php echo bitrix_sessid_post()?>
<?php
$tabControl->Begin();
$tabControl->BeginNextTab();
if ($errorMessage):?>
<tr>
	<td colspan="2" align="center"><b style="color:red"><?=$errorMessage?></b></td>
</tr>
<?endif;?>
<tr>
	<td width="40%"><?=GetMessage("VI_ACCOUNT_NAME")?>:</td>
	<td width="60%"><b><?=str_replace('.bitrixphone.com', '', $accountName)?></b></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage("VI_ACCOUNT_BALANCE")?>:</td>
	<td width="60%"><b><?=$accountBalance?></b></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage("VI_ACCOUNT_URL")?>:</td>
	<td width="60%"><input type="text" name="PUBLIC_URL" value="<?=htmlspecialcharsbx(CVoxImplantHttp::GetServerAddress())?>" /></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage("VI_ACCOUNT_DEBUG")?>:</td>
	<td width="60%"><input type="checkbox" name="DEBUG_MODE" value="Y" <?=(COption::GetOptionInt("voximplant", "debug")? 'checked':'')?> /></td>
</tr>
<?if(!IsModuleInstalled('bitrix24')):?>
<?$tabControl->Buttons();?>
<input type="submit" name="Update" value="<?echo GetMessage('MAIN_SAVE')?>">
<input type="reset" name="reset" value="<?echo GetMessage('MAIN_RESET')?>">
<?endif;?>
<?$tabControl->End();?>
</form>