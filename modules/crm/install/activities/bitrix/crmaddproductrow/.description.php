<?php

use Bitrix\Crm\Activity\Access\CatalogAccessChecker;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_APR_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_APR_DESC_2'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmAddProductRow',
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
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartDocument::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Quote::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Order::class],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['goods'],
		'SORT' => 400,
	],
];

if (Loader::includeModule('crm'))
{
	if (!CatalogAccessChecker::hasAccess())
	{
		$arActivityDescription['EXCLUDED'] = true;
	}
	elseif (isset($documentType) && $documentType[0] === 'crm')
	{
		if (CCrmBizProcHelper::isDynamicEntityWithProducts(CCrmOwnerType::ResolveID((string)$documentType[2])))
		{
			$arActivityDescription['FILTER']['INCLUDE'][] = ['crm', \Bitrix\Crm\Integration\BizProc\Document\Dynamic::class];
		}
	}
}
