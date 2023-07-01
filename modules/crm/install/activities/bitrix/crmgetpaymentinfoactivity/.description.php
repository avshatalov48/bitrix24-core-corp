<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_BP_GPI_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_BP_GPI_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmGetPaymentInfoActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'RETURN' => [
		'PaymentId' => [
			'NAME' => Loc::getMessage('CRM_BP_GPI_RETURN_PAYMENT_ID'),
			'TYPE' => 'int',
		],
		'PaymentAccountNumber' => [
			'NAME' => Loc::getMessage('CRM_BP_GPI_RETURN_PAYMENT_ACCOUNT_NUMBER'),
			'TYPE' => 'string',
		],
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartInvoice::class],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['payment', 'delivery'],
		'SORT' => 900,
		'IS_SUPPORTING_ROBOT' => true,
	],
];