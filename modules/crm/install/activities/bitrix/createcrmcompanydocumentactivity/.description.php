<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arActivityDescription = [
	'NAME' => GetMessage('CRM_ACTIVITY_CREATE_COMPANY_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ACTIVITY_CREATE_COMPANY_DESC'),
	'TYPE' => 'activity',
	'CLASS' => 'CreateCrmCompanyDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	],
	'RETURN' => [
		'CompanyId' => [
			'NAME' => GetMessage('CRM_ACTIVITY_CREATE_COMPANY_ID'),
			'TYPE' => 'int',
		],
		'ErrorMessage' => [
			'NAME' => GetMessage('CRM_ACTIVITY_CREATE_ERROR_MESSAGE'),
			'TYPE' => 'string',
		],
	],
];