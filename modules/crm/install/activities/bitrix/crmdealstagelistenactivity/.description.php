<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPCDSA_DESCR_NAME"),
	"DESCRIPTION" => "",
	"TYPE" => "activity",
	"CLASS" => "CrmDealStageListenActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "other",
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	"RETURN" => array(
		"StageSemantics" => array(
			"NAME" => GetMessage("BPCDSA_DESCR_SS"),
			"TYPE" => "string",
		),
		"StageId" => array(
			"NAME" => GetMessage("BPCDSA_DESCR_SI"),
			"TYPE" => "string",
		),
	),
);