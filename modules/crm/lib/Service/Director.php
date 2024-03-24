<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Service\Scenario\Sign\B2e\DefaultTriggers;
use CCrmOwnerType;

class Director
{
	public function getScenariosForNewCategory(int $entityTypeId, int $categoryId): Scenario\Collection
	{
		$scenarios = [];
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!($factory instanceof Factory\Dynamic))
		{
			return new Scenario\Collection($scenarios);
		}
		if ($factory->getType()->getIsSetOpenPermissions())
		{
			$scenarios[] = new Scenario\DefaultCategoryPermissions($entityTypeId, $categoryId);
		}
		$defaultStages = new Scenario\DefaultStages(
			$factory->getStagesEntityId($categoryId),
			\CCrmStatus::getDynamicEntityStatusPrefix($entityTypeId, $categoryId),
			$categoryId,
		);
		$defaultStagesData = $this->getDefaultStagesData($factory);
		if (is_array($defaultStagesData))
		{
			$defaultStages->setStagesData($defaultStagesData);
		}
		if ($entityTypeId === CCrmOwnerType::SmartB2eDocument)
		{
			$scenarios[] = new DefaultTriggers($categoryId);
		}
		$scenarios[] = $defaultStages;
		$scenarios[] = new Scenario\PurgeStagesCache($factory);

		return new Scenario\Collection($scenarios);
	}

	private function getDefaultStagesData(Factory $factory): ?array
	{
		if ($factory instanceof Factory\SmartInvoice)
		{
			return \CCrmStatus::GetDefaultInvoiceStatuses();
		}
		if ($factory instanceof Factory\SmartDocument)
		{
			return \CCrmStatus::GetDefaultSmartDocumentStatuses();
		}
		if ($factory instanceof Factory\SmartB2eDocument)
		{
			return \CCrmStatus::GetDefaultSmartB2eDocumentStatuses();
		}

		return null;
	}
}
