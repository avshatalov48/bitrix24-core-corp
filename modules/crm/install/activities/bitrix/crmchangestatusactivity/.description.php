<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

$phrase = 'CRM_CHANGE_STATUS_NAME';
if (
	isset($documentType)
	&& (
		$documentType[2] === 'DEAL'
		|| mb_strpos($documentType[2], 'DYNAMIC') === 0
	)
)
{
	$phrase = 'CRM_CHANGE_DEAL_STAGE_NAME';
}

$arActivityDescription = [
	'NAME'           => GetMessage('CRM_CHANGE_STATUS_NAME'),
	'DESCRIPTION'    => GetMessage('CRM_CHANGE_STATUS_DESC_1'),
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
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Dynamic'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Quote'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\SmartInvoice'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'TITLE'    => GetMessage($phrase),
	],
];
