<?php

namespace Bitrix\Crm\Service\WebForm\Scenario\DependencyScenario;

use Bitrix\Crm\Service\WebForm\Scenario\BaseScenario;

class DependencyScenarioCreator
{
	public static function getDependencyScenario(string $type): ?DependencyScenario
	{
		switch ($type)
		{
			case BaseScenario::SCENARIO_DEPENDENCY_UNRELATED:
				return new DependencyUnrelatedScenario();
			case BaseScenario::SCENARIO_DEPENDENCY_RELATED:
				return new DependencyRelatedScenario();
			case BaseScenario::SCENARIO_DEPENDENCY_EXCLUDING:
				return new DependencyExcludingScenario();
		}
		return null;
	}
}