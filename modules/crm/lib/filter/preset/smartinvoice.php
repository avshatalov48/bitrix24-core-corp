<?php

namespace Bitrix\Crm\Filter\Preset;

use Bitrix\Crm\Filter\ItemDataProvider;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Localization\Loc;

final class SmartInvoice extends Base
{
	public function getDefaultPresets(): array
	{
		return [
			self::ID_IN_WORK => [
				'name' => Loc::getMessage('CRM_PRESET_IN_WORK_SI'),
				'default' => true,
				'disallow_for_all' => false,
				'fields' => array_merge($this->defaultValues, [
					ItemDataProvider::FIELD_STAGE_SEMANTIC => PhaseSemantics::getProcessSemantis(),
				])
			],
			self::ID_MY => [
				'name' => Loc::getMessage('CRM_PRESET_MY_SI'),
				'disallow_for_all' => true,
				'fields' => array_merge($this->defaultValues, [
					'ASSIGNED_BY_ID_name' => $this->userName,
					'ASSIGNED_BY_ID' => $this->userId,
					ItemDataProvider::FIELD_STAGE_SEMANTIC => PhaseSemantics::getProcessSemantis(),
				])
			],
			self::ID_IN_CLOSED => [
				'name' => Loc::getMessage('CRM_PRESET_CLOSED_SI'),
				'disallow_for_all' => false,
				'fields' => array_merge($this->defaultValues, [
					ItemDataProvider::FIELD_STAGE_SEMANTIC => PhaseSemantics::getFinalSemantis(),
				])
			],
		];
	}
}
