<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Main\Result;

final class IntegerField extends Validator
{
	public function __construct(
		Dto $dto,
		private readonly string $fieldToCheck,
		private readonly ?int $min = null,
		private readonly ?int $max = null
	) {
		parent::__construct($dto);
	}

	public function validate(array $fields): Result
	{
		$result = new Result();
		$value = $fields[$this->fieldToCheck] ?? null;

		if (
			$value === null ||
			is_int($value) === false ||
			$this->isMoreThanMin($value) === false ||
			$this->isLessThanMax($value) === false
		)
		{
			return $result->addError($this->getWrongFieldError($this->fieldToCheck, $this->dto->getName()));
		}

		return $result;
	}

	private function isMoreThanMin(int $value): bool
	{
		if ($this->min === null)
		{
			return true;
		}

		return $value > $this->min;
	}

	private function isLessThanMax(int $value): bool
	{
		if ($this->max === null)
		{
			return true;
		}

		return $value < $this->max;
	}
}
