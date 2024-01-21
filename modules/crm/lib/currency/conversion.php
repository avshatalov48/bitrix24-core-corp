<?php

namespace Bitrix\Crm\Currency;

use Bitrix\Crm\Currency;

class Conversion
{
	/**
	 * Convert sum from source currency to account currency
	 * @see Currency::getAccountCurrencyId()
	 *
	 * @param float $sum
	 * @param string $sourceCurrencyId
	 * @param float|null $conversionRateFromSourceToBaseCurrency - if this value is not null, sum is converted
	 * firstly to base currency using this rate, and then converted to account currency. if this value is null, sum is
	 * converted directly from source to account currency
	 *
	 * @return float
	 */
	public static function toAccountCurrency(
		float $sum,
		string $sourceCurrencyId,
		?float $conversionRateFromSourceToBaseCurrency = null,
	): float
	{
		$params = [
			'SUM' => $sum,
			'CURRENCY_ID' => $sourceCurrencyId,
			'EXCH_RATE' => $conversionRateFromSourceToBaseCurrency,
		];

		$result = \CCrmAccountingHelper::PrepareAccountingData($params);

		return $result['ACCOUNT_SUM'] ?? 0;
	}

	/**
	 * Convert sum from source currency to destination currency
	 *
	 * @param float $sum
	 * @param string $sourceCurrencyId
	 * @param string $destCurrencyId
	 *
	 * @return float
	 */
	public static function toSpecifiedCurrency(float $sum, string $sourceCurrencyId, string $destCurrencyId): float
	{
		return \CCrmCurrency::ConvertMoney($sum, $sourceCurrencyId, $destCurrencyId);
	}

	public static function getConversionRateToBaseCurrency(string $sourceCurrencyId): float
	{
		return \CCrmCurrency::GetExchangeRate($sourceCurrencyId);
	}
}
