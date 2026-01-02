<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Validation\Validator;

use Bitrix\Main\Validation\ValidationError;
use Bitrix\Main\Validation\ValidationResult;
use Bitrix\Main\Validation\Validator\MinValidator;
use Bitrix\Main\Validation\Validator\ValidatorInterface;
use Bitrix\Tasks\Onboarding\Internal\Type;

class ArrayOfJobCodesValidator implements ValidatorInterface
{
	public function validate(mixed $value): ValidationResult
	{
		$result = new ValidationResult();

		if (!is_array($value))
		{
			$result->addError(new ValidationError('Wrong codes type'));

			return $result;
		}

		$jobCodeValidator = new JobCodeValidator();
		foreach ($value as $item)
		{
			$itemResult = $jobCodeValidator->validate($item);
			if (!$itemResult->isSuccess())
			{
				$result->addErrors($itemResult->getErrors());

				return $result;
			}
		}

		return $result;
	}
}