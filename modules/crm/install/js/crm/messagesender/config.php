<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$hasImConnector = \Bitrix\Main\Loader::includeModule('imconnector');

return [
	'css' => 'dist/messagesender.bundle.css',
	'js' => 'dist/messagesender.bundle.js',
	'rel' => [
		'main.core.events',
		'main.core',
		'crm.data-structures',
		'crm_common',
		'crm.router',
		'ui.dialogs.messagebox',
	],
	'skip_core' => false,
	'settings' => [
		'marketUrl' => Bitrix\Crm\Integration\Market\Router::getBasePath() . 'category/integration_sms/',
		'canUseNotifications' => $hasImConnector && \Bitrix\ImConnector\Limit::canUseConnector('notifications'),
	],
];
