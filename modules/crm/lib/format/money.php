<?php

namespace Bitrix\Crm\Format;

class Money
{
	/**
	 * Get money data in the formatted string representation. Format is specified by the Currency module
	 * @param float $sum
	 * @param string $currencyId
	 *
	 * @return string
	 */
	public static function format(float $sum, string $currencyId): string
	{
		return \CCrmCurrency::MoneyToString($sum, $currencyId);
	}

	/**
	 * Get money data in the formatted string representation. The provided custom template is used for formatting.
	 * When $template is not provided, no template is used.
	 * To use default template, please call Money::format
	 * @param float $sum
	 * @param string $currencyId
	 * @param string $template
	 *
	 * @return string
	 */
	public static function formatWithCustomTemplate(float $sum, string $currencyId, string $template = '#'): string
	{
		return \CCrmCurrency::MoneyToString($sum, $currencyId, $template);
	}
}