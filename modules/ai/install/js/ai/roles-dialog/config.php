<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\AI\Container;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Services\CopilotAccessCheckerService;
use Bitrix\Main\Loader;

if (Loader::includeModule('ai'))
{
	$copilotAccessCheckerService = Container::init()->getItem(CopilotAccessCheckerService::class);
	$userHasAccessToLibrary = $copilotAccessCheckerService->canShowLibrariesInFrontend(User::getCurrentUserId());
}

return [
	'css' => 'dist/roles-dialog.bundle.css',
	'js' => 'dist/roles-dialog.bundle.js',
	'rel' => [
		'ai.engine',
		'main.popup',
		'ui.vue3.components.hint',
		'ui.label',
		'ui.icon-set.animated',
		'main.core.events',
		'ui.vue3.pinia',
		'ui.notification',
		'ui.icon-set.api.vue',
		'ui.icon-set.api.core',
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'isLibraryVisible' => $userHasAccessToLibrary ?? false,
	]
];