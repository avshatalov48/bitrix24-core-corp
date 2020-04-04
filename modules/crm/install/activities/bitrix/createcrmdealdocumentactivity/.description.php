<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_ACTIVITY_CREATE_DEAL_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ACTIVITY_CREATE_DEAL_DESC'),
	'TYPE' => 'activity',
	'CLASS' => 'CreateCrmDealDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'RETURN' => array(
		'DealId' => array(
			'NAME' => GetMessage('CRM_ACTIVITY_CREATE_DEAL_ID'),
			'TYPE' => 'int',
		),
	),
);