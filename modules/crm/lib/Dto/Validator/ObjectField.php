<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Main\Result;

final class ObjectField extends Validator
{
	protected string $fieldToCheck;

	public function __construct(Dto $dto, string $fieldToCheck)
	{
		parent::__construct($dto);

		$this->fieldToCheck = $fieldToCheck;
	}

	public function validate(array $fields): Result
	{
		$result = new Result();

		if (array_key_exists($this->fieldToCheck, $fields) && !is_array($fields[$this->fieldToCheck]))
		{
			$result->addError($this->getWrongFieldError($this->fieldToCheck, $this->dto->getName()));
		}

		return $result;
	}
}
