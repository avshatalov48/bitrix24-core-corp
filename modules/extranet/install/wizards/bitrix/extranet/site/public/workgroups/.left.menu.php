<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/extranet/public/workgroups/.left.menu.php");

$aMenuLinks = Array(
	Array(
		GetMessage("EXTRANET_WORKGROUPS_LEFT_MENU_MY_GROUPS"),
		SITE_DIR . "workgroups/index.php",
		Array(), 
		Array(), 
		"" 
	)
);
