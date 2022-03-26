<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arActivityDescription = [
	'NAME' => GetMessage('CRM_BP_GPR_NAME'),
	'DESCRIPTION' => GetMessage('CRM_BP_GPR_DESC'),
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
			'NAME' => GetMessage('CRM_BP_GPR_RETURN_ROW_PRODUCT_ID'),
			'TYPE' => 'int',
		],
		'RowProductName' => [
			'NAME' => GetMessage('CRM_BP_GPR_RETURN_ROW_PRODUCT_NAME'),
			'TYPE' => 'string',
		],
		'RowPriceAccount' => [
			'NAME' => GetMessage('CRM_BP_GPR_RETURN_ROW_PRICE_ACCOUNT'),
			'TYPE' => 'double',
		],
		'RowQuantity' => [
			'NAME' => GetMessage('CRM_BP_GPR_RETURN_ROW_QUANTITY'),
			'TYPE' => 'double',
		],
		'RowMeasureName' => [
			'NAME' => GetMessage('CRM_BP_GPR_RETURN_ROW_MEASURE_NAME'),
			'TYPE' => 'string',
		],
		'RowDiscountRate' => [
			'NAME' => GetMessage('CRM_BP_GPR_RETURN_ROW_DISCOUNT_RATE'),
			'TYPE' => 'double',
		],
		'RowDiscountSum' => [
			'NAME' => GetMessage('CRM_BP_GPR_RETURN_ROW_DISCOUNT_SUM'),
			'TYPE' => 'double',
		],
		'RowTaxRate' => [
			'NAME' => GetMessage('CRM_BP_GPR_RETURN_ROW_TAX_RATE'),
			'TYPE' => 'double',
		],
		'RowTaxIncluded' => [
			'NAME' => GetMessage('CRM_BP_GPR_RETURN_ROW_TAX_INCLUDED'),
			'TYPE' => 'bool',
		],
		'RowSumAccount' => [
			'NAME' => GetMessage('CRM_BP_GPR_RETURN_ROW_SUM_ACCOUNT'),
			'TYPE' => 'double',
		],
		'RowSumAccountMoney' => [
			'NAME' => GetMessage('CRM_BP_GPR_RETURN_ROW_SUM_ACCOUNT_MONEY'),
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
	],
];