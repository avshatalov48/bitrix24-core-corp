<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2024 Bitrix
 */

namespace Bitrix\Intranet;

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Intranet\Enum\UserRole;
use Bitrix\Intranet\HR\Employee;
use Bitrix\Intranet\Counters\Counter;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\EntitySelector\EntityUsageTable;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserAccessTable;
use Bitrix\Socialnetwork\Collab\CollabFeature;
use Bitrix\Socialnetwork\UserToGroupTable;

class User
{
	private CurrentUser $currentUser;
	private int $userId;

	private static array $cacheFields = [];
	private static array $cacheAdmin = [];

	/**
	 * @throws ArgumentOutOfRangeException
	 */
	public function __construct(?int $userId = null)
	{
		if (!is_null($userId) && $userId <= 0)
		{
			throw new ArgumentOutOfRangeException('userId', 1);
		}
		$this->currentUser = CurrentUser::get();
		$this->userId = is_null($userId) ? $this->currentUser->getId() : $userId;
	}

	public function getId(): int
	{
		return $this->userId;
	}

	public function isIntranet(): bool
	{
		if ($this->isAdmin())
		{
			return true;
		}

		return $this->hasDepartment();
	}

	public function isEmail(): bool
	{
		$fields = $this->getFields();

		return array_key_exists('EXTERNAL_AUTH_ID', $fields)
			&& $fields['EXTERNAL_AUTH_ID'] === 'email';
	}

	public function isShop(): bool
	{
		$fields = $this->getFields();

		return array_key_exists('EXTERNAL_AUTH_ID', $fields)
			&& in_array($fields['EXTERNAL_AUTH_ID'], ['shop', 'sale', 'saleanonymous']);
	}

	public function isExternal(): bool
	{
		$fields = $this->getFields();

		return array_key_exists('EXTERNAL_AUTH_ID', $fields)
			&& in_array($fields['EXTERNAL_AUTH_ID'], UserTable::getExternalUserTypes());
	}

	public function isExtranet(): bool
	{
		return Loader::includeModule('extranet')
			&& in_array(\CExtranet::GetExtranetUserGroupID(), $this->getGroups());
	}

	private function hasDepartment(): bool
	{
		$fields = $this->getFields();

		return isset($fields["UF_DEPARTMENT"])
			&& (
				(
					is_array($fields["UF_DEPARTMENT"])
					&& (int)($fields["UF_DEPARTMENT"][0] ?? null) > 0
				)
				|| (
					!is_array($fields["UF_DEPARTMENT"])
					&& (int)$fields["UF_DEPARTMENT"] > 0
				)
			);
	}

	public function hasAccessToDepartment(): bool
	{
		$accessManager = new \CAccess;
		$accessManager->UpdateCodes(['USER_ID' => $this->userId]);

		$accessResult = UserAccessTable::query()
			->where('USER_ID', $this->userId)
			->whereLike('ACCESS_CODE', 'D%')
			->whereNotLike('ACCESS_CODE', 'DR%')
			->setLimit(1)
			->fetch();

		return !($accessResult === false);
	}

	public function isAdmin(): bool
	{
		if (array_key_exists($this->userId, self::$cacheAdmin))
		{
			return self::$cacheAdmin[$this->userId];
		}

		if ($this->currentUser->getId() === $this->userId && $this->currentUser->isAdmin())
		{
			self::$cacheAdmin[$this->userId] = true;

			return self::$cacheAdmin[$this->userId];
		}

		$groups = $this->getGroups();
		if (
			in_array(1, $groups)
			||
			Loader::includeModule('bitrix24')
			&&
			\CBitrix24::IsPortalAdmin($this->userId)
		)
		{
			self::$cacheAdmin[$this->userId] = true;

			return self::$cacheAdmin[$this->userId];
		}

		self::$cacheAdmin[$this->userId] = false;

		return self::$cacheAdmin[$this->userId];
	}

	public function getFields(): array
	{
		if (array_key_exists($this->userId, self::$cacheFields))
		{
			return self::$cacheFields[$this->userId];
		}

		$result = \CUser::GetById($this->userId)->fetch();
		self::$cacheFields[$this->userId] = is_array($result) ? $result : [];

		return self::$cacheFields[$this->userId];
	}

