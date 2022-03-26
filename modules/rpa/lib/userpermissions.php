<?php

namespace Bitrix\Rpa;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa\Model\EO_Timeline;
use Bitrix\Rpa\Model\Item;
use Bitrix\Rpa\Model\PermissionTable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ModuleManager;
use Bitrix\Rpa\Model\Stage;
use Bitrix\Rpa\Model\Timeline;
use Bitrix\Rpa\Model\Type;

class UserPermissions
{
	public const ENTITY_STAGE = 'STAGE';
	public const ENTITY_TYPE = 'TYPE';

	public const ACTION_ITEMS_CREATE = 'ITEMS_CREATE';
	public const ACTION_CREATE = 'CREATE';
	public const ACTION_VIEW = 'VIEW';
	public const ACTION_MODIFY = 'MODIFY';
	public const ACTION_MOVE = 'MOVE';
	public const ACTION_DELETE = 'DELETE';

	public const PERMISSION_NONE = '';
	public const PERMISSION_SELF = 'A';
	public const PERMISSION_DEPARTMENT = 'D';
	public const PERMISSION_ANY = 'X';
	public const PERMISSION_ALLOW = 'X';

	public const ACCESS_CODE_ALL_USERS = 'UA';

	protected $isAdmin;
	protected $isExtranet;
	protected $userId;
	protected $permissions;
	protected $accessCodes;
	protected $availableNextStages = [];

