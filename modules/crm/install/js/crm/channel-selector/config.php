<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'src/channel-selector.css',
	'js' => 'dist/channel-selector.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'main.core.events',
		'crm.router',
		'main.loader',
		'ui.menu-configurable',
		'ui.icons.service',
		'ui.notification',
	],
	'oninit' => function() {
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			return [
				'lang_additional' => \Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages(),
			];
		}
	},
	'skip_core' => false,
];
