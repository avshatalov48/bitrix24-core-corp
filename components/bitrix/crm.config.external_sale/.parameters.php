<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;
			
$arComponentParameters = array(
	"PARAMETERS" => array(
		"VARIABLE_ALIASES" => array(
		),
		"SEF_MODE" => array(
			"index" => array(
				"NAME" => GetMessage("CRM_BPWC_WP_SEF_INDEX"),
				"DEFAULT" => "index.php",
				"VARIABLES" => array(),
			),
			"edit" => array(
				"NAME" => GetMessage("CRM_BPWC_WP_SEF_VIEW"),
				"DEFAULT" => "edit-#id#.php",
				"VARIABLES" => array(),
			),
			"sync" => array(
				"NAME" => GetMessage("CRM_BPWC_WP_SEF_SYNC"),
				"DEFAULT" => "sync-#id#.php",
				"VARIABLES" => array(),
			),
		),
		"AJAX_MODE" => array(),
	),
);
?>