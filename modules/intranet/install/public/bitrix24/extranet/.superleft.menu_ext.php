<?

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Collab;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/extranet/.superleft.menu_ext.php");
global $CACHE_MANAGER, $USER;

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

$extEnabled = Loader::includeModule('extranet');
$USER_ID = $USER->GetID();
$isCollaber = $extEnabled && \Bitrix\Extranet\Service\ServiceContainer::getInstance()->getCollaberService()->isCollaberById($USER_ID);

$diskEnabled = \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false);
$arMenu = [];

if ($isCollaber)
{
	$arMenu = [
		[
			Loc::getMessage('MENU_IM_MESSENGER_NEW'),
			'/extranet/online/',
			[],
			[
				'counter_id' => 'im-message',
				'menu_item_id' => 'menu_im_messenger',
				'my_tools_section' => true,
			],
			''
		]
	];
}
else
{
	$arMenu = [
		[
			Loc::getMessage('MENU_LIVE_FEED3'),
			'/extranet/index.php',
			[],
			['name' => 'live_feed', 'counter_id' => 'live-feed', 'menu_item_id' => 'menu_live_feed'],
			'',
		],
		[
			Loc::getMessage('MENU_BLOG'),
			'/extranet/contacts/personal/user/' . $USER_ID . '/blog/',
			[],
			[
				'real_link' => getLeftMenuItemLink(
					'blog_messages_panel_menu',
					'/extranet/contacts/personal/user/' . $USER_ID . '/blog/'
				),
				'counter_id' => 'blog_post',
				'menu_item_id' => 'menu_blog',
				'top_menu_id' => 'blog_messages_panel_menu'
			],
			'',
		],
	];
}

if (
	Loader::includeModule('socialnetwork')
	&& Collab\CollabFeature::isOn()
	&& Collab\CollabFeature::isFeatureEnabled()
)
{
	$arMenu[] = [
		Loc::getMessage('MENU_IM_MESSENGER_COLLAB'),
		$isCollaber ? '/extranet/?IM_COLLAB=0' : '/extranet/online/?IM_COLLAB=0',
		[],
		[
			'menu_item_id' => 'menu_im_collab',
			'can_be_first_item' => false
		],
		''
	];
}

$arMenu[] = [
	Loc::getMessage('MENU_TASKS'),
	'/extranet/contacts/personal/user/' . $USER_ID . '/tasks/',
	[],
	[
		'real_link' => getLeftMenuItemLink(
			'tasks_panel_menu',
			'/extranet/contacts/personal/user/' . $USER_ID . '/tasks/'
		),
		'name' => 'tasks',
		'counter_id' => 'tasks_total',
		'top_menu_id' => 'tasks_panel_menu',
		'menu_item_id' => 'menu_tasks',
		'sub_link' => '/extranet/contacts/personal/user/' . $USER_ID . '/tasks/task/edit/0/?ta_sec=left_menu&ta_el=create_button',
	],
	''
];
$arMenu[] = [
	$isCollaber ? Loc::getMessage('MENU_FILES_COLLAB') : Loc::getMessage('MENU_FILES'),
	'/extranet/contacts/personal/user/' . $USER_ID . ($diskEnabled === 'Y' ? '/disk/path/' : '/files/lib/'),
	[],
	[
		'menu_item_id' => 'menu_files',
	],
	'',
];

if ($isCollaber)
{
	$arMenu[] = [
		Loc::getMessage('MENU_CALENDAR'),
		"/extranet/contacts/personal/user/".$USER_ID."/calendar/",
		array(),
		array(
			"real_link" => getLeftMenuItemLink(
				"top_menu_id_calendar",
				"/extranet/contacts/personal/user/".$USER_ID."/calendar/"
			),
			"menu_item_id" => "menu_calendar",
			"sub_link" => "/extranet/contacts/personal/user/" . $USER_ID . "/calendar/?EVENT_ID=NEW",
			"counter_id" => "calendar",
			"top_menu_id" => "top_menu_id_calendar",
		),
		""
	];
}

if ($extEnabled && !$isCollaber && CModule::IncludeModule("socialnetwork"))
{
	$arMenu[] = array(
		GetMessage("MENU_GROUPS"),
		"/extranet/workgroups/",
		array(),
		array(
			"class" => "menu-groups-extranet",
			"real_link" => getLeftMenuItemLink(
				"sonetgroups_panel_menu",
				"/extranet/workgroups/"
			),
			"menu_item_id"=>"menu_all_groups",
			"top_menu_id" => "sonetgroups_panel_menu",
			// todo oh 'counter_id' => 'workgroups',
		),
		""
	);

	$groups = CSocNetUserToGroup::GetList(
		["GROUP_NAME" => "ASC"],
		[
			"USER_ID" => $USER_ID,
			"<=ROLE" => SONET_ROLES_USER,
			"GROUP_ACTIVE" => "Y",
			"!GROUP_CLOSED" => "Y",
			"GROUP_SITE_ID" => CExtranet::GetExtranetSiteID()
		],
		false,
		["nTopCount" => 50],
		["ID", "GROUP_ID", "GROUP_NAME", "GROUP_SITE_ID", 'GROUP_TYPE']
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
			"",
		];
	}
}

if (defined("BX_COMP_MANAGED_CACHE"))
{
	$CACHE_MANAGER->RegisterTag('sonet_group');
	$CACHE_MANAGER->RegisterTag('sonet_user2group_U'.$USER_ID);
	$CACHE_MANAGER->RegisterTag("bitrix24_left_menu");
}

if (!$isCollaber)
{
	$arMenu[] = array(
		GetMessage("MENU_CONTACT"),
		"/extranet/contacts/",
		array(),
		array(
			"real_link" => getLeftMenuItemLink(
				"top_menu_id_extranet_contacts",
				"/extranet/contacts/"
			),
			"menu_item_id" => "menu_company",
			"top_menu_id" => "top_menu_id_extranet_contacts",
		),
		""
	);
}

$aMenuLinks = array_merge($arMenu, $aMenuLinks);
?>
