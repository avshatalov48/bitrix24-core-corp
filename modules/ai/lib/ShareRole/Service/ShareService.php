<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Service;

use Bitrix\AI\ShareRole\Dto\CreateDto;
use Bitrix\AI\ShareRole\Events\Enums\ShareType;
use Bitrix\AI\ShareRole\Repository\FavoriteRepository;
use Bitrix\AI\ShareRole\Repository\ShareRepository;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\Routing\Exceptions\ParameterNotFoundException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\EntitySelector;

class ShareService
{
	protected const ENTITY_ID_DEFAULT = 'user';
	protected const ENTITY_ID_PROJECT = 'project';

	public function __construct(
		protected RoleService $roleService,
		protected ShareRepository $shareRepository,
		protected OwnerService $ownerService,
		protected FavoriteRepository $favoriteRepository,
	)
	{
	}

	public function create(CreateDto $requestDTO): AddResult
	{
		return $this->shareRepository->create($requestDTO);
	}

	public function prepareAccessCodes(array $accessCodes, int $userIdCreator): array
	{
		if (!empty($accessCodes))
		{
			return EntitySelector\Converter::convertToFinderCodes($accessCodes);
		}

		return EntitySelector\Converter::convertToFinderCodes([
			[$this->getEntityIdDefault(), $userIdCreator]
		]);
	}

	private function getEntityIdDefault(): string
	{
		return $this->getEntityIdInAccessList(static::ENTITY_ID_DEFAULT);
	}

	/**
	 * @param string $entity
	 * @return string
	 * @throws ParameterNotFoundException
	 */
	private function getEntityIdInAccessList(string $entity)
	{
		if (!in_array($entity, array_keys(EntitySelector\Converter::getCompatEntities())))
		{
			throw new ParameterNotFoundException(
				$entity . Loc::getMessage('AI_SERVICE_CODE_NOT_FOUND')
			);
		}

		return $entity;
	}

	public function getUsersIdsFromListRawCodes(array $accessCodesRaw): array
	{
		if (empty($accessCodesRaw))
		{
			return [];
		}

		$entityIdDefault = $this->getEntityIdDefault();
		$result = [];

		foreach ($accessCodesRaw as [$entityId, $id])
		{
			if ($entityId === $entityIdDefault && !empty($id))
			{
				$result[] = (int)$id;
			}
		}

		return $result;
	}

	public function findByRoleId(int $roleId): array
	{
		$idsList = $this->shareRepository->findByRoleId($roleId);

		if (empty($idsList))
		{
			return [];
		}

		return array_map(fn($item) => (int)$item['ID'], $idsList);
	}

	public function accessOnRole(int $roleId, int $userId): array
	{
		$result = $this->shareRepository->getInfoAccessRole([$roleId], $userId);
		if (empty($result))
		{
			return [null, null];
		}

		$idInOwnerTable = null;
		foreach ($result as $row)
		{
			if (!empty($row['OWNER_ID']))
			{
				$idInOwnerTable = $row['OWNER_ID'];
				break;
			}
		}

		return [true, $idInOwnerTable];
	}

	public function hasAccessOnRoleByCode(string $roleCode, int $userId): bool
	{
		return $this->shareRepository->getInfoAccessRoleByCode($roleCode, $userId);
	}

	public function getProjectAccessCodes(array $projectIds): array
	{
		if (empty($projectIds))
		{
			return [];
		}

		$projectEntityId = $this->getEntityIdProject();

		return EntitySelector\Converter::convertToFinderCodes(
			array_map(
				fn(int $projectId) => [$projectEntityId, (int)$projectId],
				$projectIds
			)
		);

	}

	private function getEntityIdProject(): string
	{
		return $this->getEntityIdInAccessList(static::ENTITY_ID_PROJECT);
	}

	public function deleteSharingForChange(int $roleId): void
	{
		$this->shareRepository->deleteRoleId($roleId);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getAccessCodesForRole(int $roleId): array
	{
		$accessCodes = $this->shareRepository->getAccessCodesForRole($roleId);
		if (empty($accessCodes))
		{
			return [];
		}

		$result = [];
		foreach ($accessCodes as $accessCode)
		{
			$result[] = $accessCode['ACCESS_CODE'];
		}

		return $result;
	}

	public function getShareType(array $accessCodes, int $userIdCreator): ShareType
	{
		if (empty($accessCodes))
		{
			return ShareType::NotShared;
		}

		$userCodeData = EntitySelector\Converter::convertToFinderCodes([
			[$this->getEntityIdDefault(), $userIdCreator]
		]);

		if (empty($userCodeData))
		{
			return ShareType::NotShared;
		}

		reset($userCodeData);
		$userAccessCode = current($userCodeData);
		if (empty($userAccessCode))
		{
			return ShareType::NotShared;
		}

		foreach ($accessCodes as $accessCode)
		{
			if ($accessCode !== $userAccessCode)
			{
				return ShareType::Shared;
			}
		}

		return ShareType::NotShared;
	}

	public function addInFavoriteList(int $userId, string $roleCode): void
	{
		$this->favoriteRepository->addFavoriteForUser($userId, $roleCode);
	}

	public function deleteInFavoriteList(int $userId, string $roleCode): void
	{
		$this->favoriteRepository->removeFavoriteForUser($userId, $roleCode);
	}

	public function accessOnRoles(array $roleIds, int $userId): array
	{
		$result = $this->shareRepository->getInfoAccessRole($roleIds, $userId);
		if (empty($result))
		{
			return [false, []];
		}

		$ownerIdsForSharingRoles = [];
		foreach ($result as $row)
		{
			if (empty($row['ROLE_ID']) && !array_key_exists('OWNER_ID', $row))
			{
				continue;
			}

			if (empty($ownerIdsForSharingRoles[$row['ROLE_ID']]))
			{
				$ownerIdsForSharingRoles[$row['ROLE_ID']] = [];
			}

			$ownerIdsForSharingRoles[$row['ROLE_ID']][] = $row['OWNER_ID'];
		}
		return [count($roleIds) === count($ownerIdsForSharingRoles), $ownerIdsForSharingRoles];
	}
}
