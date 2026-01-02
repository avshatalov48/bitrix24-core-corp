<?php

namespace Bitrix\Crm\Dto\Validator;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Result;

class DefinedCategoryIdentifier extends Validator
{
	public function __construct(
		Dto $dto,
		private readonly string $entityTypeIdField,
		private readonly string $categoryIdField,
	)
	{
		parent::__construct($dto);
	}

	/**
	 * @throws ArgumentException
	 */
	public function validate(array $fields): Result
	{
		foreach ($this->getSubValidators() as $validator)
		{
			$result = $validator->validate($fields);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		$entityTypeId = $fields[$this->entityTypeIdField];

		return (new DefinedCategory($this->dto, (int)$entityTypeId, $this->categoryIdField))
			->validate($fields);
	}

	/**
	 * @return array<Validator>
	 */
	private function getSubValidators(): array
	{
		return [
			new RequiredField($this->dto, $this->entityTypeIdField),
			new DefinedEntityTypeId($this->dto, $this->entityTypeIdField),
			new EntityType\UseFactoryBasedApproach($this->dto, $this->entityTypeIdField),

			new RequiredField($this->dto, $this->categoryIdField),
		];
	}
}
