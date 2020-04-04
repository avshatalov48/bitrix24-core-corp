<?php
if(!$USER->IsAdmin())
	return;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/imopenlines/options.php');

CModule::IncludeModule('imopenlines');

$errorMessage = '';

$aTabs = array(
	array(
		"DIV" => "edit1", "TAB" => GetMessage("IMOPENLINES_TAB_SETTINGS"), "ICON" => "imopenlines_config", "TITLE" => GetMessage("IMOPENLINES_TAB_TITLE_SETTINGS_2"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if(strlen($_POST['Update'])>0 && check_bitrix_sessid())
{
	if (strlen($_POST['PUBLIC_URL']) > 0 && strlen($_POST['PUBLIC_URL']) < 12)
	{
		$errorMessage = GetMessage('IMOPENLINES_ACCOUNT_ERROR_PUBLIC');
	}
	else if(strlen($_POST['Update'])>0)
	{
		if ($_POST['PUBLIC_URL'] != COption::GetOptionString("imopenlines", "portal_url"))
		{
			COption::SetOptionString("imopenlines", "portal_url", $_POST['PUBLIC_URL']);

			if(\Bitrix\Main\Loader::includeModule('Crm'))
			{
				\Bitrix\Crm\SiteButton\Manager::updateScriptCacheAgent();
			}
		}

		COption::SetOptionString("imopenlines", "debug", isset($_POST['DEBUG_MODE']));

		$execMode = COption::GetOptionString("imopenlines", "exec_mode");
		if ($_POST['EXEC_MODE'] != $execMode && in_array($_POST['EXEC_MODE'], array(\Bitrix\ImOpenlines\Common::MODE_AGENT, \Bitrix\ImOpenlines\Common::MODE_CRON)))
		{
			COption::SetOptionString("imopenlines", "exec_mode", $_POST['EXEC_MODE']);
		}

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
	<td width="40%"><?=GetMessage("IMOPENLINES_ACCOUNT_URL")?>:</td>
	<td width="60%"><input type="text" name="PUBLIC_URL" value="<?=htmlspecialcharsbx(\Bitrix\ImOpenlines\Common::getServerAddress())?>" /></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage("IMOPENLINES_ACCOUNT_DEBUG")?>:</td>
	<td width="60%"><input type="checkbox" name="DEBUG_MODE" value="Y" <?=(COption::GetOptionInt("imopenlines", "debug")? 'checked':'')?> /></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage("IMOPENLINES_ACCOUNT_EXEC_MODE")?>:</td>
	<td width="60%">
		<select name="EXEC_MODE">
			<option value="<?=\Bitrix\ImOpenlines\Common::MODE_AGENT?>"
				<?if(\Bitrix\ImOpenlines\Common::getExecMode() == \Bitrix\ImOpenlines\Common::MODE_AGENT) {?>selected="selected"<?}?>>
				<?=GetMessage("IMOPENLINES_ACCOUNT_EXEC_MODE_AGENT")?>
			</option>
			<option value="<?=\Bitrix\ImOpenlines\Common::MODE_CRON?>"
					<?if(\Bitrix\ImOpenlines\Common::getExecMode() == \Bitrix\ImOpenlines\Common::MODE_CRON) {?>selected="selected"<?}?>>
				<?=GetMessage("IMOPENLINES_ACCOUNT_EXEC_MODE_CRON")?>
			</option>
		</select>
	</td>
</tr>
<tr>
	<td colspan="2">
		<div class="adm-info-message-wrap">
			<div class="adm-info-message">
				<?=GetMessage("IMOPENLINES_ACCOUNT_EXEC_DESCRIPTION")?>
			</div>
		</div>
	</td>
</tr>
<?$tabControl->Buttons();?>
<input type="submit" name="Update" value="<?echo GetMessage('MAIN_SAVE')?>">
<input type="reset" name="reset" value="<?echo GetMessage('MAIN_RESET')?>">
<?$tabControl->End();?>
</form>