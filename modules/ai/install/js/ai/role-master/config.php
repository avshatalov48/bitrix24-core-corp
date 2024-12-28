<?

use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$currentUserId = -1;

if (Loader::includeModule('ai'))
{
	$currentUserId = Bitrix\AI\Facade\User::getCurrentUserId();
}

return [
	'css' => 'dist/role-master.bundle.css',
	'js' => 'dist/role-master.bundle.js',
	'rel' => [
		'ui.vue3.components.hint',
		'ui.alerts',
		'ui.layout-form',
		'ui.uploader.core',
		'ui.notification',
		'main.core.events',
		'ui.entity-selector',
		'main.loader',
		'ui.icon-set.main',
		'ui.buttons',
		'ui.icon-set.api.vue',
		'ui.icon-set.api.core',
		'ui.icon-set.actions',
		'ui.vue3',
		'main.core',
		'main.popup',
	],
	'skip_core' => false,
	'settings' => [
		'currentUserId' => $currentUserId,
	],
];
