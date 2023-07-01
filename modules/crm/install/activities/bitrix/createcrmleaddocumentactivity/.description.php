<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arActivityDescription = [
	'NAME' => GetMessage('CRM_ACTIVITY_CREATE_LEAD_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ACTIVITY_CREATE_LEAD_DESC'),
	'TYPE' => 'activity',
	'CLASS' => 'CreateCrmLeadDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	],
	'RETURN' => [
		'LeadId' => [
			'NAME' => GetMessage('CRM_ACTIVITY_CREATE_LEAD_ID'),
			'TYPE' => 'int',
		],
		'ErrorMessage' => [
			'NAME' => GetMessage('CRM_ACTIVITY_CREATE_ERROR_MESSAGE'),
			'TYPE' => 'string',
		],
	],
];