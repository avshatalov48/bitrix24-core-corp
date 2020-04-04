<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_CRLC_NAME'),
	'DESCRIPTION' => GetMessage('CRM_CRLC_DESC'),
	'TYPE' => array('activity', 'robot_activity'),
	'CLASS' => 'CrmCreateLeadContactActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'RETURN' => array(
		'ContactId' => array(
			'NAME' => GetMessage('CRM_CRLC_RETURN_CONTACT_ID'),
			'TYPE' => 'int',
		),
	),
	'FILTER' => array(
		'INCLUDE' => array(
			array('crm', 'CCrmDocumentLead')
		),
	),
	'ROBOT_SETTINGS' => array(
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'Responsible'
	),
);