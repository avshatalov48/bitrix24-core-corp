<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_WAIT_ACTIVITY_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_WAIT_ACTIVITY_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmWaitActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
			['crm', 'CCrmDocumentLead'],
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Order'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['repeatSales', 'other'],
		'SORT' => 3500,
	],
];

if (
	Main\Loader::includeModule('crm')
	&& !Crm\Settings\ActivitySettings::areOutdatedCalendarActivitiesEnabled()
)
{
	$arActivityDescription['EXCLUDED'] = true;
}
