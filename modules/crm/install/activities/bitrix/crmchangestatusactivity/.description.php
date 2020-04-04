<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

$arActivityDescription = [
	'NAME'           => GetMessage('CRM_CHANGE_STATUS_NAME'),
	'DESCRIPTION'    => GetMessage('CRM_CHANGE_STATUS_DESC'),
	'TYPE'           => ['activity', 'robot_activity'],
	'CLASS'          => 'CrmChangeStatusActivity',
	'JSCLASS'        => 'BizProcActivity',
	'CATEGORY'       => [
		'ID'       => 'document',
		"OWN_ID"   => 'crm',
		"OWN_NAME" => 'CRM',
	],
	'FILTER'         => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
			['crm', 'CCrmDocumentLead'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Order'],
			//['crm', 'Bitrix\Crm\Integration\BizProc\Document\Invoice'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'TITLE'    => (isset($documentType) && $documentType[2] === 'DEAL') ? GetMessage('CRM_CHANGE_DEAL_STAGE_NAME') : GetMessage('CRM_CHANGE_STATUS_NAME')
	],
];