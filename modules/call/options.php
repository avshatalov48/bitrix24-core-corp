<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use \Bitrix\Main\HttpApplication;

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

if (
	($isUpdate || $isApply || $isRestoreDefaults)
	&& $hasPermissionEdit
	&& \check_bitrix_sessid()
)
{
	if ($isRestoreDefaults)
	{
		Option::delete($module_id);
		
		Option::delete('im', ['name' => 'turn_server_self']);
		Option::delete('im', ['name' => 'turn_server']);
		Option::delete('im', ['name' => 'turn_server_firefox']);
		Option::delete('im', ['name' => 'turn_server_login']);
		Option::delete('im', ['name' => 'turn_server_password']);
		Option::delete('im', ['name' => 'call_server_enabled']);

	}
	else
	{
		$selfTurnServer = isset($request['TURN_SERVER_SELF']);
		Option::set('im', 'turn_server_self', $selfTurnServer ? 'Y' : 'N');

		if ($selfTurnServer)
		{
			Option::set('im', 'turn_server', $request['TURN_SERVER']);
			Option::set('im', 'turn_server_firefox', $request['TURN_SERVER_FIREFOX']);
			Option::set('im', 'turn_server_login', $request['TURN_SERVER_LOGIN']);
			Option::set('im', 'turn_server_password', $request['TURN_SERVER_PASSWORD']);
		}
		else
		{
			Option::delete('im', ['name' => 'turn_server']);
			Option::delete('im', ['name' => 'turn_server_firefox']);
			Option::delete('im', ['name' => 'turn_server_login']);
			Option::delete('im', ['name' => 'turn_server_password']);
		}

		$enableCallServer = isset($request['CALL_SERVER_ENABLED']);
		Option::set('im', 'call_server_enabled', $enableCallServer);

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


?>
<form method="post" action="<?= $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?= LANG?>">
<?= bitrix_sessid_post()?>
<?php
$tabControl->Begin();
$tabControl->BeginNextTab();
$selfTurnServer = (Option::get('im', 'turn_server_self') == 'Y');

?>
	<tr>
		<td class="adm-detail-content-cell-l" width="40%"><?=Loc::getMessage("CALL_OPTIONS_CALL_SERVER_ENABLED_MSGVER_1")?>:</td>
		<td class="adm-detail-content-cell-r" width="60%"><input type="checkbox" name="CALL_SERVER_ENABLED" <?=(COption::GetOptionString('im', 'call_server_enabled')?'checked="checked"' :'')?>></td>
	</tr>
	<tr>
		<td class="adm-detail-content-cell-l"><?=Loc::getMessage("CALL_OPTIONS_TURN_SERVER_SELF_2")?>:</td>
		<td class="adm-detail-content-cell-r"><input type="checkbox" onclick="toogleVideoOptions(this)" name="TURN_SERVER_SELF" <?=($selfTurnServer?'checked="checked"' :'')?>></td>
	</tr>
	<tr id="video_group_2" <?php if (!$selfTurnServer):?>style="display: none"<?endif;?>>
		<td class="adm-detail-content-cell-l"><?=Loc::getMessage("CALL_OPTIONS_TURN_SERVER")?>:</td>
		<td class="adm-detail-content-cell-r"><input type="input" size="40" value="<?=htmlspecialcharsbx(COption::GetOptionString('im', 'turn_server'))?>" name="TURN_SERVER"></td>
	</tr>
	<tr id="video_group_3" <?php if (!$selfTurnServer):?>style="display: none"<?endif;?>>
		<td class="adm-detail-content-cell-l"><?=Loc::getMessage("CALL_OPTIONS_TURN_SERVER_FIREFOX")?>:</td>
		<td class="adm-detail-content-cell-r"><input type="input" size="40" value="<?=htmlspecialcharsbx(COption::GetOptionString('im', 'turn_server_firefox'))?>" name="TURN_SERVER_FIREFOX"></td>
	</tr>
	<tr id="video_group_4" <?php if (!$selfTurnServer):?>style="display: none"<?endif;?>>
		<td class="adm-detail-content-cell-l"><?=Loc::getMessage("CALL_OPTIONS_TURN_SERVER_LOGIN")?>:</td>
		<td class="adm-detail-content-cell-r"><input type="input" size="20" value="<?=htmlspecialcharsbx(COption::GetOptionString('im', 'turn_server_login'))?>" name="TURN_SERVER_LOGIN"></td>
	</tr>
	<tr id="video_group_5" <?php if (!$selfTurnServer):?>style="display: none"<?endif;?>>
		<td class="adm-detail-content-cell-l"><?=Loc::getMessage("CALL_OPTIONS_TURN_SERVER_PASSWORD")?>:<br><small>(<?=Loc::getMessage("CALL_OPTIONS_TURN_SERVER_PASSWORD_HINT")?>)</small></td>
		<td class="adm-detail-content-cell-r"><input type="input" size="20" value="<?=htmlspecialcharsbx(COption::GetOptionString('im', 'turn_server_password'))?>" name="TURN_SERVER_PASSWORD"></td>
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
	if (confirm('<?= AddSlashes(Loc::getMessage('MAIN_HINT_RESTORE_DEFAULTS_WARNING'))?>'))
	{
		window.location = "<?= $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?= LANG?>&mid=<?= urlencode($mid)."&".bitrix_sessid_get();?>";
	}
}
</script>
<input type="submit" name="Update" <?if (!$hasPermissionEdit) echo "disabled" ?> value="<?= Loc::getMessage('MAIN_SAVE')?>">
<input type="reset" name="reset" value="<?= Loc::getMessage('MAIN_RESET')?>">
<input type="button" <?if (!$hasPermissionEdit) echo "disabled" ?> title="<?= Loc::getMessage('MAIN_HINT_RESTORE_DEFAULTS')?>" OnClick="RestoreDefaults();" value="<?= Loc::getMessage('MAIN_RESTORE_DEFAULTS')?>">
<?php $tabControl->End();?>
</form>