<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Service\GridRole;

use Bitrix\AI\Entity\TranslateTrait;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Integration\Socialnetwork\GroupService;
use Bitrix\AI\ShareRole\Repository\GridRoleRepository;
use Bitrix\AI\ShareRole\Repository\ShareRepository;
use Bitrix\AI\ShareRole\Repository\UserAccessRepository;
use Bitrix\AI\ShareRole\Repository\UserRepository;
use Bitrix\AI\ShareRole\Service\GridRole\Dto\GridParamsDto;
use Bitrix\AI\ShareRole\Service\GridRole\Dto\GridRoleDto;
use Bitrix\AI\ShareRole\Service\GridRole\Dto\ShareDto;
use Bitrix\AI\ShareRole\Service\GridRole\Dto\SharingInfoDto;
use Bitrix\AI\ShareRole\Service\GridRole\Enum\Order;
use Bitrix\Intranet\User\Grid\Row\Assembler\Field\Helpers\UserPhoto;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\AccessRights\DataProvider;
use Bitrix\Main\UI\AccessRights\Entity\AccessRightEntityInterface;
use Bitrix\Main\UI\AccessRights\Entity\UserAll;
use Bitrix\Main\UI\AccessRights\Exception\UnknownEntityTypeException;

class GridRoleService
{
	use UserPhoto;
	use TranslateTrait;

	protected const MAX_SHARE_DATA = 5;
	protected DataProvider $accessRightsDataProvider;

	protected string $allUsersEntityName;

	protected string $nameFormat;

	protected array $existingGroupCodes;

	public function __construct(
		protected GridRoleRepository $gridRoleRepository,
		protected ShareRepository $shareRepository,
		protected UserRepository $userRepository,
		protected DateFormatService $dateFormatService,
		protected UserAccessRepository $userAccessRepository,
		protected GroupService $groupService
	)
	{
	}

	public function fillRoleIdsInFilter(int $userId, GridParamsDto $params): void
	{
		$params->filter->roleIds = $this->getIdsFromList(
			$this->gridRoleRepository->getAvailableRolesForGrid($userId, $params)
		);

		if (empty($params->filter->roleIds) || empty($params->filter->share))
		{
			return;
		}

		$params->filter->roleIds = $this->getIdsFromList(
			$this->gridRoleRepository->getAvailableRolesForGridWithUserInShare(
				$params->filter->share, $params->filter->roleIds
			)
		);
	}

	/**
	 * @param int $userId
	 * @param GridParamsDto $params
	 * @return array|GridRoleDto[]
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function getRolesForGrid(int $userId, GridParamsDto $params): array
	{
		if (empty($params->filter->roleIds))
		{
			return [];
		}

		$roles = $this->gridRoleRepository->getRolesForGrid($userId, $params);

		if (empty($roles))
		{
			return [];
		}

		[$gridRoleDtoList, $sharingInfoDto] = $this->fillSharing($roles, $userId);

		if ($sharingInfoDto->isEmptyUsersIdList())
		{
			return $gridRoleDtoList;
		}

		return $this->intExtraSortRoles(
			$this->fillSharingUsersInfo($gridRoleDtoList, $sharingInfoDto),
			$params
		);
	}

	/**
	 * @param GridRoleDto[] $gridRoleDtoList
	 * @param SharingInfoDto $sharingInfoDto
	 * @return GridRoleDto[]
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function fillSharingUsersInfo(array $gridRoleDtoList, SharingInfoDto $sharingInfoDto): array
	{
		$this->fillUsersSharingInfo($sharingInfoDto);

		if ($sharingInfoDto->isEmptyUsers())
		{
			return $gridRoleDtoList;
		}

		foreach ($gridRoleDtoList as $gridRoleDto)
		{
			$userIdList = $gridRoleDto->getUserIdsInShare();
			if (empty($userIdList))
			{
				continue;
			}

			$countInShare = $gridRoleDto->getCountInFillShare();
			if (empty($gridRoleDto->getCountShare()) || $countInShare >= static::MAX_SHARE_DATA)
			{
				continue;
			}

			foreach (array_slice($userIdList, 0, static::MAX_SHARE_DATA - $countInShare) as $userId)
			{
				$shareDto = $sharingInfoDto->getUserById($userId);
				if (!empty($shareDto))
				{
					$gridRoleDto->addInShare(
						$this->getCodeForUser($userId),
						$shareDto
					);
				}
			}
		}

		return $gridRoleDtoList;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	protected function fillUsersSharingInfo(SharingInfoDto $sharingInfoDto): SharingInfoDto
	{
		$usersData = $this->userRepository->getMainUserData($sharingInfoDto->getUserIdList());

		if (empty($usersData))
		{
			return $sharingInfoDto;
		}

		$format = $this->getNameFormat();

		foreach ($usersData as $userData)
		{
			$sharingInfoDto->addByUserData(
				(int)$userData['ID'],
				new ShareDto(
					\CUser::formatName(
						$format,
						[
							'NAME' => $userData['NAME'] ?? '',
							'LAST_NAME' => $userData['LAST_NAME'] ?? '',
							'SECOND_NAME' => $userData['SECOND_NAME'] ?? '',
							'EMAIL' => $userData['EMAIL'] ?? '',
							'ID' => $userData['ID'] ?? '',
							'LOGIN' => $userData['LOGIN'] ?? '',
						],
						true,
						false
					),
					$this->getCodeForUser((int)$userData['ID']),
					$this->getUserPhotoUrl([
						'PERSONAL_PHOTO' => $userData['PERSONAL_PHOTO'],
						'PERSONAL_GENDER' => $userData['PERSONAL_GENDER']
					])
				)
			);
		}

		return $sharingInfoDto;
	}

	/**
	 * @param list<array{ ID: string, SHARE_CODES: string}> $roles
	 * @param int $userId
	 * @return array{GridRoleDto[], SharingInfoDto}
	 */
	protected function fillSharing(array $roles, int $userId): array
	{
		$sharingInfoDto = $this->getSharingInfoDto();
		$format = $this->getNameFormat();

		$result = [];

		foreach ($roles as $role)
		{
			$gridRoleDto = $this->getGridRoleDto(
				$this->prepareForDTO($role, $format)
			);

			if (empty($role['SHARE_CODES']))
			{
				continue;
			}

			$codesInRole = explode(',', $role['SHARE_CODES']);

			if (empty($codesInRole))
			{
				continue;
			}

			$this->updateSharingInfo($codesInRole, $sharingInfoDto, $gridRoleDto, $userId);

			$result[] = $gridRoleDto;
		}

		return [$result, $sharingInfoDto];
	}

