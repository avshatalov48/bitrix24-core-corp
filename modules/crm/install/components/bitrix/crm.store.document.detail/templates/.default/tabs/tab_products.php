<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var $component \CrmStoreDocumentDetailComponent
 * @var $arParams array
 * @var $arResult array
 */

use Bitrix\Main\Localization\Loc;

global $APPLICATION;

$allowEdit = $arResult['ENTITY_DATA']['DEDUCTED'] !== 'Y' && !$arResult['IS_READ_ONLY'];

$APPLICATION->IncludeComponent(
	'bitrix:catalog.store.document.product.list',
	'.default',
	[
		'ALLOW_EDIT' => $allowEdit  ? 'Y' : 'N',
		'CATALOG_ID' => \Bitrix\Crm\Product\Catalog::getDefaultId(),
		'CURRENCY' => $arResult['ENTITY_DATA']['CURRENCY'] ?? null,
		'BUILDER_CONTEXT' => \Bitrix\Catalog\Url\InventoryBuilder::TYPE_ID,
		'ALLOW_ADD_PRODUCT' => 'Y',
		'ALLOW_CREATE_NEW_PRODUCT' => 'Y',
		'DOCUMENT_ID' => $arResult['DOCUMENT_ID'] ?? null,
		'DOCUMENT_TYPE' => \Bitrix\Catalog\StoreDocumentTable::TYPE_SALES_ORDERS,
		'PRODUCT_DATA_FIELD_NAME' => 'DOCUMENT_PRODUCTS',
		'PRODUCTS' => $arResult['COMPONENT_PRODUCTS'],
		'EXTERNAL_DOCUMENT' => [
			'TYPE' => \Bitrix\Catalog\StoreDocumentTable::TYPE_SALES_ORDERS,
			'TOTAL_CALCULATION_FIELD' => 'BASE_PRICE',
			'DEFAULT_COLUMNS' =>
				$allowEdit ?
					[
						'MAIN_INFO', 'BARCODE_INFO', 'STORE_FROM_INFO',
						'STORE_FROM_AVAILABLE_AMOUNT', 'STORE_FROM_AMOUNT', 'AMOUNT', 'BASE_PRICE', 'TOTAL_PRICE',
					]
					: [
						'MAIN_INFO', 'STORE_FROM_INFO',
						'STORE_FROM_AVAILABLE_AMOUNT', 'STORE_FROM_AMOUNT', 'AMOUNT', 'BASE_PRICE', 'BARCODE_INFO', 'TOTAL_PRICE',
					],
			'CUSTOM_COLUMN_NAMES' => [
				'STORE_FROM_INFO' => Loc::getMessage('CRM_STORE_DOCUMENT_DETAIL_COLUMN_STORE_FROM_INFO'),
				'STORE_FROM_AMOUNT' => Loc::getMessage('CRM_STORE_DOCUMENT_DETAIL_COLUMN_STORE_FROM_AMOUNT'),
				'STORE_FROM_AVAILABLE_AMOUNT' => Loc::getMessage('CRM_STORE_DOCUMENT_DETAIL_COLUMN_STORE_FROM_AVAILABLE_AMOUNT'),
			],
			'INITIAL_PRODUCTS' => $arResult['COMPONENT_PRODUCTS'],
			'RESTRICTED_PRODUCT_TYPES' => [
				\Bitrix\Catalog\ProductTable::TYPE_SET,
			],
		],
		'PRESELECTED_PRODUCT_ID' => $arParams['PRESELECTED_PRODUCT_ID'] ?? null,
	],
	$component
);
