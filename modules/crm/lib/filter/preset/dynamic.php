<?php

namespace Bitrix\Crm\Filter\Preset;

use Bitrix\Crm\Filter\ItemDataProvider;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Localization\Loc;

final class Dynamic extends Base
{
	public function getDefaultPresets(): array
	{
		$presets = [];

		if($this->isStagesEnabled)
		{
			$presets[self::ID_IN_WORK] = [
				'name' => Loc::getMessage('CRM_TYPE_FILTER_PRESET_IN_WORK'),
				'default' => true,
				'disallow_for_all' => false,
				'fields' => array_merge($this->defaultValues, [
					ItemDataProvider::FIELD_STAGE_SEMANTIC => PhaseSemantics::getProcessSemantis(),
				]),
			];
		}

		$presets[self::ID_MY] = [
			'name' => Loc::getMessage('CRM_TYPE_FILTER_PRESET_MY'),
			'default' => false,
			'disallow_for_all' => true,
			'fields' => array_merge($this->defaultValues, [
				'ASSIGNED_BY_ID_name' => $this->userName,
				'ASSIGNED_BY_ID' => $this->userId,
				ItemDataProvider::FIELD_STAGE_SEMANTIC => PhaseSemantics::getProcessSemantis(),
			]),
		];

		if($this->isStagesEnabled)
		{
			$presets[self::ID_IN_CLOSED] = [
				'name' => Loc::getMessage('CRM_TYPE_FILTER_PRESET_SUCCESS'),
				'default' => false,
				'disallow_for_all' => false,
				'fields' => array_merge($this->defaultValues, [
					ItemDataProvider::FIELD_STAGE_SEMANTIC => PhaseSemantics::getFinalSemantis(),
				]),
			];
		}

		return $presets;
	}
}
