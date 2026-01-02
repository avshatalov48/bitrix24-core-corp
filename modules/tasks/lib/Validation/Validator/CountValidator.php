<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Validation\Validator;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Validation\ValidationError;
use Bitrix\Main\Validation\ValidationResult;
use Bitrix\Main\Validation\Validator\ValidatorInterface;

class CountValidator implements ValidatorInterface
{
	public function __construct(
		private readonly ?int $min = null,
		private readonly ?int $max = null
	)
	{
		if (null === $this->min && null === $this->max)
		{
			throw new ArgumentException('At least one of min or max must be specified');
		}
	}

	public function validate(mixed $value): ValidationResult
	{
		$result = new ValidationResult();

		if (!is_countable($value))
		{
			$result->addError(new ValidationError(
				'Value is uncountable',
				failedValidator: $this
			));

			return $result;
		}

		$count = count($value);
		if ($this->min && $count < $this->min)
		{
			$result->addError(new ValidationError(
				'Value is less than minimum',
				failedValidator: $this
			));

			return $result;
		}

		if ($this->max && $count > $this->max)
		{
			$result->addError(new ValidationError(
				'Value is greater than maximum',
				failedValidator: $this
			));

			return $result;
		}

		return $result;
	}
}
