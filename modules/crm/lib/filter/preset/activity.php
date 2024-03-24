<?php

namespace Bitrix\Crm\Filter\Preset;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Localization\Loc;

final class Activity extends Base
{
	public function __construct()
	{
		parent::__construct();

		Loc::loadMessages(__FILE__);
	}

	public function getDefaultPresets(): array
	{
		return [
			'not_completed' => [
				'name' => Loc::getMessage('CRM_PRESET_NOT_COMPLETED'),
				'default' => true,
				'disallow_for_all' => true,
				'fields' => array_merge($this->defaultValues, [
					'COMPLETED' => ['N'],
					'RESPONSIBLE_ID_name' => $this->userName,
					'RESPONSIBLE_ID' => $this->userId,
					'STATUS_SEMANTIC_ID' => PhaseSemantics::getProcessSemantis(),
				]),
			],
			'completed' => [
				'name' => Loc::getMessage('CRM_PRESET_COMPLETED'),
				'disallow_for_all' => true,
				'fields' => array_merge($this->defaultValues, [
					'COMPLETED' => ['Y'],
					'RESPONSIBLE_ID_name' => $this->userName,
					'RESPONSIBLE_ID' => $this->userId,
					'STATUS_SEMANTIC_ID' => PhaseSemantics::getFinalSemantis(),
				]),
			],
			// @todo only deals can be used
			'not_completed_in_leads' => [
				'name' => Loc::getMessage('CRM_PRESET_NOT_COMPLETED_IN_LEADS'),
				'disallow_for_all' => true,
				'fields' => array_merge($this->defaultValues, [
					'COMPLETED' => ['N'],
					'RESPONSIBLE_ID_name' => $this->userName,
					'RESPONSIBLE_ID' => $this->userId,
					'BINDING_OWNER_TYPE_ID' => \CCrmOwnerType::Lead,
					'STATUS_SEMANTIC_ID' => PhaseSemantics::getProcessSemantis(),
				]),
			],
			'not_completed_in_deals' => [
				'name' => Loc::getMessage('CRM_PRESET_NOT_COMPLETED_IN_DEALS'),
				'disallow_for_all' => true,
				'fields' => array_merge($this->defaultValues, [
					'COMPLETED' => ['N'],
					'RESPONSIBLE_ID_name' => $this->userName,
					'RESPONSIBLE_ID' => $this->userId,
					'BINDING_OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
					'STATUS_SEMANTIC_ID' => PhaseSemantics::getProcessSemantis(),
				]),
			],
			'not_completed_all' => [
				'name' => Loc::getMessage('CRM_PRESET_NOT_COMPLETED_ALL'),
				'fields' => array_merge($this->defaultValues, [
					'COMPLETED' => ['N'],
					'STATUS_SEMANTIC_ID' => PhaseSemantics::getProcessSemantis(),
				]),
			],
		];
	}
}
