<?php

namespace Bitrix\Tasks\Filter\Scope;

use Bitrix\Tasks\Filter\Preset\CompletedPreset;
use Bitrix\Tasks\Filter\Preset\DeferredPreset;
use Bitrix\Tasks\Filter\Preset\ExpireCandidatePreset;
use Bitrix\Tasks\Filter\Preset\ExpirePreset;
use Bitrix\Tasks\Filter\Preset\InProgressPreset;
use Bitrix\Tasks\Filter\PresetCollection;

class DefaultScope extends AbstractScope
{
	public function getPresetCollection(): PresetCollection
	{
		return (new PresetCollection())
			->add((new InProgressPreset())->setIsDefault(true))
			->add(new CompletedPreset())
			->add(new DeferredPreset())
			->add(new ExpirePreset())
			->add(new ExpireCandidatePreset())
		;
	}
}
