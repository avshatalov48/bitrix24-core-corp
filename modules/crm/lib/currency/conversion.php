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
	 *
	 * @return float
	 */
	public static function toAccountCurrency(float $sum, string $sourceCurrencyId): float
	{
		$params = [
			'SUM' => $sum,
			'CURRENCY_ID' => $sourceCurrencyId,
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
}