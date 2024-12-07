<?php

namespace Bitrix\Sign\Helper;

/**
 * Snils validation rule
 */
class SnilsValidator
{
	private string $rawValue;

	public function __construct(string $rawValue)
	{
		$this->rawValue = $rawValue;
	}

	public function isValid(): bool
	{
		$normalized = $this->getNormalizedValue();
		if (mb_strlen($normalized) !== 11)
		{
			return false;
		}
		$sum = 0;
		for ($i = 0; $i < 9; $i++)
		{
			$sum += (int)mb_substr($normalized, $i, 1) * (9 - $i);
		}
		$checkSum = 0;
		if ($sum < 100)
		{
			$checkSum = $sum;
		}
		elseif ($sum > 101)
		{
			$checkSum = $sum % 101;
			if ($checkSum === 100)
			{
				$checkSum = 0;
			}
		}

		return $checkSum === (int)mb_substr($normalized, -2);
	}

	public function getNormalizedValue(): string
	{
		return preg_replace('/[^0-9]/', '', $this->rawValue);
	}
}
