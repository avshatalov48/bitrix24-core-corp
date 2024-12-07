<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Service;

use Bitrix\AI\SharePrompt\Repository\OwnerRepository;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\DB\Result;

class OwnerService
{
	public function __construct(
		protected OwnerRepository $ownerRepository
	)
	{
	}

	public function deleteForCurrentUser(
		int $userId,
		int $promptId,
		bool $hasOwnerId,
		bool $isDeleted
	): Result|Data\AddResult
	{
		if ($hasOwnerId)
		{
			return $this->ownerRepository->setDeleteFlagOnRow($userId, $promptId, $isDeleted);
		}

		return $this->ownerRepository->createWithDeleteFlag($userId, $promptId, $isDeleted);
	}

	/**
	 * @param int $userId
	 * @param array $ownerIdsForSharingPrompts
	 * @param bool $isDeleted
	 * @return array{?Data\AddResult, ?Data\Result}
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function changeDeletedForCurrentUserPrompts(
		int $userId,
		array $ownerIdsForSharingPrompts,
		bool $isDeleted
	): array
	{
		$promptIdsForNewOwners = [];
		$ownerIdsForUpdate = [];
		foreach ($ownerIdsForSharingPrompts as $promptId => $ownerIds)
		{
			if (empty($ownerIds))
			{
				$promptIdsForNewOwners[] = $promptId;
				continue;
			}

			foreach ($ownerIds as $ownerId)
			{
				if (empty($ownerId))
				{
					$promptIdsForNewOwners[] = $promptId;
					continue;
				}
				$ownerIdsForUpdate[] = (int)$ownerId;
			}
		}

		if (!empty($promptIdsForNewOwners))
		{
			$resultCreate = $this->ownerRepository->createWithDeleteFlagMulti(
				$userId,
				array_unique($promptIdsForNewOwners),
				$isDeleted
			);

		}

		if (!empty($ownerIdsForUpdate))
		{
			$this->ownerRepository->setDeleteFlagOnRows(
				array_unique($ownerIdsForUpdate), $isDeleted
			);

			$resultUpdate = new \Bitrix\Main\Result();
		}

		return [$resultCreate ?? null, $resultUpdate ?? null];
	}

	public function deleteFromFavoriteListWithCheck(int $userId, int $promptId): void
	{
		$ownerIdData = $this->ownerRepository->getByPromptIdAndUserId($userId, $promptId);
		if (empty($ownerIdData['ID']))
		{
			return;
		}

		$this->ownerRepository->setFavoriteFlagOnRow((int)$ownerIdData['ID'], false);
	}

	public function addFavoriteForUser(int $userId, int $promptId): Data\UpdateResult|Data\AddResult
	{
		$ownerIdData = $this->ownerRepository->getByPromptIdAndUserId($userId, $promptId);
		if (empty($ownerIdData['ID']))
		{
			return $this->ownerRepository->createWithFavoriteFlag($userId, $promptId);
		}

		return $this->ownerRepository->setFavoriteFlagOnRow((int)$ownerIdData['ID']);
	}

	/**
	 * @param int[] $usersIds
	 * @return void
	 */
	public function unsetDeletedFlagsForUsers(array $usersIds, int $promptId): void
	{
		if (empty($usersIds))
		{
			return;
		}

		$this->ownerRepository->unsetDeletedFlagsForUsers($usersIds, $promptId);
	}

	public function getFavoriteIdByUserIdAndPromptId(int $userId, int $promptId): ?int
	{
		$data = $this->ownerRepository->getFavoriteByPromptIdAndUserId($userId, $promptId);
		if (empty($data['ID']))
		{
			return null;
		}

		return (int)$data['ID'];
	}

	public function isFavoritePrompt(int $userId, int $promptId): bool
	{
		$data = $this->ownerRepository->getFavoriteFlag($userId, $promptId);

		return !empty($data['IS_FAVORITE']);
	}
}
