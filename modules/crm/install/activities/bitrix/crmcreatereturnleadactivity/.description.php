<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_CRL_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_CRL_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmCreateReturnLeadActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'RETURN' => [
		'LeadId' => [
			'NAME' => Loc::getMessage('CRM_CRL_RETURN_LEAD_ID'),
			'TYPE' => 'int',
		],
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'Responsible',
		'GROUP' => ['repeatSales'],
		'SORT' => 3100,
	],
];