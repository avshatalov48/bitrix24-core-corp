<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Validation\Validator;

use Bitrix\Main\Validation\ValidationError;
use Bitrix\Main\Validation\ValidationResult;
use Bitrix\Main\Validation\Validator\ValidatorInterface;
use Bitrix\Tasks\Onboarding\Internal\Type;

class JobCodeValidator implements ValidatorInterface
{
	public function validate(mixed $value): ValidationResult
	{
		$result = new ValidationResult();

		if (!is_string($value))
		{
			$result->addError(new ValidationError('Wrong code type'));

			return $result;
		}

		[$type, $userId] = explode('_', $value);

		$userId = (int)$userId;

		if ($userId <= 0)
		{
			$result->addError(new ValidationError('Wrong code user id'));

			return $result;
		}

		$jobType = Type::tryFrom($type);

		if ($jobType === null)
		{
			$result->addError(new ValidationError('Wrong job type'));

			return $result;
		}

		return $result;
	}
}