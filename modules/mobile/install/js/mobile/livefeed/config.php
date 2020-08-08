<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

Loader::includeModule('socialnetwork');

global $USER;

$allowToAll = \Bitrix\Socialnetwork\ComponentHelper::getAllowToAllDestination();
$extranetSite = (Loader::includeModule("extranet") && \CExtranet::isExtranetSite());

$langAdditional = array(
	'MOBILE_EXT_LIVEFEED_TASKS_INSTALLED' => (ModuleManager::isModuleInstalled('tasks') ? 'Y' : 'N'),
	'MOBILE_EXT_LIVEFEED_TIMEMAN_INSTALLED' => (ModuleManager::isModuleInstalled('timeman') ? 'Y' : 'N'),
	'MOBILE_EXT_LIVEFEED_LISTS_INSTALLED' => (ModuleManager::isModuleInstalled('lists') && \CLists::isFeatureEnabled() ? 'Y' : 'N'),
	'MOBILE_EXT_LIVEFEED_USE_TASKS' => (
		ModuleManager::isModuleInstalled('tasks')
		&& (
			!Loader::includeModule('bitrix24')
			|| \CBitrix24BusinessTools::isToolAvailable($USER->getId(), 'tasks')
		)
		&& \CSocNetFeaturesPerms::currentUserCanPerformOperation(SONET_ENTITY_USER, $USER->getId(), 'tasks', 'create_tasks')
			? 'Y'
			: 'N'
	),
	'MOBILE_EXT_LIVEFEED_TASK_PATH' => SITE_DIR.'mobile/tasks/snmrouter/?routePage=view&USER_ID=#user_id#&TASK_ID=#task_id#',
	'MOBILE_EXT_LIVEFEED_DEST_TO_ALL_DENIED' => ($extranetSite || !$allowToAll ? 'Y' : 'N'),
	'MOBILE_EXT_LIVEFEED_DEST_TO_ALL_DEFAULT' => (
		$allowToAll
			? (Option::get('socialnetwork', 'default_livefeed_toall', 'Y') == 'Y' ? 'Y' : 'N')
			: 'N'
	),
	'MOBILE_EXT_LIVEFEED_POST_UF_CODE' => (
		(
			Option::get('disk', 'successfully_converted', false)
			&& ModuleManager::isModuleInstalled('disk')
		)
		|| ModuleManager::isModuleInstalled('webdav')
			? 'UF_BLOG_POST_FILE'
			: 'UF_BLOG_POST_DOC'
	),
	'MOBILE_EXT_LIVEFEED_SITE_TEMPLATE_ID' => 'mobile_app',
);

return [
	'js' => './dist/livefeed.bundle.js',
//	'css' => '/bitrix/js/mobile/livefeed/mobile.livefeed.css',
	'lang_additional' => $langAdditional,
	'rel' => [
		'main.core',
		'main.core.events',
		'mobile.imageviewer',
		'mobile.utils',
	],
	'skip_core' => false,
];