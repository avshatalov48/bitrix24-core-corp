<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/public/.top.menu.php");

$aMenuLinks = Array(
	Array(
		GetMessage("EXTRANET_TOP_MENU_MAIN"),
		SITE_DIR . "index.php",
		Array(), 
		Array(), 
		"" 
	),
	Array(
		GetMessage("EXTRANET_TOP_MENU_WORKGROUPS"),
		SITE_DIR . "workgroups/",
		Array(), 
		Array(), 
		"CBXFeatures::IsFeatureEnabled('Workgroups')" 
	),
	Array(
		GetMessage("EXTRANET_TOP_MENU_CONTACTS"),
		SITE_DIR . "contacts/",
		Array(), 
		Array(), 
		"" 
	)
);
