<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Validation\Validator;

use Bitrix\Main\Validation\ValidationError;
use Bitrix\Main\Validation\ValidationResult;
use Bitrix\Main\Validation\Validator\ValidatorInterface;
use Throwable;

class SerializedValidator implements ValidatorInterface
{
	public function validate(mixed $value): ValidationResult
	{
		$result = new ValidationResult();
		if (!is_string($value))
		{
			return $result->addError(new ValidationError(
				message: 'Not a string',
				failedValidator: $this
			));
		}

		try
		{
			unserialize($value, ['allowed_classes' => false]);
		}
		catch (Throwable)
		{
			return $result->addError(new ValidationError(
				message: 'Not a serialized string',
				failedValidator: $this
			));
		}

		return $result;
	}
}