	protected function updateSharingInfo(
		array $codesInRole,
		SharingInfoDto $sharingInfoDto,
		GridRoleDto $gridRoleDto,
		int $userId
	): void
	{
		$userGroups = array_flip($this->userAccessRepository->getCodesForUserGroup($userId));
		$accessRightsDataProvider = $this->getAccessRightsDataProvider();

		foreach (array_unique($codesInRole) as $code)
		{
			if (UserAccessRepository::CODE_ALL_USER === $code)
			{
				$gridRoleDto->setShare(
					new ShareDto($this->getAllUsersEntityName(), $code)
				);

				break;
			}

			$gridRoleDto->incrementCountShare();

			if ($gridRoleDto->getCountInFillShare() >= static::MAX_SHARE_DATA)
			{
				continue;
			}

			if ($sharingInfoDto->hasCode($code))
			{
				$gridRoleDto->addInShare($code, $sharingInfoDto->getByCode($code));
				continue;
			}

			$accessCode = new AccessCode($code);
			if ($accessCode->getEntityType() === AccessCode::TYPE_USER)
			{
				$gridRoleDto->addUserIdInShare(
					$accessCode->getEntityId()
				);
				$sharingInfoDto->addUserId($accessCode->getEntityId());
				continue;
			}

			try
			{
				$entity = $accessRightsDataProvider->getEntity(
					$accessCode->getEntityType(),
					$accessCode->getEntityId()
				);
			}
			catch (UnknownEntityTypeException $exception)
			{
				$this->log('Not Found ' . $accessCode->getEntityType());
				continue;
			}

			if (!in_array($code, $this->getExistingGroupCodes(), true))
			{
				$gridRoleDto->decreaseCountShare();
				continue;
			}

			$sharingInfoDto->addForCodes(
				$this->getShareDTO($entity, $userGroups, $code)
			);

			$gridRoleDto->addInShare($code, $sharingInfoDto->getByCode($code));
		}
	}

	protected function getShareDTO(AccessRightEntityInterface $entity, array $userGroups, string $code): ShareDto
	{
		if (!array_key_exists($entity->getId(), $this->groupService->getNotVisibleGroupListInKeys()))
		{
			return new ShareDto($entity->getName(), $code, $entity->getAvatar());
		}

		$name = $entity->getName();
		$img = $entity->getAvatar();

		if (!array_key_exists($code, $userGroups))
		{
			$name = $this->getHiddenName();
			$img = null;
		}

		return new ShareDto($name, $code, $img);
	}

