<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('im');

return [
	'css' => 'dist/textarea.bundle.css',
	'js' => 'dist/textarea.bundle.js',
	'rel' => [
		'ui.uploader.core',
		'im.v2.lib.draft',
		'im.v2.lib.local-storage',
		'im.v2.lib.sound-notification',
		'rest.client',
		'im.v2.application.core',
		'im.v2.lib.smile-manager',
		'im.v2.lib.rest',
		'im.v2.lib.logger',
		'im.v2.lib.parser',
		'ui.vue3.directives.hint',
		'im.v2.lib.entity-creator',
		'im.v2.lib.market',
		'main.core.events',
		'main.core',
		'im.v2.provider.service',
		'im.v2.lib.utils',
		'im.v2.component.elements',
		'im.v2.const',
	],
	'skip_core' => false,
	'settings' => [
		'isAiBetaAvailable' => \Bitrix\Im\Settings::isAiBetaAvailable(),
		'maxLength' => \CIMMessenger::MESSAGE_LIMIT,
	]
];