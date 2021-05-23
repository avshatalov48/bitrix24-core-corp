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
		],
		"ALLOW_PAYMENT_REDIRECT" => [
			"NAME" => GetMessage("SPP_ALLOW_PAYMENT_REDIRECT"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"COLS" => 25,
			"PARENT" => "BASE",
		]
	]
];
