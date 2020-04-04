<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arServices = Array(

	"search" => Array(
		"MODULE_ID" => "search",
		"NAME" => GetMessage("SERVICE_SEARCH"),
	),

	"files" => Array(
		"MODULE_ID" => "main",
		"NAME" => GetMessage("SERVICE_FILES"),
		"STAGES" => Array(
			"files.php",
			"bitrix.php",
		),
	),

	"main" => Array(
		"NAME" => GetMessage("SERVICE_MAIN_SETTINGS"),
		"STAGES" => Array(
			"site.php", //Install site
			"template.php", //Install template
			"theme.php", //Install theme
			"groups.php", //Create user groups
			"property.php", //Create user fields
			"options.php", //Install module options
			"events.php", //Install event messages
			"mobile.php", //Install mobile interface
		),
	),

	"forum" => Array(
		"NAME" => GetMessage("SERVICE_FORUM"),
	),

	"blog" => Array(
		"NAME" => GetMessage("SERVICE_BLOG"),
	),

	"socialnetwork" => Array(
		"NAME" => GetMessage("SERVICE_SOCIALNETWORK"),
	),

	"intranet" => Array(
		"NAME" => GetMessage("SERVICE_INTRANET"),
	),

	"fileman" => Array(
		"NAME" => GetMessage("SERVICE_FILEMAN"),
	),
);
?>