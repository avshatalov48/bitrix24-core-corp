<?php

namespace Bitrix\Tasks\Integration\Bitrix24;

use Bitrix\Bitrix24\Preset\PresetTasksAI;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

class LeftMenuPreset
{
	public function getTasksAiCode(): ?string
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return null;
		}

		return PresetTasksAI::CODE;
	}

	public function isCurrentPresetIsTasksAi(): bool
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return false;
		}

		$currentPresetCode = Option::get('bitrix24', 'preset:id', null);
		$tasksAiPresetCode = $this->getTasksAiCode();

		return !is_null($currentPresetCode)
			&& $currentPresetCode === $tasksAiPresetCode;
	}
}