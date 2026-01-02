<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('im'))
{
	return [];
}

return [
	'css' => 'dist/navigation.bundle.css',
	'js' => 'dist/navigation.bundle.js',
	'rel' => [
		'im.v2.lib.logger',
		'im.v2.lib.slider',
		'im.v2.lib.phone',
		'im.v2.lib.feature',
		'im.v2.lib.permission',
		'im.v2.component.elements.scroll-with-gradient',
		'im.v2.component.elements.avatar',
		'im.v2.component.elements.button',
		'im.v2.component.elements.popup',
		'im.v2.lib.utils',
		'im.v2.provider.service.settings',
		'main.core',
		'main.popup',
		'im.v2.lib.menu',
		'im.v2.lib.desktop-api',
		'im.v2.lib.confirm',
		'im.v2.lib.desktop',
		'ui.buttons',
		'ui.feedback.form',
		'ui.fontawesome4',
		'im.v2.application.core',
		'im.v2.const',
		'im.v2.lib.market',
	],
	'skip_core' => false,
];
