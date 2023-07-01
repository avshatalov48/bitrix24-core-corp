<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\UI\Extension;

/**
 * @var $arResult[]
 */

Extension::load([
'ui.vue',
'ui.buttons',
'ui.buttons.icons',
'ui.icons',
'ui.common',
'ui.forms',
'ui.alerts',
'ui.pinner',
'ui.button.panel',
'ui.progressbar',
'ui.hint',
'ui.sidepanel-content',
'crm.config.terminal',
]);

$APPLICATION->setTitle(\Bitrix\Main\Localization\Loc::getMessage('CRM_CONFIG_TERMINAL_SETTINGS_TITLE') ?? '');

?>
<div id="terminalConfig"></div>
<style>
	#workarea-content {
		overflow: visible;
	}
</style>
<script>
	BX.ready(() => {
		const settingsPageApp = new BX.Crm.Config.Terminal.App({
			rootNodeId: 'terminalConfig',
			terminalSettings: <?=CUtil::PhpToJSObject($arResult['SETTINGS_PARAMS'])?>,
		});

		settingsPageApp.attachTemplate();
	});
</script>
