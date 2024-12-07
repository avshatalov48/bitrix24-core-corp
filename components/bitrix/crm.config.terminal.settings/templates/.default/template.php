<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;

/**
 * @var array $arResult
 * @var \CMain $APPLICATION
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
	'ui.sidepanel-wrapper',
	'ui.info-helper',
	'crm.config.terminal',
	'sidepanel',
]);

$APPLICATION->setTitle(Loc::getMessage('CRM_CONFIG_TERMINAL_SETTINGS_TITLE') ?? '');

?>
<div class="terminal-settings-wrapper">
	<div class="terminal-settings-title ui-title-1">
		<?= Loc::getMessage('CRM_CONFIG_TERMINAL_SETTINGS_TITLE') ?>
	</div>
	<div class="terminal-settings-subtitle ui-title-5">
		<?= Loc::getMessage('CRM_CONFIG_TERMINAL_SETTINGS_SUBTITLE') ?>
	</div>

	<div id="terminalConfig"></div>
</div>
<script>
	BX.ready(() => {
		const settingsPageApp = new BX.Crm.Config.Terminal.App({
			rootNodeId: 'terminalConfig',
			terminalSettings: <?=CUtil::PhpToJSObject($arResult['SETTINGS_PARAMS'])?>,
		});

		settingsPageApp.attachTemplate();
	});
</script>
