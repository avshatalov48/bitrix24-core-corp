<?php

namespace Bitrix\Crm\Security\Role\Manage;

use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Security\Role\Manage\DTO\RoleSelectionManager\CreateSettingsDto;
use Bitrix\Crm\Security\Role\Manage\Manager\AllSelection;
use Bitrix\Crm\Security\Role\Manage\Manager\ButtonSelection;
use Bitrix\Crm\Security\Role\Manage\Manager\CustomSectionListSelection;
use Bitrix\Crm\Security\Role\Manage\Manager\CustomSectionSelection;
use Bitrix\Crm\Security\Role\Manage\Manager\Decorator;
use Bitrix\Crm\Security\Role\Manage\Manager\EntitySelection;
use Bitrix\Crm\Security\Role\Manage\Manager\EntitySelection\CrmEntitySelection;
use Bitrix\Crm\Security\Role\Manage\Manager\EntitySelection\CustomSectionEntitySelection;
use Bitrix\Crm\Security\Role\Manage\Manager\WebFormSelection;
use Bitrix\Crm\Service\Container;

final class RoleManagerSelectionFactory
{
	private ?string $customSectionCode = null;
	private bool $isAutomation = false;

	public function setCustomSectionCode(?string $customSectionCode): self
	{
		$this->customSectionCode = $customSectionCode;

		return $this;
	}

	public function setAutomation(bool $isAutomation): self
	{
		$this->isAutomation = $isAutomation;

		return $this;
	}

	public function create(?string $criterion): ?RoleSelectionManager
	{
		$managerInstance = null;
		$settings = $this->getCreateSettings($criterion);

		foreach ($this->getManagers() as $manager)
		{
			$managerInstance = $manager::create($settings);
			if ($managerInstance !== null)
			{
				break;
			}
		}

		return $this->decorate($managerInstance);
	}

	/**
	 * @return RoleSelectionManager[]|string[]
	 */
	private function getManagers(): array
	{
		if ($this->customSectionCode !== null)
		{
			return [
				CustomSectionEntitySelection::class,
				CustomSectionSelection::class,
			];
		}

		if ($this->isAutomation)
		{
			return [
				CustomSectionListSelection::class,
			];
		}

		return [
			CrmEntitySelection::class,
			ButtonSelection::class,
			WebFormSelection::class,
			AllSelection::class,
		];
	}

	private function getCreateSettings(?string $criterion = null): CreateSettingsDto
	{
		return (new CreateSettingsDto())
			->setCriterion($criterion)
			->setCustomSectionCode($this->customSectionCode)
		;
	}

	private function decorate(?RoleSelectionManager $manager): ?RoleSelectionManager
	{
		if ($manager === null)
		{
			return null;
		}

		if ($manager->prohibitToSaveRoleWithoutAtLeastOneRight())
		{
			$manager = new Decorator\CheckEmptyPermissions($manager);
		}

		return $manager;
	}

	public function createByEntity(int $entityTypeId, ?int $categoryId = null): ?RoleSelectionManager
	{
		if (!$this->isAvailableCategoryId($entityTypeId, $categoryId))
		{
			$categoryId = null;
		}

		if (CrmEntitySelection::isSuitableEntity($entityTypeId, $categoryId))
		{
			return $this->decorate(
				new CrmEntitySelection($entityTypeId, $categoryId)
			);
		}

		if (CustomSectionEntitySelection::isSuitableEntity($entityTypeId, $categoryId))
		{
			$customSection = IntranetManager::getCustomSectionByEntityTypeId($entityTypeId);
			$manager = new CustomSectionEntitySelection(
				$customSection,
				$entityTypeId,
				$categoryId,
			);

			return $this->decorate($manager);
		}

		return null;
	}

	private function isAvailableCategoryId(int $entityTypeId, ?int $categoryId = null): bool
	{
		if ($categoryId === null)
		{
			return true;
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory === null)
		{
			return false;
		}

		return EntitySelection::isSuitableCategoryId($factory, $categoryId);
	}
}
