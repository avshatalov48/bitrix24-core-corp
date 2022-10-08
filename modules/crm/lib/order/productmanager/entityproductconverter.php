<?php

namespace Bitrix\Crm\Order\ProductManager;

use Bitrix\Crm\Discount;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products\BasketXmlId;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products\ProductRowXmlId;
use Bitrix\Main\Loader;
use Bitrix\Sale\PriceMaths;
use Bitrix\Sale\Tax\VatCalculator;
use Exception;

/**
 * Converter without reserve info.
 */
class EntityProductConverter implements ProductConverter
{
	/**
	 * @throws Exception if not installed 'sale' module.
	 */
	public function __construct()
	{
		Loader::requireModule('sale');
	}

	/**
	 * @inheritDoc
	 */
	public function convertToSaleBasketFormat(array $product): array
	{
		/*
		 * `BASE_PRICE` and `PRICE` in basket item it is prices without tax.
		 */
		if ($product['TAX_INCLUDED'] === 'Y')
		{
			$basePrice = (float)($product['PRICE_BRUTTO'] ?? 0);
			$price = (float)($product['PRICE'] ?? 0);
		}
		else
		{
			$basePrice = (float)($product['PRICE_NETTO'] ?? 0);
			$price = (float)($product['PRICE_EXCLUSIVE'] ?? 0);
		}

		$discountPrice = $basePrice - $price;
		return [
			'NAME' => $product['PRODUCT_NAME'],
			'MODULE' => $product['PRODUCT_ID'] ? 'catalog' : '',
			'PRODUCT_ID' => $product['PRODUCT_ID'],
			'OFFER_ID' => $product['PRODUCT_ID'], // used in basket builders
			'QUANTITY' => $product['QUANTITY'],
			'DISCOUNT_PRICE' => $discountPrice,
			'BASE_PRICE' => $basePrice,
			'PRICE' => $price,
			'CUSTOM_PRICE' => 'Y',
			'MEASURE_CODE' => $product['MEASURE_CODE'],
			'MEASURE_NAME' => $product['MEASURE_NAME'],
			'VAT_RATE' => $product['TAX_RATE'] === null ? null : $product['TAX_RATE'] * 0.01,
			'VAT_INCLUDED' => $product['TAX_INCLUDED'],
			'XML_ID' => $product['ID'] ? BasketXmlId::getXmlIdFromRowId($product['ID']) : null,
			// not `sale` basket item, but used.
			'DISCOUNT_SUM' => $discountPrice,
			'DISCOUNT_RATE' => $product['DISCOUNT_RATE'] ?? null,
			'DISCOUNT_TYPE_ID' => $product['DISCOUNT_TYPE_ID'] ?? null,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function convertToCrmProductRowFormat(array $basketItem): array
	{
		$result = [
			'XML_ID' => $basketItem['ID'] ? ProductRowXmlId::getXmlIdFromBasketId($basketItem['ID']) : null,
			'PRODUCT_NAME' => $basketItem['NAME'],
			'PRODUCT_ID' => $basketItem['PRODUCT_ID'],
			'QUANTITY' => $basketItem['QUANTITY'],
			//'PRICE_ACCOUNT' => 'Calculated when saving',
			'MEASURE_CODE' => $basketItem['MEASURE_CODE'],
			'MEASURE_NAME' => $basketItem['MEASURE_NAME'],
			'TAX_RATE' => $basketItem['VAT_RATE'] === null ? null : $basketItem['VAT_RATE'] * 100,
			'TAX_INCLUDED' => $basketItem['VAT_INCLUDED'],
		];

		// prices
		$vatRate = (float)$basketItem['VAT_RATE'];
		$vatIncluded = $basketItem['VAT_INCLUDED'] === 'Y';
		$vatCalculator = new VatCalculator($vatRate);

		$price = (float)$basketItem['PRICE'];
		$basePrice = (float)$basketItem['BASE_PRICE'];

		if ($vatIncluded)
		{
			$result['PRICE_BRUTTO'] = $basePrice;
			$result['PRICE_NETTO'] = $vatCalculator->allocate($basePrice);
			$result['PRICE_EXCLUSIVE'] = $vatCalculator->allocate($price);
			$result['PRICE'] = $price;
		}
		else
		{
			$result['PRICE_BRUTTO'] = $vatCalculator->accrue($basePrice);
			$result['PRICE_NETTO'] = $basePrice;
			$result['PRICE_EXCLUSIVE'] = $price;
			$result['PRICE'] = $vatCalculator->accrue($price);
		}

		// discount
		$result['DISCOUNT_TYPE_ID'] = Discount::MONETARY;
		$result['DISCOUNT_PERCENT'] = null; // set null, it will be recalculated when saving.
		$result['DISCOUNT_SUM'] = PriceMaths::roundPrecision($result['PRICE_NETTO'] - $result['PRICE_EXCLUSIVE']);

		return $result;
	}
}
