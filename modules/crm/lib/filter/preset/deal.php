<?php

namespace Bitrix\Crm\Filter\Preset;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Localization\Loc;

final class Deal extends Base
{
	public function getDefaultPresets(): array
	{
		return [
			self::ID_IN_WORK => [
				'name' => Loc::getMessage('CRM_PRESET_IN_WORK_DEALS'),
				'default' => true,
				'fields' => array_merge($this->defaultValues, [
					'STAGE_SEMANTIC_ID' => PhaseSemantics::getProcessSemantis(),
				])
			],
			self::ID_MY => [
				'name' => Loc::getMessage('CRM_PRESET_MY_DEALS'),
				'disallow_for_all' => true,
				'fields' => array_merge($this->defaultValues, [
					'ASSIGNED_BY_ID_name' => $this->userName,
					'ASSIGNED_BY_ID' => $this->userId,
					'STAGE_SEMANTIC_ID' => PhaseSemantics::getProcessSemantis(),
				])
			],
			self::ID_IN_CLOSED => [
				'name' => Loc::getMessage('CRM_PRESET_CLOSED_DEALS'),
				'fields' => array_merge($this->defaultValues, [
					'STAGE_SEMANTIC_ID' => PhaseSemantics::getFinalSemantis(),
				])
			],
			self::ID_ROBOT_DEBUGGER => [
				'name' => Loc::getMessage('CRM_PRESET_DEALS_IN_ROBOT_DEBUGGER'),
				'fields' => [
					'ROBOT_DEBUGGER' => 'SHOW'
				],
			],
		];
	}
}
