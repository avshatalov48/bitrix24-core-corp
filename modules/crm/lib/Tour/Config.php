<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Main\Config\Option;
use CUserOptions;

final class Config
{
	public const CODE_CLOSED = 'closed';
	public const CODE_NUMBER_OF_VIEWS = 'numberOfViews';
	public const CODE_LIMIT = 'limit';

	private const DEACTIVATED_OPTION_NAME = 'HIDE_ALL_TOURS';

	public static function getPersonalValue(string $category, string $option, string $code = null): mixed
	{
		$data = CUserOptions::getOption(
			$category,
			$option,
			[]
		);

		if (!$code)
		{
			return $data;
		}

		return CUserOptions::getOption(
			$category,
			$option,
			[]
		)[$code] ?? null;
	}

	public static function setPersonalValue(string $category, string $option, string $code, string $value): void
	{
		$data = CUserOptions::getOption(
			$category,
			$option,
			[]
		);

		if (!is_array($data))
		{
			$data = [];
		}

		$data[$code] = $value;

		CUserOptions::setOption($category, $option, $data);
	}

	public static function isToursDeactivated(string $category): bool
	{
		return Option::get($category, self::DEACTIVATED_OPTION_NAME, 'N') === 'Y';
	}
}
