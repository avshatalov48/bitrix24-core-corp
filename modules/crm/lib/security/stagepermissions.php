<?php

namespace Bitrix\Crm\Security;

use Bitrix\Crm\Security\EntityPermission\ApproveCustomPermsToExistRole;
use Bitrix\Crm\Security\Role\Manage\Permissions\Transition;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;

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

		$entityTypeName = $factory->getEntityName();
		if ($this->categoryId)
		{
			$entityTypeName = UserPermissions::getPermissionEntityType($this->entityTypeId, $this->categoryId);
		}

		$stages = $factory->getStages($this->categoryId)->getAll();
		$allStatusIds = array_map(static fn($stage) => $stage->getStatusId(), $stages);

		$userId = Container::getInstance()->getContext()->getUserId();
		$userPermissions = \CCrmRole::GetUserPerms($userId);
		$isAdmin = Container::getInstance()->getUserPermissions($userId)->isAdmin();
		$canWriteConfig = Container::getInstance()->getUserPermissions($userId)->canWriteConfig();

		$entityPermissions = $userPermissions['settings'][$entityTypeName][(new Transition())->code()] ?? [];
		$stageId = Container::getInstance()->getUserPermissions()->getStageFieldName($this->entityTypeId);

		$permissions = [];
		foreach ($allStatusIds as $statusId)
		{
			if ((new ApproveCustomPermsToExistRole())->hasWaitingPermission(new Transition()))
			{
				$permissions[$statusId] = $allStatusIds;

				continue;
			}

			$transitions = $entityPermissions[$stageId][$statusId] ?? $entityPermissions['-'] ?? [];
			if (
				(count($transitions) === 1 && reset($transitions) === Transition::TRANSITION_INHERIT)
			)
			{
				$transitions = $entityPermissions['-'] ?? [];
			}

			if (in_array(Transition::TRANSITION_ANY, $transitions))
			{
				$transitions = $allStatusIds;
			}
			$permissions[$statusId] = $isAdmin || $canWriteConfig ? $allStatusIds : array_values(array_intersect($allStatusIds, $transitions)); //merge with role stage transitions
		}

		return $permissions;
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
