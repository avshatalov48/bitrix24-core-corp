<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("ITS_NAME"),
	"DESCRIPTION" => GetMessage("ITS_DESCRIPTION"),
	"ICON" => "/images/icon.gif",
	"CACHE_PATH" => "Y",
	"SORT" => 20,
	"PATH" => array(
		"ID" => "content",
		"CHILD" => array(
			"ID" => "INTRANET_TASKS",
			"NAME" => GetMessage("ITS_MODULE")
		)
	)
);
?>