<?php

use Bitrix\Crm\Activity\Access\CatalogAccessChecker;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$returnProperties = [];
if (Loader::includeModule('crm'))
{
	$compatibilityMap = [
		'PRODUCT_ID' => 'RowProductId',
		'PRODUCT_NAME' => 'RowProductName',
		'PRICE_ACCOUNT' => 'RowPriceAccount',
		'QUANTITY' => 'RowQuantity',
		'MEASURE_NAME' => 'RowMeasureName',
		'DISCOUNT_RATE' => 'RowDiscountRate',
		'DISCOUNT_SUM' => 'RowDiscountSum',
		'TAX_RATE' => 'RowTaxRate',
		'TAX_INCLUDED' => 'RowTaxIncluded',
		'SUM_ACCOUNT' => 'RowSumAccount',
		'PRINTABLE_SUM_ACCOUNT' => 'RowSumAccountMoney',
	];
	foreach (\Bitrix\Crm\Automation\Connectors\Product::getFieldsMap() as $fieldId => $field)
	{
		$returnProperties[$compatibilityMap[$fieldId] ?? $fieldId] = [
			'NAME' => $field['Name'],
			'TYPE' => $field['Type'],
		];

		if ($fieldId === 'SUM_ACCOUNT')
		{
			$returnProperties['RowSumAccountMoney'] = [
				'NAME' => Loc::getMessage('CRM_BP_GPR_RETURN_ROW_SUM_ACCOUNT_MONEY'),
				'TYPE' => \Bitrix\Bizproc\FieldType::STRING,
			];
		}
	}
}

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_BP_GPR_NAME_2'),
	'DESCRIPTION' => Loc::getMessage('CRM_BP_GPR_DESC_2'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmGetProductRowActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'RETURN' => $returnProperties,
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartInvoice::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Quote::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Order::class],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['delivery', 'goods'],
		'SORT' => 600,
		'IS_SUPPORTING_ROBOT' => true,
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
