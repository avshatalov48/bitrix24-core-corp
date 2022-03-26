<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Service\Factory\Dynamic;
use Bitrix\Crm\Service\Factory\SmartInvoice;

class Director
{
	public function getScenariosForNewCategory(int $entityTypeId, int $categoryId): Scenario\Collection
	{
		$scenarios = [];
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory instanceof SmartInvoice)
		{
			$defaultStages = new Scenario\DefaultStages(
				$factory->getStagesEntityId($categoryId),
				\CCrmStatus::getDynamicEntityStatusPrefix($entityTypeId, $categoryId),
				$categoryId,
			);
			$defaultStages->setStagesData(\CCrmStatus::GetDefaultInvoiceStatuses());
			$scenarios[] = $defaultStages;
			$scenarios[] = new Scenario\PurgeStagesCache($factory);
		}
		elseif ($factory instanceof Dynamic)
		{
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
