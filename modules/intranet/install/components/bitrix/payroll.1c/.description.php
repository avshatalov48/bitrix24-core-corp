<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("PAYROLL_NAME"),
	"DESCRIPTION" => GetMessage("PAYROLL_DESC"),
	"ICON" => "/images/icon.gif",
	"COMPLEX" => "N",
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "utility",
		"CHILD" => array(
			"ID" => "payroll",
			"NAME" => GetMessage("PAYROLL_WS")
		)
	),
);
?>