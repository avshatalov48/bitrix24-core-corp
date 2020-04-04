<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$calendar2 = (COption::GetOptionString("intranet", "calendar_2", "N") == "Y" && CModule::IncludeModule("calendar"));
$arComponentDescription = array(
	"NAME" => GetMessage("EVENT_CALENDAR_LIST").($calendar2 ? GetMessage('EC_DEPRECATED') : ''),
	"DESCRIPTION" => GetMessage("EVENT_CALENDAR_LIST_DESCRIPTION").($calendar2 ? GetMessage('EC_USE_MODULE_CALENDAR') : ''),
	"ICON" => "/images/icon.gif",
	"COMPLEX" => "N",
	"PATH" => array(
		"ID" => "content",
		"CHILD" => array(
			"ID" => "event_calendar",
			"NAME" => GetMessage("EVENT_CALENDAR")
		)
	),
);
?>