<?php

namespace Bitrix\Tasks\Filter\Preset;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Task\Status;

class DeferredPreset extends AbstractPreset
{
	public function getCode(): string
	{
		return 'filter_tasks_deferred';
	}

	protected function getName(): ?string
	{
		return Loc::getMessage('TASKS_PRESET_DEFERRED');
	}

	protected function getFields(): array
	{
		return [
			'STATUS' => [Status::DEFERRED],
		];
	}
}
