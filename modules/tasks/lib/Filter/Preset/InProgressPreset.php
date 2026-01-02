<?php

namespace Bitrix\Tasks\Filter\Preset;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Task\Status;

class InProgressPreset extends AbstractPreset
{
	public function getCode(): string
	{
		return 'filter_tasks_in_progress';
	}

	protected function getName(): ?string
	{
		return Loc::getMessage('TASKS_PRESET_IN_PROGRESS');
	}

	protected function getFields(): array
	{
		return [
			'STATUS' => [
				Status::PENDING,
				Status::IN_PROGRESS,
				Status::SUPPOSEDLY_COMPLETED,
				Status::DEFERRED,
			],
		];
	}
}
