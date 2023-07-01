<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arActivityDescription = [
	'NAME' => GetMessage('CRM_ACTIVITY_CREATE_DEAL_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ACTIVITY_CREATE_DEAL_DESC'),
	'TYPE' => 'activity',
	'CLASS' => 'CreateCrmDealDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	],
	'RETURN' => [
		'DealId' => [
			'NAME' => GetMessage('CRM_ACTIVITY_CREATE_DEAL_ID'),
			'TYPE' => 'int',
		],
		'ErrorMessage' => [
			'NAME' => GetMessage('CRM_ACTIVITY_CREATE_MESSAGE'),
			'TYPE' => 'string',
		],
	],
];