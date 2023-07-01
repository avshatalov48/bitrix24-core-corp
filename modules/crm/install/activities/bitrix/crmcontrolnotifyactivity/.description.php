<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_CTRNA_DESCR_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_CTRNA_DESCR_DESCR_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmControlNotifyActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => "interaction",
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
			['crm', 'CCrmDocumentLead'],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Order::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Invoice::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Dynamic::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartInvoice::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Quote::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartDocument::class],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'ToUsers',
		'RESPONSIBLE_TO_HEAD' => 'ToHead',
		'GROUP' => ['employeeControl'],
		'SORT' => 1800,
	],
];