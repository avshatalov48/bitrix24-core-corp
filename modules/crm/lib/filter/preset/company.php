<?php

namespace Bitrix\Crm\Filter\Preset;

use Bitrix\Main\Localization\Loc;

final class Company extends Base
{
	public function getDefaultPresets(): array
	{
		return [
			self::ID_ALL => [
				'name' => Loc::getMessage('CRM_PRESET_ALL_COMPANIES'),
				'default' => true,
				'disallow_for_all' => true,
				'fields' => $this->defaultValues
			],
			self::ID_MY => [
				'name' => Loc::getMessage('CRM_PRESET_MY_COMPANIES'),
				'disallow_for_all' => true,
				'fields' => array_merge($this->defaultValues, [
					'ASSIGNED_BY_ID_name' => $this->userName,
					'ASSIGNED_BY_ID' => $this->userId
				])
			],
		];
	}
}
