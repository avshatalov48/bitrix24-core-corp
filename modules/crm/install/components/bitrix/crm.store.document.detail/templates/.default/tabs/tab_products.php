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

$allowEdit = $arResult['ENTITY_DATA']['DEDUCTED'] !== 'Y';

$APPLICATION->IncludeComponent(
	'bitrix:catalog.store.document.product.list',
	'.default',
	[
		'ALLOW_EDIT' => $allowEdit ? 'Y' : 'N',
		'CATALOG_ID' => \Bitrix\Crm\Product\Catalog::getDefaultId(),
		'CURRENCY' => $arResult['ENTITY_DATA']['CURRENCY'] ?? null,
		'BUILDER_CONTEXT' => \Bitrix\Catalog\Url\InventoryBuilder::TYPE_ID,
		'ALLOW_ADD_PRODUCT' => 'Y',
		'ALLOW_CREATE_NEW_PRODUCT' => 'Y',
		'DOCUMENT_ID' => $arResult['DOCUMENT_ID'] ?? null,
		'DOCUMENT_TYPE' => 'REALIZATION',
		'PRODUCT_DATA_FIELD_NAME' => 'DOCUMENT_PRODUCTS',
		'PRODUCTS' => $arResult['COMPONENT_PRODUCTS'],
		'EXTERNAL_DOCUMENT' => [
			'TYPE' => 'REALIZATION',
			'TOTAL_CALCULATION_FIELD' => 'BASE_PRICE',
			'DEFAULT_COLUMNS' =>
				$allowEdit ?
					[
						'MAIN_INFO', 'BARCODE_INFO', 'STORE_FROM_INFO',
						'STORE_FROM_AVAILABLE_AMOUNT', 'STORE_FROM_AMOUNT', 'AMOUNT', 'BASE_PRICE',
					]
					: [
						'MAIN_INFO', 'STORE_FROM_INFO',
						'STORE_FROM_AVAILABLE_AMOUNT', 'STORE_FROM_AMOUNT', 'AMOUNT', 'BASE_PRICE', 'BARCODE_INFO',
					],
			'CUSTOM_COLUMN_NAMES' => [
				'STORE_FROM_INFO' => Loc::getMessage('CRM_STORE_DOCUMENT_DETAIL_COLUMN_STORE_FROM_INFO'),
				'STORE_FROM_AMOUNT' => Loc::getMessage('CRM_STORE_DOCUMENT_DETAIL_COLUMN_STORE_FROM_AMOUNT'),
				'STORE_FROM_AVAILABLE_AMOUNT' => Loc::getMessage('CRM_STORE_DOCUMENT_DETAIL_COLUMN_STORE_FROM_AVAILABLE_AMOUNT'),
			],
			'INITIAL_PRODUCTS' => $arResult['COMPONENT_PRODUCTS'],
		],
		'PRESELECTED_PRODUCT_ID' => $arParams['PRESELECTED_PRODUCT_ID'] ?? null,
	],
	$component
);
