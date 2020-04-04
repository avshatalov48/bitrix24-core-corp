<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arTemplateParameters = array(
	"COLUMNS"=>array(
		"NAME" => GetMessage('INTR_ISS_TPL_PARAM_COLUMNS'),
		"TYPE" => "STRING",
		"DEFAULT" => '2',
	),

	"COLUMNS_FIRST"=>array(
		"NAME" => GetMessage('INTR_ISS_TPL_PARAM_COLUMNS_FIRST'),
		"TYPE" => "STRING",
		"DEFAULT" => '2',
	),

	"MAX_DEPTH"=>array(
		"NAME" => GetMessage('INTR_ISS_TPL_PARAM_MAX_DEPTH'),
		"TYPE" => "STRING",
		"DEFAULT" => '2',
	),
	"MAX_DEPTH_FIRST"=>array(
		"NAME" => GetMessage('INTR_ISS_TPL_PARAM_MAX_DEPTH_FIRST'),
		"TYPE" => "STRING",
		"DEFAULT" => '0',
	),
	
	"SHOW_SECTION_INFO"=>array(
		"NAME" => GetMessage('INTR_ISS_TPL_PARAM_SHOW_SECTION_INFO'),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => 'N',
	),
); 
?>