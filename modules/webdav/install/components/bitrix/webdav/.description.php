<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("WD_NAME"),
	"DESCRIPTION" => GetMessage("WD_DESCRIPTION"),
	"ICON" => "/images/icon.gif",
	"COMPLEX" => "Y",
	"CACHE_PATH" => "Y",
	"SORT" => 20,
	"PATH" => array(
		"ID" => "content",
		"CHILD" => array(
			"ID" => "webdav",
			"NAME" => GetMessage("WD_MODULE")
		)
	)
);
?>