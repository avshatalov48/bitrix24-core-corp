<?php

class CCrmAccountingHelper
{
	public static function PrepareAccountingData($arFields)
	{
		$accountCurrencyID = CCrmCurrency::GetAccountCurrencyID();
		if (!isset($accountCurrencyID[0]))
		{
			return false;
		}

		$currencyID = isset($arFields['CURRENCY_ID']) ? strval($arFields['CURRENCY_ID']) : '';
		if (!CCrmCurrency::GetByID($currencyID))
		{
			// Currency is invalid or not assigned
			return false;
		}

		if ($currencyID === $accountCurrencyID)
		{
			// Avoid conversion to float since possible data lost
			return [
				'ACCOUNT_CURRENCY_ID' => $accountCurrencyID,
				'ACCOUNT_SUM' => isset($arFields['SUM']) ? $arFields['SUM'] : 0.0,
			];
		}

		$account = CCrmCurrency::ConvertMoney(
			isset($arFields['SUM']) ? doubleval($arFields['SUM']) : 0.0,
			$currencyID,
			$accountCurrencyID,
			isset($arFields['EXCH_RATE']) ? doubleval($arFields['EXCH_RATE']) : -1
		);

		return [
			'ACCOUNT_CURRENCY_ID' => $accountCurrencyID,
			'ACCOUNT_SUM' => $account,
		];
	}

	/**
	 * Calculate accounting fields (ACCOUNT_CURRENCY_ID, OPPORTUNITY_ACCOUNT, possibly TAX_VALUE_ACCOUNT) and actual EXCH_RATE using new and old entity fields values
	 *
	 * @param array $newValue
	 * @param array $oldValue
	 * @param bool $calculateTax
	 * @return array
	 */
	public static function calculateAccountingData(array $newValue, array $oldValue = [], bool $calculateTax = false): array
	{
		$result = [];

		$currencyId = $newValue['CURRENCY_ID'] ?? $oldValue['CURRENCY_ID'] ?? null;
		$exchangeRate = $newValue['EXCH_RATE'] ?? $oldValue['EXCH_RATE'] ?? null;
		if (!$exchangeRate && $currencyId)
		{
			$exchangeRate = CCrmCurrency::GetExchangeRate($currencyId);
		}
		// if currency changed and exchangeRate is not directly set, need to update it
		if (
			isset($newValue['CURRENCY_ID'])
			&& isset($oldValue['CURRENCY_ID'])
			&& $newValue['CURRENCY_ID'] !== $oldValue['CURRENCY_ID']
			&& !isset($newValue['EXCH_RATE'])
		)
		{
			$exchangeRate = CCrmCurrency::GetExchangeRate($currencyId);
		}
		$accData = self::PrepareAccountingData(
			[
				'CURRENCY_ID' => $currencyId,
				'SUM' => $newValue['OPPORTUNITY'] ?? $oldValue['OPPORTUNITY'] ?? null,
				'EXCH_RATE' => $exchangeRate,
			]
		);
		if (is_array($accData))
		{
			$result['EXCH_RATE'] = $exchangeRate;
			$result['ACCOUNT_CURRENCY_ID'] = $accData['ACCOUNT_CURRENCY_ID'];
			$result['OPPORTUNITY_ACCOUNT'] = $accData['ACCOUNT_SUM'];
		}
		if ($calculateTax)
		{
			$accData = self::PrepareAccountingData(
				[
					'CURRENCY_ID' => $currencyId,
					'SUM' => $newValue['TAX_VALUE'] ?? $oldValue['TAX_VALUE'] ?? null,
					'EXCH_RATE' => $exchangeRate,
				]
			);
			if (is_array($accData))
			{
				$result['EXCH_RATE'] = $exchangeRate;
				$result['TAX_VALUE_ACCOUNT'] = $accData['ACCOUNT_SUM'];
			}
		}

		return $result;
	}
}
