<?

use Bitrix\Main\Application;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$currentUserId = null;
$language = 'en';

if (Loader::includeModule('ai'))
{
	$language = \Bitrix\AI\Facade\User::getUserLanguage();
	$currentUserId = Bitrix\AI\Facade\User::getCurrentUserId();
}

return [
	'css' => 'dist/prompt-master.bundle.css',
	'js' => 'dist/prompt-master.bundle.js',
	'rel' => [
		'ui.vue3',
		'main.loader',
		'ui.vue3.components.hint',
		'ui.vue3.directives.hint',
		'ui.alerts',
		'ui.icon-set.main',
		'ui.icon-set.crm',
		'main.popup',
		'main.core',
		'main.core.events',
		'ui.entity-selector',
		'ui.hint',
		'ui.buttons',
		'ui.icon-set.api.vue',
		'ui.icon-set.api.core',
		'ui.analytics',
	],
	'skip_core' => false,
	'settings' => [
		'userId' => Bitrix\AI\Facade\User::getCurrentUserId(),
		'language' => $language,
	]
];
