<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_RB_ACTIVITY_NAME'),
	'DESCRIPTION' => GetMessage('CRM_RB_ACTIVITY_DESC'),
	'TYPE' => array('activity', 'robot_activity'),
	'CLASS' => 'CrmResourceBooking',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'FILTER' => array(
		'INCLUDE' => array(
			['crm', 'CCrmDocumentDeal'],
			['crm', 'CCrmDocumentLead'],
			//['crm', 'Bitrix\Crm\Integration\BizProc\Document\Order'],
		),
	),
	'ROBOT_SETTINGS' => array(
		'CATEGORY' => 'employee'
	),
);