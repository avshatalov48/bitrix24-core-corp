<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentParameters = [
	"PARAMETERS" => [
		"TEMPLATE_MODE" => [
			"NAME" => GetMessage("SOD_TEMPLATE_MODE"),
			"TYPE" => "LIST",
			"MULTIPLE" => "N",
			"VALUES" => [
				'darkmode' => GetMessage("SOD_TEMPLATE_MODE_DARK_VALUE"),
				'lightmode' => GetMessage("SOD_TEMPLATE_MODE_LIGHT_VALUE")
			],
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "BASE",
		],
		"SHOW_HEADER" => [
			"NAME" => GetMessage("SOD_SHOW_HEADER"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"COLS" => 25,
			"PARENT" => "BASE",
		],
		"HEADER_TITLE" => [
			"NAME" => GetMessage("SOD_HEADER_TITLE"),
			"TYPE" => "STRING",
			"DEFAULT" => 'Company 24',
			"PARENT" => "BASE",
		],
	]
];