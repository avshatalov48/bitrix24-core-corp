<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Disk\Validation\Validator;

use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Main\Loader;
use Bitrix\Main\Validation\ValidationError;
use Bitrix\Main\Validation\ValidationResult;
use Bitrix\Main\Validation\Validator\MinValidator;
use Bitrix\Main\Validation\Validator\ValidatorInterface;

class NewFilesValidator implements ValidatorInterface
{
	public function validate(mixed $value): ValidationResult
	{
		$result = new ValidationResult();

		if (!is_array($value))
		{
			$result->addError(
				new ValidationError(
					message: 'Value must be an array of strings.',
					failedValidator: $this
				)
			);

			return $result;
		}

		if (!Loader::includeModule('disk'))
		{
			$result->addError(
				new ValidationError(
					message: 'Disk module is not installed.',
					failedValidator: $this
				)
			);

			return $result;
		}

		foreach ($value as $item)
		{
			if (!is_string($item))
			{
				$result->addError(
					new ValidationError(
						message: 'Each element must be a string.',
						failedValidator: $this
					)
				);

				return $result;
			}
			if (!str_starts_with($item, FileUserType::NEW_FILE_PREFIX))
			{
				$result->addError(
					new ValidationError(
						message: 'Each string must start with "n" followed by a positive integer.',
						failedValidator: $this
					)
				);

				return $result;
			}

			$number = substr($item, strlen(FileUserType::NEW_FILE_PREFIX));
			$minValidator = new MinValidator(1);

			if (!$minValidator->validate($number)->isSuccess())
			{
				$result->addError(
					new ValidationError(
						message: 'Each string must start with "n" followed by a positive integer.',
						failedValidator: $this
					)
				);

				return $result;
			}
		}

		return $result;
	}
}
