<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPDD_DESCR_NAME2"),
	"DESCRIPTION" => GetMessage("BPDD_DESCR_DESCR2"),
	"TYPE" => "activity",
	"CLASS" => "DiskDetailActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "document",
		"OWN_ID" => 'disk',
		"OWN_NAME" => GetMessage("BPDD_DESCR_CATEGORY"),
	),
	"RETURN" => array(
		"ObjectId" => array(
			"NAME" => 'ID',
			"TYPE" => "int",
		),
		"Type" => array(
			"NAME" => GetMessage("BPDD_DESCR_TYPE"),
			"TYPE" => "string",
		),
		"Name" => array(
			"NAME" => GetMessage("BPDD_DESCR_NAME"),
			"TYPE" => "string",
		),
		"SizeBytes" => array(
			"NAME" => GetMessage("BPDD_DESCR_SIZE_BYTES"),
			"TYPE" => "int",
		),
		"SizeFormatted" => array(
			"NAME" => GetMessage("BPDD_DESCR_SIZE_FORMATTED"),
			"TYPE" => "string",
		),
		"DetailUrl" => array(
			"NAME" => GetMessage("BPDD_DESCR_DETAIL_URL"),
			"TYPE" => "string",
		),
		"DownloadUrl" => array(
			"NAME" => GetMessage("BPDD_DESCR_DOWNLOAD_URL"),
			"TYPE" => "string",
		),
	)
);
?>