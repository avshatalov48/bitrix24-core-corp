<?php

namespace Bitrix\Tasks\Filter\Preset;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Internals\Counter\Role;
use Bitrix\Tasks\Internals\Task\Status;

class AccomplicePreset extends AbstractPreset
{
	public function getCode(): string
	{
		return Filter::ACCOMPLICE_PRESET;
	}

	protected function getName(): ?string
	{
		return Loc::getMessage('TASKS_PRESET_I_ACCOMPLICES');
	}

	protected function getFields(): array
	{
		return [
			'ROLEID' => Role::ACCOMPLICE,
			'STATUS' => [
				Status::PENDING,
				Status::IN_PROGRESS,
				Status::SUPPOSEDLY_COMPLETED,
				Status::DEFERRED,
			],
		];
	}
}
