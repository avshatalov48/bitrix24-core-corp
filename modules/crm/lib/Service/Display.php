<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Service\Display\Field;
use Bitrix\Crm\Service\Display\Options;

class Display
{
	/** @var $displayedFields Field[] */
	protected $displayedFields = [];
	/** @var $displayOptions Options */
	protected $displayOptions;
	protected $entityTypeId;
	private $skipEmptyFields = true;

	protected $items = [];

	public function __construct(int $entityTypeId, array $displayedFields, Options $displayOptions = null)
	{
		$this->entityTypeId = $entityTypeId;
		$this->displayedFields = $displayedFields;

		$this->displayOptions = $displayOptions ?? (new Options());
	}

	public function setDisplayOptions(Options $options): Display
	{
		$this->displayOptions = $options;

		return $this;
	}

	public function skipEmptyFields(bool $skipEmptyFields): Display
	{
		$this->skipEmptyFields = $skipEmptyFields;

		return $this;
	}

	public function addItem(int $itemId, array $itemFieldsValues): Display
	{
		$this->items[$itemId] = $itemFieldsValues;

		return $this;
	}

	public function setItems(array $items): Display
	{
		$this->items = $items;

		return $this;
	}

	public function getValue(int $itemId, string $fieldName)
	{
		$values = $this->getValues($itemId);

		return $values[$fieldName] ?? null;
	}

	public function getValues(int $itemId): ?array
	{
		$allValues = $this->getAllValues();

		return $allValues[$itemId] ?? null;
	}

	public function getAllValues(): array
	{
		return $this->processValues($this->items);
	}

	public function processValues(array $items): array
	{
		$linkedValuesIds = [];

		$result = [];
		/**
		 * @var $displayedField Field
		 */
		foreach ($this->displayedFields as $fieldId => $displayedField)
		{
			$displayedField->setEntityTypeId($this->entityTypeId);
			foreach ($items as $itemId => $item)
			{
				$fieldValue = $items[$itemId][$fieldId] ?? null;
				if ($displayedField->useLinkedEntities())
				{
					$displayedField->prepareLinkedEntities(
						$linkedValuesIds,
						$fieldValue,
						$itemId,
						$fieldId
					);
				}
			}
		}

		$linkedEntitiesValues = (!empty($linkedValuesIds) ? $this->loadLinkedEntitiesValues($linkedValuesIds) : []);

		/**
		 * @var $displayedField Field
		 */
		foreach ($this->displayedFields as $fieldId => $displayedField)
		{
			$isExportContext = $displayedField->isExportContext();
			$restrictedValueReplacer = (
				$isExportContext
					? $this->displayOptions->getRestrictedValueTextReplacer()
					: $this->displayOptions->getRestrictedValueHtmlReplacer()
			);

			if ($displayedField->useLinkedEntities())
			{
				$displayedField->setLinkedEntitiesValues($linkedEntitiesValues[$displayedField->getType()] ?? null);
			}

			foreach ($items as $itemId => $item)
			{
				$fieldValue = $items[$itemId][$fieldId] ?? null;
				if ($displayedField->isValueEmpty($fieldValue))
				{
					if ($displayedField->isMultiple() && $displayedField->isUserField())
					{
						$result[$itemId][$fieldId] = ''; // multiple user fields should be converted from empty array to empty string
					}

					if ($this->skipEmptyFields)
					{
						continue;
					}
				}

				if (!isset($result[$itemId]) && $displayedField->isMultiple())
				{
					$result[$itemId] = [];
				}

				$result[$itemId][$fieldId] = ($displayedField->isMultiple() ? [] : '');

				if ($this->isRestrictedField($itemId, $fieldId))
				{
					$result[$itemId][$fieldId] = $restrictedValueReplacer;
					if (!empty($restrictedValueReplacer) && !$isExportContext)
					{
						$displayedField->setWasRenderedAsHtml(true);
					}
					continue;
				}

				$displayedField->prepareField();

				$result[$itemId][$fieldId] = $displayedField->getFormattedValue(
					$fieldValue,
					$itemId,
					$this->displayOptions
				);
			}
		}

		return $result;
	}

	/**
	 * Load values for special fields
	 * @param array $linkedValuesIds
	 * @return array
	 */
	protected function loadLinkedEntitiesValues(array $linkedValuesIds): array
	{
		$linkedEntitiesValues = [];
		foreach ($linkedValuesIds as $fieldType => $linkedEntity)
		{
			$field = Field::createByType($fieldType);
			$field->setEntityTypeId($this->entityTypeId);

			// collect multi data
			$field->loadLinkedEntities($linkedEntitiesValues, $linkedEntity);
		}

		return $linkedEntitiesValues;
	}

	/**
	 * @param string $itemId
	 * @param string $fieldId
	 * @return bool
	 */
	protected function isRestrictedField(string $itemId, string $fieldId): bool
	{
		$restrictedItemIds = $this->displayOptions->getRestrictedItemIds();
		$restrictedFieldsToShow = $this->displayOptions->getRestrictedFieldsToShow();

		return (in_array($itemId, $restrictedItemIds) && !in_array($fieldId, $restrictedFieldsToShow));
	}
}
