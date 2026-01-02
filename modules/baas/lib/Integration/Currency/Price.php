<?php

declare(strict_types=1);

namespace Bitrix\Baas\Integration\Currency;

use \Bitrix\UI;
use \Bitrix\Main;

class Price
{
	private bool $isModuleIncluded;

	private static array $knownCompatibilities = [
		'RUR' => 'RUB',
	];

	public function __construct(
		protected int|float $price,
		protected string $currencyId,
	)
	{
		$this->isModuleIncluded = Main\Loader::includeModule('currency');
	}

	public function getFormatted(): string
	{
		if ($this->isModuleIncluded)
		{
			$currencyId = self::$knownCompatibilities[$this->currencyId] ?? $this->currencyId;

			$format = \CCurrencyLang::GetFormatDescription($currencyId);

			$result = \CCurrencyLang::formatValue(
				$this->price,
				$format,
			);

			return (string)$result;
		}

		return UI\Currency\CurrencyFormat::convertBySettings($this->price, $this->currencyId);
	}
}
