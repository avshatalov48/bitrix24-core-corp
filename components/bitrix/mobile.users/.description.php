<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("EM_NAME"),
	"DESCRIPTION" => GetMessage("EM_DESCRIPTION"),
	"ICON" => "/images/icon.gif",
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "content",
		"CHILD" => array(
			"ID" => "employes",
			"NAME" => GetMessage("EM_MODULE")
		)
	),
);
?>
