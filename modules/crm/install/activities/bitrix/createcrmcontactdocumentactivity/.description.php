<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arActivityDescription = [
	'NAME' => GetMessage('CRM_ACTIVITY_CREATE_CONTACT_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ACTIVITY_CREATE_CONTACT_DESC'),
	'TYPE' => 'activity',
	'CLASS' => 'CreateCrmContactDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	],
	'RETURN' => [
		'ContactId' => [
			'NAME' => GetMessage('CRM_ACTIVITY_CREATE_CONTACT_ID'),
			'TYPE' => 'int',
		],
		'ErrorMessage' => [
			'NAME' => GetMessage('CRM_ACTIVITY_CREATE_ERROR_MESSAGE'),
			'TYPE' => 'string',
		],
	],
];