	public function __construct(int $userId)
	{
		$this->userId = $userId;
		$this->loadUserPermissions();
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function canViewAtLeastOneType(): bool
	{
		return static::canViewAnyType();
	}

	public function canViewType(int $typeId): bool
	{
		if ($this->isAccessDeniedToEverything())
		{
			return false;
		}
		if(static::canViewAnyType())
		{
			return true;
		}

		return $this->canPerform(self::ENTITY_TYPE, $typeId, self::ACTION_VIEW);
	}

	public function canCreateType(): bool
	{
		if ($this->isAccessDeniedToEverything())
		{
			return false;
		}

		return true;
	}

	public function canModifyType(int $typeId): bool
	{
		return $this->canPerform(self::ENTITY_TYPE, $typeId, self::ACTION_MODIFY);
	}

	public function canDeleteType(int $typeId): bool
	{
		return $this->canModifyType($typeId);
	}

	public function canViewItemsInStage(Type $type, int $stageId): bool
	{
		if ($this->isAccessDeniedToEverything())
		{
			return false;
		}
		if(static::canViewOnAnyStage())
		{
			return true;
		}

		return ($this->canModifyItemsInStage($type, $stageId) || $this->canPerform(self::ENTITY_STAGE, $stageId, self::ACTION_MOVE));
	}

	public function canModifyItemsInStage(Type $type, int $stageId): bool
	{
		if ($this->isAccessDeniedToEverything())
		{
			return false;
		}
		if(static::canEditItemOnAnyStage())
		{
			return $this->canAddItemsToType($type->getId());
		}
		if($this->isSimplePermissionsForTheFirstStage())
		{
			$firstStage = $type->getFirstStage();
			if($firstStage && $firstStage->getId() === $stageId)
			{
				return $this->canAddItemsToType($type->getId());
			}
		}

		return $this->canPerform(self::ENTITY_STAGE, $stageId, self::ACTION_MODIFY);
	}

	public function canAddItemsToType(int $typeId): bool
	{
		return $this->canPerform(self::ENTITY_TYPE, $typeId, static::ACTION_ITEMS_CREATE);
	}

	public function canViewItem(Item $item): bool
	{
		if ($this->isAccessDeniedToEverything())
		{
			return false;
		}
		if($item->getCreatedBy() === $this->userId)
		{
			return true;
		}
		if(
			$item->getPreviousStageId() > 0 &&
			($this->canViewItemsInStage($item->getType(), $item->getStageId()) || $this->canViewItemsInStage($item->getType(), $item->getPreviousStageId()))
		)
		{
			return true;
		}

		if($this->canViewItemsInStage($item->getType(), $item->getStageId()))
		{
			return true;
		}

		return false;
	}

	public function canMoveFromStage(Type $type, int $stageId): bool
	{
		if ($this->isAccessDeniedToEverything())
		{
			return false;
		}
		if(static::canMoveAnywhere())
		{
			return true;
		}
		if($this->isSimplePermissionsForTheFirstStage())
		{
			$firstStage = $type->getFirstStage();
			if($firstStage && $firstStage->getId() === $stageId && $this->canAddItemsToType($type->getId()))
			{
				return true;
			}
		}
		if(self::isAlwaysCanMoveToTheNextStage())
		{
			$stage = $type->getStages()->getByPrimary($stageId);
			if($stage && !$stage->isSuccess() && !$stage->isFail())
			{
				return true;
			}
		}

		return $this->canPerform(self::ENTITY_STAGE, $stageId, self::ACTION_MOVE);
	}

	public function canMoveItem(Item $item, int $fromStageId, int $toStageId): bool
	{
		$result = false;

		if ($this->isAccessDeniedToEverything())
		{
			return false;
		}

		if(static::canMoveAnywhere())
		{
			return true;
		}

		// moving forward
		if($this->isSimplePermissionsForTheFirstStage())
		{
			$firstStage = $item->getType()->getFirstStage();
			$result = ($firstStage && $firstStage->getId() === $fromStageId && $item->getCreatedBy() === $this->userId);
		}
		if(!$result && $this->canPerform(self::ENTITY_STAGE, $fromStageId, self::ACTION_MOVE))
		{
			$result = $this->isMovingFromStageToStageAvailable($item->getType(), $fromStageId, $toStageId);
		}
		// move back
		if(!$result && $this->isMoverAlwaysCanMoveBack() && $item->getMovedBy() === $this->userId)
		{
			$toStage = $item->getType()->getStages()->getByPrimary($toStageId);
			$result = ($toStage && isset($toStage->getPossibleNextStageIds()[$fromStageId]));
		}

		return $result;
	}

	public function canDeleteItem(Item $item): bool
	{
		if ($this->isAccessDeniedToEverything())
		{
			return false;
		}
		if($item->getCreatedBy() === $this->getUserId())
		{
			return true;
		}

		if($this->hasAdminAccess())
		{
			return true;
		}

		$taskManager = Driver::getInstance()->getTaskManager();
		if($taskManager && $taskManager->getUserItemIncompleteCounter($item) > 0)
		{
			return false;
		}

		return (
			$this->canModifyItemsInStage($item->getType(), $item->getStageId())
		);
	}

	public function canViewComment(?Item $item = null, ?EO_Timeline $timelineRecord = null): bool
	{
		if ($this->isAccessDeniedToEverything())
		{
			return false;
		}
		if ($timelineRecord && $timelineRecord->getUserId() === $this->getUserId())
		{
			return true;
		}

		if ($item)
		{
			return $this->canModifyItemsInStage($item->getType(), $item->remindActualStageId());
		}

		return $this->hasAdminAccess();
	}

	public function canAddComment(Item $item): bool
	{
		return $this->canModifyItemsInStage($item->getType(), $item->remindActualStageId());
	}

	public function canUpdateComment(Timeline $timeline): bool
	{
		if ($this->isAccessDeniedToEverything())
		{
			return false;
		}
		if($this->hasAdminAccess())
		{
			return true;
		}

		$canViewItem = true;

		$item = $timeline->getItem();
		if($item)
		{
			$canViewItem = $this->canViewItem($item);
		}

		return ($canViewItem && ($timeline->getUserId() === $this->getUserId()));
	}

	public function canDeleteComment(Timeline $timeline): bool
	{
		return $this->canUpdateComment($timeline);
	}

	protected function isMovingFromStageToStageAvailable(Type $type, int $fromStageId, int $toStageId): bool
	{
		$fromStage = $type->getStages()->getByPrimary($fromStageId);
		return ($fromStage && isset($fromStage->getPossibleNextStageIds()[$toStageId]));
	}

	protected function getNextStageId(Type $type, int $stageId): ?int
	{
		$stages = clone $type->getStages();
		reset($stages);
		$isPreviousStage = false;
		foreach($stages as $stage)
		{
			if($isPreviousStage)
			{
				return $stage->getId();
			}
			if($stage->getId() === $stageId)
			{
				$isPreviousStage = true;
			}
		}

		return null;
	}

	/**
	 * Returns true if an item can be moved from this stage from at least one another stage.
	 *
	 * @param Stage $stageTo
	 * @return bool
	 * @throws \Bitrix\Main\SystemException
	 */
	public function canMoveToStage(Stage $stageTo): bool
	{
		if ($this->isAccessDeniedToEverything())
		{
			return false;
		}
		if(static::canMoveAnywhere())
		{
			return true;
		}

		$type = $stageTo->getType();
		$typeId = $type->getId();

		if(!isset($this->availableNextStages[$typeId]))
		{
			$this->availableNextStages[$typeId] = [];
			$stages = clone $type->getStages();
			reset($stages);
			foreach($stages as $stage)
			{
				if($this->canMoveFromStage($type, $stage->getId()))
				{
					foreach($stage->getPossibleNextStageIds() as $nextStageId)
					{
						$this->availableNextStages[$typeId][$nextStageId] = $nextStageId;
					}
				}
			}
		}

		return isset($this->availableNextStages[$typeId][$stageTo->getId()]);
	}

	public function getFilterForViewableItems(Type $type): array
	{
		return [
			[
				'LOGIC' => 'OR',
				[
					'=CREATED_BY' => $this->userId,
				],
				[
					'@STAGE_ID' => $this->getViewableStageIds($type),
				],
			]
		];
	}

	protected function getViewableStageIds(Type $type): array
	{
		if(static::canViewOnAnyStage())
		{
			return $type->getStages()->getIdList();
		}

		$firstStageId = null;
		$firstStage = $type->getFirstStage();
		if($firstStage)
		{
			$firstStageId = $firstStage->getId();
		}
		$viewableStageIds = [];
		foreach($this->permissions as $entity => $permission)
		{
			if(preg_match('/^'.static::ENTITY_STAGE.'(\d+)$/', $entity, $matches))
			{
				$matches[1] = (int) $matches[1];
				if($matches[1] !== $firstStageId)
				{
					$viewableStageIds[] = $matches[1];
				}
			}
		}

		if(empty($viewableStageIds))
		{
			$viewableStageIds[] = '!@#$%';
		}

		return $viewableStageIds;
	}

	public function getFilterForViewableTypes(): array
	{
		$viewableTypeIds = $this->getViewableTypeIds();

		if(empty($viewableTypeIds))
		{
			return [
				'=ID' => 0,
			];
		}

		return [
			'@ID' => $viewableTypeIds,
		];
	}

	protected function getViewableTypeIds(): array
	{
		$viewableTypeIds = [];
		foreach($this->permissions as $entity => $permission)
		{
			if(preg_match('/^'.static::ENTITY_TYPE.'(\d+)$/', $entity, $matches))
			{
				$viewableTypeIds[] = (int) $matches[1];
			}
		}

		return $viewableTypeIds;
	}

	public function getFilterForEditableTypes(): array
	{
		if($this->hasAdminAccess())
		{
			return [];
		}

		$editableTypeIds = $this->getEditableTypeIds();

		if(empty($editableTypeIds))
		{
			return [
				'=ID' => 0,
			];
		}

		return [
			'@ID' => $editableTypeIds,
		];
	}

	protected function getEditableTypeIds(): array
	{
		$editableTypeIds = [];
		foreach($this->permissions as $entity => $permissions)
		{
			if(preg_match('/^'.static::ENTITY_TYPE.'(\d+)$/', $entity, $matches))
			{
				if(isset($permissions[static::ACTION_MODIFY]) && $permissions[static::ACTION_MODIFY] > static::PERMISSION_NONE)
				{
					$editableTypeIds[] = (int) $matches[1];
				}
			}
		}

		return $editableTypeIds;
	}

	public static function filterUserIdsWhoCanViewItem(Item $item, array $userIds): array
	{
		$result = [];
		foreach($userIds as $userId)
		{
			$userId = (int)$userId;
			if($userId > 0)
			{
				$userPermissions = Driver::getInstance()->getUserPermissions($userId);
				if($userPermissions->canViewItem($item))
				{
					$result[$userId] = $userId;
				}
			}
		}

		return $result;
	}

	public static function filterUserIdsWhoCanViewType(int $typeId, array $userIds): array
	{
		$result = [];
		foreach($userIds as $userId)
		{
			$userId = (int)$userId;
			if($userId > 0)
			{
				$userPermissions = Driver::getInstance()->getUserPermissions($userId);
				if($userPermissions->canViewType($typeId))
				{
					$result[$userId] = $userId;
				}
			}
		}

		return $result;
	}

	public static function getPermissionsMap(): array
	{
		return [
			self::PERMISSION_SELF => Loc::getMessage('RPA_PERMISSION_SELF'),
			self::PERMISSION_DEPARTMENT => Loc::getMessage('RPA_PERMISSION_DEPARTMENT'),
			self::PERMISSION_ANY => Loc::getMessage('RPA_PERMISSION_ANY'),
		];
	}

	public function getAccessCodes(): array
	{
		if($this->accessCodes === null)
		{
			$this->accessCodes = \CAccess::GetUserCodesArray($this->userId);
			$this->accessCodes = $this->extendAccessCodes($this->accessCodes);
		}

		return $this->accessCodes;
	}

	protected function extendAccessCodes(array $accessCodes): array
	{
		$result = [];

		foreach($accessCodes as $accessCode)
		{
			$result[] = $accessCode;
			if(preg_match('/^(SG[\d]+)_[A|E|K]/', $accessCode, $matches))
			{
				$result[] = $matches[1];
			}
		}

		$result = array_unique($result);

		$result[] = static::ACCESS_CODE_ALL_USERS;

		return $result;
	}

	/** @noinspection ReturnTypeCanBeDeclaredInspection */
	public function loadUserPermissions()
	{
		$this->permissions = [];
		$this->availableNextStages = [];

		$userAccessCodes = $this->getAccessCodes();
		if(!is_array($userAccessCodes) || count($userAccessCodes) === 0)
		{
			return;
		}
		if ($this->isExtranetUser())
		{
			return;
		}

		$permissions = PermissionTable::getList(['filter' => [
			'@ACCESS_CODE' => $userAccessCodes
		]]);

		while($permission = $permissions->fetch())
		{
			$entityCode = $permission['ENTITY'].$permission['ENTITY_ID'];
			if (
				!isset($this->permissions[$entityCode][$permission['ACTION']]) ||
				$this->permissions[$entityCode][$permission['ACTION']] < $permission['PERMISSION']
			)
			{
				$this->permissions[$entityCode][$permission['ACTION']] = $permission['PERMISSION'];
			}
		}
	}

	public function isAdmin(): bool
	{
		return $this->hasAdminAccess();
	}

	protected function hasAdminAccess(): bool
	{
		if($this->isAdmin === null)
		{
			$this->isAdmin = false;

			if($this->userId > 0 && Driver::getInstance()->getUserId() === $this->userId)
			{
				$currentUser = CurrentUser::get();
				if(ModuleManager::isModuleInstalled('bitrix24'))
				{
					$this->isAdmin = $currentUser->canDoOperation('bitrix24_config');
				}
				else
				{
					$this->isAdmin = $currentUser->isAdmin();
				}
			}
		}

		return $this->isAdmin;
	}

	protected function canPerform(string $entity, int $entityId, string $action): bool
	{
		if($this->hasAdminAccess())
		{
			return true;
		}
		$entityCode = $entity.$entityId;

		return (
			isset($this->permissions[$entityCode][$action]) &&
			$this->permissions[$entityCode][$action] > self::PERMISSION_NONE
		);
	}

	protected function isAccessDeniedToEverything(): bool
	{
		if ($this->hasAdminAccess())
		{
			return false;
		}

		return $this->isExtranetUser();
	}

	protected function isExtranetUser(): bool
	{
		if ($this->isExtranet === null)
		{
			$this->isExtranet = false;
			if (
				Loader::includeModule('intranet')
				&& Loader::includeModule('extranet')
				&& !\CExtranet::IsIntranetUser(SITE_ID, $this->getUserId())
			)
			{
				$this->isExtranet = true;
			}
		}

		return $this->isExtranet;
	}

	// region rules
	protected function isSimplePermissionsForTheFirstStage(): bool
	{
		return true;
	}

	protected function isMoverAlwaysCanMoveBack(): bool
	{
		return false;
	}

	public static function isAlwaysCanMoveToTheNextStage(): bool
	{
		return true;
	}

	public static function canMoveAnywhere(): bool
	{
		return true;
	}

	public static function canViewOnAnyStage(): bool
	{
		return true;
	}

	public static function canEditItemOnAnyStage(): bool
	{
		return true;
	}

	public static function canViewAnyType(): bool
	{
		return true;
	}
	//endregion
}