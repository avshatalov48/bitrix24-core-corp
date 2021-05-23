<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("EBMNP_DEFAULT_TEMPLATE_NAME"),
	"DESCRIPTION" => GetMessage("EBMNP_DEFAULT_TEMPLATE_DESCRIPTION"),
	"ICON" => "/images/icon.gif",
	"SORT" => 120,
	"PATH" => array(
		"ID" => "communication",
		"CHILD" => array(
			"ID" => "blog",
			"NAME" => GetMessage("EBMNP_NAME")
		)
	),
);
?>