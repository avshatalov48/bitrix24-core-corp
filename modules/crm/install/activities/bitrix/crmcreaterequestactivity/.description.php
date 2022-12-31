<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_CREATE_REQUEST_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_CREATE_REQUEST_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmCreateRequestActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'interaction',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'RETURN' => [
		'Id' => [
			'NAME' => Loc::getMessage('CRM_CREATE_REQUEST_ID'),
			'TYPE' => 'int',
		],
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentLead'],
			['crm', 'CCrmDocumentDeal'],
			['crm', 'CCrmDocumentContact'],
			['crm', 'CCrmDocumentCompany'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Order'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'RESPONSIBLE_PROPERTY' => 'Responsible',
		'GROUP' => ['employeeControl'],
		'SORT' => 2000,
	],
];

if (
	Main\Loader::includeModule('crm')
	&& Crm\Settings\Crm::isUniversalActivityScenarioEnabled()
)
{
	$arActivityDescription['EXCLUDED'] = true;
}
