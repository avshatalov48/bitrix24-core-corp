<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("DISK_VOLUME_NAME"),
	"DESCRIPTION" => GetMessage("DISK_VOLUME_DESC"),
	"ICON" => "/images/disk-volume.gif",
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "service",
		"CHILD" => array(
			"ID" => "disk-volume",
			"NAME" => GetMessage("DISK_SERVICE")
		)
	),
);
?>