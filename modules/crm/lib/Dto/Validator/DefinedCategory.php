<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\ArgumentException;
use Bitrix\Crm\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

final class DefinedCategory extends Validator
{
	private readonly Factory $factory;

	/**
	 * @throws ArgumentException
	 */
	public function __construct(
		Dto $dto,
		private readonly int $entityTypeId,
		private readonly string $categoryIdField,
	)
	{
		parent::__construct($dto);

		if (!CCrmOwnerType::isUseFactoryBasedApproach($this->entityTypeId))
		{
			throw new ArgumentException('Must be use factory based approach', 'entityTypeId');
		}

		$this->factory = Container::getInstance()->getFactory($this->entityTypeId);
	}

	public function validate(array $fields): Result
	{
		if (!array_key_exists($this->categoryIdField, $fields))
		{
			return Result::success();
		}

		$categoryId = $fields[$this->categoryIdField];
		if ($categoryId === null && !$this->factory->isCategoriesSupported())
		{
			return Result::success();
		}

		if (!is_numeric($categoryId) || !$this->factory->isCategoryAvailable((int)$categoryId))
		{
			return Result::fail($this->error());
		}

		return Result::success();
	}

	private function error(): Error
	{
		return new Error(
			message: Loc::getMessage('CRM_DTO_VALIDATOR_DEFINED_CATEGORY', [
				'#FIELD#' => $this->categoryIdField,
				'#PARENT_OBJECT#' => $this->dto->getName(),
				'#ENTITY_NAME#' => CCrmOwnerType::GetDescription($this->entityTypeId),
			]),
			code: 'DEFINED_CATEGORY',
			customData: [
				'FIELD' => $this->categoryIdField,
				'PARENT_OBJECT' => $this->dto->getName(),
				'ENTITY_TYPE_ID' => $this->entityTypeId,
			],
		);
	}
}
