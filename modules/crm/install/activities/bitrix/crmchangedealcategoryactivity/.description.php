<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arActivityDescription = [
	'NAME' => GetMessage('CRM_CDCA_NAME'),
	'DESCRIPTION' => GetMessage('CRM_CDCA_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmChangeDealCategoryActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal']
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee'
	],
];