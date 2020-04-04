<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("INTR_ISIN_COMPONENT_NAME"),
	"DESCRIPTION" => GetMessage("INTR_ISIN_COMPONENT_DESCR"),
	"ICON" => "/images/comp.gif",
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "intranet",
		'NAME' => GetMessage('INTR_GROUP_NAME'),
		"CHILD" => array(
			"ID" => "hr",
			"NAME" => GetMessage("INTR_HR_GROUP_NAME"),
			'CHILD' => array(
				'ID' => 'events',
				'NAME' => GetMessage('INTR_EVENTS_GROUP_NAME'),
			),
		),
	),
);
?>