<?php

namespace Bitrix\DocumentGenerator\Value;

use Bitrix\DocumentGenerator\Value;

class PlaneString extends Value
{
	public const LETTER_CASE_LOWER = 'lower';
	public const LETTER_CASE_UPPER = 'upper';
	public const LETTER_CASE_TITLE = 'title';

	public function toString($modifier = ''): string
	{
		$case = $this->getOptions($modifier)['letterCase'] ?? null;

		$mbConstant = $this->getMultiByteConstant($case);
		if ($mbConstant !== null)
		{
			return mb_convert_case($this->value, $mbConstant);
		}

		return $this->value;
	}

	protected function getMultiByteConstant(?string $letterCase): ?int
	{
		$map = [
			static::LETTER_CASE_LOWER => MB_CASE_LOWER,
			static::LETTER_CASE_UPPER => MB_CASE_UPPER,
			static::LETTER_CASE_TITLE => MB_CASE_TITLE,
		];

		return $map[$letterCase] ?? null;
	}
}
