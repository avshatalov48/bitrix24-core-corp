<?php

namespace Bitrix\Crm\Dto\Validator\EntityType;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Crm\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

class IsPossibleDynamicType extends Validator
{
	public function __construct(
		Dto $dto,
		private readonly string $entityTypeIdField,
	)
	{
		parent::__construct($dto);
	}

	public function validate(array $fields): \Bitrix\Main\Result
	{
		if (!array_key_exists($this->entityTypeIdField, $fields))
		{
			return Result::success();
		}

		$entityTypeId = $fields[$this->entityTypeIdField];
		if (!is_numeric($entityTypeId) || !CCrmOwnerType::isPossibleDynamicTypeId((int)$entityTypeId))
		{
			return Result::fail($this->error());
		}

		return Result::success();
	}

	private function error(): Error
	{
		return new Error(
			message: Loc::getMessage('CRM_DTO_VALIDATOR_ENTITY_TYPE_IS_POSSIBLE_DYNAMIC_TYPE', [
				'#FIELD#' => $this->entityTypeIdField,
				'#PARENT_OBJECT#' => $this->dto->getName(),
			]),
			code: 'ENTITY_TYPE_IS_POSSIBLE_DYNAMIC_TYPE',
			customData: [
				'FIELD' => $this->entityTypeIdField,
				'PARENT_OBJECT' => $this->dto->getName(),
			],
		);
	}
}
