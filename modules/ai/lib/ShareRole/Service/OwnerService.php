<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Service;

use Bitrix\AI\ShareRole\Repository\OwnerRepository;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\DB\Result;

class OwnerService
{
	public function __construct(
		protected OwnerRepository $ownerRepository
	)
	{
	}

	public function unsetDeletedFlagsForUsers(array $userIds, int $roleId): void
	{
		if (empty($userIds))
		{
			return;
		}

		$this->ownerRepository->unsetDeletedFlagsForUsers($userIds, $roleId);
	}
	public function deleteForCurrentUser(
		int $userId,
		int $roleId,
		bool $hasOwnerId,
		bool $isDeleted
	): Result|Data\AddResult
	{
		if ($hasOwnerId)
		{
			return $this->ownerRepository->setDeleteFlagOnRow($userId, $roleId, $isDeleted);
		}

		return $this->ownerRepository->createWithDeleteFlag($userId, $roleId, $isDeleted);
	}

	/**
	 * @param int $userId
	 * @param array $ownerIdsForSharingRoles
	 * @param bool $isDeleted
	 * @return array{?Data\AddResult, ?Data\Result}
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function changeDeletedForCurrentUserRoles(
		int   $userId,
		array $ownerIdsForSharingRoles,
		bool  $isDeleted
	): array
	{
		[$roleIdsForNewOwners, $ownerIdsForUpdate] = $this->extractRoleAndOwnerIds($ownerIdsForSharingRoles);

		if (!empty($roleIdsForNewOwners))
		{
			$resultCreate = $this->ownerRepository->createWithDeleteFlagMulti(
				$userId,
				$roleIdsForNewOwners,
				$isDeleted
			);

		}

		if (!empty($ownerIdsForUpdate))
		{
			$resultUpdate = $this->ownerRepository->setDeleteFlagOnRows(
				$ownerIdsForUpdate,
				$isDeleted
			);

		}

		return [$resultCreate ?? null, $resultUpdate ?? null];
	}

	private function extractRoleAndOwnerIds(array $ownerIdsForSharingRoles): array
	{
		$roleIdsForNewOwners = [];
		$ownerIdsForUpdate = [];
		foreach ($ownerIdsForSharingRoles as $roleId => $ownerIds)
		{
			if (empty($ownerIds))
			{
				$roleIdsForNewOwners[] = $roleId;
				continue;
			}

			foreach ($ownerIds as $ownerId)
			{
				if (empty($ownerId))
				{
					$roleIdsForNewOwners[] = $roleId;
					continue;
				}
				$ownerIdsForUpdate[] = (int)$ownerId;
			}
		}

		return [array_unique($roleIdsForNewOwners), array_unique($ownerIdsForUpdate)];
	}
}
