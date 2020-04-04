<?
use Bitrix\Main\ModuleManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (SITE_TEMPLATE_ID !== "bitrix24")
{
	return;
}

if (!\Bitrix\Main\Loader::includeModule("socialnetwork") || !\Bitrix\Main\Loader::includeModule("extranet"))
{
	return;
}

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/public/.left.menu_ext.php");

$userId = $GLOBALS["USER"]->getId();

$moduleFeatures = CSocNetAllowed::GetAllowedFeatures();
$userFeatures = CSocNetFeatures::GetActiveFeatures(SONET_ENTITY_USER, $userId);

$menuItems = array(
	array(
		GetMessage("EXTRANET_LEFT_MENU_LIVE_FEED"),
		SITE_DIR,
		array(),
		array("name" => "live_feed"),
		""
	)
);

$allowedFeatures = array();
foreach (array("tasks", "files", "photo", "blog") as $feature)
{
	$allowedFeatures[$feature] =
		array_key_exists($feature, $moduleFeatures) &&
		array_key_exists("allowed", $moduleFeatures[$feature]) &&
		in_array(SONET_ENTITY_USER, $moduleFeatures[$feature]["allowed"]) &&
		in_array($feature, $userFeatures)
	;
}

if ($GLOBALS["USER"]->IsAuthorized())
{
	if ($allowedFeatures["tasks"] && ModuleManager::isModuleInstalled("tasks"))
	{
		$menuItems[] = array(
			GetMessage("EXTRANET_LEFT_MENU_TASKS"),
			SITE_DIR."contacts/personal/user/".$userId."/tasks/",
			array(),
			array(
				"name" => "tasks",
				"counter_id" => "tasks_total",
				"sub_link" => SITE_DIR."contacts/personal/user/".$userId."/tasks/task/edit/0/"
			),
			"CBXFeatures::IsFeatureEnabled('Tasks')"
		);
	}

	if ($allowedFeatures["files"] && (ModuleManager::isModuleInstalled("disk") || ModuleManager::isModuleInstalled("webdav")))
	{
		$diskEnabled = \Bitrix\Main\Config\Option::get("disk", "successfully_converted", false);
		$menuItems[] = array(
			GetMessage("EXTRANET_LEFT_MENU_DISK"),
			SITE_DIR."contacts/personal/user/".$userId.($diskEnabled ? "/disk/path/" : "/files/lib/"),
			array(),
			array(),
			"CBXFeatures::IsFeatureEnabled('PersonalFiles')"
		);
	}

	if ($allowedFeatures["photo"] && ModuleManager::isModuleInstalled("photogallery"))
	{
		$menuItems[] = array(
			GetMessage("EXTRANET_LEFT_MENU_PHOTO"),
			SITE_DIR."contacts/personal/user/".$userId."/photo/",
			array(),
			array(),
			"CBXFeatures::IsFeatureEnabled('PersonalPhoto')"
		);
	}

	if ($allowedFeatures["blog"] && ModuleManager::isModuleInstalled("blog"))
	{
		$menuItems[] = array(
			GetMessage("EXTRANET_LEFT_MENU_BLOG"),
			SITE_DIR."contacts/personal/user/".$userId."/blog/",
			array(),
			array("counter_id" => "blog_post"),
			"CBXFeatures::IsFeatureEnabled('PersonalBlog')"
		);
	}
}

if (CBXFeatures::IsFeatureEnabled("Workgroups") && CBXFeatures::IsFeatureEnabled("Extranet"))
{
	$menuItems[] = array(
		GetMessage("EXTRANET_LEFT_MENU_GROUPS"),
		SITE_DIR."workgroups/",
		array(),
		array("class" => "menu-groups-extranet"),
		""
	);

	$groups = CSocNetUserToGroup::GetList(
		array("GROUP_NAME" => "ASC"),
		array(
			"USER_ID" => $userId,
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
		$menuItems[] = array(
			$group["GROUP_NAME"],
			SITE_DIR."workgroups/group/".$group["GROUP_ID"]."/",
			array(),
			array(),
			""
		);
	}
}

foreach ($aMenuLinks as $item)
{
	$menuLink = $item[1];
	if (!preg_match("~(/workgroups/|".SITE_DIR."index.php|".SITE_DIR.")$~i", $menuLink))
	{
		$menuItems[] = $item;
	}
}

if (defined("BX_COMP_MANAGED_CACHE"))
{
	$GLOBALS["CACHE_MANAGER"]->registerTag("sonet_user2group_U".$userId);
	$GLOBALS["CACHE_MANAGER"]->registerTag("sonet_group");
}

$aMenuLinks = $menuItems;