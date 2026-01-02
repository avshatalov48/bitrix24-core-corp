<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CCrmOwnerType;

class DefinedEntityTypeId extends Validator
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

		if (array_key_exists($this->fieldToCheck, $fields) && !CCrmOwnerType::IsDefined($fields[$this->fieldToCheck]))
		{
			$result->addError($this->error());
		}

		return $result;
	}

	private function error(): Error
	{
		return new Error(
			message: Loc::getMessage('CRM_DTO_VALIDATOR_DEFINED_ENTITY_TYPE_ID', [
				'#FIELD#' => $this->fieldToCheck,
				'#PARENT_OBJECT#' => $this->dto->getName(),
			]),
			code: 'DEFINED_ENTITY_TYPE_ID',
			customData: [
				'FIELD' => $this->fieldToCheck,
				'PARENT_OBJECT' => $this->dto->getName(),
			],
		);
	}
}
