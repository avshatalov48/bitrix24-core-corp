<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule("controller"))
	return;

if(!ControllerIsSharedMode())
	return false;

$arComponentDescription = array(
	"NAME" => GetMessage("CD_BCSC_NAME"),
	"DESCRIPTION" => GetMessage("CD_BCSC_DESCRIPTION"),
	"ICON" => "/images/1c-imp.gif",
	"CACHE_PATH" => "Y",
	"SORT" => 120,
	"PATH" => array(
		"ID" => "service",
		"CHILD" => array(
			"ID" => "controller",
			"NAME" => GetMessage("CD_BCSC_CONTROLLER"),
			"SORT" => 30,
		),
	),
);

?>