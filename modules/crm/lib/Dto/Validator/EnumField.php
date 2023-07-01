<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Main\Result;

class EnumField extends Validator
{
	protected string $fieldToCheck;
	protected array $possibleValues;

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
			$result->addError($this->getWrongFieldError($this->fieldToCheck, $this->dto->getName()));
		}

		return $result;
	}
}
