<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Call\JwtCall;
use Bitrix\Call\Signaling;
use Bitrix\Call\Library;
use Bitrix\Call\NotifyService;

/**
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global string $mid
 */

global $APPLICATION, $USER;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/im/options.php');
Loc::loadMessages(__FILE__);

$module_id = 'call';

$userRight = $APPLICATION->GetGroupRight($module_id);
$hasPermissionEdit = ($userRight >= 'W');
if (!$hasPermissionEdit)
{
	$APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

if (!Loader::includeModule($module_id))
{
	return;
}

//region Option description

$aTabs = [
	0 => [
		'DIV' => 'edit1',
		'TAB' => Loc::getMessage('CALL_TAB_SETTINGS'),
	],
];
$tabControl = new \CAdminTabControl('tabControl', $aTabs);


//endregion

//region POST Action

$request = HttpApplication::getInstance()->getContext()->getRequest();

$isUpdate = $request->isPost() && !empty($request['Update']);
$isApply = $request->isPost() && !empty($request['Apply']);
$isRestoreDefaults = $request->isPost() && !empty($request['RestoreDefaults']);

$publicUrl = '';

if (
	($isUpdate || $isApply || $isRestoreDefaults)
	&& $hasPermissionEdit
	&& \check_bitrix_sessid()
)
{
	if ($isRestoreDefaults)
	{
		Option::delete($module_id);
		
		Option::delete('call', ['name' => 'turn_server_self']);
		Option::delete('call', ['name' => 'turn_server']);
		Option::delete('call', ['name' => 'turn_server_login']);
		Option::delete('call', ['name' => 'turn_server_password']);
		Option::delete('im', ['name' => 'call_server_enabled']);
		Option::delete('call', ['name' => 'public_url']);
		Option::delete('call', ['name' => 'call_v2_enabled']);
	}
	else
	{
		$selfTurnServer = isset($request['TURN_SERVER_SELF']);
		Option::set('call', 'turn_server_self', $selfTurnServer ? 'Y' : 'N');

		if ($selfTurnServer)
		{
			Option::set('call', 'turn_server', $request['TURN_SERVER']);
			Option::set('call', 'turn_server_login', $request['TURN_SERVER_LOGIN']);
			Option::set('call', 'turn_server_password', $request['TURN_SERVER_PASSWORD']);
		}
		else
		{
			Option::delete('call', ['name' => 'turn_server']);
			Option::delete('call', ['name' => 'turn_server_login']);
			Option::delete('call', ['name' => 'turn_server_password']);
		}

		$enableCallServer = isset($request['CALL_SERVER_ENABLED']);
		Option::set('im', 'call_server_enabled', $enableCallServer);

		$prevPublicUrl = Option::get('call', 'public_url', '');
		$publicUrl = trim($request['PUBLIC_URL'] ?? '');
		$isPublicUrlValid = true;

		$prevFlagJwtScheme = (bool)Option::get('call', 'call_v2_enabled');
		$enableJwtScheme = isset($request['JWT_SCHEME_ENABLE']);

		if (
			($prevPublicUrl !== $publicUrl)
			|| (($prevFlagJwtScheme != $enableJwtScheme) && $enableJwtScheme)
		)
		{
			if (!empty($publicUrl))
			{
				$checkPublicUrlResult = JwtCall::checkPublicUrl($publicUrl);
				if (!$checkPublicUrlResult->isSuccess())
				{
					$isPublicUrlValid = false;
					$APPLICATION->ThrowException(
						Loc::getMessage('CALL_OPTIONS_ERROR_PUBLIC_CHECK',
							['#ERROR#' => implode('<br>- ', $checkPublicUrlResult->getErrorMessages())]
						)
					);
				}
				else
				{
					Option::set('call', 'public_url', $publicUrl);
					NotifyService::getInstance()->clearAdminNotify();
				}
			}
			elseif (isset($request['PUBLIC_URL']) && $publicUrl === '')
			{
				// use domain value from 'main:server_name' option
				Option::delete('call', ['name' => 'public_url']);

				$checkPublicUrlResult = JwtCall::checkPublicUrl(Library::getPortalPublicUrl());
				if (!$checkPublicUrlResult->isSuccess())
				{
					$isPublicUrlValid = false;
					$APPLICATION->ThrowException(
						Loc::getMessage('CALL_OPTIONS_ERROR_PUBLIC_CHECK',
							['#ERROR#' => implode('<br>- ', $checkPublicUrlResult->getErrorMessages())]
						)
					);
				}
				else
				{
					NotifyService::getInstance()->clearAdminNotify();
				}
			}
		}

		if (
			$prevFlagJwtScheme != $enableJwtScheme
			&& $isPublicUrlValid === true
		)
		{
			if ($enableJwtScheme)
			{
				$result = JwtCall::registerPortal();
				if ($result->isSuccess())
				{
					Option::set('call', 'call_v2_enabled', true);
					Signaling::sendClearCallTokens();
					NotifyService::getInstance()->clearAdminNotify();
				}
				else
				{
					$APPLICATION->ThrowException(
						Loc::getMessage('CALL_OPTIONS_JWT_SCHEME_ERROR',
							['#ERROR#' => implode('<br>- ', $result->getErrorMessages())]
						)
					);
				}
			}
			else
			{
				Option::set('call', 'call_v2_enabled', false);
				JwtCall::unregisterPortal();
				Signaling::sendChangedCallV2Enable(false);
				NotifyService::getInstance()->clearAdminNotify();
			}
		}
	}

	// errors
	if ($exception = $APPLICATION->getException())
	{
		\CAdminMessage::showMessage([
			'DETAILS' => $exception->getString(),
			'TYPE' => 'ERROR',
			'HTML' => true
		]);
	}
	elseif (!empty($request['back_url_settings']))
	{
		\LocalRedirect($request['back_url_settings']);
	}
	else
	{
		\LocalRedirect(
			$APPLICATION->getCurPage()
			. '?mid='. urlencode($mid)
			. '&mid_menu=1'
			. '&lang='. \LANGUAGE_ID
			. '&'. $tabControl->activeTabParam()
		);
	}
}

//endregion

\Bitrix\Main\UI\Extension::load([
	'main.core',
	'ui.alerts',
	'ui.notification',
]);


?>
<form method="post" action="<?= $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?= LANG?>">
<?= bitrix_sessid_post()?>
<?php
$tabControl->Begin();
$tabControl->BeginNextTab();
$selfTurnServer = (Option::get('call', 'turn_server_self') == 'Y');
$isJwtSchemeEnabled = (bool)Option::get('call', 'call_v2_enabled');
$portalHasRegistered  = ((int)Option::get('call', 'call_portal_id', 0) > 0);

?>
	<tr>
		<td class="adm-detail-content-cell-l" width="40%"><?=Loc::getMessage("CALL_OPTIONS_CALL_SERVER_ENABLED_MSGVER_1")?>:</td>
		<td class="adm-detail-content-cell-r" width="60%"><input type="checkbox" name="CALL_SERVER_ENABLED" <?=( (bool)Option::get('im', 'call_server_enabled') ? 'checked="checked"' : '')?>></td>
	</tr>
	<tr>
		<td class="adm-detail-content-cell-l"><?=Loc::getMessage("CALL_OPTIONS_TURN_SERVER_SELF_2")?>:</td>
		<td class="adm-detail-content-cell-r"><input type="checkbox" onclick="toogleVideoOptions(this)" name="TURN_SERVER_SELF" <?= ($selfTurnServer ? 'checked="checked"' : '') ?>></td>
	</tr>
	<tr id="video_group_2" <?php if (!$selfTurnServer):?>style="display: none"<?endif;?>>
		<td class="adm-detail-content-cell-l"><?=Loc::getMessage("CALL_OPTIONS_TURN_SERVER")?>:</td>
		<td class="adm-detail-content-cell-r"><input type="input" size="40" value="<?=htmlspecialcharsbx(COption::GetOptionString('call', 'turn_server'))?>" name="TURN_SERVER"></td>
	</tr>
	<tr id="video_group_4" <?php if (!$selfTurnServer):?>style="display: none"<?endif;?>>
		<td class="adm-detail-content-cell-l"><?=Loc::getMessage("CALL_OPTIONS_TURN_SERVER_LOGIN")?>:</td>
		<td class="adm-detail-content-cell-r"><input type="input" size="20" value="<?=htmlspecialcharsbx(COption::GetOptionString('call', 'turn_server_login'))?>" name="TURN_SERVER_LOGIN"></td>
	</tr>
	<tr id="video_group_5" <?php if (!$selfTurnServer):?>style="display: none"<?endif;?>>
		<td class="adm-detail-content-cell-l"><?=Loc::getMessage("CALL_OPTIONS_TURN_SERVER_PASSWORD")?>:<br><small>(<?=Loc::getMessage("CALL_OPTIONS_TURN_SERVER_PASSWORD_HINT")?>)</small></td>
		<td class="adm-detail-content-cell-r"><input type="input" size="20" value="<?=htmlspecialcharsbx(COption::GetOptionString('call', 'turn_server_password'))?>" name="TURN_SERVER_PASSWORD"></td>
	</tr>

	<tr class="heading">
		<td colspan="2"><?=Loc::getMessage("CALL_OPTIONS_HEADER_REGISTRATION")?></td>
	</tr>
	<tr>
		<td class="adm-detail-content-cell-l"><?=Loc::getMessage("CALL_OPTIONS_PUBLIC_URL")?>:</td>
		<td class="adm-detail-content-cell-r">
			<input type="text"
				name="PUBLIC_URL"
				value="<?= htmlspecialcharsbx(Option::get('call', 'public_url', $publicUrl)) ?>"
				placeholder="<?= htmlspecialcharsbx(Library::getPortalPublicUrl()) ?>" /></td>
	</tr>
	<tr>
		<td class="adm-detail-content-cell-l"><?= Loc::getMessage("CALL_OPTIONS_JWT_SCHEME_ENABLE") ?>:</td>
		<td class="adm-detail-content-cell-r">
			<input type="checkbox"
				name="JWT_SCHEME_ENABLE"
				<?= ($isJwtSchemeEnabled ? 'checked="checked"' : '') ?>></td>
	</tr>
	<tr>
		<td class="adm-detail-content-cell-l"><?=Loc::getMessage('CALL_OPTIONS_STATUS')?></td>
		<td class="adm-detail-content-cell-r">
			<? if ($portalHasRegistered): ?>
				<span style="color:green; font-weight: bold"><?= Loc::getMessage('CALL_OPTIONS_STATUS_REGISTERED') ?></span>
			<? else: ?>
				<span style="color:gray; font-weight: bold"><?= Loc::getMessage('CALL_OPTIONS_STATUS_IS_NOT_REGISTERED') ?></span>
			<? endif; ?>
		</td>
	</tr>
	<tr>
		<td class="adm-detail-content-cell-l"><?=Loc::getMessage("CALL_OPTIONS_REGISTER_SECRET_KEY")?></td>
		<td class="adm-detail-content-cell-r">
			<input type="button"
				name="REGISTER_SECRET_KEY"
				id="video_group_6"
				value="<?= $portalHasRegistered
					? Loc::getMessage("CALL_OPTIONS_REGISTER_SECRET_KEY_ACTION")
					: Loc::getMessage("CALL_OPTIONS_REGISTER_SECRET_KEY_NEW") ?>"
				class="adm-btn-save"
				<?php if (!$isJwtSchemeEnabled):?>disabled="disabled"<?endif;?>
				onclick="RegisterSecretKey();" /></td>
	</tr>
	<tr>
		<td colspan="2">
			<div class="adm-info-message-wrap" style="text-align:center">
				<div class="adm-info-message">
					<?= $isJwtSchemeEnabled
						? Loc::getMessage("CALL_OPTIONS_JWT_SCHEME_ENABLE_NOTE")
						: Loc::getMessage("CALL_OPTIONS_JWT_SCHEME_DISABLE_NOTE")
					?>
				</div>
			</div>
		</td>
	</tr>
<?php $tabControl->Buttons();?>
<script>
function toogleVideoOptions(el)
{
	BX.style(BX('video_group_2'), 'display', el.checked? 'table-row': 'none');
	BX.style(BX('video_group_3'), 'display', el.checked? 'table-row': 'none');
	BX.style(BX('video_group_4'), 'display', el.checked? 'table-row': 'none');
	BX.style(BX('video_group_5'), 'display', el.checked? 'table-row': 'none');
}
function RestoreDefaults()
{
	if (confirm('<?= \CUtil::JSEscape(Loc::getMessage('MAIN_HINT_RESTORE_DEFAULTS_WARNING'))?>'))
	{
		window.location = "<?= $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?= LANG?>&mid=<?= urlencode($mid)?>";
	}
}
function ReloadPage()
{
	window.location = "<?= $APPLICATION->GetCurPage()?>?lang=<?= LANG?>&mid=<?= urlencode($mid)?>";
}
function RegisterSecretKey()
{
	let registerKeyDialog = new BX.CDialog({
		title: '<?= \CUtil::JSEscape(Loc::getMessage('CALL_OPTIONS_POPUP_REGISTER_SECRET_TITLE'))?>',
		content: '<?= \CUtil::JSEscape(Loc::getMessage('CALL_OPTIONS_POPUP_REGISTER_SECRET_MESSAGE'))?>',
		height: 100,
		width: 420,
		resizable: false,
		buttons: [ {
			title: '<?= \CUtil::JSEscape(Loc::getMessage('CALL_OPTIONS_POPUP_REGISTER_SECRET_OK_BTN'))?>',
			id: 'my_save',
			className: 'adm-btn-save',
			action: () => {
				var dialog = BX.WindowManager.Get();
				const button = dialog.Get().querySelector("input[type='button']");
				dialog.showWait(button);

				BX.ajax.runAction("call.Settings.registerKey", {})
					.then(function(response) {

						const alertMess = new BX.UI.Alert({
							text: '<?= \CUtil::JSEscape(Loc::getMessage('CALL_OPTIONS_REGISTER_SECRET_KEY_SUCCESS'))?>',
							inline: true,
							size: BX.UI.Alert.Size.SMALL,
							color: BX.UI.Alert.Color.SUCCESS,
							icon: BX.UI.Alert.Icon.INFO,
							closeBtn: false,
							animated: false
						});

						dialog = BX.WindowManager.Get();
						dialog.SetContent(alertMess.getContainer());
						dialog.ClearButtons();
						dialog.SetButtons([{
							title: BX.message('JS_CORE_WINDOW_CLOSE'),
							id: 'close',
							name: 'close',
							action: function () {
								this.parentWindow.Close();
								window.ReloadPage();
							}
						}]);

					}, function(response) {
						if (response.status == 'error' && response.errors.length > 0)
						{
							var errorContent = response.errors.map(function(element) {
								return element.message;
							}).join('. ');

							const alertMess = new BX.UI.Alert({
								text: '<?= \CUtil::JSEscape(Loc::getMessage('CALL_OPTIONS_REGISTER_SECRET_KEY_ERROR'))?>',
								inline: true,
								size: BX.UI.Alert.Size.SMALL,
								color: BX.UI.Alert.Color.DANGER,
								icon: BX.UI.Alert.Icon.DANGER,
								closeBtn: false,
								animated: false
							});

							dialog = BX.WindowManager.Get();
							dialog.SetContent(alertMess.getContainer());
							dialog.ClearButtons();
							dialog.SetButtons([BX.CDialog.btnClose]);
						}
					});

			}
		}, BX.CAdminDialog.btnCancel ]
	});

	registerKeyDialog.Show();
}
</script>
<input type="submit" name="Update" <?if (!$hasPermissionEdit) echo "disabled" ?> value="<?= Loc::getMessage('MAIN_SAVE')?>" title="<?=Loc::getMessage("MAIN_OPT_SAVE_TITLE")?>">
<input type="submit" name="Apply" <?if (!$hasPermissionEdit) echo "disabled" ?> value="<?=Loc::getMessage("MAIN_OPT_APPLY")?>" title="<?=Loc::getMessage("MAIN_OPT_APPLY_TITLE")?>">
<? if ($request["back_url_settings"] <> ''):?>
	<input <?if ($userRight < 'W') echo "disabled" ?> type="button" name="Cancel" value="<?=Loc::getMessage("MAIN_OPT_CANCEL")?>" title="<?=Loc::getMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?= htmlspecialcharsbx(CUtil::addslashes($request["back_url_settings"]))?>'">
	<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($request["back_url_settings"])?>">
<? endif; ?>
<input type="button"
	<?if (!$hasPermissionEdit) echo "disabled" ?>
	title="<?= Loc::getMessage('MAIN_HINT_RESTORE_DEFAULTS')?>"
	OnClick="RestoreDefaults();"
	value="<?= Loc::getMessage('MAIN_RESTORE_DEFAULTS')?>">
<?php $tabControl->End();?>
</form>