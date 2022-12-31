<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_CVTDA_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_CVTDA_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmConvertDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
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
		'GROUP' => ['payment'],
		'SORT' => 1500,
	],
	'RETURN' => [
		'InvoiceId' => [
			'NAME' => Loc::getMessage('CRM_CVTDA_RETURN_INVOICE_ID'),
			'TYPE' => 'int',
		],
		'QuoteId' => [
			'NAME' => Loc::getMessage('CRM_CVTDA_RETURN_QUOTE_ID'),
			'TYPE' => 'int',
		],
		'DealId' => [
			'NAME' => Loc::getMessage('CRM_CVTDA_RETURN_DEAL_ID'),
			'TYPE' => 'int',
		],
		'ContactId' => [
			'NAME' => Loc::getMessage('CRM_CVTDA_RETURN_CONTACT_ID'),
			'TYPE' => 'int',
		],
		'CompanyId' => [
			'NAME' => Loc::getMessage('CRM_CVTDA_RETURN_COMPANY_ID'),
			'TYPE' => 'int',
		],
	],
];

if (isset($documentType) && $documentType[2] === 'DEAL')
{
	$arActivityDescription['RETURN'] = [
		'InvoiceId' => [
			'NAME' => Loc::getMessage('CRM_CVTDA_RETURN_INVOICE_ID'),
			'TYPE' => 'int',
		],
		'QuoteId' => [
			'NAME' => Loc::getMessage('CRM_CVTDA_RETURN_QUOTE_ID'),
			'TYPE' => 'int',
		],
	];
}

if (isset($documentType) && $documentType[2] === 'LEAD')
{
	$arActivityDescription['RETURN'] = [
		'DealId' => [
			'NAME' => Loc::getMessage('CRM_CVTDA_RETURN_DEAL_ID'),
			'TYPE' => 'int',
		],
		'ContactId' => [
			'NAME' => Loc::getMessage('CRM_CVTDA_RETURN_CONTACT_ID'),
			'TYPE' => 'int',
		],
		'CompanyId' => [
			'NAME' => Loc::getMessage('CRM_CVTDA_RETURN_COMPANY_ID'),
			'TYPE' => 'int',
		],
	];
}
