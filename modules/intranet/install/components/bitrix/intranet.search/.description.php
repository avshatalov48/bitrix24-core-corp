<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("INTR_IS_COMPONENT_NAME"),
	"DESCRIPTION" => GetMessage("INTR_IS_COMPONENT_DESCR"),
	"ICON" => "/images/comp.gif",
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "intranet",
		'NAME' => GetMessage('INTR_GROUP_NAME'),
		"CHILD" => array(
			"ID" => "structure",
			"NAME" => GetMessage("INTR_STRUCTURE_GROUP_NAME"),
		)
	),
	'COMPLEX' => 'Y',
);
?>