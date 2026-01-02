<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Sign;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Config\Feature;

$arActivityDescription = [
	'NAME' => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_TITLE'),
	'DESCRIPTION' => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_DESCRIPTION'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'SignB2EDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Dynamic::class],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['paperwork'],
		'IS_SUPPORTING_ROBOT' => false,
		'RESPONSIBLE_PROPERTY' => 'employee',
		'SORT' => 1300,
	],
	'EXCLUDED' => (
		!Main\Loader::includeModule('sign')
		|| !Main\Loader::includeModule('crm')
		|| !Sign\Config\Storage::instance()->isB2eAvailable()
		|| !Feature::instance()->isB2eRobotEnabled()
	),
	'RETURN' => [
		'documentId' => [
			'NAME' => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_ID'),
			'TYPE' => 'int',
		],
		'documentStatus' => [
			'NAME' => Loc::getMessage('SIGN_ACTIVITIES_SIGN_B2E_DOCUMENT_STATUS'),
			'TYPE' => 'string',
		],
	],
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
