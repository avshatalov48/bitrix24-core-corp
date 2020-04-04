<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_ACTIVITY_CREATE_CONTACT_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ACTIVITY_CREATE_CONTACT_DESC'),
	'TYPE' => 'activity',
	'CLASS' => 'CreateCrmContactDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'RETURN' => array(
		'ContactId' => array(
			'NAME' => GetMessage('CRM_ACTIVITY_CREATE_CONTACT_ID'),
			'TYPE' => 'int',
		),
	),
);