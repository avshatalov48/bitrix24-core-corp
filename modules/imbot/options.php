<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	Bitrix\Main\Config\Option,
	Bitrix\Main\HttpApplication,
	Bitrix\ImBot,
	Bitrix\ImBot\Bot
;

/**
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global string $mid
 */

if (!$USER->isAdmin())
{
	$APPLICATION->authForm(Loc::getMessage("ACCESS_DENIED"));
}

$module_id = 'imbot';

Loader::requireModule($module_id);

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
Loc::loadMessages(__FILE__);


$tabs = [
	[
		"DIV" => "edit1",
		"TAB" => Loc::getMessage("IMBOT_TAB_SETTINGS"),
		"ICON" => "imbot_config",
		"TITLE" => Loc::getMessage("IMBOT_TAB_TITLE_SETTINGS_2"),
	],
];

$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion() ?: '';

$bots = [
	'BOT_GIPHY' => [
		'class' => Bot\Giphy::class,
		'title' => Loc::getMessage('IMBOT_ENABLE_GIPHY_BOT') ?? Bot\Giphy::getLangMessage('IMBOT_GIPHY_BOT_NAME'),
		'active' => Bot\Giphy::isEnabled(),
	],
];
if (in_array($region, ['ru', 'by', 'kz'], true))
{
	$bots['BOT_PROPERTIES'] = [
		'class' => Bot\Properties::class,
		'title' => Loc::getMessage('IMBOT_ENABLE_PROPERTIES_BOT') ?? Bot\Properties::getLangMessage('IMBOT_PROPERTIES_BOT_NAME'),
		'active' => Bot\Properties::isEnabled(),
	];
}
elseif ($region === 'ua')
{
	$bots['BOT_PROPERTIES'] = [
		'class' => Bot\PropertiesUa::class,
		'title' => (Loc::getMessage('IMBOT_ENABLE_PROPERTIESUA_BOT')
			?? Bot\PropertiesUa::getLangMessage('IMBOT_PROPERTIESUA_BOT_NAME').' ('.Loc::getMessage('IMBOT_BOT_POSTFIX_UA').')'),
		'active' => Bot\PropertiesUa::isEnabled(),
	];
}

if (Loader::includeModule('bitrix24'))
{
	$bots['BOT_SUPPORT'] = [
		'class' => Bot\Support24::class,
		'title' => Loc::getMessage('IMBOT_ENABLE_SUPPORT_BOT'),
		'active' => Bot\Support24::isEnabled(),
		'disable' => true,
	];
	$bots['BOT_SALE_SUPPORT'] = [
		'class' => Bot\SaleSupport24::class,
		'title' => Loc::getMessage('IMBOT_ENABLE_SALE_SUPPORT_BOT'),
		'active' => Bot\SaleSupport24::isEnabled(),
		'disable' => true,
	];
}
else
{
	$bots['BOT_SUPPORT'] = [
		'class' => Bot\SupportBox::class,
		'title' => Loc::getMessage('IMBOT_ENABLE_SUPPORT_BOT'),
		'active' => (Bot\SupportBox::getBotId() > 0),
	];
}

$tabControl = new \CAdminTabControl("tabControl", $tabs);

$request = HttpApplication::getInstance()->getContext()->getRequest();

$isUpdate = $request->isPost() && !empty($request['Update']);

$publicUrl = '';

