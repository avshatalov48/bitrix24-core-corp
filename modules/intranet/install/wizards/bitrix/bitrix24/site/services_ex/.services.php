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
			"site.php", 
			"template.php", //Install template
			"groups.php", //Create user groups
			"property.php", //Create user fields
			"options.php", //Install module options
			"events.php", //Install event messages
			"mobile.php", // template and urlrewriter for mobile
		),
	),

	"forum" => Array(
		"NAME" => GetMessage("SERVICE_FORUM"),
	),

	"iblock" => Array(
		"NAME" => GetMessage("SERVICE_COMPANY_STRUCTURE"),
		"STAGES" => Array(
			"types.php", //IBlock types
			//"master.php",
			//"calendar_groups.php",
			//"group_files.php",
			"group_photogallery.php",
		),
	),

/*	"advertising" => Array(
		"NAME" => GetMessage("SERVICE_ADVERTISING"),
	),

	"subscribe" => Array(
		"NAME" => GetMessage("SERVICE_SUBSCRIBE"),
	),*/

	"blog" => Array(
		"NAME" => GetMessage("SERVICE_BLOG"),
	),

	"socialnetwork" => Array(
		"NAME" => GetMessage("SERVICE_SOCIALNETWORK"),
	),

	"intranet" => Array(
		"NAME" => GetMessage("SERVICE_INTRANET"),
	),

/*	"support" => Array(
		"NAME" => GetMessage("SERVICE_SUPPORT"),
	),*/
	"fileman" => Array(
		"NAME" => GetMessage("SERVICE_FILEMAN"),
	),
	/*"video" => Array(
		"NAME" => GetMessage("SERVICE_VIDEO"),
	),*/
	"wiki" => Array(
		"NAME" => GetMessage("SERVICE_WIKI"),
	),
);
?>