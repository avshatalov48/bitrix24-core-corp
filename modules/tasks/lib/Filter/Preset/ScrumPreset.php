<?php

namespace Bitrix\Tasks\Filter\Preset;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Scrum\Form\EntityForm;

class ScrumPreset extends AbstractPreset
{
	public function getCode(): string
	{
		return Filter::SCRUM_PRESET;
	}

	protected function getName(): ?string
	{
		return Loc::getMessage('TASKS_PRESET_SCRUM');
	}

	protected function getFields(): array
	{
		return [
			'STATUS' => [
				Status::PENDING,
				Status::IN_PROGRESS,
				Status::SUPPOSEDLY_COMPLETED,
				Status::DEFERRED,
				EntityForm::STATE_COMPLETED_IN_ACTIVE_SPRINT,
			],
			'STORY_POINTS' => '',
		];
	}
}
