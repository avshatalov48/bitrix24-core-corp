<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Item;
use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Settings\InvoiceSettings;

class SmartInvoice implements PermissionEntity
{
	private function permissions(bool $isAutomationEnabled, array $stages): array
	{
		$permissions =  $isAutomationEnabled ?
			PermissionAttrPresets::crmEntityPresetAutomation()
			: PermissionAttrPresets::crmEntityPreset();

		return array_merge(
			$permissions,
			PermissionAttrPresets::crmEntityKanbanHideSum(),
			PermissionAttrPresets::crmStageTransition($stages)
		);
	}

	/**
	 * @return EntityDTO[]
	 */
	public function make(): array
	{
		if (!InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
		{
			return [];
		}

		$smartInvoiceFactory = Service\Container::getInstance()->getFactory(\CCrmOwnerType::SmartInvoice);
		if (!$smartInvoiceFactory)
		{
			return [];
		}

		$isAutomationEnabled = $smartInvoiceFactory->isAutomationEnabled();

		$result = [];
		foreach ($smartInvoiceFactory->getCategories() as $category)
		{
			$entityName = Service\UserPermissions::getPermissionEntityType(\CCrmOwnerType::SmartInvoice, $category->getId());
			$entityTitle = \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartInvoice);
			if ($smartInvoiceFactory->isCategoriesEnabled())
			{
				$entityTitle .= ' ' . $category->getSingleNameIfPossible();
			}

			$stages = $this->prepareStages($smartInvoiceFactory, $category);
			$perms = $this->permissions($isAutomationEnabled, $stages);

			$result[] = new EntityDTO(
				$entityName,
				$entityTitle,
				[Item::FIELD_NAME_STAGE_ID => $stages],
				$perms,
				null,
				'invoice',
				'#0B66C3',
			);
		}

		return $result;
	}

	private function prepareStages(Factory $smartInvoiceFactory, Category $category): array
	{
		$stages = [];
		foreach ($smartInvoiceFactory->getStages($category->getId()) as $stage)
		{
			$stages[$stage->getStatusId()] = $stage->getName();
		}

		return $stages;
	}
}
