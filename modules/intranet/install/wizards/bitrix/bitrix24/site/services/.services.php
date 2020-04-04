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
		),
	),

	"main" => Array(
		"NAME" => GetMessage("SERVICE_MAIN_SETTINGS"),
		"STAGES" => Array(
			"template.php", //Install template
			"groups.php", //Create user groups
			"languages.php",//create spanish language
			"property.php", //Create user fields
			"options.php", //Install module options
			"rating.php",
			"extranet.php",
			"events.php",
			"smiles.php"
		),
	),
	
	"forum" => Array(
		"NAME" => GetMessage("SERVICE_FORUM"),
	),

	"iblock" => Array(
		"NAME" => GetMessage("SERVICE_COMPANY_STRUCTURE"),
		"STAGES" => Array(
			"types.php", //IBlock types

			"departments.php",
			"absence.php",
			"honour.php",

			//"calendar_groups.php",
			//"calendar_employees.php",
			//"res.php",
			//"group_files.php",
			//"user_files.php",
			//"shared_files.php",
			"user_photogallery.php",
			"group_photogallery.php",
		),
	),
	
	"pull" => Array(
		"NAME" => "Push & Pull",
	),

	"security" => Array(
		"NAME" => "Security",
	),

	"socialnetwork" => Array(
		"NAME" => GetMessage("SERVICE_SOCIALNETWORK"),
	),

	"intranet" => Array(
		"NAME" => GetMessage("SERVICE_INTRANET"),
		"STAGES" => Array(
			"index.php",
			"rating.php",
		)
	),

	"blog" => Array(
		"NAME" => GetMessage("SERVICE_BLOG"),
	),

	"tasks" => Array(
		"NAME" => GetMessage("SERVICE_TASKS"),
	),

	"calendar" => Array(
		"NAME" => GetMessage("SERVICE_CALENDAR"),
		"STAGES" => Array(
			"index.php",
		)
	),

	"fileman" => Array(
		"NAME" => GetMessage("SERVICE_FILEMAN"),
	),

	"wiki" => Array(
		"NAME" => GetMessage("SERVICE_WIKI"),
	),	
	"crm" => Array(
		"NAME" => GetMessage("SERVICE_CRM"),
	),
	"timeman" => Array(
		"NAME" => GetMessage("SERVICE_TIMEMAN"),
	), 
	"vote" => Array(
		"NAME" => GetMessage("SERVICE_VOTE"),
	),

	"mail" => Array(
		"NAME" => GetMessage("SERVICE_MAIL"),
	),
	"disk" => array(
		"NAME" => GetMessage("SERVICE_DISK"),
	),
	"bitrix24" => array(
		"NAME" => GetMessage("SERVICE_BITIRIX24"),
	),
	"voximplant" => array(
		"NAME" => GetMessage("SERVICE_VOXIMPLANT"),
	),
	"lists" => Array(
		"NAME" => GetMessage("SERVICE_LISTS"),
	),
	"socialservices" => Array(
		"NAME" => GetMessage("SERVICE_SOCIALSERVICES"),
	)
);
?>