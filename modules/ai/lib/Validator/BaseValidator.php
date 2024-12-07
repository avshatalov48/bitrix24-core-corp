<?php declare(strict_types=1);

namespace Bitrix\AI\Validator;

use Bitrix\AI\Exception\ValidateException;
use Bitrix\Main\Localization\Loc;

class BaseValidator
{
	/**
	 * @throws ValidateException
	 */
	public function strRequire(mixed $value, string $fieldName): void
	{
		if (!is_string($value) || empty($value))
		{
			throw new ValidateException(
				$fieldName, Loc::getMessage('AI_VALIDATOR_REQUIRE_NOT_EMPTY_STRING')
			);
		}
	}

	/**
	 * @throws ValidateException
	 */
	public function isArray($value, string $fieldName): void
	{
		if (!is_array($value))
		{
			throw new ValidateException(
				$fieldName, Loc::getMessage('AI_VALIDATOR_REQUIRE_ARRAY')
			);
		}
	}

	/**
	 * @throws ValidateException
	 */
	public function isNotEmptyArray($value, string $fieldName): void
	{
		if (!is_array($value) || empty($value))
		{
			throw new ValidateException(
				$fieldName, Loc::getMessage('AI_VALIDATOR_REQUIRE_NOT_EMPTY_ARRAY')
			);
		}
	}

	/**
	 * @throws ValidateException
	 */
	public function maxLen(string $str, int $len, string $fieldName): void
	{
		if (mb_strlen($str) > $len)
		{
			throw new ValidateException(
				$fieldName, Loc::getMessage('AI_VALIDATOR_STRING_SHOULD_BE_SMALLER') . $len
			);
		}
	}

	/**
	 * @throws ValidateException
	 */
	public function minLen(string $str, int $len, string $fieldName): void
	{
		if (mb_strlen($str) < $len)
		{
			throw new ValidateException(
				$fieldName, Loc::getMessage('AI_VALIDATOR_STRING_SHOULD_BE_LARGER') . $len
			);
		}
	}
}
