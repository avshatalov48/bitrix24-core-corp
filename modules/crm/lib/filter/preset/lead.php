<?php

namespace Bitrix\Crm\Filter\Preset;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Localization\Loc;

final class Lead extends Base
{
	public function getDefaultPresets(): array
	{
		return [
			self::ID_IN_WORK => [
				'name' => Loc::getMessage('CRM_PRESET_IN_WORK_LEADS'),
				'default' => true,
				'disallow_for_all' => true,
				'fields' => array_merge($this->defaultValues, [
					'STATUS_SEMANTIC_ID' => PhaseSemantics::getProcessSemantis()
				])
			],
			self::ID_MY => [
				'name' => Loc::getMessage('CRM_PRESET_MY_LEADS'),
				'fields' => array_merge($this->defaultValues, [
					'ASSIGNED_BY_ID_name' => $this->userName,
					'ASSIGNED_BY_ID' => $this->userId,
					'STATUS_SEMANTIC_ID' => PhaseSemantics::getProcessSemantis()
				])
			],
			self::ID_IN_CLOSED => [
				'name' => Loc::getMessage('CRM_PRESET_CLOSED_LEADS'),
				'fields' => array_merge($this->defaultValues, [
					'STATUS_SEMANTIC_ID' => PhaseSemantics::getFinalSemantis()
				])
			],
		];
	}
}
