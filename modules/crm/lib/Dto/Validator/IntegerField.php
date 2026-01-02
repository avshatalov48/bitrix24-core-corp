<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
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
			return $result->addError($this->error());
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

	private function error(): Error
	{
		return new Error(
			message: $this->buildErrorMessage(),
			code: 'INTEGER_FIELD',
			customData: [
				'FIELD' => $this->fieldToCheck,
				'PARENT_OBJECT' => $this->dto->getName(),
				'MIN' => $this->min,
				'MAX' => $this->max,
			],
		);
	}

	private function buildErrorMessage(): string
	{
		$commonReplace = [
			'#FIELD#' => $this->fieldToCheck,
			'#PARENT_OBJECT#' => $this->dto->getName(),
		];

		if ($this->min !== null && $this->max !== null)
		{
			return Loc::getMessage('CRM_DTO_VALIDATOR_INTEGER_FIELD_MIN_MAX', [
				...$commonReplace,
				'#MIN#' => $this->min,
				'#MAX#' => $this->max,
			]);
		}

		if ($this->min !== null)
		{
			return Loc::getMessage('CRM_DTO_VALIDATOR_INTEGER_FIELD_MIN', [
				...$commonReplace,
				'#MIN#' => $this->min,
			]);
		}

		if ($this->max !== null)
		{
			return Loc::getMessage('CRM_DTO_VALIDATOR_INTEGER_FIELD_MAX', [
				...$commonReplace,
				'#MAX#' => $this->max,
			]);
		}

		return Loc::getMessage('CRM_DTO_VALIDATOR_INTEGER_FIELD', $commonReplace);
	}
}
