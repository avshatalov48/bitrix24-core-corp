<?php

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Socialnetwork\Collab;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (SITE_TEMPLATE_ID !== "bitrix24")
{
	return;
}

if (!Loader::includeModule("socialnetwork") || !Loader::includeModule("extranet"))
{
	return;
}

if (!function_exists("getLeftMenuItemLink"))
{
	function getLeftMenuItemLink($sectionId, $defaultLink = "")
	{
		$settings = CUserOptions::GetOption("UI", $sectionId);
		return
			is_array($settings) && isset($settings["firstPageLink"]) && mb_strlen($settings["firstPageLink"]) ?
				$settings["firstPageLink"] :
				$defaultLink;
	}
}

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/public/.left.menu_ext.php");

global $USER, $CACHE_MANAGER;
$extEnabled = Loader::includeModule('extranet');
$userId = $USER->getId();
$isCollaber = $extEnabled && ServiceContainer::getInstance()->getCollaberService()->isCollaberById($userId);

$moduleFeatures = CSocNetAllowed::GetAllowedFeatures();
$userFeatures = CSocNetFeatures::GetActiveFeatures(SONET_ENTITY_USER, $userId);

if ($isCollaber)
{
	$menuItems = [
		[
			Loc::getMessage('EXTRANET_LEFT_MENU_IM_MESSENGER'),
			SITE_DIR . 'online/',
			[],
			[
				'counter_id' => 'im-message',
				'menu_item_id' => 'menu_im_messenger',
				'my_tools_section' => true,
			],
			'',
		]
	];
}
else
{
	$menuItems = [
		[
			Loc::getMessage('EXTRANET_LEFT_MENU_LIVE_FEED2'),
			SITE_DIR,
			[],
			[
				'name' => 'live_feed',
				'counter_id' => 'live-feed',
				'menu_item_id' => 'menu_live_feed',
			],
			''
		]
	];
}

if (
	Loader::includeModule('socialnetwork')
	&& Collab\CollabFeature::isOn()
	&& Collab\CollabFeature::isFeatureEnabled()
)
{
	$menuItems[] = [
		Loc::getMessage('EXTRANET_LEFT_MENU_IM_COLLAB'),
		$isCollaber ? '/extranet/?IM_COLLAB' : '/extranet/online/?IM_COLLAB',
		[],
		[
			'menu_item_id' => 'menu_im_collab',
			'can_be_first_item' => false
		],
		''
	];
}

$allowedFeatures = [];
foreach (['tasks', 'files', 'photo', 'blog', 'calendar'] as $feature)
{
	$allowedFeatures[$feature] =
		array_key_exists($feature, $moduleFeatures) &&
		array_key_exists('allowed', $moduleFeatures[$feature]) &&
		in_array(SONET_ENTITY_USER, $moduleFeatures[$feature]['allowed']) &&
		in_array($feature, $userFeatures)
	;
}

if ($USER->IsAuthorized())
{
	if ($allowedFeatures['tasks'] && ModuleManager::isModuleInstalled('tasks'))
	{
		$menuItems[] = array(
			Loc::getMessage('EXTRANET_LEFT_MENU_ONLY_TASKS'),
			SITE_DIR . 'contacts/personal/user/' . $userId . '/tasks/',
			[],
			[
				'real_link' => getLeftMenuItemLink(
					'tasks_panel_menu',
					SITE_DIR . 'contacts/personal/user/' . $userId . '/tasks/'
				),
				'counter_id' => 'tasks_total',
				'menu_item_id' => 'menu_tasks',
				'top_menu_id' => 'tasks_panel_menu',
				'sub_link' => SITE_DIR . 'contacts/personal/user/' . $userId . '/tasks/task/edit/0/',
				'name' => 'tasks',
			],
			"CBXFeatures::IsFeatureEnabled('Tasks')"
		);
	}

	if (
		$allowedFeatures['files']
		&& (ModuleManager::isModuleInstalled('disk') || ModuleManager::isModuleInstalled('webdav'))
	)
	{
		$diskEnabled = \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false);
		$menuItems[] = [
			$isCollaber ? Loc::getMessage('EXTRANET_LEFT_MENU_FILES_COLLAB') : Loc::getMessage('EXTRANET_LEFT_MENU_DISK'),
			SITE_DIR . 'contacts/personal/user/' . $userId . ($diskEnabled ? '/disk/path/' : '/files/lib/'),
			[],
			[
				'menu_item_id' => 'menu_files',
			],
			"CBXFeatures::IsFeatureEnabled('PersonalFiles')"
		];
	}

	if (!$isCollaber && $allowedFeatures['photo'] && ModuleManager::isModuleInstalled('photogallery'))
	{
		$menuItems[] = array(
			Loc::getMessage('EXTRANET_LEFT_MENU_PHOTO'),
			SITE_DIR . 'contacts/personal/user/' . $userId . '/photo/',
			[],
			['menu_item_id' => 'menu_photo'],
			"CBXFeatures::IsFeatureEnabled('PersonalPhoto')"
		);
	}

	if (!$isCollaber && $allowedFeatures["blog"] && ModuleManager::isModuleInstalled("blog"))
	{
		$menuItems[] = array(
			Loc::getMessage('EXTRANET_LEFT_MENU_BLOG'),
			SITE_DIR . 'contacts/personal/user/' . $userId . '/blog/',
			[],
			[
				'real_link' => getLeftMenuItemLink(
					'blog_messages_panel_menu',
					SITE_DIR . 'contacts/personal/user/' . $userId . '/blog/'
				),
				'counter_id' => 'blog_post',
				'menu_item_id' => 'menu_blog',
				'top_menu_id' => 'blog_messages_panel_menu'
			],
			"CBXFeatures::IsFeatureEnabled('PersonalBlog')"
		);
	}

	if ($isCollaber && $allowedFeatures['calendar'] && ModuleManager::isModuleInstalled('calendar'))
	{
		$menuItems[] = [
			Loc::getMessage('EXTRANET_LEFT_MENU_CALENDAR'),
			SITE_DIR . 'contacts/personal/user/' . $userId . '/calendar/',
			[],
			[
				'real_link' => getLeftMenuItemLink(
					'top_menu_id_calendar',
					SITE_DIR . 'contacts/personal/user/' . $userId . '/calendar/'
				),
				'menu_item_id' => 'menu_calendar',
				'sub_link' => SITE_DIR . 'contacts/personal/user/' . $userId . '/calendar/?EVENT_ID=NEW',
				'counter_id' => 'calendar',
				'top_menu_id' => 'top_menu_id_calendar',
			],
			''
		];
	}
}

