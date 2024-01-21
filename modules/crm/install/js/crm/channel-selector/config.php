<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'src/channel-selector.css',
	'js' => 'dist/channel-selector.bundle.js',
	'rel' => [
		'crm.router',
		'main.core',
		'main.core.events',
		'main.loader',
		'main.popup',
		'ui.icons.service',
		'ui.menu-configurable',
		'ui.notification',
	],
	'oninit' => function() {
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			return [
				'lang_additional' => array_merge(
					\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages(),
					[
						'MARKET_BASE_PATH' => \Bitrix\Crm\Integration\Market\Router::getBasePath(),
					]
				),
			];
		}
	},
	'skip_core' => false,
];
