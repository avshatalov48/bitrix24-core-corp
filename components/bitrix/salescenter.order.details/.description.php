<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("SPOD_DEFAULT_TEMPLATE_NAME"),
	"DESCRIPTION" => GetMessage("SPOD_DEFAULT_TEMPLATE_DESCRIPTION"),
	"ICON" => "",
	"PATH" => array(
		"ID" => "e-store",
		"CHILD" => array(
			"ID" => "salecenter_order_details",
			"NAME" => GetMessage("SPOD_NAME")
		)
	),
);
?>