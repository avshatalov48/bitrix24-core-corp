<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/extranet/.superleft.menu_ext.php");
global $CACHE_MANAGER, $USER;

if (!function_exists("getLeftMenuItemLink"))
{
	function getLeftMenuItemLink($sectionId, $defaultLink = "")
	{
		$settings = CUserOptions::GetOption("UI", $sectionId);
		return
			is_array($settings) && isset($settings["firstPageLink"]) && strlen($settings["firstPageLink"]) ?
				$settings["firstPageLink"] :
				$defaultLink;
	}
}

$USER_ID = $USER->GetID();
$diskEnabled = \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false);

$arMenu = array(
	array(
		GetMessage("MENU_LIVE_FEED"),
		"/extranet/index.php",
		array(),
		array("name" => "live_feed", "counter_id" => "live-feed", "menu_item_id"=>"menu_live_feed"),
		""
	),
	array(
		GetMessage("MENU_TASKS"),
		"/extranet/contacts/personal/user/".$USER_ID."/tasks/",
		array(),
		array(
			"real_link" => getLeftMenuItemLink(
				"tasks_panel_menu",
				"/extranet/contacts/personal/user/".$USER_ID."/tasks/"
			),
			"name" => "tasks",
			"counter_id" => "tasks_total",
			"top_menu_id" => "tasks_panel_menu",
			"sub_link" => "/extranet/contacts/personal/user/".$USER_ID."/tasks/task/edit/0/"
		),
		""
	),
	array(
		GetMessage("MENU_BLOG"),
		"/extranet/contacts/personal/user/".$USER_ID."/blog/",
		array(),
		array(
			"real_link" => getLeftMenuItemLink(
				"blog_messages_panel_menu",
				"/extranet/contacts/personal/user/".$USER_ID."/blog/"
			),
			"counter_id" => "blog_post",
			"top_menu_id" => "blog_messages_panel_menu"
		),
		""
	),
	array(
		GetMessage("MENU_FILES"),
		"/extranet/contacts/personal/user/".$USER_ID.($diskEnabled == "Y" ? "/disk/path/" : "/files/lib/"),
		array(),
		array(),
		""
	),
);

$extEnabled = false;
if (IsModuleInstalled("extranet"))
	$extEnabled = true;

if (CModule::IncludeModule("socialnetwork") && CModule::IncludeModule("extranet"))
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
			"top_menu_id" => "sonetgroups_panel_menu",
		),
		""
	);

	$groups = CSocNetUserToGroup::GetList(
		array("GROUP_NAME" => "ASC"),
		array(
			"USER_ID" => $USER_ID,
			"<=ROLE" => SONET_ROLES_USER,
			"GROUP_ACTIVE" => "Y",
			"!GROUP_CLOSED" => "Y",
			"GROUP_SITE_ID" => CExtranet::GetExtranetSiteID()
		),
		false,
		array("nTopCount" => 50),
		array("ID", "GROUP_ID", "GROUP_NAME", "GROUP_SITE_ID")
	);

	while ($group = $groups->GetNext())
	{
		$arMenu[] = array(
			$group["GROUP_NAME"],
			"/extranet/workgroups/group/".$group["GROUP_ID"]."/",
			array(),
			array(),
			""
		);
	}
}

if (defined("BX_COMP_MANAGED_CACHE"))
{
	$CACHE_MANAGER->RegisterTag('sonet_group');
	$CACHE_MANAGER->RegisterTag('sonet_user2group_U'.$USER_ID);
	$CACHE_MANAGER->RegisterTag("bitrix24_left_menu");
}

$arMenu[] = array(
	GetMessage("MENU_CONTACT"),
	"/extranet/contacts/",
	array(),
	array(
		"real_link" => getLeftMenuItemLink(
			"top_menu_id_extranet_contacts",
			"/extranet/contacts/"
		),
		"top_menu_id" => "top_menu_id_extranet_contacts",
	),
	""
);

$aMenuLinks = array_merge($arMenu, $aMenuLinks);
?>