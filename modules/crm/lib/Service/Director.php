<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Service\Scenario\Sign\B2e\DefaultEmployeeTriggers;
use Bitrix\Crm\Service\Scenario\Sign\B2e\DefaultTriggers;
use Bitrix\Crm\Service\Sign\B2e\TypeService;
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
		$scenarios[] = new Scenario\DefaultCategoryPermissions($entityTypeId, $categoryId, $factory->getType()->getIsSetOpenPermissions());

		$defaultStages = new Scenario\DefaultStages(
			$factory->getStagesEntityId($categoryId),
			\CCrmStatus::getDynamicEntityStatusPrefix($entityTypeId, $categoryId),
			$categoryId,
		);

		$typeService = Container::getInstance()->getSignB2eTypeService();
		$category = $typeService->getCategoryById($categoryId);
		$categoryCode = (string)($category['CODE'] ?? '');
		$defaultStagesData = $this->getDefaultStagesData($factory, $categoryCode);

		if (is_array($defaultStagesData))
		{
			$defaultStages->setStagesData($defaultStagesData);
		}
		if ($entityTypeId === CCrmOwnerType::SmartB2eDocument)
		{
			$scenarios[] = match ($categoryCode)
			{
				TypeService::SIGN_B2E_EMPLOYEE_ITEM_CATEGORY_CODE => new DefaultEmployeeTriggers($categoryId),
				default => new DefaultTriggers($categoryId),
			};
		}
		$scenarios[] = $defaultStages;
		$scenarios[] = new Scenario\PurgeStagesCache($factory);

		return new Scenario\Collection($scenarios);
	}

	private function getDefaultStagesData(Factory $factory, string $categoryCode): ?array
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
			return match ($categoryCode)
			{
				TypeService::SIGN_B2E_EMPLOYEE_ITEM_CATEGORY_CODE => \CCrmStatus::GetDefaultSmartB2eEmployeeDocumentStatuses(),
				default => \CCrmStatus::GetDefaultSmartB2eDocumentStatuses(),
			};
		}

		return null;
	}
}
