<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_ACTIVITY_CREATE_LEAD_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ACTIVITY_CREATE_LEAD_DESC'),
	'TYPE' => 'activity',
	'CLASS' => 'CreateCrmLeadDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'RETURN' => array(
		'LeadId' => array(
			'NAME' => GetMessage('CRM_ACTIVITY_CREATE_LEAD_ID'),
			'TYPE' => 'int',
		),
	),
);