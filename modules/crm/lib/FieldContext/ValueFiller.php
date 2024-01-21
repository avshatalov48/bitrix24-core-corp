<?php

namespace Bitrix\Crm\FieldContext;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Comparer\Difference;
use Bitrix\Crm\Entity\FieldDataProvider;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\UserField\Types\EnumType;

class ValueFiller
{
	private const SINGLE_FIELD_VALUE_ID = 0;
	private const POSITION_CURRENT = 'current';
	private const POSITION_PREVIOUS = 'previous';
	private const LINK_TO_OTHER_ENTITIES_TYPES = [
		'employee',
		'crm',
		'crm_status',
		'iblock_element',
		'iblock_section',
		'file',
	];

	private ItemIdentifier $itemIdentifier;
	private string $scope;
	private ?int $userId;
	private Difference $difference;
	private Repository $repository;
	private ?array $fieldsWithContext = null;

	public function __construct(int $entityTypeId, int $entityId, string $scope, ?int $userId = null)
	{
		$this->itemIdentifier = new ItemIdentifier($entityTypeId, $entityId);
		$this->scope = $scope;
		$this->userId = $userId;
	}

	public function fill(array $previousValues, array $currentValues): void
	{
		$availableFields = $this->getAvailableFields($this->itemIdentifier->getEntityTypeId(), $this->userId);
		if (empty($availableFields))
		{
			return;
		}

		$this->difference = $this->getDifference($previousValues, $currentValues);
		$this->repository = $this->getRepositoryInstance($this->itemIdentifier, $this->scope);

		foreach ($availableFields as $availableField)
		{
			if ($this->fieldValuesHasDifferent($availableField))
			{
				$this->processField($availableField);
			}
		}
	}

	protected function getAvailableFields(int $entityTypeId, ?int $userId = null): array
	{
		$userId ??= Container::getInstance()->getContext()->getUserId();

		$fields = (new FieldDataProvider($entityTypeId))->getAccessibleByUserFieldData($userId);

		$this->appendEntityFields($fields);

		return $fields;
	}

	protected function appendEntityFields(&$fields): void
	{
		$fields[] = [
			'ID' => 'COMMENTS',
			'MULTIPLE' => false,
			'TYPE' => 'string',
		];
	}

	protected function getDifference(array $previousValues, array $currentValues): Difference
	{
		return ComparerBase::compareEntityFields($previousValues, $currentValues);
	}

	protected function getRepositoryInstance(ItemIdentifier $itemIdentifier, string $scope): Repository
	{
		$itemIdentifier = new ItemIdentifier($itemIdentifier->getEntityTypeId(), $itemIdentifier->getEntityId());

		return (new Repository($itemIdentifier))->setContextId($this->getScopesMap()[$scope] ?? null);
	}

	protected function getScopesMap(): array
	{
		return Container::getInstance()->getFieldsContextManager()->getContextMap();
	}

	protected function fieldValuesHasDifferent(array $field): bool
	{
		return $this->difference->isChanged($field['ID']);
	}

	protected function processField(array $field): void
	{
		if ($field['MULTIPLE'] === true && $field['TYPE'] !== 'resourcebooking')
		{
			[$valueIds, $deletedValueIds] = $this->getMultipleFieldDifferenceValueIds($field);
		}
		else
		{
			[$valueIds, $deletedValueIds] = $this->getSingleFieldDifferenceValueIds();
		}

		$valueIds = array_values($valueIds);
		$deletedValueIds = array_values($deletedValueIds);

		$this->processFieldValues($field['ID'], $valueIds, $deletedValueIds);
	}

	protected function getMultipleFieldDifferenceValueIds(array $field): array
	{
		$fieldName = $field['ID'];

		$previousValues = $this->getFieldValue($fieldName, self::POSITION_PREVIOUS);
		$currentValues = $this->getFieldValue($fieldName, self::POSITION_CURRENT);

		if ($field['TYPE'] === EnumType::USER_TYPE_ID)
		{
			$valueIds = $this->getEnumDiffKeys($currentValues, $previousValues);
			$deletedValueIds = $this->getEnumDiffKeys($previousValues, $currentValues);
		}
		elseif (in_array($field['TYPE'], self::LINK_TO_OTHER_ENTITIES_TYPES, true))
		{
			$valueIds = $this->getDiffValues($currentValues, $previousValues);

			$deletedValueIds = $this->getDiffValues($previousValues, $currentValues);
			$deletedValueIds = array_diff($deletedValueIds, $valueIds);
		}
		else
		{
			$valueIds = $this->getDiffKeys($currentValues, $previousValues);

			$deletedValueIds = $this->getDiffKeys($previousValues, $currentValues);
			$deletedValueIds = array_diff($deletedValueIds, $valueIds);
		}

		if (!$this->repository->getContextId())
		{
			$deletedValueIds = array_merge($deletedValueIds, $valueIds);
			$valueIds = [];
		}

		return [$valueIds, $deletedValueIds];
	}

	protected function getFieldValue(string $fieldName, string $position)
	{
		$values = (
			$position === self::POSITION_PREVIOUS
				? $this->difference->getPreviousValue($fieldName)
				: $this->difference->getCurrentValue($fieldName)
		) ?? [];

		if (!is_array($values))
		{
			$values = [];
		}

		return $values;
	}

	private function getEnumDiffKeys(array $array1, array $array2): array
	{
		return array_keys(
			array_flip(
				array_diff($array1, $array2)
			)
		);
	}

	private function getDiffKeys(array $array1, array $array2): array
	{
		return array_keys(
			array_diff_assoc($array1, $array2)
		);
	}

	private function getDiffValues(array $array1, array $array2): array
	{
		return array_diff($array1, $array2);
	}

	protected function getSingleFieldDifferenceValueIds(): array
	{
		if ($this->repository->getContextId())
		{
			return [
				[self::SINGLE_FIELD_VALUE_ID],
				[],
			];
		}

		return [
			[],
			[self::SINGLE_FIELD_VALUE_ID],
		];
	}

	protected function processFieldValues(
		string $fieldId,
		array $newValueIds,
		array $unnecessaryValueIds
	): void
	{
		$repository = $this->repository;

		foreach ($newValueIds as $newValueId)
		{
			$repository->add($fieldId, $newValueId);
		}

		if (empty($unnecessaryValueIds))
		{
			return;
		}

		$fieldsWithContext = $this->getFieldsWithContext();

		foreach ($unnecessaryValueIds as $unnecessaryValueId)
		{
			if (in_array($fieldId, $fieldsWithContext, true))
			{
				$repository->delete($fieldId, $unnecessaryValueId);
			}
		}
	}

	protected function getFieldsWithContext(): array
	{
		if ($this->fieldsWithContext === null)
		{
			$data = $this->repository->getFieldsData();

			$this->fieldsWithContext = array_keys($data);
		}

		return $this->fieldsWithContext;
	}
}
