<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/public/search/.left.menu.php");
$aMenuLinks = Array(
	Array(
		GetMessage("EXTRANET_SEARCH_LEFT_MENU_SEARCH"),
		SITE_DIR . "search/index.php",
		Array(), 
		Array(), 
		"" 
	),
	Array(
		GetMessage("EXTRANET_SEARCH_LEFT_MENU_MAP"),
		SITE_DIR . "search/map.php",
		Array(), 
		Array(), 
		"" 
	)
);
?>