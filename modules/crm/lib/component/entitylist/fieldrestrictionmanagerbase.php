<?php

namespace Bitrix\Crm\Component\EntityList;

use Bitrix\Crm\Filter\Filter as CrmFilter;
use Bitrix\Main\Grid;
use Bitrix\Main\UI\Filter;

abstract class FieldRestrictionManagerBase
{
	/**
	 * Is field(s) has restriction
	 *
	 * @return bool
	 */
	abstract public function hasRestrictions(): bool;

	/**
	 * Get restrictions slider js code
	 *
	 * @return string
	 */
	abstract public function getJsCallback(): string;

	/**
	 * @param string $fieldName
	 *
	 * @return bool
	 */
	abstract protected function isFieldRestricted(string $fieldName): bool;

	/**
	 * Remove contact/company fields from filter
	 *
	 * @param Filter\Options $filterOptions
	 */
	public function removeRestrictedFieldsFromFilter(Filter\Options $filterOptions): void
	{
		if (!$this->hasRestrictions())
		{
			return;
		}

		$presetWasChanged = false;

		foreach ($filterOptions->getPresets() as $presetId => $presetData)
		{
			$presetFields = $filterOptions->fetchPresetFields($presetData);
			foreach ($presetFields as $i => $filterFieldId)
			{
				if ($this->isFieldRestricted($filterFieldId))
				{
					$filterOptions->removeRowFromPreset($presetId, $filterFieldId);
					$presetWasChanged = true;
				}
			}
		}

		if ($presetWasChanged)
		{
			$filterOptions->save();
			$filterOptions->reset();
		}
	}

	/**
	 * Remove contact/company fields from grid sort
	 *
	 * @param Grid\Options $gridOptions
	 */
	public function removeRestrictedFieldsFromSort(Grid\Options $gridOptions): void
	{
		if (!$this->hasRestrictions())
		{
			return;
		}

		$sort = $gridOptions->GetSorting()['sort'];
		if (empty($sort))
		{
			return;
		}

		reset($sort);

		if ($this->isFieldRestricted(key($sort)))
		{
			$gridOptions->SetSorting('date_create', 'desc');
			$gridOptions->Save();
		}
	}

	/**
	 * Get grid rows belonging to contact/company
	 *
	 * @param array $headers
	 *
	 * @return array
	 */
	public function getRestrictedGridFields(array $headers): array
	{
		$result = [];

		foreach ($headers as $field)
		{
			$fieldId = $field['id'];
			if (
				isset($field['sort'])
				&& $field['sort']
				&& $this->isFieldRestricted($fieldId)
			)
			{
				$result[] = $fieldId;
			}
		}

		return $result;
	}

	/**
	 * Get filter fields belonging to contact/company
	 *
	 * @param CrmFilter $entityFilter
	 *
	 * @return array
	 */
	public function getRestrictedFilterFields(CrmFilter $entityFilter): array
	{
		$result = [];

		foreach ($entityFilter->getFields() as $field)
		{
			$fieldId = $field->getId();
			if ($this->isFieldRestricted($fieldId))
			{
				$result[] = $fieldId;
			}
		}

		return $result;
	}

	/**
	 * Allows return BX.Crm.Restriction.FilterFieldsRestriction component  right away
	 *
	 * @return bool
	 */
	public function returnJsComponent(): bool
	{
		return false;
	}
}
