<?php

namespace Bitrix\Crm\Dto;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

abstract class Validator
{
	protected Dto $dto;

	public function __construct(Dto $dto)
	{
		$this->dto = $dto;
	}

	abstract public function validate(array $fields): Result;

	protected function getWrongFieldError(string $fieldName, string $parentObjectName): Error
	{
		return new Error(
			Loc::getMessage('CRM_DTO_VALIDATOR_WRONG_FIELD_VALUE', [
				'#FIELD#' => $fieldName,
				'#PARENT_OBJECT#' => $parentObjectName,
			]),
			'WRONG_FIELD_VALUE',
			[
				'FIELD' => $fieldName,
				'PARENT_OBJECT' => $parentObjectName,
			]
		);
	}

	protected function getTooManyItemsError(string $fieldName, string $parentObjectName, int $maxCount): Error
	{
		return new Error(
			Loc::getMessage('CRM_DTO_VALIDATOR_TOO_MANY_ITEMS', [
				'#FIELD#' => $fieldName,
				'#PARENT_OBJECT#' => $parentObjectName,
				'#COUNT#' => $maxCount,
			]),
			'TOO_MANY_ITEMS',
			[
				'FIELD' => $fieldName,
				'PARENT_OBJECT' => $parentObjectName,
				'COUNT' => $maxCount,
			]
		);
	}

	protected function getKeyValidationError($key, string $parentObjectName): ?Error
	{
		if (is_int($key))
		{
			return null;
		}

		$error = new Error(
			Loc::getMessage('CRM_DTO_VALIDATOR_KEY_CONTAIN_WRONG_SYMBOLS', [
				'#KEY#' => $key,
				'#PARENT_OBJECT#' => $parentObjectName,
			]),
			'KEY_CONTAIN_WRONG_SYMBOLS',
			[
				'KEY' => $key,
				'PARENT_OBJECT' => $parentObjectName,
			]
		);

		if (!is_string($key))
		{
			return $error;
		}
		if (!preg_match('/^[a-zA-Z0-9-_]+$/s', $key))
		{
			return $error;
		}

		return null;
	}
}
