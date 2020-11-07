<?php
if(!$USER->IsAdmin())
	return;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/transformer/options.php');

CModule::IncludeModule('transformer');

$errorMessage = '';

$aTabs = array(
	array(
		"DIV" => "edit1", "TAB" => GetMessage("TRANSFORMER_TAB_SETTINGS"), "ICON" => "transformer_config", "TITLE" => GetMessage("TRANSFORMER_TAB_TITLE_SETTINGS_2"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if($_POST['Update'] <> '' && check_bitrix_sessid())
{
	if($_POST['PUBLIC_URL'] <> '' && mb_strlen($_POST['PUBLIC_URL']) < 12)
	{
		$errorMessage = GetMessage('TRANSFORMER_ACCOUNT_ERROR_PUBLIC');
	}
	elseif($_POST['Update'] <> '')
	{
		COption::SetOptionString("transformer", "portal_url", $_POST['PUBLIC_URL']);
		COption::SetOptionString("transformer", "debug", isset($_POST['DEBUG_MODE']));
		COption::SetOptionString("transformer", "connection_time", $_POST['CONNECTION_TIME']);
		COption::SetOptionString("transformer", "stream_time", $_POST['STREAM_TIME']);

		if($Update <> '' && $_REQUEST["back_url_settings"] <> '')
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
		<td width="40%"><?=GetMessage("TRANSFORMER_PUBLIC_URL")?>:</td>
		<td width="60%"><input type="text" name="PUBLIC_URL" value="<?=htmlspecialcharsbx(\Bitrix\Transformer\Http::getServerAddress())?>" /></td>
	</tr>
	<tr>
		<td width="40%"><?=GetMessage("TRANSFORMER_ACCOUNT_DEBUG")?>:</td>
		<td width="60%"><input type="checkbox" name="DEBUG_MODE" value="Y" <?=(COption::GetOptionInt("transformer", "debug")? 'checked':'')?> /></td>
	</tr>
	<tr>
		<td width="40%"><?=GetMessage("TRANSFORMER_CONNECTION_TIME")?>:</td>
		<td width="60%"><input type="text" name="CONNECTION_TIME" value="<?=COption::GetOptionInt("transformer", "connection_time", 2);?>" /></td>
	</tr>
	<tr>
		<td width="40%"><?=GetMessage("TRANSFORMER_STREAM_TIME")?>:</td>
		<td width="60%"><input type="text" name="STREAM_TIME" value="<?=COption::GetOptionInt("transformer", "stream_time", 2);?>" /></td>
	</tr>
	<?$tabControl->Buttons();?>
	<input type="submit" name="Update" value="<?echo GetMessage('MAIN_SAVE')?>">
	<input type="reset" name="reset" value="<?echo GetMessage('MAIN_RESET')?>">
	<?$tabControl->End();?>
</form>