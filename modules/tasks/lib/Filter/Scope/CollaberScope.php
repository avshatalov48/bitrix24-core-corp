<?php

namespace Bitrix\Tasks\Filter\Scope;

use Bitrix\Tasks\Filter\Preset\CollabPreset;
use Bitrix\Tasks\Filter\Preset\CompletedPreset;
use Bitrix\Tasks\Filter\Preset\DeferredPreset;
use Bitrix\Tasks\Filter\Preset\ExpireCandidatePreset;
use Bitrix\Tasks\Filter\Preset\ExpirePreset;
use Bitrix\Tasks\Filter\Preset\InProgressPreset;
use Bitrix\Tasks\Filter\PresetCollection;

class CollaberScope extends AbstractScope
{
	public function getPresetCollection(): PresetCollection
	{
		return (new PresetCollection())
			->add((new InProgressPreset())->setIsDefault(true))
			->add(new CollabPreset())
			->add(new CompletedPreset())
			->add(new DeferredPreset())
			->add(new ExpirePreset())
			->add(new ExpireCandidatePreset())
		;
	}
}