	protected function prepareForDTO(array $role, string $format): array
	{

		$role['AUTHOR'] = \CUser::formatName($format, [
			'NAME' => $role['AUTHOR_NAME'] ?? '',
			'LAST_NAME' => $role['AUTHOR_LAST_NAME'] ?? '',
			'SECOND_NAME' => $role['AUTHOR_SECOND_NAME'] ?? '',
			'EMAIL' => $role['AUTHOR_EMAIL'] ?? '',
			'ID' => $role['AUTHOR_ID'] ?? '',
			'LOGIN' => $role['AUTHOR_LOGIN'] ?? '',
		], true, false);

		$role['EDITOR'] = \CUser::formatName($format, [
			'NAME' => $role['EDITOR_NAME'] ?? '',
			'LAST_NAME' => $role['EDITOR_LAST_NAME'] ?? '',
			'SECOND_NAME' => $role['EDITOR_SECOND_NAME'] ?? '',
			'EMAIL' => $role['EDITOR_EMAIL'] ?? '',
			'ID' => $role['EDITOR_ID'] ?? '',
			'LOGIN' => $role['EDITOR_LOGIN'] ?? '',
		], true, false);

		if (!empty($role['AUTHOR_PHOTO_ID']) && array_key_exists('AUTHOR_GENDER', $role))
		{
			$role['AUTHOR_PHOTO_URL'] = $this->getUserPhotoUrl([
				'PERSONAL_PHOTO' => $role['AUTHOR_PHOTO_ID'],
				'PERSONAL_GENDER' => $role['AUTHOR_GENDER']
			]);
		}

		if (!empty($role['EDITOR_PHOTO_ID']) && array_key_exists('EDITOR_GENDER', $role))
		{
			$role['EDITOR_PHOTO_URL'] = $this->getUserPhotoUrl([
				'PERSONAL_PHOTO' => $role['EDITOR_PHOTO_ID'],
				'PERSONAL_GENDER' => $role['EDITOR_GENDER']
			]);
		}

		if (!empty($role['DATE_CREATE']) && $role['DATE_CREATE'] instanceof DateTime)
		{
			$role['DATE_CREATE_STRING'] = $this->dateFormatService->formatDate(
				$role['DATE_CREATE']->getTimestamp()
			);
		}

		if (!empty($role['DATE_MODIFY']) && $role['DATE_MODIFY'] instanceof DateTime)
		{
			$role['DATE_MODIFY_STRING'] = $this->dateFormatService->formatDate(
				$role['DATE_MODIFY']->getTimestamp()
			);
		}

		return $role;
	}

	protected function getSharingInfoDto(): SharingInfoDto
	{
		return new SharingInfoDto();
	}

	protected function getGridRoleDto(array $role): GridRoleDto
	{
		return new GridRoleDto($role);
	}

	protected function getCodeForUser(int $userId): string
	{
		return 'U' . $userId;
	}

	protected function getCodeForGroup(int $groupId): string
	{
		return 'SG' . $groupId;
	}


	protected function getIdsFromList(array $list): array
	{
		if (empty($list))
		{
			return [];
		}

		$result = [];
		foreach ($list as $item)
		{
			if (!empty($item['ID']))
			{
				$result[] = (int)$item['ID'];
			}
		}

		return array_unique($result);
	}

	protected function getAllUsersEntityName(): string
	{
		if (empty($this->allUsersEntityName))
		{
			$this->allUsersEntityName = (new UserAll(0))->getName();
		}

		return $this->allUsersEntityName;
	}

	protected function getExistingGroupCodes(): array
	{
		if (empty($this->existingGroupCodes))
		{
			$this->existingGroupCodes = $this->groupService->getAllGroupCodes();
			$this->existingGroupCodes = array_map(fn($value) => $this->getCodeForGroup($value), $this->existingGroupCodes);
		}
		return $this->existingGroupCodes;
	}

	protected function log(string $text): void
	{
		AddMessage2Log('AI_GRID_ROLE_SERVICE_ERROR: ' . $text);
	}

	protected function getAccessRightsDataProvider(): DataProvider
	{
		if (empty($this->accessRightsDataProvider))
		{
			$this->accessRightsDataProvider = new DataProvider();
		}

		return $this->accessRightsDataProvider;
	}

	protected function getNameFormat(): string
	{
		if (empty($this->nameFormat))
		{
			$this->nameFormat = \CSite::GetNameFormat();
		}

		return $this->nameFormat;
	}

	protected function getHiddenName(): string
	{
		return Loc::getMessage('AI_SERVICE_GRID_HIDDEN_ROLE_TITLE') ?? '';
	}

	/**
	 * @param GridRoleDto[] $gridRoleDtoList
	 * @param GridParamsDto $params
	 * @return GridRoleDto[]
	 */
	protected function intExtraSortRoles(array $gridRoleDtoList, GridParamsDto $params): array
	{
		if (empty($params->order[0]) || empty($params->order[1]))
		{
			return $gridRoleDtoList;
		}

		$fieldForOrder = $params->order[0];

		if ($fieldForOrder !== Order::Author->value && $fieldForOrder !== Order::Editor->value)
		{
			return $gridRoleDtoList;
		}

		return $this->sortRoles(
			$gridRoleDtoList,
			$fieldForOrder,
			$params->order[1]
		);
	}

	/**
	 * @param GridRoleDto[] $gridRoleDtoList
	 * @param string $fieldForOrder
	 * @param string $order
	 * @return array
	 */
	private function sortRoles(array $gridRoleDtoList, string $fieldForOrder, string $order): array
	{
		usort($gridRoleDtoList, function (GridRoleDto $a, GridRoleDto $b) use ($fieldForOrder, $order) {
			$aProperty = $a->getAuthor();
			$bProperty = $b->getAuthor();
			if ($fieldForOrder === Order::Editor->value)
			{
				$aProperty = $a->getEditor();
				$bProperty = $b->getEditor();
			}


			if ($aProperty === $bProperty)
			{
				return 0;
			}

			if ($order === Order::Asc)
			{
				return ($aProperty < $bProperty) ? -1 : 1;
			}

				return ($aProperty > $bProperty) ? -1 : 1;
		});

		return $gridRoleDtoList;
	}
}
