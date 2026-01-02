<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Crm\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class StringStartsWith extends Validator
{
	public function __construct(
		Dto $dto,
		private readonly string $field,
		private readonly string $needle,
	)
	{
		parent::__construct($dto);
	}

	public function validate(array $fields): \Bitrix\Main\Result
	{
		if (!array_key_exists($this->field, $fields))
		{
			return Result::success();
		}

		$value = $fields[$this->field];
		if (is_string($value) && !str_starts_with((string)$value, $this->needle))
		{
			return Result::fail($this->error());
		}

		return Result::success();
	}

	private function error(): Error
	{
		return new Error(
			message: Loc::getMessage('CRM_DTO_VALIDATOR_STRING_STARTS_WITH', [
				'#FIELD#' => $this->field,
				'#PARENT_OBJECT#' => $this->dto->getName(),
				'#STARTS_WITH#' => $this->needle,
			]),
			code: 'STRING_STARTS_WITH',
			customData: [
				'FIELD' => $this->field,
				'PARENT_OBJECT' => $this->dto->getName(),
				'STARTS_WITH' => $this->needle,
			],
		);
	}
}
