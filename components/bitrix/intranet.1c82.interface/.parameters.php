<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$arComponentParameters = Array(
	"GROUPS" => array(
		"VARIABLE_ALIASES" => array(
			"NAME" => '1C',
		),
	),
	"PARAMETERS" => Array(
		
"1C_URL" => Array(
			"NAME" => GetMessage('1C_URL'),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
"LOGIN" => Array(
			"NAME" => GetMessage('1C_LOGIN'),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "ADDITIONAL_SETTINGS",
		),		
"PASS" => Array(
			"NAME" => GetMessage('1C_PASS'),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "ADDITIONAL_SETTINGS",
		),	

"NAME" => Array(
			"NAME" => GetMessage('1C_NAME'),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "ADDITIONAL_SETTINGS",
		),
"BLANK_MODE" => Array(
			"NAME" => GetMessage('1C_BLANK_MODE'),
			"TYPE" => "CHECKBOX",
			"MULTIPLE" => "N",
			"DEFAULT" => "",
			"PARENT" => "ADDITIONAL_SETTINGS",
		),

"SET_TITLE" => Array(),
	)
   );
?>
