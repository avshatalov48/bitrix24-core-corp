<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$arComponentDescription = array(
	"NAME" => GetMessage("INTRANET_TASKS_CREATE"),
	"DESCRIPTION" => GetMessage("INTRANET_TASKS_CREATE_DESCRIPTION"),
	"ICON" => "/images/icon.gif",
	"COMPLEX" => "N",
	"PATH" => array(
		"ID" => "content",
		"CHILD" => array(
			"ID" => "INTRANET_TASKS",
			"NAME" => GetMessage("INTRANET_TASKS")
		)
	),
);
?>