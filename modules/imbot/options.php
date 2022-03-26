<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	Bitrix\Main\Config\Option,
	Bitrix\ImBot;

/**
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global string $mid
 */

if (!$USER->isAdmin())
{
	return;
}
if (!Loader::includeModule('imbot'))
{
	return;
}

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
Loc::loadMessages(__FILE__);


$tabs = array(
	array(
		"DIV" => "edit1",
		"TAB" => Loc::getMessage("IMBOT_TAB_SETTINGS"),
		"ICON" => "imbot_config",
		"TITLE" => Loc::getMessage("IMBOT_TAB_TITLE_SETTINGS_2"),
	),
);
$tabControl = new \CAdminTabControl("tabControl", $tabs);

$publicUrl = '';

if ($_POST['Update'] <> '' && \check_bitrix_sessid())
{
	if (!defined('BOT_CLIENT_URL'))
	{
		$publicUrl = trim($_POST['PUBLIC_URL'] ?? '');
		$isPublicUrlValid = false;

		if (
			!empty($publicUrl)
			&& ImBot\Bot\Network::checkPublicUrl($publicUrl)
		)
		{
			Option::set('imbot', 'portal_url', $publicUrl);
			$isPublicUrlValid = true;
		}
		elseif (isset($_POST['PUBLIC_URL']) && $publicUrl === '')
		{
			// gonna use domain value from 'main:server_name' option
			Option::delete('imbot', ['name' => 'portal_url']);
			$isPublicUrlValid = ImBot\Bot\Network::checkPublicUrl();
		}

		if (!$isPublicUrlValid)
		{
			$error = ImBot\Bot\Network::getError();
			if ($error->error)
			{
				$message = Loc::getMessage('IMBOT_ACCOUNT_ERROR_PUBLIC_CHECK', ['#ERROR#' => $error->msg]);
			}
			else
			{
				$message = Loc::getMessage('IMBOT_ACCOUNT_ERROR_PUBLIC');
			}
			$APPLICATION->throwException($message);
		}
	}

	Option::set("imbot", "debug", isset($_POST['DEBUG_MODE']));
	if (isset($_POST['DEBUG_MODE']))
	{
		Option::set("imbot", "wait_response", isset($_POST['WAIT_RESPONSE']));
	}
	if (isset($_POST['BOT_GIPHY']))
	{
		if (!ImBot\Bot\Giphy::getBotId())
		{
			ImBot\Bot\Giphy::register();
		}
	}
	else
	{
		if (ImBot\Bot\Giphy::getBotId())
		{
			ImBot\Bot\Giphy::unRegister();
		}
	}
	if (isset($_POST['BOT_PROPERTIES']))
	{
		if (!ImBot\Bot\Properties::getBotId())
		{
			ImBot\Bot\Properties::register();
		}
	}
	else
	{
		if (ImBot\Bot\Properties::getBotId())
		{
			ImBot\Bot\Properties::unRegister();
		}
	}
	if (isset($_POST['BOT_PROPERTIESUA']))
	{
		if (!ImBot\Bot\PropertiesUa::getBotId())
		{
			ImBot\Bot\PropertiesUa::register();
		}
	}
	else
	{
		if (ImBot\Bot\PropertiesUa::getBotId())
		{
			ImBot\Bot\PropertiesUa::unRegister();
		}
	}

	if (!Loader::includeModule('bitrix24'))
	{
		if (isset($_POST['BOT_SUPPORT']))
		{
			if (!ImBot\Bot\SupportBox::register())
			{
				$error = ImBot\Bot\SupportBox::getError();
				if ($error->error)
				{
					$message = Loc::getMessage('IMBOT_SUPPORT_BOX_ACTIVATION_ERROR', ['#ERROR#' => $error->msg]);
				}
				else
				{
					$message = Loc::getMessage('IMBOT_SUPPORT_BOX_ACTIVATION_ERROR_UNKNOWN');
				}
				$APPLICATION->throwException($message);

				ImBot\Bot\SupportBox::unRegister();
			}
		}
		else
		{
			if (ImBot\Bot\SupportBox::getBotId())
			{
				ImBot\Bot\SupportBox::unRegister();
			}
		}
	}

	if ($exception = $APPLICATION->getException())
	{
		\CAdminMessage::showMessage([
			'DETAILS' => $exception->getString(),
			'TYPE' => 'ERROR',
			'HTML' => true
		]);
	}
	elseif($_REQUEST['back_url_settings'] <> '')
	{
		\LocalRedirect($_REQUEST['back_url_settings']);
	}
	else
	{
		\LocalRedirect(
			$APPLICATION->getCurPage()
				. '?mid='. urlencode($mid)
				. '&mid_menu=1'
				. '&lang='. urlencode(\LANGUAGE_ID)
				. '&'. $tabControl->activeTabParam()
		);
	}
}

