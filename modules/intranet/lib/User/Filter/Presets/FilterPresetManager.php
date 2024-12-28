<?php

namespace Bitrix\Intranet\User\Filter\Presets;

use Bitrix\Intranet\User\Filter\IntranetUserSettings;

class FilterPresetManager
{
	/**
	 * @var FilterPreset[]
	 */
	private array $availablePresetList = [];
	private array $disabledPresetList = [];
	private ?IntranetUserSettings $filterSettings = null;

	/**
	 * @param FilterPreset[] $additionalPresets
	 */
	public function __construct(?IntranetUserSettings $filterSettings, array $additionalPresets = [])
	{
		$presetList = [
			new CompanyPreset(),
			new InvitedPreset(),
			new WaitConfirmationPreset(),
			new AdminPreset(),
			new ExtranetPreset(),
			new FiredPreset(),
			new CollaberPreset(),
		];
		$this->filterSettings = $filterSettings;

		foreach ($additionalPresets as $additionalPreset)
		{
			if ($additionalPreset instanceof FilterPreset)
			{
				$presetList[] = $additionalPreset;
			}
		}

		foreach ($presetList as $preset)
		{
			if ($this->isPresetAvailable($preset))
			{
				$this->availablePresetList[] = $preset;
			}
			else
			{
				$this->disabledPresetList[] = $preset;
			}
		}
	}

	public function getPresetsArrayData(array $defaultFields = []): array
	{
		$result = [];

		foreach ($this->availablePresetList as $preset)
		{
			$result[$preset->getId()] = $preset->toArray($defaultFields);
		}

		return $result;
	}

	/**
	 * @return FilterPreset[]
	 */
	public function getPresets(): array
	{
		return $this->availablePresetList;
	}

	/**
	 * @return FilterPreset[]
	 */
	public function getDisabledPresets(): array
	{
		return $this->disabledPresetList;
	}

	private function isPresetAvailable(FilterPreset $preset): bool
	{
		if (isset($this->filterSettings))
		{
			foreach ($preset->getFilterFields() as $field => $value)
			{
				if ($value === 'Y' && !$this->filterSettings->isFilterAvailable($field))
				{
					return false;
				}
			}
		}

		return true;
	}
}