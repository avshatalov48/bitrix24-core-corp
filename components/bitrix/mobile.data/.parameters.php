<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();


$arComponentParameters = Array(
	"GROUPS" => array(
		"VARIABLE_ALIASES" => array(
			"NAME" => GetMessage("INTL_VARIABLE_ALIASES"),
		),
	),
	"PARAMETERS" => Array(
		"START_PAGE" => Array(
			"NAME" => GetMessage("MB_START_PAGE"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 255,
			"PARENT" => "BASE",
		),
		"AUTH_PAGE" => Array(
			"NAME" => GetMessage("MB_START_PAGE"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 255,
			"PARENT" => "BASE",
		),
		"MENU_PAGE" => Array(
			"NAME" => GetMessage("MB_MENU_PAGE"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 255,
			"PARENT" => "BASE",
		)
	)
);
?>