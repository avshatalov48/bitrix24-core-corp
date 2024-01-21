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

	public function __construct(Dto $dto, string $fieldToCheck)
	{
		parent::__construct($dto);

		$this->fieldToCheck = $fieldToCheck;
	}

	public function validate(array $fields): Result
	{
		$result = new Result();

		if (empty($fields[$this->fieldToCheck]))
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
