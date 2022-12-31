<?php

namespace Bitrix\Crm\Service;

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

		return null;
	}
}
