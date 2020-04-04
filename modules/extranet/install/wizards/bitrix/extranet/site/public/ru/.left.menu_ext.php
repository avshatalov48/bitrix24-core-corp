<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (SITE_TEMPLATE_ID !== "bitrix24")
	return;
	
if (!CModule::IncludeModule("socialnetwork"))
	return;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/public/.left.menu_ext.php");

$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
$arUserActiveFeatures = CSocNetFeatures::GetActiveFeatures(SONET_ENTITY_USER, $GLOBALS["USER"]->GetID());
GLOBAL $USER;
$USER_ID = $USER->GetID();

$aMenuB24 = array();
	
$aMenuB24[] = Array(
		GetMessage("EXTRANET_LEFT_MENU_LIVE_FEED"),
		"#SITE_DIR#index.php",
		Array(),
		Array("name" => "live_feed"),
		""
	);
	
if ($GLOBALS["USER"]->IsAuthorized()):
	if (
		array_key_exists("tasks", $arSocNetFeaturesSettings)
		&& array_key_exists("allowed", $arSocNetFeaturesSettings["tasks"])
		&& in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings["tasks"]["allowed"])
		&& in_array("tasks", $arUserActiveFeatures)
	)
		$aMenuB24[] = Array(
			GetMessage("EXTRANET_LEFT_MENU_TASKS"),
			"#SITE_DIR#contacts/personal/user/".$USER_ID."/tasks/",
			Array(),
			Array("name" => "tasks", "counter_id" => "tasks_total"),
			"CBXFeatures::IsFeatureEnabled('Tasks')"
		);
/*
	if (
		array_key_exists("calendar", $arSocNetFeaturesSettings)	
		&& array_key_exists("allowed", $arSocNetFeaturesSettings["calendar"])
		&& in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings["calendar"]["allowed"])
		&& in_array("calendar", $arUserActiveFeatures)
	)
		$aMenuB24[] = Array(
			GetMessage("EXTRANET_LEFT_MENU_CALENDAR"),
			"#SITE_DIR#contacts/personal/user/".$USER_ID."/calendar/",
			Array(),
			Array(),
			"CBXFeatures::IsFeatureEnabled('Calendar')"
		);
*/
	if (
		(CModule::IncludeModule("webdav") || CModule::IncludeModule("disk")) && $GLOBALS["USER"]->IsAuthorized()
		&& array_key_exists("files", $arSocNetFeaturesSettings)	
		&& array_key_exists("allowed", $arSocNetFeaturesSettings["files"])
		&& in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings["files"]["allowed"])
		&& in_array("files", $arUserActiveFeatures)
	)
		$diskEnabled = \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false);
		$aMenuB24[] = Array(
			GetMessage("EXTRANET_LEFT_MENU_DISK"),
			"#SITE_DIR#contacts/personal/user/".$USER_ID. ($diskEnabled? "/disk/path/" : "/files/lib/"),
			Array(),
			Array(),
			"CBXFeatures::IsFeatureEnabled('PersonalFiles')"
		);
	if (
		CModule::IncludeModule("photogallery") 
		&& array_key_exists("photo", $arSocNetFeaturesSettings)	
		&& array_key_exists("allowed", $arSocNetFeaturesSettings["photo"])
		&& in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings["photo"]["allowed"])
		&& in_array("photo", $arUserActiveFeatures)	
	)
		$aMenuB24[] = Array(
			GetMessage("EXTRANET_LEFT_MENU_PHOTO"),
			"#SITE_DIR#contacts/personal/user/".$USER_ID."/photo/",
			Array(),
			Array(),
			"CBXFeatures::IsFeatureEnabled('PersonalPhoto')"
		);
	if (
		CModule::IncludeModule("blog") 
		&& array_key_exists("blog", $arSocNetFeaturesSettings)
		&& array_key_exists("allowed", $arSocNetFeaturesSettings["blog"])
		&& in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings["blog"]["allowed"])
		&& in_array("blog", $arUserActiveFeatures)	
	)
		$aMenuB24[] = Array(
			GetMessage("EXTRANET_LEFT_MENU_BLOG"),
			"#SITE_DIR#contacts/personal/user/".$USER_ID."/blog/",
			Array(),
			Array("counter_id" => "blog_post"),
			"CBXFeatures::IsFeatureEnabled('PersonalBlog')"
		);
endif;
$aMenuLinks = array_merge($aMenuLinks, $aMenuB24);
?>