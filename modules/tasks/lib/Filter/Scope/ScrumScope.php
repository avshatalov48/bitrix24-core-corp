<?php

namespace Bitrix\Tasks\Filter\Scope;

use Bitrix\Tasks\Filter\Preset\CompletedPreset;
use Bitrix\Tasks\Filter\Preset\InProgressPreset;
use Bitrix\Tasks\Filter\Preset\MyPreset;
use Bitrix\Tasks\Filter\Preset\ScrumPreset;
use Bitrix\Tasks\Filter\PresetCollection;

class ScrumScope extends AbstractScope
{
	public function __construct(private readonly int $userId)
	{}

	public function getPresetCollection(): PresetCollection
	{
		return (new PresetCollection())
			->add((new ScrumPreset())->setIsDefault(true))
			->add(new InProgressPreset())
			->add(new CompletedPreset())
			->add(new MyPreset($this->userId))
		;
	}
}
