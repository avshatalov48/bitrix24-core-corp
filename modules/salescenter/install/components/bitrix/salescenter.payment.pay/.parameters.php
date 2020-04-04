<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentParameters = [
	"PARAMETERS" => [
		"TEMPLATE_MODE" => [
			"NAME" => GetMessage("SPP_TEMPLATE_MODE"),
			"TYPE" => "LIST",
			"MULTIPLE" => "N",
			"VALUES" => [
				'darkmode' => GetMessage("SPP_TEMPLATE_MODE_DARK_VALUE"),
				'lightmode' => GetMessage("SPP_TEMPLATE_MODE_LIGHT_VALUE")
			],
			"DEFAULT" => "",
			"COLS" => 25,
			"PARENT" => "BASE",
		]
	]
];
