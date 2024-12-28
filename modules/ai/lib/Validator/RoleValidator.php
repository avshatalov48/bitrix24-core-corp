<?php declare(strict_types=1);

namespace Bitrix\AI\Validator;

use Bitrix\AI\Exception\ValidateException;
use Bitrix\AI\ShareRole\Repository\ShareRepository;
use Bitrix\AI\ShareRole\Service\FavoriteService;
use Bitrix\AI\ShareRole\Service\OwnerService;
use Bitrix\AI\ShareRole\Service\RoleService;
use Bitrix\AI\ShareRole\Service\ShareService;
use Bitrix\Main\Localization\Loc;

class RoleValidator
{
	public function __construct(
		protected RoleService     $roleService,
		protected ShareRepository $shareRepository,
		protected ShareService    $shareService,
		protected OwnerService    $ownerService,
		protected FavoriteService    $favoriteService,
	)
	{
	}

	public function getRoleIdNotSystemByCode(string $roleCode, string $fieldName): int
	{
		[$roleId, $isSystem] = $this->roleService->getMainRoleDataByCode($roleCode);
		if (is_null($roleId) || $roleId === 0)
		{
			throw new ValidateException($fieldName, 'not found by code');
		}

		if ($isSystem)
		{
			throw new ValidateException($fieldName, 'The role is system');
		}

		return $roleId;
	}

	public function hasRoleIdInShare(int $roleId, string $fieldName): array
	{
		$roleIds = $this->shareService->findByRoleId($roleId);

		if (empty($roleIds))
		{
			throw new ValidateException($fieldName, 'sharing with this ID is not found');
		}

		return $roleIds;
	}

	public function accessOnRole(int $roleId, string $fieldName, int $userId): bool
	{
		$this->checkUserId($userId);

		[$hasAccess, $inOwnerId] = $this->shareService->accessOnRole($roleId, $userId);
		if (!$hasAccess)
		{
			throw new ValidateException($fieldName, 'user has no access to this role');
		}

		return !empty($inOwnerId);
	}

	private function checkUserId(int $userId): void
	{
		if (empty($userId))
		{
			throw new ValidateException('currentUser', 'the user is not authorized or not found');
		}
	}

	public function getRoleByCode(string $code, string $fieldName): int
	{
		$roleId = $this->roleService->getRoleIdByCode($code);

		if(is_null($roleId) || $roleId === 0)
		{
			throw new ValidateException($fieldName, 'code not found');
		}

		return $roleId;
	}

	/**
	 * @throws ValidateException
	 */
	public function hasInFavoriteList(string $roleCode, string $fieldName, int $userId): void
	{
		if (!$this->favoriteService->isFavoriteRole($userId, $roleCode))
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_IS_NOT_IN_FAVORITE'));
		}
	}

	/**
	 * @throws ValidateException
	 */
	public function hasNotInFavoriteList(string $roleCode, string $fieldName, int $userId): void
	{
		if ($this->favoriteService->isFavoriteRole($userId, $roleCode))
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_IN_FAVORITE'));
		}
	}

	/**
	 * @param int $roleId
	 * @param string $fieldName
	 * @param int $userId
	 * @return void
	 * @throws ValidateException
	 */
	public function inAccessibleIgnoreDelete(int $roleId, string $fieldName, int $userId): void
	{
		$roleIdInAccessibleList = $this->roleService->getRoleIdInAccessibleList($userId, $roleId);
		if (is_null($roleIdInAccessibleList) || $roleIdInAccessibleList === 0)
		{
			throw new ValidateException($fieldName, Loc::getMessage('AI_VALIDATOR_NOT_ACCESSIBLE'));
		}
	}

	public function getRoleByCodesNotSystems(array $roleCodes, string $fieldName): array
	{
		$rolesData = $this->roleService->getMainRoleDataByCodes($roleCodes);
		if (empty($rolesData))
		{
			throw new ValidateException($fieldName, 'roles not found');
		}

		$roleIds = [];
		foreach ($rolesData as $roleData)
		{
			[$roleId, $isSystem] = $roleData;
			if (is_null($roleId) || $roleId === 0)
			{
				throw new ValidateException($fieldName, 'roles not found');
			}

			if ($isSystem)
			{
				throw new ValidateException($fieldName, 'The role is system');
			}

			$roleIds[] = $roleId;
		}

		if (count($roleCodes) !== count($roleIds))
		{
			throw new ValidateException($fieldName, 'not found by codes');
		}

		return $roleIds;
	}

	public function accessOnRoles(array $roleIds, string $fieldName, int $userId): array
	{
		$this->checkUserId($userId);

		[$hasAccess, $ownerIdsForSharingRoles] = $this->shareService->accessOnRoles($roleIds, $userId);
		if(!$hasAccess)
		{
			throw new ValidateException($fieldName, 'no access');
		}

		return $ownerIdsForSharingRoles;
	}
}
