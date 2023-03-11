<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader,
	Bitrix\Main\Config\Option,
	Bitrix\Main\HttpApplication,
	Bitrix\Main\Localization\Loc,
	Bitrix\ImOpenLines,
	Bitrix\ImOpenLines\Common;

/**
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global string $mid
 */

if (!$USER->isAdmin())
{
	return;
}

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
Loc::loadMessages(__FILE__);

$module_id = 'imopenlines';

if (!Loader::includeModule($module_id))
{
	return;
}


$aTabs = array(
	array(
		'DIV' => 'edit1',
		'TAB' => Loc::getMessage('IMOPENLINES_TAB_SETTINGS'),
		'ICON' => 'imopenlines_config',
		'TITLE' => Loc::getMessage('IMOPENLINES_TAB_TITLE_SETTINGS_2'),
	),
);
$tabControl = new \CAdminTabControl('tabControl', $aTabs);

$defaults = Option::getDefaults($module_id);

//region POST Action

$request = HttpApplication::getInstance()->getContext()->getRequest();

$isUpdate = $request->isPost() && !empty($request['Update']);
$isApply = $request->isPost() && !empty($request['Apply']);
$isRestoreDefaults = $request->isPost() && !empty($request['RestoreDefaults']);

$publicUrl = '';

if (
	($isUpdate || $isApply || $isRestoreDefaults)
	&& \check_bitrix_sessid()
)
{
	if ($isRestoreDefaults)
	{
		Option::delete($module_id);
	}
	else
	{
		$APPLICATION->resetException();

		$publicUrl = trim($request['PUBLIC_URL'] ?? '');

		if (defined('BOT_CLIENT_URL'))
		{
			if ($publicUrl != '' && mb_strlen($publicUrl) < 12)
			{
				$APPLICATION->throwException(Loc::getMessage('IMOPENLINES_ACCOUNT_ERROR_PUBLIC'));
			}
			elseif (isset($request['PUBLIC_URL']) && $publicUrl === '')
			{
				// gonna use domain value from 'main:server_name' option
				Option::delete('imopenlines', ['name' => 'portal_url']);
			}
			elseif ($publicUrl != Option::get('imopenlines', 'portal_url'))
			{
				Option::set('imopenlines', 'portal_url', $publicUrl);

				if (Loader::includeModule('crm'))
				{
					\Bitrix\Crm\SiteButton\Manager::updateScriptCacheAgent();
				}
			}
		}
		else
		{
			$checkUrlResult = false;
			if (!empty($publicUrl))
			{
				$checkUrlResult = Common::checkPublicUrl($publicUrl);
				if ($checkUrlResult->isSuccess())
				{
					Option::set('imopenlines', 'portal_url', $publicUrl);
				}
			}
			elseif (isset($request['PUBLIC_URL']) && $publicUrl === '')
			{
				// gonna use domain value from 'main:server_name' option
				Option::delete('imopenlines', ['name' => 'portal_url']);
				$checkUrlResult = Common::checkPublicUrl(Common::getServerAddress());
			}

			if ($checkUrlResult instanceof ImOpenLines\Result)
			{
				if ($checkUrlResult->isSuccess())
				{
					if (Loader::includeModule('crm'))
					{
						\Bitrix\Crm\SiteButton\Manager::updateScriptCacheAgent();
					}
				}
				else
				{
					$error = $checkUrlResult->getErrors()[0];
					if ($error->getMessage())
					{
						$message = Loc::getMessage('IMOPENLINES_ACCOUNT_ERROR_PUBLIC_CHECK',
							['#ERROR#' => $error->getMessage()]);
					}
					else
					{
						$message = Loc::getMessage('IMOPENLINES_ACCOUNT_ERROR_PUBLIC');
					}
					$APPLICATION->throwException($message);
				}
			}
		}

		Option::set('imopenlines', 'debug', isset($request['DEBUG_MODE']));

		$execMode = Option::get('imopenlines', 'exec_mode');
		if ($request['EXEC_MODE'] != $execMode && in_array($request['EXEC_MODE'], [Common::MODE_AGENT, Common::MODE_CRON]))
		{
			Option::set('imopenlines', 'exec_mode', $request['EXEC_MODE']);
		}

		if (!empty($request['queue_interact_count']) && (int)$request['queue_interact_count'] > 0)
		{
			Option::set('imopenlines', 'queue_interact_count', (int)$request['queue_interact_count']);
		}
		else
		{
			Option::delete('imopenlines', ['name' => 'queue_interact_count']);
		}

		if ($exception = $APPLICATION->getException())
		{
			\CAdminMessage::showMessage([
				'DETAILS' => $exception->getString(),
				'TYPE' => 'ERROR',
				'HTML' => true
			]);
		}
		elseif ($_REQUEST['back_url_settings'] <> '')
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
}
?>
<form method="post" action="<?= $APPLICATION->getCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?= LANG?>">
	<?= \bitrix_sessid_post() ?>
	<?php
	$tabControl->begin();
	$tabControl->beginNextTab();
	?>
	<tr>
		<td width="40%"><?=Loc::getMessage("IMOPENLINES_ACCOUNT_URL")?>:</td>
		<td width="60%">
			<input type="text"
					name="PUBLIC_URL"
					value="<?= htmlspecialcharsbx(Option::get('imopenlines', 'portal_url', $publicUrl)) ?>"
					placeholder="<?= htmlspecialcharsbx(defined('BOT_CLIENT_URL') ? \BOT_CLIENT_URL : Common::getServerAddress()) ?>" /></td>
	</tr>
	<tr>
		<td><?=Loc::getMessage("IMOPENLINES_ACCOUNT_DEBUG")?>:</td>
		<td><input type="checkbox" name="DEBUG_MODE" value="Y" <?=( (int)Option::get('imopenlines', 'debug') ? 'checked' : '')?> /></td>
	</tr>
	<tr>
		<td><?=Loc::getMessage('IMOPENLINES_QUEUE_INTERACT_COUNT')?>:</td>
		<td>
			<input type="text"
					name="queue_interact_count"
					value="<?= Option::getRealValue('imopenlines', 'queue_interact_count') ?>"
					placeholder="<?= $defaults['queue_interact_count'] ?>" /></td>
	</tr>
	<tr>
		<td><?=Loc::getMessage("IMOPENLINES_ACCOUNT_EXEC_MODE")?>:</td>
		<td>
			<select name="EXEC_MODE">
				<option value="<?= Common::MODE_AGENT ?>"
					<? if (Common::getExecMode() == Common::MODE_AGENT) {?>selected="selected"<?}?>>
					<?=Loc::getMessage("IMOPENLINES_ACCOUNT_EXEC_MODE_AGENT")?>
				</option>
				<option value="<?= Common::MODE_CRON ?>"
					<? if (Common::getExecMode() == Common::MODE_CRON) {?>selected="selected"<?}?>>
					<?=Loc::getMessage("IMOPENLINES_ACCOUNT_EXEC_MODE_CRON")?>
				</option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<div class="adm-info-message-wrap">
				<div class="adm-info-message">
					<?=Loc::getMessage("IMOPENLINES_ACCOUNT_EXEC_DESCRIPTION")?>
				</div>
			</div>
		</td>
	</tr>
	<?
	$tabControl->buttons();

	?>
	<input type="submit" name="Update" value="<?=Loc::getMessage("MAIN_SAVE")?>" title="<?=Loc::getMessage("MAIN_OPT_SAVE_TITLE")?>">
	<input type="submit" name="Apply" value="<?=Loc::getMessage("MAIN_OPT_APPLY")?>" title="<?=Loc::getMessage("MAIN_OPT_APPLY_TITLE")?>">
	<?
	if ($request["back_url_settings"] <> ''):
		?>
		<input type="button" name="Cancel" value="<?=Loc::getMessage("MAIN_OPT_CANCEL")?>" title="<?=Loc::getMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?= htmlspecialcharsbx(\CUtil::addslashes($request["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($request["back_url_settings"])?>">
		<?
	endif;
	?>
	<input type="submit" name="RestoreDefaults" title="<?= Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" onclick="return confirm('<?= \AddSlashes(Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?= Loc::getMessage("MAIN_RESTORE_DEFAULTS")?>">
	<?

	$tabControl->end();
	?>
</form>
<?
//endregion