<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("T_IBLOCK_DESC_LIST"),
	"DESCRIPTION" => GetMessage("T_IBLOCK_DESC_LIST_DESC"),
	"ICON" => "/images/icon.gif",
	"CACHE_PATH" => "Y",
	"SORT" => 20,
	"PATH" => array(
		"ID" => "content",
		"CHILD" => array(
			"ID" => "webdav",
			"NAME" => GetMessage("T_IBLOCK_DESC_DOCS")
		)
	)
);
?>