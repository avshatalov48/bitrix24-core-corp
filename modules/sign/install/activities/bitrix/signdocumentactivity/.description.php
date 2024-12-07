<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Sign;
use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('SIGN_ACTIVITIES_SIGN_DOCUMENT_TITLE_1'),
	'DESCRIPTION' => Loc::getMessage('SIGN_ACTIVITIES_SIGN_DOCUMENT_DESCRIPTION_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'SignDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['paperwork'],
		'ASSOCIATED_TRIGGERS' => [
			'SIGN_INITIATOR_SIGNING' => 1,
			'SIGN_OTHER_SIGNING' => 2,
			'SIGN_FINAL_SIGNING' => 3,
		],
		'SORT' => 1300,
	],
	'EXCLUDED' => (
		!Main\Loader::includeModule('sign')
		|| !Sign\Config\Storage::instance()->isAvailable()
	),
];

if (
	Main\Loader::includeModule('bitrix24')
	&& !\Bitrix\Bitrix24\Feature::isFeatureEnabled('sign_automation')
)
{
	$arActivityDescription['LOCKED'] = [
		'INFO_CODE' => 'limit_crm_sign_automation',
	];
}
