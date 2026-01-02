<?php

namespace Bitrix\Intranet\Integration\HumanResources;

use Bitrix\HumanResources\Service\Access\Structure\StructureAccessService;
use Bitrix\HumanResources\Type\StructureAction;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\Department;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;

class PermissionInvitation
{
	private ?StructureAccessService $structureAccessService;

	/**
	 * @throws LoaderException
	 */
	public function __construct(
		private readonly ?int $userId,
	)
	{
		if (!Loader::includeModule('humanresources'))
		{
			throw new SystemException('The "humanresources" module is not installed');
		}

		$this->structureAccessService = new StructureAccessService();
		$this->structureAccessService->setAction(StructureAction::InviteUserAction);
		if ((int)$this->userId > 0)
		{
			$this->structureAccessService->setUserId($this->userId);
		}
	}

	/**
	 * @throws SystemException
	 * @throws LoaderException
	 */
	public static function createByCurrentUser(): self
	{
		return new self(CurrentUser::get()?->getId());
	}

	public function canInvite(): bool
	{
		return $this->structureAccessService->canDoActionWithAnyNode();
	}

	public function canInviteToDepartment(Department $department): bool
	{
		return $this->structureAccessService->canDoActionWithTheNode($department->getId());
	}

	public function findFirstPossibleAvailableDepartment(): ?Department
	{
		$node = $this->structureAccessService->findFirstPossibleAvailableNode();
		if (!$node)
		{
			return null;
		}

		return new Department(
			name: $node->name,
			id: $node->id,
			parentId: $node->parentId,
			createdBy: $node->createdBy,
			createdAt: $node->createdAt,
			updatedAt: $node->updatedAt,
			xmlId: $node->xmlId,
			sort: $node->sort,
			isActive: $node->active,
			isGlobalActive: $node->globalActive,
			depth: $node->depth,
			accessCode: $node->accessCode,
		);
	}
}