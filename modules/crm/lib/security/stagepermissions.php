<?php

namespace Bitrix\Crm\Security;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Security\EntityPermission\ApproveCustomPermsToExistRole;
use Bitrix\Crm\Security\Role\Manage\Permissions\Transition;
use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\Container;

final class StagePermissions
{
	private const MODULE_ID = 'crm';
	private const STUB_PERMISSIONS_OPTION = 'crm.stub.stage.permissions';

	private ?array $permissions = null;

	public function __construct(
		private int $entityTypeId,
		private ?int $categoryId = null,
	)
	{
		$this->permissions = $this->getPermissions();
	}

	public function fill(array &$stages): void
	{
		foreach ($stages as &$stage)
		{
			$stage['STAGES_TO_MOVE'] = $this->permissions[$stage['STATUS_ID']] ?? [];
		}
	}

	public static function fillAllPermissionsByStages(array &$stages): void
	{
		$allPermissions = array_values(
			array_map(static fn($stage) => $stage['STATUS_ID'], $stages),
		);

		foreach ($stages as &$stage)
		{
			$stage['STAGES_TO_MOVE'] = $allPermissions;
		}
	}

	public function getPermissionsByStatusId(string $statusId): array
	{
		return $this->permissions[$statusId] ?? [];
	}

	public function getPermissions(): array
	{
		if ($this->permissions === null)
		{
			$this->permissions = $this->getStubPermissions() ?? $this->getAllPermissions();
		}

		return $this->permissions;
	}

	private function getAllPermissions(): array
	{
		if (!\CCrmOwnerType::isUseFactoryBasedApproach($this->entityTypeId))
		{
			return [];
		}

		$factory = Container::getInstance()->getFactory($this->entityTypeId);
		if (!$factory || !$factory->isStagesSupported())
		{
			return [];
		}

		$permissionEntity = (new PermissionEntityTypeHelper($this->entityTypeId))->getPermissionEntityTypeForCategory((int)$this->categoryId);
		$permissionType = (new Transition())->code();

		$userPermissions = Container::getInstance()->getUserPermissions();
		$permissionLevel = PermissionsManager::getInstance($userPermissions->getUserId())
			->getPermissionLevel($permissionEntity, $permissionType)
		;

		$stages = $factory->getStages($this->categoryId)->getAll();
		$allStagesIds = array_map(static fn($stage) => $stage->getStatusId(), $stages);

		$isAdmin = $userPermissions->isAdminForEntity($this->entityTypeId);

		$permissionsByStage = [];
		foreach ($allStagesIds as $stageId)
		{
			if ((new ApproveCustomPermsToExistRole())->hasWaitingPermission(new Transition()))
			{
				$permissionsByStage[$stageId] = $allStagesIds;

				continue;
			}
			$transitions = $permissionLevel->getSettingsForStage($stageId);

			if (in_array(Transition::TRANSITION_ANY, $transitions))
			{
				$transitions = $allStagesIds;
			}
			$permissionsByStage[$stageId] = $isAdmin ? $allStagesIds : array_values(array_intersect($allStagesIds, $transitions)); //merge with role stage transitions
		}

		return $permissionsByStage;
	}

	private function getStubPermissions(): ?array
	{
		$userOptions = \CUserOptions::GetOption(
			self::MODULE_ID,
			self::STUB_PERMISSIONS_OPTION,
			[],
		);

		return $userOptions[$this->getStubPermissionsOptionName()] ?? null;
	}

	public function setStubPermissions(array $stubPermissions): self
	{
		\CUserOptions::SetOption(
			self::MODULE_ID,
			self::STUB_PERMISSIONS_OPTION,
			[ $this->getStubPermissionsOptionName() => $stubPermissions ],
		);

		return $this;
	}

	private function getStubPermissionsOptionName(): string
	{
		if ($this->categoryId === null)
		{
			return "{$this->entityTypeId}_stub_permissions";
		}

		return "{$this->entityTypeId}_{$this->categoryId}_stub_permissions";
	}
}
