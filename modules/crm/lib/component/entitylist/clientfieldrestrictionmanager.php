<?php

namespace Bitrix\Crm\Component\EntityList;

class ClientFieldRestrictionManager
{
	/**
	 * Is deals count exceed?
	 * @return bool
	 */
	public function hasRestrictions(): bool
	{
		return \Bitrix\Crm\Restriction\RestrictionManager::getDealClientFieldsRestriction()->isExceeded();
	}

	/**
	 * Remove contact/company fields from grid sort
	 *
	 * @param \Bitrix\Main\Grid\Options $gridOptions
	 */
	public function removeRestrictedFieldsFromSort(\Bitrix\Main\Grid\Options $gridOptions): void
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
	 * Remove contact/company fields from filter
	 *
	 * @param \Bitrix\Main\UI\Filter\Options $filterOptions
	 */
	public function removeRestrictedFieldsFromFilter(\Bitrix\Main\UI\Filter\Options $filterOptions): void
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
	 * Get filter fields belonging to contact/company
	 *
	 * @param \Bitrix\Crm\Filter\Filter $entityFilter
	 * @return array
	 */
	public function getRestrictedFilterFields(\Bitrix\Crm\Filter\Filter $entityFilter): array
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
	 * Get grid rows belonging to contact/company
	 *
	 * @param array $headers
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
	 * Get restrictions slider js code
	 *
	 * @return string
	 */
	public function getJsCallback(): string
	{
		return (string)\Bitrix\Crm\Restriction\RestrictionManager::getDealClientFieldsRestriction()->prepareInfoHelperScript();
	}

	protected function isFieldRestricted(string $fieldName): bool
	{
		return (
			(
				mb_strpos($fieldName, 'CONTACT_') === 0
				|| mb_strpos($fieldName, 'COMPANY_') === 0
			)
			&& !in_array($fieldName, ['CONTACT_ID', 'COMPANY_ID'])
		);
	}

}