	/**
	 * @deprecated
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function createFilterForInvitedExtranetUser(): array
	{
		$result = [];
		if (
			Loader::includeModule('extranet')
			&& !$this->isExtranetAdmin()
			&& Loader::includeModule('socialnetwork')
		)
		{
			$workgroupIdList = [];
			$res = UserToGroupTable::getList([
				'filter' => [
					'=USER_ID' => $this->getId(),
					'@ROLE' => UserToGroupTable::getRolesMember(),
					'=GROUP.ACTIVE' => 'Y'
				],
				'select' => [ 'GROUP_ID' ]
			]);

			while ($userToGroupFields = $res->fetch())
			{
				$workgroupIdList[] = $userToGroupFields['GROUP_ID'];
			}
			$workgroupIdList = array_unique($workgroupIdList);

			if ($this->isIntranet())
			{
				if (!empty($workgroupIdList))
				{
					$subQuery = new \Bitrix\Main\Entity\Query(UserToGroupTable::getEntity());
					$subQuery->addSelect('USER_ID');
					$subQuery->addFilter('@ROLE', [UserToGroupTable::ROLE_REQUEST, UserToGroupTable::ROLE_USER]);
					$subQuery->addFilter('@GROUP_ID', $workgroupIdList);
					$subQuery->addGroup('USER_ID');

					$result[] = [
						'LOGIC' => 'OR',
						[
							'!UF_DEPARTMENT' => false
						],
						[
							'@ID' => new SqlExpression($subQuery->getQuery())
						],
					];
				}
				else
				{
					$result[] = ['!UF_DEPARTMENT' => false];
				}
			}
			else
			{
				$publicUserIdList = [];
				$userTypeFilter = [
					'ENTITY_ID' => \Bitrix\Main\UserTable::getUfId(),
					'FIELD_NAME' => 'UF_PUBLIC'
				];

				$userTypeResult = \CUserTypeEntity::GetList([], $userTypeFilter);
				if ($userTypeResult->Fetch())
				{
					$res = \Bitrix\Main\UserTable::getList([
						'filter' => [
							'!UF_DEPARTMENT' => false,
							'=UF_PUBLIC' => true,
						],
						'select' => [ 'ID' ]
					]);

					while($userFields = $res->fetch())
					{
						$publicUserIdList[] = (int)$userFields['ID'];
					}
				}

				if (
					empty($workgroupIdList)
					&& empty($publicUserIdList)
				)
				{
					$result[] = ['ID' => $this->getId()];
				}
				else if (!empty($workgroupIdList))
				{
					if (!empty($publicUserIdList))
					{
						$result[] = [
							'LOGIC' => 'OR',
							[
								'<=UG.ROLE' => UserToGroupTable::ROLE_USER,
								'@UG.GROUP_ID' => $workgroupIdList
							],
							[
								'@ID' => $publicUserIdList
							],
						];
					}
					else
					{
						$result[] = ['<=UG.ROLE' => UserToGroupTable::ROLE_USER];
						$result[] = ['@UG.GROUP_ID' => $workgroupIdList];
					}
				}
				else
				{
					$result[] = ['@ID' => $publicUserIdList];
				}
			}
		}

		return $result;
	}

	protected function isExtranetAdmin(): bool
	{
		if(!Loader::includeModule('extranet'))
		{
			return false;
		}

		$arGroups = (new \CUser())->GetUserGroup($this->getId());
		$iExtGroups = \CExtranet::GetExtranetUserGroupID();

		$arSubGroups = \CGroup::GetSubordinateGroups($arGroups) ?? [];
		if (in_array($iExtGroups, $arSubGroups))
		{
			return true;
		}

		if (
			Loader::includeModule('socialnetwork')
			&& \CSocNetUser::IsUserModuleAdmin($this->getId())
		)
		{
			return true;
		}

		return false;
	}

	public function numberOfInvitationsSent(): int
	{
		$query = UserTable::createInvitedQuery()->where('ACTIVE', 'Y');

		if (!$this->isAdmin())
		{
			$query->addFilter('INVITATION.ORIGINATOR_ID', $this->userId);
			$extFilter = $this->createFilterForInvitedExtranetUser();
			if (!empty($extFilter))
			{
				$query->addFilter(null, $extFilter);
			}
		}

		return $query->queryCountTotal();
	}

	public function fetchOriginatorUser(): ?self
	{
		$user = UserTable::query()
			->where('ID', $this->userId)
			->setSelect(['ID', 'OWN_USER_ID' => 'INVITATION.ORIGINATOR_ID'])
			->setLimit(1)
			->fetch();

		if (isset($user['OWN_USER_ID']) && (int)$user['OWN_USER_ID'] > 0)
		{
			return new static((int)$user['OWN_USER_ID']);
		}

		return null;
	}

	/**
	 * Returns sorted array of user id.
	 * Flags for correct complex sorting
	 * @param bool $onlyActive
	 * @param bool $withInvited
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getStructureSort(bool $onlyActive = true, bool $withInvited = true): array
	{
		$userDepartment = \CIntranetUtils::GetUserDepartments($this->userId);
		$departmentId = !empty($userDepartment) ? $userDepartment[0] : 0;
		$list = [];

		if ($departmentId)
		{
			if ($managerId = \CIntranetUtils::GetDepartmentManagerID($departmentId))
			{
				$list[] = $managerId;
			}

			$list = array_merge(
				$list,
				Employee::getInstance()->getListByDepartmentId($departmentId, $onlyActive, $withInvited),
			);
		}

		$list = array_merge(
			$list,
			$this->getUserUsageList()
		);

		return array_reverse(array_unique($list));
	}

	private function getUserUsageList(): array
	{
		$query = EntityUsageTable::query()
			->setSelect(['ENTITY_ID', 'ITEM_ID', 'MAX_LAST_USE_DATE'])
			->setGroup(['ENTITY_ID', 'ITEM_ID'])
			->where('USER_ID', $this->userId)
			->where('ENTITY_ID', 'user')
			->registerRuntimeField(new \Bitrix\Main\ORM\Fields\ExpressionField('MAX_LAST_USE_DATE', 'MAX(%s)', 'LAST_USE_DATE'))
			->setOrder(['MAX_LAST_USE_DATE' => 'asc'])
			->setLimit(20);

		$userEntityList = $query->exec()->fetchAll();
		$result = [];

		foreach ($userEntityList as $userEntity)
		{
			$result[] = $userEntity['ITEM_ID'];
		}

		return $result;
	}

	public function isInitializedUser(): bool
	{
		return !UserTable::createInvitedQuery()->where('ID', $this->getId())->queryCountTotal();
	}

	public function getInvitationCounterValue(): int
	{
		return (new Counter(Invitation::getInvitedCounterId()))->getValue($this);
	}

	public function getTotalInvitationCounterValue(): int
	{
		return (new Counter(Invitation::getTotalInvitationCounterId()))->getValue($this);
	}

	public function getWaitConfirmationCounterValue(): int
	{
		return (new Counter(Invitation::getWaitConfirmationCounterId()))->getValue($this);
	}

	public function getGender(): ?string
	{
		return $this->getFields()['PERSONAL_GENDER'] ?? null;
	}

	public function getUserRole(): UserRole
	{
		if (
			Loader::includeModule('bitrix24')
			&& \Bitrix\Bitrix24\Integrator::isIntegrator($this->userId)
		)
		{
			return UserRole::INTEGRATOR;
		}

		if ($this->isAdmin())
		{
			return UserRole::ADMIN;
		}

		if ($this->isIntranet())
		{
			return UserRole::INTRANET;
		}

		if (
			Loader::includeModule('socialnetwork')
			&& CollabFeature::isOn()
			&& ServiceContainer::getInstance()->getCollaberService()->isCollaberById($this->userId)
		)
		{
			return UserRole::COLLABER;
		}

		if ($this->isExtranet())
		{
			return UserRole::EXTRANET;
		}

		if ($this->isEmail())
		{
			return UserRole::EMAIL;
		}

		if ($this->isShop())
		{
			return UserRole::SHOP;
		}

		if ($this->isExternal())
		{
			return UserRole::EXTERNAL;
		}

		return UserRole::VISITOR;
	}

	private function getGroups(): array
	{
		global $USER;

		$groups = ($USER instanceof \CUser && $USER->GetID() === $this->userId)
			? $USER->GetUserGroupArray() : \CUser::GetUserGroup($this->userId)
		;

		return array_map('intval', is_array($groups) ? $groups : []);
	}
}
