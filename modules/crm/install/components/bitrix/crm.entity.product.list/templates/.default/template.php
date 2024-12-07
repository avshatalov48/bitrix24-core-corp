<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

/**
 * @var \CCrmEntityProductListComponent $component
 * @var \CBitrixComponentTemplate $this
 * @global \CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */

Extension::load([
	'ui.hint',
	'ui.notification',
	'catalog.product-calculator',
	'ui.design-tokens',
]);

/** @var array $grid */
$grid = &$arResult['GRID'];
/** @var string $gridId */
$gridId = $grid['GRID_ID'];
/** @var array $settings */
$settings = &$arResult['SETTINGS'];
/** @var array $currency */
$currency = &$arResult['CURRENCY'];
/** @var array $measures */
$measures = &$arResult['MEASURES'];
/** @var array $stores */
$stores = &$arResult['STORES'];

$moneyTemplate = [];
$moneyValueIndex = $currency['FORMAT']['TEMPLATE']['VALUE_INDEX'];
foreach ($currency['FORMAT']['TEMPLATE']['PARTS'] as $index => $value)
{
	if ($index == $moneyValueIndex)
	{
		$moneyTemplate[$index] = $value;
	}
	else
	{
		$moneyTemplate[$index] = '<span class="crm-entity-product-info-price-currency">'.$value.'</span>';
	}
}

$taxList = [];
if (!empty($arResult['PRODUCT_VAT_LIST']) && is_array($arResult['PRODUCT_VAT_LIST']))
{
	foreach ($arResult['PRODUCT_VAT_LIST'] as $id => $value)
	{
		$taxList[] = [
			'ID' => $id,
			'VALUE' => $value,
		];
	}
}

$pricePrecision = $arResult['PRICE_PRECISION'];
$quantityPrecision = $arResult['QUANTITY_PRECISION'];
$commonPrecision = $arResult['COMMON_PRECISION'];

$isSetItems = $settings['SET_ITEMS'];
$isReadOnly = !$settings['ALLOW_EDIT'] || $isSetItems;

$containerId = $arResult['PREFIX'].'_crm_entity_product_list_container';

$productTotalContainerId = $arResult['PREFIX'].'_product_sum_total_container';
$rowIdPrefix = $arResult['PREFIX'].'_product_row_';

$jsEventsManagerId = 'PageEventsManager_'.$arResult['COMPONENT_ID'];

$disabledAddRowButton = !$component->checkProductReadRights() && $arResult['CATALOG_ENABLE_EMPTY_PRODUCT_ERROR'];
$disabledSelectProductButton = !$component->checkProductReadRights();

$editorConfig = [
	'componentName' => $component->getName(),
	'signedParameters' => $component->getSignedParameters(),
	'reloadUrl' => '/bitrix/components/bitrix/crm.entity.product.list/list.ajax.php',
	'productUrlBuilderContext' => $arResult['URL_BUILDER_CONTEXT'],

	'containerId' => $containerId,
	'totalBlockContainerId' => $productTotalContainerId,
	'gridId' => $gridId,
	'formId' => $grid['FORM_ID'],
	'entityId' => $arResult['ENTITY']['ID'] ?? 0,
	'entityTypeId' => $arResult['ENTITY']['TYPE_ID'] ?? '',

	'allowEdit' => $settings['ALLOW_EDIT'],
	'allowedStores' => $arResult['ALLOWED_STORES'],
	'allowReservation' => $arResult['ALLOW_RESERVATION'],
	'allowProductView' => $arResult['ALLOW_PRODUCT_VIEW'] ?? null,
	'allowDiscountChange' => $arResult['ALLOW_DISCOUNT_CHANGE'],
	'disabledAddRowButton' => $disabledAddRowButton,
	'disabledSelectProductInput' => $disabledAddRowButton,
	'disabledSelectProductButton' => $disabledSelectProductButton,
	'allowCatalogPriceEdit' => $arResult['ALLOW_CATALOG_PRICE_EDIT'],
	'allowCatalogPriceSave' => $arResult['ALLOW_CATALOG_PRICE_SAVE'],
	'enableEmptyProductError' => $arResult['CATALOG_ENABLE_EMPTY_PRODUCT_ERROR'],
	'enableSelectProductImageInput' => $arResult['IS_SHOW_PRODUCT_IMAGES'],

	'dataFieldName' => $arResult['PRODUCT_DATA_FIELD_NAME'],
	'defaultDateReservation' => $arResult['DEFAULT_DATE_RESERVATION'],

	'rowIdPrefix' => $rowIdPrefix,

	'pricePrecision' => $pricePrecision,
	'quantityPrecision' => $quantityPrecision,
	'commonPrecision' => $commonPrecision,

	'taxList' => $taxList,
	'allowTax' => $arResult['ALLOW_TAX'] ? 'Y' : 'N',
	'enableTax' => $arResult['ENABLE_TAX'] ? 'Y' : 'N',
	'taxUniform' => $arResult['PRODUCT_ROW_TAX_UNIFORM'],
	'isLocationDependantTaxesEnabled' => $arResult['ALLOW_LD_TAX'] ? 'Y' : 'N',
	'locationId' => $arResult['LOCATION_ID'],

	'newRowPosition' => $arResult['NEW_ROW_POSITION'],
	'enableDiscount' => $arResult['ENABLE_DISCOUNT'] ? 'Y' : 'N',

	'measures' => $measures['LIST'],
	'defaultMeasure' => $measures['DEFAULT'],

	'currencyId' => $currency['ID'],

	'popupSettings' => $component->getPopupSettings(),
	'languageId' => $component->getLanguageId(),
	'siteId' => $component->getSiteId(),
	'catalogId' => $arResult['CATALOG_ID'],
	'componentId' => $arResult['COMPONENT_ID'],
	'jsEventsManagerId' => $jsEventsManagerId,

	'readOnly' => $isReadOnly,

	'items' => [],

	'isInventoryManagementToolEnabled' => $arResult['IS_INVENTORY_MANAGEMENT_TOOL_ENABLED'],
	'inventoryManagementMode' => \Bitrix\Catalog\Store\EnableWizard\Manager::getCurrentMode(),
	'isOnecInventoryManagementRestricted' => (
		\Bitrix\Catalog\Store\EnableWizard\Manager::isOnecMode()
		&& \Bitrix\Catalog\Store\EnableWizard\TariffChecker::isOnecInventoryManagementRestricted()
	),
	'isReserveBlocked' => $arResult['IS_RESERVE_BLOCKED'],
	'isReserveEqualProductQuantity' => $arResult['IS_RESERVE_EQUAL_PRODUCT_QUANTITY'],
	'restrictedProductTypes' => $arResult['RESTRICTED_PRODUCT_TYPES'],
];

