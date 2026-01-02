<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class EnumField extends Validator
{
	protected string $fieldToCheck;
	protected array $possibleValues;

	private const ERROR_AVAILABLE_VALUES_SEPARATOR = ', ';

	public function __construct(Dto $dto, string $fieldToCheck, array $possibleValues)
	{
		parent::__construct($dto);

		$this->fieldToCheck = $fieldToCheck;
		$this->possibleValues = $possibleValues;
	}

	public function validate(array $fields): Result
	{
		$result = new Result();

		if (array_key_exists($this->fieldToCheck, $fields) && !in_array($fields[$this->fieldToCheck], $this->possibleValues))
		{
			$result->addError($this->error());
		}

		return $result;
	}

	private function error(): Error
	{
		return new Error(
			message: Loc::getMessage('CRM_DTO_VALIDATOR_ENUM_FIELD', [
				'#FIELD#' => $this->fieldToCheck,
				'#PARENT_OBJECT#' => $this->dto->getName(),
				'#VALUES#' => $this->buildValuesString(),
			]),
			code: 'ENUM_FIELD',
			customData: [
				'FIELD' => $this->fieldToCheck,
				'PARENT_OBJECT' => $this->dto->getName(),
				'VALUES' => $this->possibleValues,
			],
		);
	}

	private function buildValuesString(): string
	{
		$values = [];
		foreach ($this->possibleValues as $value)
		{
			if (is_string($value))
			{
				$values[] = "'{$value}'";
			}
			else
			{
				$values[] = $value;
			}
		}

		return implode(self::ERROR_AVAILABLE_VALUES_SEPARATOR, $values);
	}
}
