<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_CREATE_CALL_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_CREATE_CALL_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmCreateCallActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'interaction',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'RETURN' => [
		'Id' => [
			'NAME' => Loc::getMessage('CRM_CREATE_CALL_ID'),
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
		'GROUP' => ['repeatSales'],
		'SORT' => 3400,
	],
];

if (
	Main\Loader::includeModule('crm')
	&& !Crm\Settings\ActivitySettings::areOutdatedCalendarActivitiesEnabled()
)
{
	$arActivityDescription['EXCLUDED'] = true;
}
