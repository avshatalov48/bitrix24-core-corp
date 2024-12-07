<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\Bitrix24;

$arActivityDescription = [
	'NAME' => Loc::getMessage('BPTA2_DESCR_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('BPTA2_DESCR_DESCR_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'Task2Activity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'interaction',
	],
	'RETURN' => [
		'TaskId' => [
			'NAME' => Loc::getMessage('BPTA2_DESCR_TASKID'),
			'TYPE' => 'int',
		],
		'ClosedDate' => [
			'NAME' => Loc::getMessage('BPTA2_DESCR_CLOSEDDATE'),
			'TYPE' => 'datetime',
		],
		'ClosedBy' => [
			'NAME' => Loc::getMessage('BPTA2_DESCR_CLOSEDBY'),
			'TYPE' => 'user',
		],
		'IsDeleted' => [
			'NAME' => Loc::getMessage('BPTA2_DESCR_IS_DELETED'),
			'TYPE' => 'bool',
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'Fields.RESPONSIBLE_ID',
		'GROUP' => ['employeeControl', 'taskManagement'],
		'ASSOCIATED_TRIGGERS' => [
			'TASK_STATUS' => 1,
		],
		'SORT' => 2100,
	],
	'EXCLUDED' => (!\Bitrix\Main\Loader::includeModule('tasks')),
];

if (
	isset($documentType)
	&& $documentType[0] === 'crm'
	&& !Bitrix24::checkFeatureEnabled(Bitrix24\FeatureDictionary::TASK_CRM_INTEGRATION)
)
{
	$arActivityDescription['LOCKED'] = [
		'INFO_CODE' => \Bitrix\Tasks\Helper\RestrictionUrl::TASK_LIMIT_CRM_INTEGRATION,
	];
}
