<?php

namespace Bitrix\Crm\Controller\Validator;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class Order implements Validator
{
	private const ALLOWED_SORT_DIRECTIONS = ['ASC', 'DESC'];

	public function __construct(
		private readonly array $allowedFields
	)
	{
	}

	public function validate(mixed $value): Result
	{
		$result = new Result();

		if (!is_array($value))
		{
			return $result->addError(new Error(
				'Order should be an associative array',
				ErrorCode::INVALID_ARG_VALUE,
			));
		}

		foreach ($value as $fieldName => $sortDirection)
		{
			if (!in_array($fieldName, $this->allowedFields, true))
			{
				$result->addError(
					new Error(
						"Invalid order: field '{$fieldName}' is not allowed in order",
						ErrorCode::INVALID_ARG_VALUE,
					)
				);
			}

			if (!in_array($sortDirection, self::ALLOWED_SORT_DIRECTIONS, true))
			{
				$result->addError(
					new Error(
						'Invalid order: allowed sort directions are '
						. implode(', ', self::ALLOWED_SORT_DIRECTIONS)
						. ". But got '{$sortDirection}' for field '{$fieldName}'"
						,
						ErrorCode::INVALID_ARG_VALUE,
					)
				);
			}
		}

		return $result;
	}
}
