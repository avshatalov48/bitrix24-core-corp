<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_CREATE_ADS_NAME_MSGVER_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_CREATE_ADS_DESC_MSGVER_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmCreateAdsActivityGa',
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
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'ads',
		'GROUP' => ['ads'],
		'SORT' => 4200,
	],
];
