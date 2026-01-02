<?php

namespace Bitrix\Crm\Agent\Security\Service;

use Bitrix\Crm\Agent\Security\Service\PermissionExtender\ConfigExtender;
use Bitrix\Crm\Agent\Security\Service\RoleSeparator\CustomSectionList;
use Bitrix\Crm\Agent\Security\Service\RoleSeparator\PermissionType;
use Bitrix\Crm\Agent\Security\Service\RoleSeparator\PermissionTypeList;
use Bitrix\Crm\AutomatedSolution\AutomatedSolutionManager;
use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Integration\Catalog\Contractor\CategoryRepository;
use Bitrix\Crm\Security\Role\GroupCodeGenerator;
use Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\ButtonConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\ContractorConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\WebFormConfig;
use Bitrix\Crm\Service\Container;
use CCrmOwnerType;

final class RoleSeparatorFactory
{
	private AutomatedSolutionManager $automatedSolutionManager;

	public function __construct()
	{
		$this->automatedSolutionManager = Container::getInstance()->getAutomatedSolutionManager();
	}

	/**
	 * @return RoleSeparator[]
	 */
	public function getAll(): array
	{
		$separators = [
			$this->getSiteButtonSeparator(),
			$this->getWebFormSeparator(),
			$this->getAutomatedSolutionListSeparator(),
			...$this->getAutomatedSolutionSeparators(),
		];

		$contractorSeparator = $this->getContractorSeparator();
		if ($contractorSeparator !== null)
		{
			$separators[] = $contractorSeparator;
		}

		return $separators;
	}

	public function getSiteButtonSeparator(): PermissionType
	{
		return (new RoleSeparator\PermissionType('BUTTON', GroupCodeGenerator::getWidgetGroupCode()))
			->expandBy(new ConfigExtender(ButtonConfig::CODE));
	}

	public function getWebFormSeparator(): PermissionType
	{
		return (new RoleSeparator\PermissionType('WEBFORM', GroupCodeGenerator::getCrmFormGroupCode()))
			->expandBy(new ConfigExtender(WebFormConfig::CODE));
	}

	public function getAutomatedSolutionSeparators(): array
	{
		$separators = [];
		foreach ($this->automatedSolutionManager->getExistingAutomatedSolutions() as $solution)
		{
			$solutionId = $solution['ID'];
			$typeIds = $solution['TYPE_IDS'] ?? [];
			$entityTypeIds = [];

			foreach ($typeIds as $typeId)
			{
				$entityTypeId = Container::getInstance()->getType($typeId)?->getEntityTypeId();
				if (CCrmOwnerType::IsDefined($entityTypeId))
				{
					$entityTypeIds[] = $entityTypeId;
				}
			}

			if (empty($entityTypeIds))
			{
				continue;
			}

			$separators[] = (new RoleSeparator\CustomSection($solutionId, $entityTypeIds))
				->expandBy(new ConfigExtender(AutomatedSolutionConfig::generateEntity($solutionId)))
			;
		}

		return $separators;
	}

	public function getAutomatedSolutionListSeparator(): CustomSectionList
	{
		return new RoleSeparator\CustomSectionList();
	}

	public function getContractorSeparator(): ?PermissionTypeList
	{
		$permissionEntities = [
			$this->createContractorPermissionEntity(CCrmOwnerType::Contact),
			$this->createContractorPermissionEntity(CCrmOwnerType::Company),
		];

		$permissionEntities = array_filter($permissionEntities, static fn (?string $entity) => $entity !== null);
		if (empty($permissionEntities))
		{
			return null;
		}

		return (new PermissionTypeList($permissionEntities, GroupCodeGenerator::getContractorGroupCode()))
			->expandBy(new ConfigExtender(ContractorConfig::CODE));
	}

	private function createContractorPermissionEntity(int $entityTypeId): ?string
	{
		$contractorCategory = CategoryRepository::getByEntityTypeId($entityTypeId);
		if ($contractorCategory === null)
		{
			return null;
		}

		return (new PermissionEntityTypeHelper($entityTypeId))
			->getPermissionEntityTypeForCategory($contractorCategory->getId());
	}
}
