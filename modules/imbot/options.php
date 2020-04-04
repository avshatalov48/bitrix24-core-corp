<?php
if(!$USER->IsAdmin())
	return;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/imbot/options.php');

CModule::IncludeModule('imbot');

$errorMessage = '';

$aTabs = array(
	array(
		"DIV" => "edit1", "TAB" => GetMessage("IMBOT_TAB_SETTINGS"), "ICON" => "imbot_config", "TITLE" => GetMessage("IMBOT_TAB_TITLE_SETTINGS_2"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if(strlen($_POST['Update'])>0 && check_bitrix_sessid())
{
	if (strlen($_POST['PUBLIC_URL']) > 0 && strlen($_POST['PUBLIC_URL']) < 12)
	{
		$errorMessage = GetMessage('IMBOT_ACCOUNT_ERROR_PUBLIC');
	}
	else if(strlen($_POST['Update'])>0)
	{
		COption::SetOptionString("imbot", "portal_url", $_POST['PUBLIC_URL']);
		COption::SetOptionString("imbot", "debug", isset($_POST['DEBUG_MODE']));
		if (isset($_POST['DEBUG_MODE']))
		{
			COption::SetOptionString("imbot", "wait_response", isset($_POST['WAIT_RESPONSE']));
		}
		if (isset($_POST['BOT_GIPHY']))
		{
			if (!\Bitrix\ImBot\Bot\Giphy::getBotId())
			{
				\Bitrix\ImBot\Bot\Giphy::register();
			}
		}
		else
		{
			if (\Bitrix\ImBot\Bot\Giphy::getBotId())
			{
				\Bitrix\ImBot\Bot\Giphy::unRegister();
			}
		}
		if (isset($_POST['BOT_PROPERTIES']))
		{
			if (!\Bitrix\ImBot\Bot\Properties::getBotId())
			{
				\Bitrix\ImBot\Bot\Properties::register();
			}
		}
		else
		{
			if (\Bitrix\ImBot\Bot\Properties::getBotId())
			{
				\Bitrix\ImBot\Bot\Properties::unRegister();
			}
		}
		if (isset($_POST['BOT_PROPERTIESUA']))
		{
			if (!\Bitrix\ImBot\Bot\PropertiesUa::getBotId())
			{
				\Bitrix\ImBot\Bot\PropertiesUa::register();
			}
		}
		else
		{
			if (\Bitrix\ImBot\Bot\PropertiesUa::getBotId())
			{
				\Bitrix\ImBot\Bot\PropertiesUa::unRegister();
			}
		}

		if (!\CModule::IncludeModule('bitrix24'))
		{
			if (isset($_POST['BOT_SUPPORT']))
			{
				if (!\Bitrix\ImBot\Bot\Support::checkPublicUrl())
				{
					$APPLICATION->ThrowException(\Bitrix\Main\Localization\Loc::getMessage('SUPPORT_ERROR_URL'));
					\Bitrix\ImBot\Bot\Support::unRegister();
				}
				elseif (!\Bitrix\ImBot\Bot\Support::getBotId())
				{
					\Bitrix\ImBot\Bot\Support::register();
				}
			}
			else
			{
				if (\Bitrix\ImBot\Bot\Support::getBotId())
				{
					\Bitrix\ImBot\Bot\Support::unRegister();
				}
			}
		}

		if ($e = $APPLICATION->getException())
		{
			CAdminMessage::ShowMessage(array(
				"DETAILS" => $e->getString(),
				"TYPE" => "ERROR",
				"HTML" => true));
		}
		elseif(strlen($Update)>0 && strlen($_REQUEST["back_url_settings"])>0)
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
	<td width="40%"><?=GetMessage("IMBOT_ACCOUNT_URL")?>:</td>
	<td width="60%"><input type="text" name="PUBLIC_URL" value="<?=htmlspecialcharsbx(\Bitrix\ImBot\Http::getServerAddress())?>" /></td>
</tr>
<?if (COption::GetOptionInt("imbot", "debug")):?>
<tr>
	<td width="40%" valign="top"><?=GetMessage("IMBOT_WAIT_RESPONSE")?>:</td>
	<td width="60%">
		<input type="checkbox" name="WAIT_RESPONSE" value="Y" <?=(COption::GetOptionInt("imbot", "wait_response")? 'checked':'')?> /><br>
		<?=GetMessage("IMBOT_WAIT_RESPONSE_DESC")?>
	</td>
</tr>
<?endif;?>
<tr>
	<td width="40%"><?=GetMessage("IMBOT_ACCOUNT_DEBUG")?>:</td>
	<td width="60%"><input type="checkbox" name="DEBUG_MODE" value="Y" <?=(COption::GetOptionInt("imbot", "debug")? 'checked':'')?> /></td>
</tr>
<tr class="heading">
	<td colspan="2"><b><?=GetMessage('IMBOT_HEADER_BOTS')?></b></td>
</tr>
<tr>
	<td width="40%"><?=\Bitrix\ImBot\Bot\Giphy::getLangMessage('IMBOT_GIPHY_BOT_NAME')?>:</td>
	<td width="60%"><input type="checkbox" name="BOT_GIPHY" value="Y" <?=(\Bitrix\ImBot\Bot\Giphy::getBotId()? 'checked':'')?> /></td>
</tr>
<tr>
	<td width="40%"><?=\Bitrix\ImBot\Bot\Properties::getLangMessage('IMBOT_PROPERTIES_BOT_NAME')?>:</td>
	<td width="60%"><input type="checkbox" name="BOT_PROPERTIES" value="Y" <?=(\Bitrix\ImBot\Bot\Properties::getBotId()? 'checked':'')?> /></td>
</tr>
<tr>
	<td width="40%"><?=\Bitrix\ImBot\Bot\PropertiesUa::getLangMessage('IMBOT_PROPERTIESUA_BOT_NAME').' ('.\Bitrix\Main\Localization\Loc::getMessage('IMBOT_BOT_POSTFIX_UA').')'?>:</td>
	<td width="60%"><input type="checkbox" name="BOT_PROPERTIESUA" value="Y" <?=(\Bitrix\ImBot\Bot\PropertiesUa::getBotId()? 'checked':'')?> /></td>
</tr>
<? if (!\CModule::IncludeModule('bitrix24')): ?>
<tr>
	<td width="40%"><?=\Bitrix\ImBot\Bot\Properties::getLangMessage('IMBOT_SUPPORT_BOT_NAME')?>:</td>
	<td width="60%"><input type="checkbox" name="BOT_SUPPORT" value="Y" <?=(\Bitrix\ImBot\Bot\Support::getBotId()? 'checked':'')?> /></td>
</tr>
<?endif;?>
<?$tabControl->Buttons();?>
<input type="submit" name="Update" value="<?echo GetMessage('MAIN_SAVE')?>">
<input type="reset" name="reset" value="<?echo GetMessage('MAIN_RESET')?>">
<?$tabControl->End();?>
</form>
<div class="adm-info-message-wrap">
	<div class="adm-info-message">
	<?=GetMessage('IMBOT_BOT_NOTICE')?>
	</div>
</div>
