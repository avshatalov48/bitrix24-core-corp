<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("TM_TMR_COMPONENT_NAME"),
	"DESCRIPTION" => GetMessage("TM_TMR_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/comp.gif",
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "intranet",
		'NAME' => GetMessage('INTR_GROUP_NAME'),
		"CHILD" => array(
			"ID" => "timeman",
			"NAME" => GetMessage("TM_GROUP_NAME"),
		)
	)
);
?>