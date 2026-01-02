<?php

namespace Bitrix\Tasks\Filter\Preset;

use Bitrix\Main\Localization\Loc;

class MyPreset extends AbstractPreset
{
	public function __construct(private readonly int $userId)
	{}

	public function getCode(): string
	{
		return 'filter_tasks_my';
	}

	protected function getName(): ?string
	{
		return Loc::getMessage('TASKS_PRESET_MY');
	}

	protected function getFields(): array
	{
		return [
			'RESPONSIBLE_ID' => $this->userId,
		];
	}
}
