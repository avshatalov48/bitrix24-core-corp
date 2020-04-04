<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_CREATE_REQUEST_NAME_NEW'),
	'DESCRIPTION' => GetMessage('CRM_CREATE_REQUEST_DESC_NEW'),
	'TYPE' => array('activity', 'robot_activity'),
	'CLASS' => 'CrmCreateRequestActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'interaction',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'RETURN' => array(
		'Id' => array(
			'NAME' => GetMessage('CRM_CREATE_REQUEST_ID'),
			'TYPE' => 'int',
		),
	),
	'FILTER' => array(
		'INCLUDE' => array(
			['crm', 'CCrmDocumentLead'],
			['crm', 'CCrmDocumentDeal'],
			['crm', 'CCrmDocumentContact'],
			['crm', 'CCrmDocumentCompany'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Order'],
		),
	),
	'ROBOT_SETTINGS' => array(
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'Responsible'
	),
);