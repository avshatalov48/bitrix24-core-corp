<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$successfullyConverted = \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false);
$arDescription = Array(
		"NAME"=>GetMessage("GD_SHARED_DOCS_NAME"),
		"DESCRIPTION"=>GetMessage("GD_SHARED_DOCS_DESC"),
		"ICON"=>"",
		"GROUP"=> Array("ID"=>"communications"),
		"WEBDAV_ONLY"=> $successfullyConverted !== 'Y',
		"DISK_ONLY"=> $successfullyConverted === 'Y',
		"COMMON_DOCS_ONLY"=> true,
		"CAN_BE_FIXED"=> true,
	);
?>