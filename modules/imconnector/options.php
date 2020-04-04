<?php
if(!$USER->CanDoOperation('edit_other_settings'))
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

use Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Loader,
	\Bitrix\Main\HttpApplication,
	\Bitrix\Main\Config\Option;
use \Bitrix\ImConnector\Connector,
	\Bitrix\ImConnector\Output,
	\Bitrix\ImConnector\Library;

$module_id = 'imconnector';

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
Loc::loadMessages(__FILE__);

Loader::includeModule($module_id);

$request = HttpApplication::getInstance()->getContext()->getRequest();

$listConnector = Connector::getListConnectorReal(true, false);

//Temporary - TODO - remove with removing old instagram connector
if(!empty($listConnector[Library::ID_INSTAGRAM_CONNECTOR]))
{
	unset($listConnector[Library::ID_INSTAGRAM_CONNECTOR]);
}

/**
 * Description of options
 *
 * Stored in options:
 * 1. the server address
 * 2. a list of available connectors
 * 3. debug mode
 */
$aTabs = array(
	0 => array(
		'DIV' => 'edit1',
		'TAB' => Loc::getMessage('IMCONNECTOR_TAB_SETTINGS'),
		'OPTIONS' => array(
			array('uri_client', Loc::getMessage('IMCONNECTOR_FIELD_URI_CLIENT_TITLE'),
				'',
				array('text', 50)),
			array('debug', Loc::getMessage('IMCONNECTOR_FIELD_DEBUG_TITLE'),
				'',
				array('checkbox')),
			array('list_connector', Loc::getMessage('IMCONNECTOR_FIELD_LIST_CONNECTOR_TITLE'),
				'',
				array('multiselectbox', $listConnector)),
		)
	)
);

if(defined('BX24_HOST_NAME'))
{
	$aTabs[0]['OPTIONS'][] = array('uri_server', Loc::getMessage('IMCONNECTOR_FIELD_URI_SERVER_TITLE'),
			'',
			array('text', 50));
}
//Save

if ($request->isPost() && $request['Update'] . $request['Apply'] . $request['RestoreDefaults'] <> '' && check_bitrix_sessid())
{
	if($request["RestoreDefaults"] <> '')
	{
		Option::delete($module_id);
	}
	else
	{
		if($request['uri_client'] != Option::get(Library::MODULE_ID, "uri_client"))
			Output::saveDomainSite($request['uri_client']);

		foreach ($aTabs as $aTab)
		{
			\__AdmSettingsSaveOptions($module_id, $aTab['OPTIONS']);
		}
	}
}

$tabControl = new \CAdminTabControl('tabControl', $aTabs);

?>
<? $tabControl->Begin(); ?>
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($module_id)?>&amp;lang=<?=htmlspecialcharsbx($request['lang'])?>">

	<? foreach ($aTabs as $aTab):
			if($aTab['OPTIONS']):?>
		<? $tabControl->BeginNextTab(); ?>
		<? \__AdmSettingsDrawList($module_id, $aTab['OPTIONS']); ?>

	<?	  endif;
		endforeach;

	$tabControl->Buttons(); ?>
	<?if($request["back_url_settings"] <> ''):?>
		<input type="submit" name="Update" value="<?=Loc::getMessage("MAIN_SAVE")?>" title="<?=Loc::getMessage("MAIN_OPT_SAVE_TITLE")?>">
	<?endif?>
	<input type="submit" name="Apply" value="<?=Loc::getMessage("MAIN_OPT_APPLY")?>" title="<?=Loc::getMessage("MAIN_OPT_APPLY_TITLE")?>">
	<?if($request["back_url_settings"] <> ''):?>
		<input type="button" name="Cancel" value="<?=Loc::getMessage("MAIN_OPT_CANCEL")?>" title="<?=Loc::getMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo \htmlspecialcharsbx(\CUtil::addslashes($request["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=\htmlspecialcharsbx($request["back_url_settings"])?>">
	<?endif?>
	<input type="submit" name="RestoreDefaults" title="<?echo Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" onclick="return confirm('<?echo AddSlashes(Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo Loc::getMessage("MAIN_RESTORE_DEFAULTS")?>">
	<?=\bitrix_sessid_post();?>
</form>
<? $tabControl->End(); ?>