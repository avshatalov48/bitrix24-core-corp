<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage('1C82NAME'),
	"DESCRIPTION" => GetMessage('1C82DESC'),
	"ICON" => "/images/icon.gif",
	"SORT" => 240,
	"PATH" => array(
		"ID" => "utility",
		"CHILD" => array(
			"ID" => "one_c",
			"NAME" => GetMessage("REPORT1C_SERVICE")
		)
	),
);
?>