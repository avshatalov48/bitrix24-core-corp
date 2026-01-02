<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Crm\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class StringField extends Validator
{
	public function __construct(
		Dto $dto,
		private readonly string $field,
	)
	{
		parent::__construct($dto);
	}

	public function validate(array $fields): Result
	{
		if (!array_key_exists($this->field, $fields))
		{
			return Result::success();
		}

		if (!is_string($fields[$this->field]))
		{
			return Result::fail($this->error());
		}

		return Result::success();
	}

	private function error(): Error
	{
		return new Error(
			message: Loc::getMessage('CRM_DTO_VALIDATOR_FIELD_IS_STRING', [
				'#FIELD#' => $this->field,
				'#PARENT_OBJECT#' => $this->dto->getName(),
			]),
			code: 'FIELD_IS_STRING',
			customData: [
				'FIELD' => $this->field,
				'PARENT_OBJECT' => $this->dto->getName(),
			],
		);
	}
}
