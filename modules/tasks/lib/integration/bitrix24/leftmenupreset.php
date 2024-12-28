<?php

namespace Bitrix\Tasks\Integration\Bitrix24;

use Bitrix\Bitrix24\Preset\PresetTasksAI;
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
}