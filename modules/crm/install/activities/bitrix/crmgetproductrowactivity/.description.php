<?php

use Bitrix\Crm\Activity\Access\CatalogAccessChecker;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_BP_GPR_NAME_1'),
	'DESCRIPTION' => Loc::getMessage('CRM_BP_GPR_DESC_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmGetProductRowActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'RETURN' => [
		'RowProductId' => [
			'NAME' => Loc::getMessage('CRM_BP_GPR_RETURN_ROW_PRODUCT_ID'),
			'TYPE' => 'int',
		],
		'RowProductName' => [
			'NAME' => Loc::getMessage('CRM_BP_GPR_RETURN_ROW_PRODUCT_NAME'),
			'TYPE' => 'string',
		],
		'RowPriceAccount' => [
			'NAME' => Loc::getMessage('CRM_BP_GPR_RETURN_ROW_PRICE_ACCOUNT'),
			'TYPE' => 'double',
		],
		'RowQuantity' => [
			'NAME' => Loc::getMessage('CRM_BP_GPR_RETURN_ROW_QUANTITY'),
			'TYPE' => 'double',
		],
		'RowMeasureName' => [
			'NAME' => Loc::getMessage('CRM_BP_GPR_RETURN_ROW_MEASURE_NAME'),
			'TYPE' => 'string',
		],
		'RowDiscountRate' => [
			'NAME' => Loc::getMessage('CRM_BP_GPR_RETURN_ROW_DISCOUNT_RATE'),
			'TYPE' => 'double',
		],
		'RowDiscountSum' => [
			'NAME' => Loc::getMessage('CRM_BP_GPR_RETURN_ROW_DISCOUNT_SUM'),
			'TYPE' => 'double',
		],
		'RowTaxRate' => [
			'NAME' => Loc::getMessage('CRM_BP_GPR_RETURN_ROW_TAX_RATE'),
			'TYPE' => 'double',
		],
		'RowTaxIncluded' => [
			'NAME' => Loc::getMessage('CRM_BP_GPR_RETURN_ROW_TAX_INCLUDED'),
			'TYPE' => 'bool',
		],
		'RowSumAccount' => [
			'NAME' => Loc::getMessage('CRM_BP_GPR_RETURN_ROW_SUM_ACCOUNT'),
			'TYPE' => 'double',
		],
		'RowSumAccountMoney' => [
			'NAME' => Loc::getMessage('CRM_BP_GPR_RETURN_ROW_SUM_ACCOUNT_MONEY'),
			'TYPE' => 'int',
		],
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartInvoice::class],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['delivery', 'goods'],
		'SORT' => 600,
		'IS_SUPPORTING_ROBOT' => true,
	],
];

if (Loader::includeModule('crm') && !CatalogAccessChecker::hasAccess())
{
	$arActivityDescription['EXCLUDED'] = true;
}
