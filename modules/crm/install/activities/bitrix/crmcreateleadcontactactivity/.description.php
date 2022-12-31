<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_CRLC_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_CRLC_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmCreateLeadContactActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'RETURN' => [
		'ContactId' => [
			'NAME' => Loc::getMessage('CRM_CRLC_RETURN_CONTACT_ID'),
			'TYPE' => 'int',
		],
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentLead'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'Responsible',
		'GROUP' => ['clientData'],
		'SORT' => 4400,
	],
];