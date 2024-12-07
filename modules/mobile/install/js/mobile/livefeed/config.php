<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Engine\ActionFilter\Service\Token;

Loader::includeModule('socialnetwork');
Loader::includeModule('mobileapp');

global $USER;

$allowToAll = \Bitrix\Socialnetwork\ComponentHelper::getAllowToAllDestination();
$extranetSite = (Loader::includeModule("extranet") && \CExtranet::isExtranetSite());
$componentUrl = \Bitrix\MobileApp\Janative\Manager::getComponentPath('livefeed.postform');
$langAdditional = [
	'MOBILE_EXT_LIVEFEED_SERVER_NAME' => (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'],
	'MOBILE_EXT_LIVEFEED_TASKS_INSTALLED' => (ModuleManager::isModuleInstalled('tasks') ? 'Y' : 'N'),
	'MOBILE_EXT_LIVEFEED_TIMEMAN_INSTALLED' => (ModuleManager::isModuleInstalled('timeman') ? 'Y' : 'N'),
	'MOBILE_EXT_LIVEFEED_LISTS_INSTALLED' => (Loader::includeModule('lists') && \CLists::isFeatureEnabled() ? 'Y' : 'N'),
	'MOBILE_EXT_LIVEFEED_DISK_INSTALLED' => (Option::get('disk', 'successfully_converted', false) && ModuleManager::isModuleInstalled('disk') ? 'Y' : 'N'),
	'MOBILE_EXT_LIVEFEED_WEBDAV_INSTALLED' => (ModuleManager::isModuleInstalled('webdav') ? 'Y' : 'N'),
	'MOBILE_EXT_LIVEFEED_VOTE_INSTALLED' => (
		ModuleManager::isModuleInstalled('vote')
		&& (
			!\Bitrix\Main\Loader::includeModule('bitrix24')
			|| \Bitrix\Bitrix24\Feature::isFeatureEnabled('socialnetwork_livefeed_vote')
		)
			? 'Y'
			: 'N'
	),
	'MOBILE_EXT_LIVEFEED_USE_IMPORTANT' => (
		!\Bitrix\Main\Loader::includeModule('bitrix24')
		|| \Bitrix\Bitrix24\Feature::isFeatureEnabled('socialnetwork_livefeed_important')
			? 'Y'
			: 'N'
	),
	'MOBILE_EXT_LIVEFEED_USE_TASKS' => (
		ModuleManager::isModuleInstalled('tasks')
		&& ModuleManager::isModuleInstalled('tasksmobile')
		&& (
			!Loader::includeModule('bitrix24')
			|| \CBitrix24BusinessTools::isToolAvailable($USER->getId(), 'tasks')
		)
		&& \CSocNetFeaturesPerms::currentUserCanPerformOperation(SONET_ENTITY_USER, $USER->getId(), 'tasks', 'create_tasks')
			? 'Y'
			: 'N'
	),
	'MOBILE_EXT_LIVEFEED_TASK_PATH' => SITE_DIR.'mobile/tasks/snmrouter/?routePage=view&USER_ID=#user_id#&TASK_ID=#task_id#',
	'MOBILE_EXT_LIVEFEED_FILE_ATTACH_PATH' => (
		Option::get('disk', 'successfully_converted', false) && ModuleManager::isModuleInstalled('disk')
			? SITE_DIR.'mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId='.$USER->getId()
			: SITE_DIR.'mobile/webdav/user/'.$USER->getId().'/'
	),
	'MOBILE_EXT_LIVEFEED_DEST_TO_ALL_DENIED' => ($extranetSite || !$allowToAll ? 'Y' : 'N'),
	'MOBILE_EXT_LIVEFEED_DEST_TO_ALL_DEFAULT' => (
		$allowToAll
			? (Option::get('socialnetwork', 'default_livefeed_toall', 'Y') === 'Y' ? 'Y' : 'N')
			: 'N'
	),
	'MOBILE_EXT_LIVEFEED_POST_FILE_UF_CODE' => (
		(
			Option::get('disk', 'successfully_converted', false)
			&& ModuleManager::isModuleInstalled('disk')
		)
		|| ModuleManager::isModuleInstalled('webdav')
			? 'UF_BLOG_POST_FILE'
			: 'UF_BLOG_POST_DOC'
	),
	'MOBILE_EXT_LIVEFEED_SITE_TEMPLATE_ID' => 'mobile_app',
	'MOBILE_EXT_LIVEFEED_SITE_DIR' => SITE_DIR,
	'MOBILE_EXT_LIVEFEED_COMPONENT_URL' => $componentUrl,
	'MOBILE_EXT_LIVEFEED_CURRENT_EXTRANET_SITE' => ($extranetSite ? 'Y' : 'N'),
	'MOBILE_EXT_LIVEFEED_CURRENT_USER_ID' => $USER->getId(),
	'MOBILE_EXT_LIVEFEED_DEVICE_WIDTH' => (int)\CMobile::getInstance()->getDevicewidth(),
	'MOBILE_EXT_LIVEFEED_DEVICE_HEIGHT' => (int)\CMobile::getInstance()->getDeviceheight(),
	'MOBILE_EXT_LIVEFEED_DEVICE_RATIO' => \CMobile::getInstance()->getPixelRatio(),
	'MOBILE_EXT_LIVEFEED_COLLAPSED_PINNED_PANEL_ITEMS_LIMIT' => \Bitrix\Mobile\Component\LogList\Util::getCollapsedPinnedPanelItemsLimit(),
	'MOBILE_EXT_LIVEFEED_AJAX_ENTITY_HEADER_NAME' => Token::getEntityHeader(),
	'MOBILE_EXT_LIVEFEED_AJAX_TOKEN_HEADER_NAME' => Token::getTokenHeader(),
];

return [
	'js' => './dist/livefeed.bundle.js',
//	'css' => '/bitrix/js/mobile/livefeed/mobile.livefeed.css',
	'lang_additional' => $langAdditional,
	'rel' => [
		'ui.analytics',
		'main.core',
		'main.core.events',
		'mobile.utils',
		'mobile.ajax',
		'mobile.imageviewer',
	],
	'skip_core' => false,
];
