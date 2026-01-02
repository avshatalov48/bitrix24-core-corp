<?php

namespace Bitrix\Tasks\Filter\Preset;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Internals\Counter\Role;
use Bitrix\Tasks\Internals\Task\Status;

class OriginatorPreset extends AbstractPreset
{
	public function getCode(): string
	{
		return Filter::ORIGINATOR_PRESET;
	}

	protected function getName(): ?string
	{
		return Loc::getMessage('TASKS_PRESET_I_ORIGINATOR');
	}

	protected function getFields(): array
	{
		return [
			'ROLEID' => Role::ORIGINATOR,
			'STATUS' => [
				Status::PENDING,
				Status::IN_PROGRESS,
				Status::SUPPOSEDLY_COMPLETED,
				Status::DEFERRED,
			],
		];
	}
}
