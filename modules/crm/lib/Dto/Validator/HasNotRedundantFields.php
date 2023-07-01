<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class HasNotRedundantFields extends Validator
{
	private array $propertiesNames;
	public function __construct(Dto $dto, array $propertiesNames)
	{
		parent::__construct($dto);
		$this->propertiesNames = $propertiesNames;
	}

	public function validate(array $fields): Result
	{
		$result = new Result();
		foreach (array_keys($fields) as $fieldName)
		{
			if (!in_array($fieldName, $this->propertiesNames))
			{
				$result->addError(new Error(
					Loc::getMessage('CRM_DTO_VALIDATOR_FIELD_IS_REDUNDANT', [
						'#FIELD#' => $fieldName,
						'#PARENT_OBJECT#' => $this->dto->getName(),
					]),
					'FIELD_IS_REDUNDANT',
					[
						'FIELD' => $fieldName,
						'PARENT_OBJECT' => $this->dto->getName(),
					]
				));
			}
		}

		return $result;
	}
}
