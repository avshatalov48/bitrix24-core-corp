<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPCDA_DESCR_NAME"),
	"DESCRIPTION" => GetMessage("BPCDA_DESCR_DESCR"),
	"TYPE" => "activity",
	"CLASS" => "CreateDocumentActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "document",
	),
	'FILTER' => array(
		'EXCLUDE' => array(
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Order'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Invoice'],
			['tasks'],
		)
	)
);