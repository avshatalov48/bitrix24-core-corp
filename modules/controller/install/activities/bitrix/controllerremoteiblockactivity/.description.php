<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPCRIA_DESCR_NAME"),
	"DESCRIPTION" => GetMessage("BPCRIA_DESCR_DESCR"),
	"TYPE" => "activity",
	"CLASS" => "ControllerRemoteIBlockActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "document",
	),
	'FILTER' => array(
		'INCLUDE' => array(
			array('iblock', 'CIBlockDocument'),
		)
	)
);
?>
