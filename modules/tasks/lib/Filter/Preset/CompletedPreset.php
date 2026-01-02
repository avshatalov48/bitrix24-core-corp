<?php

namespace Bitrix\Tasks\Filter\Preset;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Task\Status;

class CompletedPreset extends AbstractPreset
{
	public function getCode(): string
	{
		return 'filter_tasks_completed';
	}

	protected function getName(): ?string
	{
		return Loc::getMessage('TASKS_PRESET_COMPLETED');
	}

	protected function getFields(): array
	{
		return [
			'STATUS' => [Status::COMPLETED],
		];
	}
}
