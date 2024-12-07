<?php

namespace Bitrix\Tasks\TourGuide;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Update\Preset;

class PresetsMoved extends TourGuide
{
	protected const OPTION_CATEGORY = 'tasks';
	protected const OPTION_NAME = 'preset_aha_moment_viewed';

	public function proceed(): bool
	{
		if ($this->isDisabled() || !Preset::isRolePresetsEnabled() || $this->isFinished())
		{
			return false;
		}

		// do not show this tour guide to new portals
		if ($this->hasActiveFirstExperienceGuides())
		{
			$this->finish();
			return false;
		}

		if ($this->hasActiveGuides())
		{
			return false;
		}

		return true;
	}

	protected function getDefaultSteps(): array
	{
		return [
			[
				'maxTriesCount' => 3,
				'currentTry' => 0,
				'isFinished' => false,
				'additionalData' => [],
			],
		];
	}

	protected function loadPopupData(): array
	{
		return [
			[
				[
					'title' => Loc::getMessage('TASKS_INTERFACE_FILTER_PRESETS_MOVED_TITLE'),
					'text' => Loc::getMessage('TASKS_INTERFACE_FILTER_PRESETS_MOVED_TEXT_V2'),
				],
			],
		];
	}

	private function isDisabled(): bool
	{
		return true;
	}
}
