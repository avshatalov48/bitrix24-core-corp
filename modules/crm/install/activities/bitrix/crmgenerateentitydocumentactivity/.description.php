<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_ACTIVITY_GENERATE_ENTITY_DOCUMENT_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_ACTIVITY_GENERATE_ENTITY_DOCUMENT_DESC_1_MSGVER_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmGenerateEntityDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm'],
		],
		'EXCLUDE' => [
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartDocument::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartB2eDocument::class],
		],
	],
	'RETURN' => [
		'DocumentId' => [
			'NAME' => Loc::getMessage('CRM_ACTIVITY_GENERATE_ENTITY_DOCUMENT_ID'),
			'TYPE' => 'int',
		],
		'DocumentUrl' => [
			'NAME' => Loc::getMessage('CRM_ACTIVITY_GENERATE_ENTITY_DOCUMENT_URL'),
			'TYPE' => 'string',
		],
		'DocumentPdf' => [
			'NAME' => Loc::getMessage('CRM_ACTIVITY_GENERATE_ENTITY_PDF_FILE'),
			'TYPE' => 'file',
		],
		'DocumentDocx' => [
			'NAME' => Loc::getMessage('CRM_ACTIVITY_GENERATE_ENTITY_DOCX_FILE'),
			'TYPE' => 'file',
		],
		'DocumentNumber' => [
			'NAME' => Loc::getMessage('CRM_ACTIVITY_GENERATE_ENTITY_DOCUMENT_NUMBER'),
			'TYPE' => 'string',
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['paperwork'],
		'ASSOCIATED_TRIGGERS' => [
			'DOCUMENT_VIEW' => 1,
			'DOCUMENT_CREATE' => 2,
		],
		'SORT' => 1400,
	],
];
if (
	\Bitrix\Main\Loader::includeModule('crm')
	&& !\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->isEnabled()
)
{
	$arActivityDescription['EXCLUDED'] = true;
}
