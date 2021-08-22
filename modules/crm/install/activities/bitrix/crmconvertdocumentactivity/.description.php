<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arActivityDescription = [
	'NAME' => GetMessage('CRM_CVTDA_NAME'),
	'DESCRIPTION' => GetMessage('CRM_CVTDA_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmConvertDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentLead'],
			['crm', 'CCrmDocumentDeal'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'Responsible',
	],
];

if (isset($documentType) && $documentType[2] === 'DEAL')
{
	$arActivityDescription['RETURN'] = [
		'InvoiceId' => [
			'NAME' => GetMessage('CRM_CVTDA_RETURN_INVOICE_ID'),
			'TYPE' => 'int',
		],
		'QuoteId' => [
			'NAME' => GetMessage('CRM_CVTDA_RETURN_QUOTE_ID'),
			'TYPE' => 'int',
		],
	];
}

if (isset($documentType) && $documentType[2] === 'LEAD')
{
	$arActivityDescription['RETURN'] = [
		'DealId' => [
			'NAME' => GetMessage('CRM_CVTDA_RETURN_DEAL_ID'),
			'TYPE' => 'int',
		],
		'ContactId' => [
			'NAME' => GetMessage('CRM_CVTDA_RETURN_CONTACT_ID'),
			'TYPE' => 'int',
		],
		'CompanyId' => [
			'NAME' => GetMessage('CRM_CVTDA_RETURN_COMPANY_ID'),
			'TYPE' => 'int',
		],
	];
}
