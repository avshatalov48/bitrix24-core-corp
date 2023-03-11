<?php

namespace Bitrix\Crm\Order\ProductManager\ProductConverter;

use Bitrix\Crm\Discount;
use Bitrix\Main\Loader;
use Bitrix\Sale\PriceMaths;
use Bitrix\Sale\Tax\VatCalculator;

/**
 * Converter prices.
 *
 * @see \CAllCrmProductRow method `preparePrices` for details calculating (functional repeats the calculations).
 */
class PricesConverter
{
	public function __construct()
	{
		Loader::requireModule('sale');
	}

	/**
	 * Convert product row prices to basket item prices.
	 *
	 * @param float $price
	 * @param float $priceExclusive
	 * @param float $priceNetto
	 * @param float $priceBrutto
	 * @param bool $taxIncluded
	 *
	 * @return array with keys `PRICE`, `BASE_PRICE`, `DISCOUNT_PRICE`
	 */
	public function convertToSaleBasketPrices(float $price, float $priceExclusive, float $priceNetto, float $priceBrutto, bool $taxIncluded): array
	{
		$result = [];

		if ($taxIncluded)
		{
			$result['BASE_PRICE'] = $priceBrutto;
			$result['PRICE'] = $price;
		}
		else
		{
			$result['BASE_PRICE'] = $priceNetto;
			$result['PRICE'] = $priceExclusive;
		}

		$result['DISCOUNT_PRICE'] = PriceMaths::roundPrecision($result['BASE_PRICE'] - $result['PRICE']);

		return $result;
	}

	/**
	 * Convert basket item prices to product row prices.
	 *
	 * @param float $price
	 * @param float $basePrice
	 * @param float $vatRate between 0 and 1
	 * @param bool $vatIncluded
	 *
	 * @return array with keys `PRICE_BRUTTO`, `PRICE_NETTO`, `PRICE_EXCLUSIVE`, `PRICE`, `DISCOUNT_SUM`, `DISCOUNT_RATE`
	 */
	public function convertToProductRowPrices(float $price, float $basePrice, float $vatRate, bool $vatIncluded): array
	{
		$result = [];
		$vatCalculator = new VatCalculator($vatRate);

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

		$result['DISCOUNT_RATE'] = Discount::calculateDiscountRate($result['PRICE_NETTO'], $result['PRICE_EXCLUSIVE']);
		$result['DISCOUNT_SUM'] = Discount::calculateDiscountSum($result['PRICE_EXCLUSIVE'], $result['DISCOUNT_RATE']);
		$result['DISCOUNT_SUM'] = PriceMaths::roundPrecision($result['DISCOUNT_SUM']);

		return $result;
	}
}
