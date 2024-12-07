<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_RMPR_NAME_MSGVER_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_RMPR_DESC_2_MSGVER_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmRemoveProductRow',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartInvoice::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Quote::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Order::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartDocument::class],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['goods'],
		'SORT' => 700,
	],
];

if (\Bitrix\Main\Loader::includeModule('crm'))
{
	if (
		isset($documentType)
		&& $documentType[0] === 'crm'
		&& CCrmBizProcHelper::isDynamicEntityWithProducts(CCrmOwnerType::ResolveID((string)$documentType[2]))
	)
	{
		$arActivityDescription['FILTER']['INCLUDE'][] = ['crm', \Bitrix\Crm\Integration\BizProc\Document\Dynamic::class];
	}
}