if (!$isCollaber && CBXFeatures::IsFeatureEnabled('Workgroups') && CBXFeatures::IsFeatureEnabled('Extranet'))
{
	$menuItems[] = array(
		Loc::getMessage('EXTRANET_LEFT_MENU_GROUPS'),
		SITE_DIR . 'workgroups/',
		[],
		[
			'class' => 'menu-groups-extranet',
			'real_link' => getLeftMenuItemLink(
				'sonetgroups_panel_menu',
				SITE_DIR . 'workgroups/'
			),
			'menu_item_id' => 'menu_all_groups',
			'top_menu_id' => 'sonetgroups_panel_menu',
			// todo oh 'counter_id' => 'workgroups',
		],
		''
	);

	$groups = CSocNetUserToGroup::GetList(
		['GROUP_NAME' => 'ASC'],
		[
			'USER_ID' => $userId,
			'<=ROLE' => SONET_ROLES_USER,
			'GROUP_ACTIVE' => 'Y',
			'!GROUP_CLOSED' => 'Y',
			'GROUP_SITE_ID' => CExtranet::GetExtranetSiteID()
		],
		false,
		['nTopCount' => 50],
		['ID', 'GROUP_ID', 'GROUP_NAME', 'GROUP_SITE_ID', 'GROUP_TYPE']
	);

	$groupsData = [];
	while ($group = $groups->GetNext())
	{
		$groupsData[(int)$group['GROUP_ID']] = $group;
	}

	$chatIds = \Bitrix\Socialnetwork\Integration\Im\Chat\Workgroup::getChatData(['group_id' => array_keys($groupsData)]);

	foreach ($groupsData as $groupId => $groupData)
	{
		$arMenu[] = [
			$groupData["GROUP_NAME"],
			\Bitrix\Socialnetwork\Site\GroupUrl::get(
				$groupId,
				$groupData['GROUP_TYPE'],
				['chatId' => $chatIds[$groupId] ?? 0]
			),
			[],
			[],
			'',
		];
	}
}

if (!$isCollaber)
{
	$menuItems[] = array(
		Loc::getMessage('EXTRANET_LEFT_MENU_CONTACTS'),
		SITE_DIR . 'contacts/',
		[],
		[
			'real_link' => getLeftMenuItemLink(
				'top_menu_id_extranet_contacts',
				SITE_DIR . 'contacts/'
			),
			'menu_item_id' => 'menu_company',
			'top_menu_id' => 'top_menu_id_extranet_contacts',
		],
		''
	);
}

foreach ($aMenuLinks as $item)
{
	$menuLink = $item[1];
	if (!preg_match("~(/workgroups/|/contacts/|".SITE_DIR."index.php|".SITE_DIR.")$~i", $menuLink))
	{
		$menuItems[] = $item;
	}
}

if (defined('BX_COMP_MANAGED_CACHE'))
{
	$CACHE_MANAGER->registerTag('sonet_user2group_U' . $userId);
	$CACHE_MANAGER->registerTag('sonet_group');
}

$aMenuLinks = $menuItems;