$portalPrefix = (static function () {
	$portalZone = '';

	if (Loader::includeModule('bitrix24'))
	{
		$licensePrefix = \CBitrix24::getLicensePrefix();
		if ($licensePrefix !== false)
		{
			$portalZone = (string)$licensePrefix;
		}
	}
	elseif (Loader::includeModule('intranet'))
	{
		$portalZone = \CIntranetUtils::getPortalZone();
	}
	return $portalZone;
})();

?>
<form method="post" action="<?= $APPLICATION->getCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?= LANG?>">
<?= \bitrix_sessid_post() ?>
<?php
$tabControl->begin();
$tabControl->beginNextTab();
?>
<tr>
	<td width="40%"><?=Loc::getMessage("IMBOT_ACCOUNT_URL")?>:</td>
	<td width="60%">
		<input type="text"
			name="PUBLIC_URL"
			value="<?= htmlspecialcharsbx(Option::get('imbot', 'portal_url', $publicUrl)) ?>"
			placeholder="<?= htmlspecialcharsbx(defined('BOT_CLIENT_URL') ? \BOT_CLIENT_URL : ImBot\Http::getServerAddress()) ?>" /></td>
</tr>
<?if ((int)Option::get("imbot", "debug")):?>
<tr>
	<td valign="top"><?=Loc::getMessage("IMBOT_WAIT_RESPONSE")?>:</td>
	<td>
		<input type="checkbox" name="WAIT_RESPONSE" value="Y" <?=((int)Option::get("imbot", "wait_response")? 'checked':'')?> /><br>
		<?=Loc::getMessage("IMBOT_WAIT_RESPONSE_DESC")?>
	</td>
</tr>
<?endif;?>
<tr>
	<td><?=Loc::getMessage("IMBOT_ACCOUNT_DEBUG")?>:</td>
	<td><input type="checkbox" name="DEBUG_MODE" value="Y" <?=((int)Option::get("imbot", "debug")? 'checked':'')?> /></td>
</tr>
<tr class="heading">
	<td colspan="2"><b><?=Loc::getMessage('IMBOT_HEADER_BOTS')?></b></td>
</tr>
<tr>
	<td><?= (Loc::getMessage('IMBOT_ENABLE_GIPHY_BOT') ?? ImBot\Bot\Giphy::getLangMessage('IMBOT_GIPHY_BOT_NAME'))?>:</td>
	<td><input type="checkbox" name="BOT_GIPHY" value="Y" <?=(ImBot\Bot\Giphy::getBotId()? 'checked':'')?> /></td>
</tr>
<? if ($portalPrefix === 'ru' || $portalPrefix === ''): ?>
<tr>
	<td><?= (Loc::getMessage('IMBOT_ENABLE_PROPERTIES_BOT') ?? ImBot\Bot\Properties::getLangMessage('IMBOT_PROPERTIES_BOT_NAME'))?>:</td>
	<td><input type="checkbox" name="BOT_PROPERTIES" value="Y" <?=(ImBot\Bot\Properties::getBotId()? 'checked':'')?> /></td>
</tr>
<?endif;?>
<? if ($portalPrefix === 'ua' || $portalPrefix === ''): ?>
<tr>
	<td><?= (Loc::getMessage('IMBOT_ENABLE_PROPERTIESUA_BOT')
			?? ImBot\Bot\PropertiesUa::getLangMessage('IMBOT_PROPERTIESUA_BOT_NAME').' ('.Loc::getMessage('IMBOT_BOT_POSTFIX_UA').')') ?>:</td>
	<td><input type="checkbox" name="BOT_PROPERTIESUA" value="Y" <?=(ImBot\Bot\PropertiesUa::getBotId()? 'checked':'')?> /></td>
</tr>
<?endif;?>
<? if (Loader::includeModule('bitrix24')): ?>
<tr>
	<td><?= Loc::getMessage('IMBOT_ENABLE_SUPPORT_BOT') ?>:</td>
	<td><input type="checkbox" disabled="disabled" <?=(ImBot\Bot\Support24::isEnabled() ? 'checked':'')?> /></td>
</tr>
<?else:?>
<tr>
	<td><?= Loc::getMessage('IMBOT_ENABLE_SUPPORT_BOT') ?>:</td>
	<td><input type="checkbox" name="BOT_SUPPORT" value="Y" <?=(ImBot\Bot\SupportBox::getBotId() ? 'checked':'')?> /></td>
</tr>
<?endif;?>
<? $tabControl->buttons() ?>
<input type="submit" name="Update" value="<?= Loc::getMessage('MAIN_SAVE')?>">
<input type="reset" name="reset" value="<?= Loc::getMessage('MAIN_RESET')?>">
<? $tabControl->end() ?>
</form>
<div class="adm-info-message-wrap">
	<div class="adm-info-message">
	<?=Loc::getMessage('IMBOT_BOT_NOTICE')?>
	</div>
</div>
