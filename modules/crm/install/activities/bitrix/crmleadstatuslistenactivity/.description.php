<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPCLSLA_DESCR_NAME_MSGVER_1"),
	"DESCRIPTION" => "",
	"TYPE" => "activity",
	"CLASS" => "CrmLeadStatusListenActivity",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "other",
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	"RETURN" => array(
		"StatusSemantics" => array(
			"NAME" => GetMessage("BPCLSLA_DESCR_SS_MSGVER_1"),
			"TYPE" => "string",
		),
		"StatusId" => array(
			"NAME" => GetMessage("BPCLSLA_DESCR_SI_MSGVER_1"),
			"TYPE" => "string",
		),
	),
);