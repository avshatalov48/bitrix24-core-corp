<?php

namespace Bitrix\Tasks\Filter\Preset;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Task\Status;
use CTaskListState;

class ExpirePreset extends AbstractPreset
{
	public function getCode(): string
	{
		return 'filter_tasks_expire';
	}

	protected function getName(): ?string
	{
		return Loc::getMessage('TASKS_PRESET_EXPIRED');
	}

	protected function getFields(): array
	{
		return [
			'STATUS' => [
				Status::PENDING,
				Status::IN_PROGRESS,
			],
			'PROBLEM' => CTaskListState::VIEW_TASK_CATEGORY_EXPIRED,
		];
	}
}