$productIdMask = '#PRODUCT_ID_MASK#';
$grid['ROWS']['template_0'] = [
	'ID' => $productIdMask,
	'PRODUCT_ID' => null,
	'PARENT_PRODUCT_ID' => null,
	'IBLOCK_ID' => \CCrmCatalog::GetDefaultID(),
	'OFFERS_IBLOCK_ID' => null,
	'OFFER_ID' => null,
	'PRODUCT_NAME' => '',
	'FIXED_PRODUCT_NAME' => '',
	'QUANTITY' => 1,
	'DISCOUNT_TYPE_ID' => Crm\Discount::PERCENTAGE,
	'DISCOUNT_RATE' => 0,
	'DISCOUNT_SUM' => 0,
	'DISCOUNT_ROW' => 0,
	'BASE_PRICE_ID' => $arParams['BASE_PRICE_ID'],
	'NAME' => '',
	'PRICE' => 0,
	'BASE_PRICE' => 0,
	'PRICE_EXCLUSIVE' => 0,
	'PRICE_NETTO' => 0,
	'PRICE_BRUTTO' => 0,
	'CURRENCY' => $arResult['CURRENCY']['ID'],
	'TAX_RATE' => null,
	'TAX_INCLUDED' => 'N',
	'TAX_SUM' => 0,
	'SUM' => 0,
	'STORE_ID' => null,
	'STORE_TITLE' => '',
	'STORE_AVAILABLE' => null,
	'STORE_AMOUNT' => 0,
	'COMMON_STORE_AMOUNT' => 0,
	'COMMON_STORE_RESERVED' => 0,
	'RESERVE_QUANTITY' => null,
	'ROW_RESERVED' => null,
	'INPUT_RESERVE_QUANTITY' => null,
	'DEDUCTED_QUANTITY' => null,
	'DATE_RESERVE' => '',
	'DATE_RESERVE_END' => '',
	'CUSTOMIZED' => 'N',
	'MEASURE_CODE' => $measures['DEFAULT']['CODE'],
	'MEASURE_NAME' => $measures['DEFAULT']['SYMBOL'],
	'MEASURE_EXISTS' => true,
	'SORT' => null,
	'IS_NEW' => 'N',
	'TYPE' => Crm\ProductType::TYPE_PRODUCT,
	'SKU_PROPERTIES' => [],
	'PRODUCT_PROPERTIES' => [],
	'STORE_MAP' => [],
];

$visibleColumnsIds = array_column($grid['VISIBLE_COLUMNS'], 'id');

