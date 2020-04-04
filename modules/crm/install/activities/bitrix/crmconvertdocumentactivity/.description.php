<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_CVTDA_NAME'),
	'DESCRIPTION' => GetMessage('CRM_CVTDA_DESC'),
	'TYPE' => array('activity', 'robot_activity'),
	'CLASS' => 'CrmConvertDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'FILTER' => array(
		'INCLUDE' => array(
			array('crm', 'CCrmDocumentLead'),
			array('crm', 'CCrmDocumentDeal'),
		),
	),
	'ROBOT_SETTINGS' => array(
		'CATEGORY' => 'employee'
	),
);