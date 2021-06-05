<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Service\Factory\Dynamic;

class Director
{
	public function getScenariosForNewCategory(int $entityTypeId, int $categoryId): Scenario\Collection
	{
		$scenarios = [];
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory && $factory instanceof Dynamic)
		{
			$scenarios = [];
			if ($factory->getType()->getIsSetOpenPermissions())
			{
				$scenarios[] = new Scenario\DefaultCategoryPermissions($entityTypeId, $categoryId);
			}
			$scenarios[] = new Scenario\DefaultStages(
				$factory->getStagesEntityId($categoryId),
				\CCrmStatus::getDynamicEntityStatusPrefix($entityTypeId, $categoryId),
				$categoryId
			);
			$scenarios[] = new Scenario\PurgeStagesCache($factory);
		}

		return new Scenario\Collection($scenarios);
	}
}
