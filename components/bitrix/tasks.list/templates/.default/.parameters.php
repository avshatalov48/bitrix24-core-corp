<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentParameters = Array(
	"PARAMETERS" => Array(
		"TASKS_ALWAYS_EXPANDED" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("TTL_PARAM_TASKS_ALWAYS_EXPANDED"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		),
	)
);