$rows = [];
foreach ($grid['ROWS'] as $product)
{
	$columns = [];

	$rawProduct = $product;
	$rowId = $rowIdPrefix.$rawProduct['ID'];

	$rawProduct['MEASURE_CODE'] = (string)$rawProduct['MEASURE_CODE'];
	$measureName = htmlspecialcharsbx($rawProduct['MEASURE_NAME']);

	// region editorConfig
	$productName = $rawProduct['PRODUCT_NAME'] ?? '';
	if ($productName === '' && is_numeric($rawProduct['ID']) && $rawProduct['ID'] !== $productIdMask)
	{
		$productName = ((int)$rawProduct['ID'] > 0 && isset($rawProduct['ORIGINAL_PRODUCT_NAME'])
			? $rawProduct['ORIGINAL_PRODUCT_NAME']
			: "[{$rawProduct['ID']}]"
		);
	}

	$fixedProductName = \CCrmProductRow::GetProductTypeName($productName);
	if ($fixedProductName === null)
	{
		$fixedProductName = '';
	}

	$item = [
		'ROW_ID' => $rowId,
		'ID' => $rawProduct['ID'],
		'IBLOCK_ID' => $rawProduct['IBLOCK_ID'],
		'BASE_PRICE_ID' => $rawProduct['BASE_PRICE_ID'],
		'PARENT_PRODUCT_ID' => $rawProduct['PARENT_PRODUCT_ID'],
		'PRODUCT_ID' => $rawProduct['PRODUCT_ID'],
		'OFFERS_IBLOCK_ID' => $rawProduct['OFFERS_IBLOCK_ID'],
		'OFFER_ID' => $rawProduct['OFFER_ID'],
		'PRODUCT_NAME' => $productName,
		'NAME' => $productName,
		// 'IMAGES' => $rawProduct['IMAGES'],
		'FIXED_PRODUCT_NAME' => $fixedProductName,
		'QUANTITY' => $rawProduct['QUANTITY'],
		'DISCOUNT_TYPE_ID' => $rawProduct['DISCOUNT_TYPE_ID'],
		'DISCOUNT_RATE' => $rawProduct['DISCOUNT_RATE'],
		'DISCOUNT_SUM' => $rawProduct['DISCOUNT_SUM'],
		'DISCOUNT_ROW' => $rawProduct['QUANTITY'] * $rawProduct['DISCOUNT_SUM'],
		'BASE_PRICE' => $rawProduct['BASE_PRICE'],
		'PRICE' => $rawProduct['PRICE'],
		'PRICE_EXCLUSIVE' => $rawProduct['PRICE_EXCLUSIVE'],
		'PRICE_NETTO' => $rawProduct['PRICE_NETTO'],
		'PRICE_BRUTTO' => $rawProduct['PRICE_BRUTTO'],
		'CURRENCY' => $rawProduct['CURRENCY'] ?? $arResult['CURRENCY']['ID'],
		'TAX_RATE' => $rawProduct['TAX_RATE'],
		'TAX_INCLUDED' => $rawProduct['TAX_INCLUDED'],
		'TAX_SUM' => $rawProduct['TAX_SUM'],
		'SUM' => $rawProduct['PRICE'] * $rawProduct['QUANTITY'],
		'CUSTOMIZED' => $rawProduct['CUSTOMIZED'],
		'MEASURE_CODE' => $rawProduct['MEASURE_CODE'],
		'MEASURE_NAME' => $rawProduct['MEASURE_NAME'],
		'SORT' => $rawProduct['SORT'],
		'STORE_ID' => $rawProduct['STORE_ID'] ?? null,
		'STORE_TITLE' => $rawProduct['STORE_TITLE'],
		'STORE_AVAILABLE' => $rawProduct['STORE_AVAILABLE'],
		'STORE_AMOUNT' => $rawProduct['STORE_AMOUNT'],
		'COMMON_STORE_AMOUNT' => $rawProduct['COMMON_STORE_AMOUNT'],
		'COMMON_STORE_RESERVED' => $rawProduct['COMMON_STORE_RESERVED'],
		'RESERVE_QUANTITY' => $rawProduct['RESERVE_QUANTITY'],
		'ROW_RESERVED' => $rawProduct['ROW_RESERVED'],
		'INPUT_RESERVE_QUANTITY' => $rawProduct['INPUT_RESERVE_QUANTITY'],
		'DEDUCTED_QUANTITY' => $rawProduct['DEDUCTED_QUANTITY'],
		'DATE_RESERVE' => $rawProduct['DATE_RESERVE'],
		'DATE_RESERVE_END' => $rawProduct['DATE_RESERVE_END'],
		'RESERVE_ID' => $rawProduct['RESERVE_ID'] ?? null,
		'IS_NEW' => $rawProduct['IS_NEW'],
		'SKU_TREE' => $rawProduct['SKU_TREE'] ?? null,
		'DETAIL_URL' => $rawProduct['DETAIL_URL'] ?? null,
		'IMAGE_INFO' => $rawProduct['IMAGE_INFO'] ?? null,
		'STORE_MAP' => $rawProduct['STORE_MAP'] ?? [],
		'TYPE' => $rawProduct['TYPE'] ?? Crm\ProductType::TYPE_PRODUCT,
	];

	// clear overhead fields
	if (!in_array('MAIN_INFO', $visibleColumnsIds, true))
	{
		unset(
			$item['SKU_TREE'],
			$item['IMAGE_INFO'],
			$product['SKU_TREE'],
			$product['IMAGE_INFO'],
		);
	}

	$selectorId = 'crm_grid_'.$rowId;
	if ($rawProduct['ID'] !== $productIdMask)
	{
		$editorConfig['items'][] = [
			'rowId' => $rowId,
			'selectorId' => $selectorId,
			'fields' => $item,
		];
	}
	// endregion editorConfig

	// region MAIN_INFO
	if (in_array('MAIN_INFO', $visibleColumnsIds, true))
	{
		if ($isReadOnly)
		{
			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:catalog.grid.product.field',
				'',
				[
					'IS_NEW' => $rawProduct['IS_NEW'],
					'BUILDER_CONTEXT' => \Bitrix\Crm\Product\Url\ProductBuilder::TYPE_ID,
					'GRID_ID' => $gridId,
					'ROW_ID' => $rowId,
					'GUID' => $selectorId,
					'PRODUCT_FIELDS' => [
						'ID' => $rawProduct['PARENT_PRODUCT_ID'],
						'NAME' => $item['PRODUCT_NAME'],
						'IBLOCK_ID' => $item['IBLOCK_ID'],
						'SKU_IBLOCK_ID' => $item['OFFERS_IBLOCK_ID'],
						'SKU_ID' => $item['OFFER_ID'],
						'BASE_PRICE_ID' => $item['BASE_PRICE_ID'],
					],
					'SKU_TREE' => $rawProduct['SKU_TREE'] ?? null,
					'MODE' => 'view',
					'ENABLE_SEARCH' => false,
					'ENABLE_IMAGE_CHANGE_SAVING' => false,
					'ENABLE_EMPTY_PRODUCT_ERROR' => false,
					'ENABLE_INPUT_DETAIL_LINK' => true,
					'ENABLE_SKU_SELECTION' => false,
					'HIDE_UNSELECTED_ITEMS' => true,
				]
			);
			$mainInfoColumn = '<div class="main-grid-row-number"></div>' . ob_get_clean();
		}
		else
		{
			$mainInfoColumn = HtmlFilter::encode($item['PRODUCT_NAME']);
		}

		$columns['MAIN_INFO'] = $mainInfoColumn;
	}
	// endregion MAIN_INFO

	// region STORE_INFO
	if (in_array('STORE_INFO', $visibleColumnsIds, true))
	{
		$columns['STORE_INFO'] = HtmlFilter::encode($product['STORE_TITLE']);
	}
	// endregion STORE_INFO

	// region PRICE
	$price = $rawProduct['TAX_INCLUDED'] === 'N' ? $rawProduct['PRICE_NETTO'] : $rawProduct['PRICE_BRUTTO'];
	if ($price !== 0)
	{
		$price = number_format($price, $pricePrecision, '.', '');
	}

	$isDisabledPrice =
		!$arResult['ALLOW_CATALOG_PRICE_EDIT']
		&& $rawProduct['IS_NEW'] === 'N'
		&& (int)$rawProduct['OFFER_ID'] > 0
	;

	$product['PRICE'] = [
		'PRICE' => [
			'NAME' => $rowId.'_PRICE',
			'VALUE' => $price,
			'DISABLED' => true,//$isDisabledPrice,
		],
		'CURRENCY' => [
			'NAME' => $rowId.'_PRICE_CURRENCY',
			'VALUE' => $currency['ID'],
			'DISABLED' => true,
		],
	];

	if (in_array('PRICE', $visibleColumnsIds, true))
	{
		$columns['PRICE'] = CCrmCurrency::MoneyToString($price, $currency['ID']);
	}
	// endregion PRICE

	// region QUANTITY
	$product['QUANTITY'] = [
		'PRICE' => [
			'NAME' => $rowId.'_QUANTITY',
			'VALUE' => $rawProduct['QUANTITY'],
		],
		'CURRENCY' => [
			'NAME' => $rowId.'_MEASURE_CODE',
			'VALUE' => $rawProduct['MEASURE_CODE'],
			'DISABLED' => !$arResult['IS_PRODUCT_EDITABLE'] && $rawProduct['PRODUCT_ID'] > 0,
		],
	];

	if (in_array('QUANTITY', $visibleColumnsIds, true))
	{
		$columns['QUANTITY'] = (float)$rawProduct['QUANTITY'] . ' ' . $measureName;
	}
	// endregion QUANTITY

	// region DISCOUNT_PRICE
	if ($rawProduct['DISCOUNT_TYPE_ID'] === Crm\Discount::PERCENTAGE)
	{
		$discountValue = rtrim(rtrim(number_format($rawProduct['DISCOUNT_RATE'], $commonPrecision, '.', ''), '0'), '.');
	}
	else
	{
		$discountValue = rtrim(rtrim(number_format($rawProduct['DISCOUNT_SUM'], $pricePrecision, '.', ''), '0'), '.');
	}

	$product['DISCOUNT_PRICE'] = [
		'PRICE' => [
			'NAME' => $rowId.'_DISCOUNT_PRICE',
			'VALUE' => $discountValue,
		],
		'CURRENCY' => [
			'NAME' => $rowId.'_DISCOUNT_TYPE_ID',
			'VALUE' => $rawProduct['DISCOUNT_TYPE_ID'],
		],
	];

	if (in_array('DISCOUNT_PRICE', $visibleColumnsIds, true))
	{
		$discountColumn = CCrmCurrency::MoneyToString($rawProduct['DISCOUNT_SUM'], $currency['ID']);
		$columns['DISCOUNT_PRICE'] = "<span data-name='DISCOUNT_PRICE'>{$discountColumn}</span>";
	}
	// endregion DISCOUNT_PRICE

	// region DISCOUNT_ROW
	$discountRowValue = (float)$rawProduct['QUANTITY'] * (float)$rawProduct['DISCOUNT_SUM'];
	$discountRowValue = number_format($discountRowValue, $pricePrecision, '.', '');
	$product['DISCOUNT_ROW'] = [
		'PRICE' => [
			'NAME' => $rowId.'_DISCOUNT_ROW',
			'VALUE' => $discountRowValue,
		],
		'CURRENCY' => [
			'NAME' => $rowId.'_DISCOUNT_ROW_CURRENCY',
			'VALUE' => $currency['ID'],
			'DISABLED' => true,
		],
	];

	if (in_array('DISCOUNT_ROW', $visibleColumnsIds, true))
	{
		$discountRowColumn = CCrmCurrency::MoneyToString($discountRowValue, $currency['ID']);
		$columns['DISCOUNT_ROW'] = "<span data-name='DISCOUNT_ROW'>{$discountRowColumn}</span>";
	}
	// end region DISCOUNT_ROW

	// region TAX
	if ($arResult['ALLOW_TAX'])
	{
		// region TAX_RATE
		if (in_array('TAX_RATE', $visibleColumnsIds, true))
		{
			if (isset($rawProduct['TAX_RATE']))
			{
				$taxRateSelected = round((float)$rawProduct['TAX_RATE'], $commonPrecision);
				$columns['TAX_RATE'] = htmlspecialcharsbx($taxRateSelected).' %';
			}
			else
			{
				$taxRateSelected = null;
				$columns['TAX_RATE'] = \CCrmTax::GetVatRateNameByValue($rawProduct['TAX_RATE']);
			}

			$taxRates = $arResult['PRODUCT_VAT_LIST'];

			if (!in_array($taxRateSelected, $taxRates, true))
			{
				$taxRates['custom'] = $taxRateSelected;
			}

			asort($taxRates, SORT_NUMERIC);

			$taxRateHtml = '<select class="crm-entity-product-control-select-field"'
				.' id="'.$rowId.'_TAX_RATE"'
				.' data-field-code="TAX_RATE"'
				.' data-product-field="Y" data-parent-id="'.$rowId.'"'
				.'>';

			foreach ($taxRates as $taxId => $taxRate)
			{
				if (isset($taxRate))
				{
					$taxRate = (float)$taxRate;
					$name = $taxRate.' %';
				}
				else
				{
					$name = \CCrmTax::GetVatRateNameByValue($taxRate);
				}

				$selected = ($taxRateSelected === $taxRate) ? 'selected' : '';
				$taxRate = htmlspecialcharsbx($taxRate);
				$taxRateHtml .= "<option value='{$taxRate}' data-tax-id='{$taxId}' {$selected}>{$name}</option>";
			}

			$taxRateHtml .= '</select>';

			$product['TAX_RATE'] = '<div class="crm-entity-product-control-select">'.$taxRateHtml.'</div>';
		}
		// end region TAX_RATE

		// region TAX_INCLUDED
		if (in_array('TAX_INCLUDED', $visibleColumnsIds, true))
		{
			$columns['TAX_INCLUDED'] =
				$rawProduct['TAX_INCLUDED'] === 'Y'
					? Loc::getMessage('CRM_ENTITY_PL_YES')
					: Loc::getMessage('CRM_ENTITY_PL_NO')
			;
			$product['TAX_INCLUDED'] =
				'<div class="crm-entity-product-control-checkbox">'
					. '<input type="checkbox"'
					. ' id="' . $rowId . '_TAX_INCLUDED"'
					. ' data-field-code="TAX_INCLUDED"'
					. ' data-product-field="Y" data-parent-id="' . $rowId . '"'
					. ($rawProduct['TAX_INCLUDED'] === 'Y' ? ' checked' : '')
					. '>'
					. '</div>'
			;
		}

		// end region TAX_INCLUDED

		// region TAX_SUM
		if (in_array('TAX_SUM', $visibleColumnsIds, true))
		{
			$taxSum = CCrmCurrency::MoneyToString($rawProduct['TAX_SUM'], $currency['ID']);
			$columns['TAX_SUM'] = '<span data-name="TAX_SUM">' . $taxSum . '</span>';
			$product['TAX_SUM'] = '<div class="crm-entity-product-control-tax-sum-field" id="' . $rowId . '_TAX_SUM">' . $taxSum . '</div>';
		}
		// end region TAX_SUM
	}
	// end region TAX

	// region SUM
	$sum = $rawProduct['PRICE'] * $rawProduct['QUANTITY'];
	$sum = number_format($sum, $pricePrecision, '.', '');

	$product['SUM'] = [
		'PRICE' => [
			'NAME' => $rowId.'_SUM',
			'VALUE' => $sum,
		],
		'CURRENCY' => [
			'NAME' => $rowId.'_SUM_CURRENCY',
			'VALUE' => $currency['ID'],
			'DISABLED' => true,
		],
	];

	if (in_array('SUM', $visibleColumnsIds, true))
	{
		$columns['SUM'] = CCrmCurrency::MoneyToString($sum, $currency['ID']);
	}
	// endregion SUM

	// region PURCHASING_PRICE
	if (in_array('PURCHASING_PRICE_FORMATTED', $visibleColumnsIds, true))
	{
		$purchasingPriceColumn = $rawProduct['SKU_PROPERTIES']['PURCHASING_PRICE_FORMATTED'] ?? null;
		$columns['PURCHASING_PRICE_FORMATTED'] = "<span data-name='PURCHASING_PRICE_FORMATTED'>{$purchasingPriceColumn}</a>";
	}
	// endregion PURCHASING_PRICE

	// region RESERVE_INFO
	if (in_array('RESERVE_INFO', $visibleColumnsIds, true))
	{
		$reserveInfo =
			$rawProduct['INPUT_RESERVE_QUANTITY'] !== null
				? $rawProduct['INPUT_RESERVE_QUANTITY'] . " " . $measureName
				: ''
		;
		$columns['RESERVE_INFO'] = "<span data-name='INPUT_RESERVE_QUANTITY'>{$reserveInfo}</span>";
	}
	// endregion RESERVE_INFO

	// region DEDUCTED_INFO
	if (in_array('DEDUCTED_INFO', $visibleColumnsIds, true))
	{
		$deductedInfo =
			$rawProduct['DEDUCTED_QUANTITY'] !== null
				? $rawProduct['DEDUCTED_QUANTITY'] . " " . $measureName
				: ''
		;
		$columns['DEDUCTED_INFO'] = "<span data-name='DEDUCTED_QUANTITY'>{$deductedInfo}</span>";
	}
	// endregion DEDUCTED_INFO

	// region ROW_RESERVED
	if (in_array('ROW_RESERVED', $visibleColumnsIds, true))
	{
		$rowReserved =
			$rawProduct['ROW_RESERVED'] !== null
				? $rawProduct['ROW_RESERVED'] . " " . $measureName
				: ''
		;
		$columns['ROW_RESERVED'] = "<span data-name='ROW_RESERVED'>{$rowReserved}</span>";
	}
	// endregion ROW_RESERVED

	// region STORE_AVAILABLE
	if (in_array('STORE_AVAILABLE', $visibleColumnsIds, true))
	{
		$storeAvailable =
			$rawProduct['STORE_AVAILABLE'] !== null && in_array((int)($rawProduct['STORE_ID'] ?? 0), $component->getAllowedStories(), true)
				? $rawProduct['STORE_AVAILABLE'] . " " . $measureName
				: ''
		;
		$columns['STORE_AVAILABLE'] = "<a href='#' data-name='STORE_AVAILABLE'>{$storeAvailable}</a>";
	}
	// endregion STORE_AVAILABLE

	// region USER_FIELD_COLUMNS
	if (!empty($arResult['USER_FIELD_COLUMNS']))
	{
		foreach ($arResult['USER_FIELD_COLUMNS'] as $propName)
		{
			if (in_array($propName, $visibleColumnsIds, true))
			{
				$value = $rawProduct[$propName] ?? '';
				$columns[$propName] = "<span data-name='{$propName}'>{$value}</a>";
			}
		}
	}
	// endregion USER_FIELD_COLUMNS

	$rows[] = [
		'id' => $rawProduct['ID'] === $productIdMask ? 'template_0' : $rawProduct['ID'],
		'raw_data' => $rawProduct,
		'data' => $product,
		'columns' => $columns,
		'has_child' => $isSetItems,
		'parent_id' => \Bitrix\Main\Grid\Context::isInternalRequest() && !empty($rawProduct['PARENT_ID']) ? $rawProduct['PARENT_ID'] : 0,
		'editable' => !$isSetItems && !$isReadOnly,
	];
}

