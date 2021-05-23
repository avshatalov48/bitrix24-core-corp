<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("CRM_PRODUCT_FILE_NAME"),
	"DESCRIPTION" => GetMessage("CRM_PRODUCT_FILE_DESCRIPTION"),
	"ICON" => "/images/crm_product_file.gif",
	"SORT" => 110,
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "content",
		"CHILD" => array(
			"ID" => "crm",
			"NAME" => GetMessage("CRM_PRODUCT_FILE_CRM"),
			"SORT" => 35,
		)
	),
);

?>