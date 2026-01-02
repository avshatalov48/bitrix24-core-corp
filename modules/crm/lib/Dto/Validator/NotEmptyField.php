<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class NotEmptyField extends Validator
{
	protected string $fieldToCheck;

	public function __construct(
		Dto $dto,
		string $fieldToCheck,
		private readonly bool $isNeedTrim = true,
		private readonly string $trimCharacters = " \t\n\r\0\x0B",
	)
	{
		parent::__construct($dto);

		$this->fieldToCheck = $fieldToCheck;
	}

	public function validate(array $fields): Result
	{
		$result = new Result();

		$value = $fields[$this->fieldToCheck] ?? null;
		if ($this->isNeedTrim && is_string($value))
		{
			$value = preg_replace('/\s+/u', ' ', $value);
			$value = trim($value, $this->trimCharacters);
		}

		if (empty($value))
		{
			$result->addError(new Error(
				Loc::getMessage('CRM_DTO_VALIDATOR_FIELD_CANT_BE_EMPTY', [
					'#FIELD#' => $this->fieldToCheck,
					'#PARENT_OBJECT#' => $this->dto->getName(),
				]),
				'FIELD_CANT_BE_EMPTY',
				[
					'FIELD' => $this->fieldToCheck,
					'PARENT_OBJECT' => $this->dto->getName(),
				]
			));
		}

		return $result;
	}
}
