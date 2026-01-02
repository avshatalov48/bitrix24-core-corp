<?php

namespace Bitrix\Tasks\Filter\Preset;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Task\Status;

class CollabPreset extends AbstractPreset
{
	public function getCode(): string
	{
		return 'filter_tasks_collab';
	}

	protected function getName(): ?string
	{
		return Loc::getMessage('TASKS_PRESET_COLLAB');
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
			'PARAMS' => [
				'ANY_TASK',
			],
		];
	}
}
