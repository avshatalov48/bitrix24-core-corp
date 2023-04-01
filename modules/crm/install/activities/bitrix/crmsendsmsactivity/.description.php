<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_SSMSA_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_SSMSA_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmSendSmsActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentLead'],
			['crm', 'CCrmDocumentDeal'],
			['crm', 'CCrmDocumentContact'],
			['crm', 'CCrmDocumentCompany'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Order'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Dynamic'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Quote'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\SmartInvoice'],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartDocument::class],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => ['employee', 'client'],
		'TITLE_CATEGORY' => [
			'employee' => Loc::getMessage('CRM_SSMSA_ROBOT_TITLE_EMPLOYEE_1'),
			'client' => Loc::getMessage('CRM_SSMSA_ROBOT_TITLE_CLIENT_1'),
		],
		'DESCRIPTION_CATEGORY' => [
			'employee' => Loc::getMessage('CRM_SSMA_ROBOT_DESCRIPTION_EMPLOYEE'),
			'client' => Loc::getMessage('CRM_SSMA_ROBOT_DESCRIPTION_CLIENT'),
		],
		'GROUP' => ['clientCommunication', 'informingEmployee', 'delivery'],
		'TITLE_GROUP' => [
			'clientCommunication' => Loc::getMessage('CRM_SSMSA_ROBOT_TITLE_CLIENT_1'),
			'informingEmployee' => Loc::getMessage('CRM_SSMSA_ROBOT_TITLE_EMPLOYEE_1'),
			'delivery' => Loc::getMessage('CRM_SSMSA_ROBOT_TITLE_CLIENT_1'),
		],
		'DESCRIPTION_GROUP' => [
			'clientCommunication' => Loc::getMessage('CRM_SSMA_ROBOT_DESCRIPTION_CLIENT'),
			'informingEmployee' => Loc::getMessage('CRM_SSMA_ROBOT_DESCRIPTION_EMPLOYEE'),
			'delivery' => Loc::getMessage('CRM_SSMA_ROBOT_DESCRIPTION_CLIENT'),
		],
		'SORT' => 1300,
	],
];