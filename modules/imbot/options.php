<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc,
	Bitrix\Main;

/**
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global string $mid
 */

if (!$USER->IsAdmin())
{
	return;
}
if (!Main\Loader::includeModule('imbot'))
{
	return;
}

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
Loc::loadMessages(__FILE__);


$errorMessage = '';

$aTabs = array(
	array(
		"DIV" => "edit1",
		"TAB" => Loc::getMessage("IMBOT_TAB_SETTINGS"),
		"ICON" => "imbot_config",
		"TITLE" => Loc::getMessage("IMBOT_TAB_TITLE_SETTINGS_2"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if($_POST['Update'] <> '' && check_bitrix_sessid())
{
	if ($_POST['PUBLIC_URL'] <> '' && mb_strlen($_POST['PUBLIC_URL']) < 12)
	{
		$errorMessage = Loc::getMessage('IMBOT_ACCOUNT_ERROR_PUBLIC');
	}
	else if($_POST['Update'] <> '')
	{
		if (!defined('BOT_CLIENT_URL'))
		{
			if (\Bitrix\ImBot\Bot\Network::checkPublicUrl() !== true)
			{
				$error = \Bitrix\ImBot\Bot\Base::getError();
				if ($error->error)
				{
					$message = Loc::getMessage('IMBOT_ACCOUNT_ERROR_PUBLIC_CHECK', ['#ERROR#' => $error->msg]);
				}
				else
				{
					$message = Loc::getMessage('IMBOT_ACCOUNT_ERROR_PUBLIC');
				}
				$APPLICATION->ThrowException($message);
			}
			else
			{
				Main\Config\Option::set("imbot", "portal_url", $_POST['PUBLIC_URL']);
			}
		}

		Main\Config\Option::set("imbot", "debug", isset($_POST['DEBUG_MODE']));
		if (isset($_POST['DEBUG_MODE']))
		{
			Main\Config\Option::set("imbot", "wait_response", isset($_POST['WAIT_RESPONSE']));
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

		if (!Main\Loader::includeModule('bitrix24'))
		{
			if (isset($_POST['BOT_SUPPORT']))
			{
				if (!\Bitrix\ImBot\Bot\SupportBox::register())
				{
					$error = \Bitrix\ImBot\Bot\SupportBox::getError();
					if ($error->error)
					{
						$message = Loc::getMessage('IMBOT_SUPPORT_BOX_ACTIVATION_ERROR', ['#ERROR#' => $error->msg]);
					}
					else
					{
						$message = Loc::getMessage('IMBOT_SUPPORT_BOX_ACTIVATION_ERROR_UNKNOWN');
					}
					$APPLICATION->ThrowException($message);

					\Bitrix\ImBot\Bot\SupportBox::unRegister();
				}
			}
			else
			{
				if (\Bitrix\ImBot\Bot\SupportBox::getBotId())
				{
					\Bitrix\ImBot\Bot\SupportBox::unRegister();
				}
			}
		}

		if ($e = $APPLICATION->getException())
		{
			\CAdminMessage::ShowMessage(array(
				"DETAILS" => $e->getString(),
				"TYPE" => "ERROR",
				"HTML" => true
			));
		}
		elseif($_REQUEST["back_url_settings"] <> '')
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
<form method="post" action="<?= $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?= LANG?>">
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
	<td width="40%"><?=Loc::getMessage("IMBOT_ACCOUNT_URL")?>:</td>
	<td width="60%"><input type="text" name="PUBLIC_URL" value="<?= htmlspecialcharsbx(\Bitrix\ImBot\Http::getServerAddress()) ?>" /></td>
</tr>
<?if ((int)Main\Config\Option::get("imbot", "debug")):?>
<tr>
	<td width="40%" valign="top"><?=Loc::getMessage("IMBOT_WAIT_RESPONSE")?>:</td>
	<td width="60%">
		<input type="checkbox" name="WAIT_RESPONSE" value="Y" <?=((int)Main\Config\Option::get("imbot", "wait_response")? 'checked':'')?> /><br>
		<?=Loc::getMessage("IMBOT_WAIT_RESPONSE_DESC")?>
	</td>
</tr>
<?endif;?>
<tr>
	<td width="40%"><?=Loc::getMessage("IMBOT_ACCOUNT_DEBUG")?>:</td>
	<td width="60%"><input type="checkbox" name="DEBUG_MODE" value="Y" <?=((int)Main\Config\Option::get("imbot", "debug")? 'checked':'')?> /></td>
</tr>
<tr class="heading">
	<td colspan="2"><b><?=Loc::getMessage('IMBOT_HEADER_BOTS')?></b></td>
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
<? if (!Main\Loader::includeModule('bitrix24')): ?>
<tr>
	<td width="40%"><?= Loc::getMessage('IMBOT_SUPPORT_BOT_NAME') ?>:</td>
	<td width="60%"><input type="checkbox" name="BOT_SUPPORT" value="Y" <?=(\Bitrix\ImBot\Bot\SupportBox::getBotId()? 'checked':'')?> /></td>
</tr>
<?endif;?>
<?$tabControl->Buttons();?>
<input type="submit" name="Update" value="<?= Loc::getMessage('MAIN_SAVE')?>">
<input type="reset" name="reset" value="<?= Loc::getMessage('MAIN_RESET')?>">
<?$tabControl->End();?>
</form>
<div class="adm-info-message-wrap">
	<div class="adm-info-message">
	<?=Loc::getMessage('IMBOT_BOT_NOTICE')?>
	</div>
</div>
