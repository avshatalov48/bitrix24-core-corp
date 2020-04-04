<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("MEETINGS_NAME"),
	"DESCRIPTION" => GetMessage("MEETINGS_DESCRIPTION"),
	"ICON" => "/images/meeting.gif",
	"SORT" => 10,
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "intranet",
		'NAME' => GetMessage('INTR_GROUP_NAME'),
		"CHILD" => array(
			"ID" => "meeting",
			"NAME" => GetMessage("INTR_MEETING_GROUP_NAME"),
		),
	),
);

?>