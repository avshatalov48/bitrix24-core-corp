<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_CDA_NAME'),
	'DESCRIPTION' => GetMessage('CRM_CDA_DESC'),
	'TYPE' => array('activity', 'robot_activity'),
	'CLASS' => 'CrmCopyDealActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'RETURN' => array(
		'DealId' => array(
			'NAME' => GetMessage('CRM_CDA_RETURN_DEAL_ID'),
			'TYPE' => 'int',
		),
	),
	'FILTER' => array(
		'INCLUDE' => array(
			array('crm', 'CCrmDocumentDeal')
		),
	),
	'ROBOT_SETTINGS' => array(
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'Responsible'
	),
);