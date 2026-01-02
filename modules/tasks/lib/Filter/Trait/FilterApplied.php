<?php

namespace Bitrix\Tasks\Filter\Trait;

trait FilterApplied
{
	public function isUserFilterApplied(): bool
	{
		$currentPreset = $this->filterOptions->getCurrentFilterId();
		$isDefaultPreset = ($this->filterOptions->getDefaultFilterId() === $currentPreset);
		$additionalFields = $this->filterOptions->getAdditionalPresetFields($currentPreset);
		$isSearchStringEmpty = ($this->filterOptions->getSearchString() === '');

		return (
			!$isSearchStringEmpty
			|| !$isDefaultPreset
			|| !empty($additionalFields)
		);
	}
}