foreach ($rows as $key => $row)
{
	if ($row['id'] === 'template_0')
	{
		$editorConfig['templateGridEditData']['template_0'] = $row['data'];
		$editorConfig['templateItemFields'] = $row['raw_data'];
		$editorConfig['templateIdMask'] = $productIdMask;
	}
	else
	{
		$editorConfig['templateGridEditData'][$row['id']] = $row['data'];
	}
}

?>
<div class="crm-entity-product-list-wrapper" id="<?=$containerId?>"><?php
	if (!$isReadOnly)
	{
		$panelStatus = ($arResult['NEW_ROW_POSITION'] === 'bottom') ? 'hidden' : 'active';
		$buttonTopPanelClasses = [
			'crm-entity-product-list-add-block',
			'crm-entity-product-list-add-block-top',
			'crm-entity-product-list-add-block-' . $panelStatus,
		];

		$buttonTopPanelClasses = implode(' ', $buttonTopPanelClasses);
		?>
		<div class="<?=$buttonTopPanelClasses?>">
			<div>
				<?php
				$lockedClasses = 'ui-btn-icon-lock ui-btn-disabled';
				$buttonHintAttributes = 'data-hint="' . Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_CATALOG_ERR_ACCESS_DENIED') . '" data-hint-no-icon';
				?>
				<a class="ui-btn ui-btn-primary <?=$disabledAddRowButton ? $lockedClasses : ''?>"
						data-role="product-list-add-button"
						title="<?=Loc::getMessage('CRM_ENTITY_PL_ADD_PRODUCT_TITLE')?>"
						tabindex="-1"
						<?=$disabledAddRowButton ? $buttonHintAttributes : ''?>
				>
					<?=Loc::getMessage('CRM_ENTITY_PL_ADD_PRODUCT')?>
				</a>
				<?php if (!$arResult['IS_EXTERNAL_CATALOG']): ?>
				<a class="ui-btn ui-btn-light-border <?=$disabledSelectProductButton ? $lockedClasses : ''?>"
				   data-role="product-list-select-button"
				   title="<?=Loc::getMessage('CRM_ENTITY_PL_SELECT_PRODUCT_TITLE')?>"
				   tabindex="-1"
					<?=$disabledSelectProductButton ? $buttonHintAttributes : ''?>
				>
					<?=Loc::getMessage('CRM_ENTITY_PL_SELECT_PRODUCT')?>
				</a>
				<?php endif; ?>
			</div>
			<button class="ui-btn ui-btn-light-border ui-btn-icon-setting"
					data-role="product-list-settings-button"></button>
		</div>
		<?php
	}

	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.grid',
		'',
		[
			'GRID_ID' => $gridId,
			'HEADERS' => $grid['COLUMNS'],
			'HEADERS_SECTIONS' => $grid['HEADERS_SECTIONS'],
			// 'ROW_LAYOUT' => $rowLayout,
			'SORT' => $grid['SORT'],
			'SORT_VARS' => $grid['SORT_VARS'],
			'ROWS' => $rows,
			'FORM_ID' => $grid['FORM_ID'],
			'TAB_ID' => $grid['TAB_ID'],
			'AJAX_ID' => $grid['AJAX_ID'],
			'AJAX_MODE' => $grid['AJAX_MODE'],
			'AJAX_OPTION_JUMP' => $grid['AJAX_OPTION_JUMP'],
			'AJAX_OPTION_HISTORY' => $grid['AJAX_OPTION_HISTORY'],
			'AJAX_LOADER' => $grid['AJAX_LOADER'],
			'SHOW_NAVIGATION_PANEL' => $grid['SHOW_NAVIGATION_PANEL'],
			'SHOW_PAGINATION' => $grid['SHOW_PAGINATION'],
			'SHOW_TOTAL_COUNTER' => $grid['SHOW_TOTAL_COUNTER'],
			'SHOW_PAGESIZE' => $grid['SHOW_PAGESIZE'],
			'SHOW_ROW_ACTIONS_MENU' => false,
			'PAGINATION' => $grid['PAGINATION'],
			'ALLOW_SORT' => false,
			'ALLOW_ROWS_SORT' => true,
			'ALLOW_ROWS_SORT_IN_EDIT_MODE' => true,
			'ALLOW_ROWS_SORT_INSTANT_SAVE' => false,
			'ENABLE_ROW_COUNT_LOADER' => false,
			'ENABLE_FIELDS_SEARCH' => 'Y',
			'HIDE_FILTER' => true,
			'ENABLE_COLLAPSIBLE_ROWS' => false,
			'ADVANCED_EDIT_MODE' => true,
			'TOTAL_ROWS_COUNT' => $grid['TOTAL_ROWS_COUNT'],
			'NAME_TEMPLATE' => (string)($arParams['~NAME_TEMPLATE'] ?? ''),
			'ACTION_PANEL' => $grid['ACTION_PANEL'],
			'SHOW_ACTION_PANEL' => !empty($grid['ACTION_PANEL']),
			'SHOW_ROW_CHECKBOXES' => false,
			'SETTINGS_WINDOW_TITLE' => $arResult['ENTITY']['TITLE'],
			'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => (bool)(
				$grid['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] ?? \Bitrix\Main\ModuleManager::isModuleInstalled('ui')
			),
		],
		$component
	);
	if (!$isReadOnly)
	{
		$panelStatus = ($arResult['NEW_ROW_POSITION'] !== 'bottom') ? 'hidden' : 'active';
		$buttonBottomPanelClasses = [
			'crm-entity-product-list-add-block',
			'crm-entity-product-list-add-block-bottom',
			'crm-entity-product-list-add-block-' . $panelStatus,
		];

		$buttonBottomPanelClasses = implode(' ', $buttonBottomPanelClasses);
		?>
		<div class="<?=$buttonBottomPanelClasses?>">
			<div>
				<?php
				$lockedClasses = 'ui-btn-icon-lock ui-btn-disabled';
				$buttonHintAttributes = 'data-hint="' . Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_CATALOG_ERR_ACCESS_DENIED') . '" data-hint-no-icon';
				?>
				<a class="ui-btn ui-btn-primary <?=$disabledAddRowButton ? $lockedClasses : ''?>"
				   data-role="product-list-add-button"
				   title="<?=Loc::getMessage('CRM_ENTITY_PL_ADD_PRODUCT_TITLE')?>"
				   tabindex="-1"
					<?=$disabledAddRowButton ? $buttonHintAttributes : ''?>
				>
					<?=Loc::getMessage('CRM_ENTITY_PL_ADD_PRODUCT')?>
				</a>
				<?php if (!$arResult['IS_EXTERNAL_CATALOG']): ?>
				<a class="ui-btn ui-btn-light-border <?=$disabledSelectProductButton ? $lockedClasses : ''?>"
				   data-role="product-list-select-button"
				   title="<?=Loc::getMessage('CRM_ENTITY_PL_SELECT_PRODUCT_TITLE')?>"
				   tabindex="-1"
					<?=$disabledSelectProductButton ? $buttonHintAttributes : ''?>
				>
					<?=Loc::getMessage('CRM_ENTITY_PL_SELECT_PRODUCT')?>
				</a>
				<?php endif; ?>
			</div>
			<button class="ui-btn ui-btn-light-border ui-btn-icon-setting"
					data-role="product-list-settings-button"></button>
		</div>
		<?php
	}

	function formatTotalAmount($total, $currencyId, $fieldName): string
	{
		$formattedValue =
			'<span class="crm-product-list-result-grid-total" data-total="' . $fieldName . '">'
			. \CCurrencyLang::CurrencyFormat($total, $currencyId, false)
			. '</span>'
		;

		return \CCurrencyLang::getPriceControl($formattedValue, $currencyId);
	}

	?>
	<div class="crm-entity-total-wrapper crm-product-list-page-content">
		<div class="crm-product-list-result-container" id="<?=$productTotalContainerId?>">
			<table class="crm-product-list-payment-side-table">
				<tr class="crm-product-list-payment-side-table-row">
					<td><?=Loc::getMessage('CRM_PRODUCT_TOTAL_BEFORE_DISCOUNT')?>:</td>
					<td class="crm-product-list-payment-side-table-column">
						<?= formatTotalAmount($arResult['TOTAL_BEFORE_DISCOUNT'], $currency['ID'], 'totalWithoutDiscount') ?>
					</td>
				</tr>
				<tr class="crm-product-list-payment-side-table-row">
					<td><?=Loc::getMessage('CRM_DELIVERY_TOTAL')?>:</td>
					<td class="crm-product-list-payment-side-table-column">
						<?= formatTotalAmount($arResult['TOTAL_DELIVERY_SUM'], $currency['ID'], 'totalDelivery') ?>
					</td>
				</tr>
				<tr class="crm-product-list-payment-side-table-row crm-product-list-result-grid-benefit">
					<td>
						<?=Loc::getMessage('CRM_PRODUCT_TOTAL_DISCOUNT')?>:
					</td>
					<td class="crm-product-list-payment-side-table-column">
						<?= formatTotalAmount($arResult['TOTAL_DISCOUNT'], $currency['ID'], 'totalDiscount') ?>
					</td>
				</tr>
				<tr class="crm-product-list-payment-side-table-row">
					<td><?=Loc::getMessage('CRM_PRODUCT_TOTAL_BEFORE_TAX')?>:</td>
					<td class="crm-product-list-payment-side-table-column">
						<?= formatTotalAmount($arResult['TOTAL_BEFORE_TAX'], $currency['ID'], 'totalWithoutTax') ?>
					</td>
				</tr>
				<tr class="crm-product-list-payment-side-table-row">
					<td class="crm-product-list-payment-side-table-td-border">
						<?=Loc::getMessage('CRM_PRODUCT_TOTAL_TAX')?>:
					</td>
					<td class="crm-product-list-payment-side-table-td-border crm-product-list-payment-side-table-column">
						<?= formatTotalAmount($arResult['TOTAL_TAX'], $currency['ID'], 'totalTax') ?>
					</td>
				</tr>
				<tr class="crm-product-list-payment-side-table-row">
					<td class="crm-product-list-result-grid-total-big">
						<?=Loc::getMessage('CRM_PRODUCT_SUM_TOTAL')?>:
					</td>
					<td class="crm-product-list-result-grid-total-big crm-product-list-payment-side-table-column">
						<?= formatTotalAmount($arResult['TOTAL_SUM'], $currency['ID'], 'totalCost') ?>
					</td>
				</tr>
			</table>
		</div>
	</div>
	<input type="hidden" name="<?=htmlspecialcharsbx($arResult['PRODUCT_DATA_FIELD_NAME'])?>" value="" />
	<input type="hidden"
			name="<?=htmlspecialcharsbx($arResult['PRODUCT_DATA_FIELD_NAME'].'_SETTINGS')?>"
			value="" />
</div>
<script>
	BX.message(<?=Json::encode(Loc::loadLanguageFile(__FILE__))?>);
	BX.ready(function() {
		if (!BX.Reflection.getClass('BX.Crm.Entity.ProductList.Instance'))
		{
			BX.Crm.Entity.ProductList.Instance = new BX.Crm.Entity.ProductList.Editor('<?=$arResult['ID']?>');
		}
		BX.Crm.Entity.ProductList.Instance.init(<?=Json::encode($editorConfig)?>);
		BX.Crm["<?=$jsEventsManagerId?>"] = BX.Crm.Entity.ProductList.Instance.getPageEventsManager();
	});
</script>
