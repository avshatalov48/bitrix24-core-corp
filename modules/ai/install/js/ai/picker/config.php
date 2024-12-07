<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'ai.payload.textpayload',
		'ui.icon-set.icon.actions',
		'ui.notification',
		'clipboard',
		'ui.buttons',
		'main.core.events',
		'main.popup',
		'ui.icon-set.main',
		'ui.icon-set.actions',
		'ai.engine',
		'ai.ajax-error-handler',
		'ai.agreement',
		'ui.icon-set.api.core',
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'isRestrictedByEula' => Loader::includeModule('ai') && Bitrix\AI\Facade\Bitrix24::isFeatureEnabled('ai_available_by_version') === false,
	]
];