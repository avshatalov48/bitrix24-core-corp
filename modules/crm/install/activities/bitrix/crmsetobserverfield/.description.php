<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_SOF_NAME'),
	'DESCRIPTION' => GetMessage('CRM_SOF_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmSetObserverField',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentLead'],
			['crm', 'CCrmDocumentDeal'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Dynamic'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Quote'],
		]
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee'
	]
);