if ($isUpdate && \check_bitrix_sessid())
{
	if (!defined('BOT_CLIENT_URL'))
	{
		$publicUrl = trim($request['PUBLIC_URL'] ?? '');
		$isPublicUrlValid = false;

		if (
			!empty($publicUrl)
			&& Bot\Network::checkPublicUrl($publicUrl)
		)
		{
			Option::set($module_id, 'portal_url', $publicUrl);
			$isPublicUrlValid = true;
		}
		elseif (isset($request['PUBLIC_URL']) && $publicUrl === '')
		{
			// gonna use domain value from 'main:server_name' option
			Option::delete($module_id, ['name' => 'portal_url']);
			$isPublicUrlValid = Bot\Network::checkPublicUrl();
		}

		if (!$isPublicUrlValid)
		{
			$error = Bot\Network::getError();
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

	Option::set($module_id, "debug", isset($request['DEBUG_MODE']));
	if (isset($request['DEBUG_MODE']))
	{
		Option::set($module_id, "wait_response", isset($request['WAIT_RESPONSE']));
	}

	foreach ($bots as $botId => $bot)
	{
		/** @var Bot\ChatBot $botClass */
		$botClass = $bot['class'];
		if (isset($request[$botId]))
		{
			if (!$botClass::register())
			{
				$error = $botClass::getError();
				if (is_subclass_of($botClass, Bot\SupportBot::class))
				{
					if ($error->error)
					{
						$message = Loc::getMessage('IMBOT_SUPPORT_BOX_ACTIVATION_ERROR', ['#ERROR#' => $error->msg]);
					}
					else
					{
						$message = Loc::getMessage('IMBOT_SUPPORT_BOX_ACTIVATION_ERROR_UNKNOWN');
					}
				}
				elseif ($error->error)
				{
					$message = $error->msg;
				}
				$APPLICATION->throwException($message);

				$botClass::unRegister();
			}
		}
		elseif (!isset($bot['disable']))
		{
			if ($botClass::getBotId())
			{
				$botClass::unRegister();
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
				. '?mid='. urlencode($module_id)
				. '&mid_menu=1'
				. '&lang='. urlencode(\LANGUAGE_ID)
				. '&'. $tabControl->activeTabParam()
		);
	}
}


?>
<form method="post" action="<?= $APPLICATION->getCurPage()?>?mid=<?=htmlspecialcharsbx($module_id)?>&lang=<?= LANG?>&mid_menu=1">
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
			value="<?= htmlspecialcharsbx(Option::get($module_id, 'portal_url', $publicUrl)) ?>"
			placeholder="<?= htmlspecialcharsbx(defined('BOT_CLIENT_URL') ? \BOT_CLIENT_URL : ImBot\Http::getServerAddress()) ?>" /></td>
</tr>
<?if ((int)Option::get($module_id, "debug")):?>
<tr>
	<td valign="top"><?=Loc::getMessage("IMBOT_WAIT_RESPONSE")?>:</td>
	<td>
		<input type="checkbox" name="WAIT_RESPONSE" value="Y" <?=((int)Option::get($module_id, "wait_response")? 'checked':'')?> /><br>
		<?=Loc::getMessage("IMBOT_WAIT_RESPONSE_DESC")?>
	</td>
</tr>
<?endif;?>
<tr>
	<td><?=Loc::getMessage("IMBOT_ACCOUNT_DEBUG")?>:</td>
	<td><input type="checkbox" name="DEBUG_MODE" value="Y" <?=((int)Option::get($module_id, "debug")? 'checked':'')?> /></td>
</tr>
<tr class="heading">
	<td colspan="2"><b><?=Loc::getMessage('IMBOT_HEADER_BOTS')?></b></td>
</tr>
<? foreach ($bots as $botId => $bot): ?>
	<tr>
		<td><?= $bot['title']?>:</td>
		<td><input type="checkbox" name="<?= $botId ?>" value="Y" <?=($bot['active'] ? 'checked="checked"' : '')?> <?=(isset($bot['disable']) && $bot['disable'] ? 'disabled="disabled"' : '')?> /></td>
	</tr>
<? endforeach; ?>
<? $tabControl->buttons() ?>
<input type="submit" name="Update" value="<?= Loc::getMessage('MAIN_SAVE')?>">
<? $tabControl->end() ?>
</form>
<div class="adm-info-message-wrap">
	<div class="adm-info-message">
	<?=Loc::getMessage('IMBOT_BOT_NOTICE')?>
	</div>
</div>
