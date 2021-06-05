<?php

namespace Bitrix\Crm;

class Currency
{
	public static function getAccountCurrencyId(): string
	{
		return \CCrmCurrency::GetAccountCurrencyID();
	}

	public static function getBaseCurrencyId(): string
	{
		return \CCrmCurrency::GetBaseCurrencyID();
	}

	/**
	 * Returns a flat array of all currencyId
	 *
	 * @return array
	 */
	public static function getCurrencyIds(): array
	{
		return array_keys(static::getCurrencyList());
	}

	/**
	 * Returns a flat array of currencies, where key is currency id and value is currency name.
	 *
	 * @return string[]
	 */
	public static function getCurrencyList(): array
	{
		static $currencyList;

		if (is_null($currencyList))
		{
			$currencyList = \CCrmCurrencyHelper::PrepareListItems();
		}

		return $currencyList;
	}

	public static function getCurrencyCaption(string $currencyId): ?string
	{
		return static::getCurrencyList()[$currencyId] ?? null;
	}
}
