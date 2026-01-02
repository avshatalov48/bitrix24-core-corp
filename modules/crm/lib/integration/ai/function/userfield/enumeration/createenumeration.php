<?php

namespace Bitrix\Crm\Integration\AI\Function\UserField\Enumeration;

use Bitrix\Crm\Integration\AI\Function\UserField\AbstractCreateUserField;
use Bitrix\Crm\Integration\AI\Function\UserField\Dto\CreateUserFieldParameters;
use Bitrix\Crm\Integration\AI\Function\UserField\Enum\UserFieldType;
use Bitrix\Crm\Result;
use Bitrix\Crm\UserField\Dto\EnumerationItem;
use CUserFieldEnum;

abstract class CreateEnumeration extends AbstractCreateUserField
{
	protected function settings(): array
	{
		return [
			'DISPLAY' => 'UI',
		];
	}

	protected function getType(): UserFieldType
	{
		return UserFieldType::Enumeration;
	}

	protected function parseParameters(array $args): CreateUserFieldParameters
	{
		return new CreateUserFieldParameters\CreateEnumerationParameters($args);
	}

	/**
	 * @param CreateUserFieldParameters\CreateEnumerationParameters $parameters
	 * @return Result
	 */
	protected function save(CreateUserFieldParameters $parameters): Result
	{
		$result = parent::save($parameters);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$id = $result->getData()['id'];

		$manager = new CUserFieldEnum();
		$enum = $this->prepareEnum($parameters->enumerationList);

		$isSuccess = $manager->setEnumValues($id, $enum);
		if (!$isSuccess)
		{
			return $result->fillErrorsFromApplication();
		}

		return $result;
	}

	/**
	 * @param EnumerationItem[] $enumerationList
	 * @return array
	 */
	protected function prepareEnum(array $enumerationList): array
	{
		$enum = [];

		$i = 0;
		foreach ($enumerationList as $enumerationItem)
		{
			$enum["n{$i}"] = [
				'DEF' => $enumerationItem->isDefault,
				'VALUE' => $enumerationItem->value,
				'SORT' => $enumerationItem->sort,
			];

			$i++;
		}

		return $enum;
	}
}
