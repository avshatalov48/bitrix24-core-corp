<?php

namespace Bitrix\Intranet\Settings\Requisite;

use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\PresetTable;

class PresetList
{
	private array $presets = [];

	public function __construct(
		private RequisiteList $requisiteList,
	)
	{
	}

	private function load(): void
	{
		$filter = [
			'ID' => array_column($this->requisiteList->toArray(), 'PRESET_ID')
		];
		$this->presets = PresetTable::getList(['filter' => $filter])->fetchAll();
	}

	public function toArray(): array
	{
		if (empty($this->presets))
		{
			$this->load();
		}

		return $this->presets;
	}

	public function getById(int $id)
	{
		if (empty($this->presets))
		{
			$this->load();
		}

		foreach ($this->presets as $preset)
		{
			if ((int)$preset['ID'] === $id)
			{
				return $preset;
			}
		}

		return null;
	}

	public function getFieldNameById(int $id): array
	{
		$preset = $this->getById($id);

		if (!is_array($preset['SETTINGS']))
		{
			$preset['SETTINGS'] = [];
		}

		return EntityPreset::getSingleInstance()->settingsGetFields($preset['SETTINGS']);
	}
}