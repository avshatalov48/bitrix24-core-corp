<?php

namespace Bitrix\Crm\Security\Role\Manage\Manager;

use Bitrix\Crm\AutomatedSolution\AutomatedSolutionManager;
use Bitrix\Crm\Integration\Intranet\CustomSection;
use Bitrix\Crm\Integration\Intranet\SystemPageProvider\PermissionsPage;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Security\Role\GroupCodeGenerator;
use Bitrix\Crm\Security\Role\Manage\DTO\RoleSelectionManager\CreateSettingsDto;
use Bitrix\Crm\Security\Role\Manage\Enum\Permission;
use Bitrix\Crm\Security\Role\Manage\PermissionEntityBuilder;
use Bitrix\Crm\Security\Role\Manage\RoleSelectionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;
use Bitrix\Crm\AutomatedSolution\Entity\AutomatedSolutionTable;

final class CustomSectionSelection implements RoleSelectionManager
{
	public const GROUP_CODE_PREFIX = 'AUTOMATED_SOLUTION_';

	public function __construct(
		private readonly CustomSection $customSection,
	)
	{
	}

	public static function create(?CreateSettingsDto $settingsDto): ?self
	{
		if ($settingsDto?->getCriterion() !== null)
		{
			return null;
		}

		$customSectionCode = $settingsDto?->getCustomSectionCode();
		if ($customSectionCode === null)
		{
			return null;
		}

		$customSection = IntranetManager::getCustomSection($customSectionCode);
		if ($customSection === null)
		{
			return null;
		}

		return new self($customSection);
	}

	public function buildModels(): array
	{
		$entityTypes = IntranetManager::getEntityTypesInCustomSection($this->customSection->getCode());

		return (new PermissionEntityBuilder())
			->include(Permission::Dynamic)
			->filterByEntityTypeIds(Permission::Dynamic, $entityTypes)
			->include(Permission::AutomatedSolutionConfig)
			->filterByAutomatedSolution(Permission::AutomatedSolutionConfig, $this->getAutomatedSolutionId())
			->buildOfMade()
		;
	}

	public function preSaveChecks(array $userGroups): Result
	{
		return new Result();
	}

	public function hasPermissionsToEditRights(): bool
	{
		$solutionId = $this->getAutomatedSolutionId();
		if ($solutionId === null)
		{
			return false;
		}

		return Container::getInstance()
			->getUserPermissions()
			->isAutomatedSolutionAdmin($solutionId)
		;
	}

	public function prohibitToSaveRoleWithoutAtLeastOneRight(): bool
	{
		return true;
	}

	public function needShowRoleWithoutRights(): bool
	{
		return true;
	}

	public function getSliderBackUrl(): ?Uri
	{
		$pages = $this->customSection->getPages();
		$firstPage = array_shift($pages);

		return IntranetManager::getUrlForCustomSectionPage(
			$this->customSection->getCode(),
			$firstPage?->getCode(),
		);
	}

	public function getUrl(): ?Uri
	{
		return IntranetManager::getUrlForCustomSectionPage(
			$this->customSection->getCode(),
			PermissionsPage::CODE,
		);
	}

	public function isAvailableTool(): bool
	{
		return Container::getInstance()
			->getIntranetToolsManager()
			->checkExternalDynamicAvailability()
		;
	}

	public function printInaccessibilityContent(): void
	{
		print AvailabilityManager::getInstance()
			->getExternalDynamicInaccessibilityContent()
		;
	}

	public function getGroupCode(): ?string
	{
		$automatedSolutionId = $this->getAutomatedSolutionId();

		return $automatedSolutionId ? GroupCodeGenerator::getGroupCodeByAutomatedSolutionId($automatedSolutionId) : null;
	}

	private function getAutomatedSolutionId(): ?int
	{
		$solutions = Container::getInstance()->getAutomatedSolutionManager()->getExistingAutomatedSolutions();
		$solution = $solutions[$this->customSection->getId()] ?? null;
		if (!isset($solution['ID']))
		{
			return null;
		}

		return (int)$solution['ID'];
	}

	public function getMenuId(): ?string
	{
		return $this->customSection->getId();
	}

	public function getControllerData(): array
	{
		return [
			'criterion' => null,
			'sectionCode' => $this->customSection->getCode(),
			'isAutomation' => false,
			'menuId' => $this->getMenuId(),
		];
	}

	public function getTitle(): string
	{
		return $this->customSection->getTitle();
	}
}
