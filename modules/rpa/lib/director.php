<?php

namespace Bitrix\Rpa;

use Bitrix\Rpa\Model\Type;
use Bitrix\Rpa\Scenario\Collection;
use Bitrix\Rpa\Scenario\DefaultStages;
use Bitrix\Rpa\Scenario\DraftType;
use Bitrix\Rpa\Scenario\TypeAutomation;
use Bitrix\Rpa\Scenario\TypeFieldSettings;

class Director
{
	public function getScenariosForType(Type $type): Collection
	{
		$scenarios = [];

		$stages = $type->getStages();
		if($stages->count() === 0)
		{
			$scenarios[] = new DefaultStages($type);
			$scenarios[] = new TypeAutomation($type);
			$scenarios[] = new TypeFieldSettings($type);
		}

		return new Collection($scenarios);
	}

	public function getDraftTypeScenarios(): Collection
	{
		$scenarios = [new DraftType()];

		return new Collection($scenarios);
	}
}