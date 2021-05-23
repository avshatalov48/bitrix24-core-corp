<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("dav_synchronize_comp_name"),
	"DESCRIPTION" => GetMessage("dav_synchronize_comp_desc"),
	"PATH" => array(
		"ID" => "utility",
		"CHILD" => array(
			"ID" => "user",
			"NAME" => GetMessage("dav_synchronize_comp_user")
		)
	),
);
