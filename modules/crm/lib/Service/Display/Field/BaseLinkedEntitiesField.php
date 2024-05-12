<?php


namespace Bitrix\Crm\Service\Display\Field;


use Bitrix\Crm\Service\Display\Field;
use Bitrix\Crm\Service\Display\Options;

abstract class BaseLinkedEntitiesField extends Field
{
	protected $linkedEntitiesValues = [];

	public function useLinkedEntities(): bool
	{
		return true;
	}

	public function prepareLinkedEntities(
		array &$linkedEntities,
		$fieldValue,
		int $itemId,
		string $fieldId
	): void
	{
		$fieldType = $this->getType();
		foreach ((array)$fieldValue as $value)
		{
			if (!is_scalar($value) || $value === '' || $value <= 0)
			{
				continue;
			}
			$linkedEntities[$fieldType]['FIELD'][$itemId][$fieldId][$value] = $value;
			$linkedEntities[$fieldType]['ID'][$value] = $value;
		}
	}

	protected function getFormattedValueForGrid($fieldValue, int $itemId, Options $displayOptions)
	{
		$results = $this->getFormattedValueForKanban($fieldValue, $itemId, $displayOptions);
		return (
			is_array($results)
				? implode($displayOptions->getMultipleFieldsDelimiter(), $results)
				: $results
		);
	}

	/**
	 * @return array
	 */
	protected function getLinkedEntitiesValues(): array
	{
		return $this->linkedEntitiesValues;
	}

	/**
	 * @param array|null $linkedEntitiesValues
	 * @return Field
	 */
	public function setLinkedEntitiesValues(?array $linkedEntitiesValues): Field
	{
		$this->linkedEntitiesValues = ($linkedEntitiesValues ?? []);
		return $this;
	}
}
