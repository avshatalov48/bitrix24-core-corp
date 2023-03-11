<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use CCrmOwnerType;

class DedupeParams
{
	protected $typeID = DuplicateIndexType::UNDEFINED;
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	protected $userID = 0;
	protected $enablePermissionCheck = false;
	protected $scope = DuplicateIndexType::DEFAULT_SCOPE;
	protected $limitByAssignedUser = false;
	protected $indexDate = null;
	protected $limitByDirtyIndexItems = false;
	protected $checkChangedOnly = false;

	/**
	 * Entity category ID. Currently, use system category with ID = 0 only.
	 *
	 * @var int
	 */
	private int $categoryId = 0;

	public function __construct($entityTypeID, $userID, $enablePermissionCheck = false,
		$scope = DuplicateIndexType::DEFAULT_SCOPE)
	{
		$this->setEntityTypeID($entityTypeID);
		$this->setUserID($userID);
		$this->enabledPermissionCheck($enablePermissionCheck);
		$this->setScope($scope);
	}

	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}
	public function setEntityTypeID($entityTypeID)
	{
		if(!is_integer($entityTypeID))
		{
			$entityTypeID = intval($entityTypeID);
		}
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			$entityTypeID = \CCrmOwnerType::Undefined;
		}

		if($this->entityTypeID === $entityTypeID)
		{
			return;
		}

		$this->entityTypeID = $entityTypeID;
	}
	public function getUserID()
	{
		return $this->userID;
	}
	public function setUserID($userID)
	{
		if(!is_integer($userID))
		{
			$userID = intval($userID);
		}
		$userID = max($userID, 0);

		if($this->userID === $userID)
		{
			return;
		}

		$this->userID = $userID;
	}
	public function isPermissionCheckEnabled()
	{
		return $this->enablePermissionCheck;
	}
	public function enabledPermissionCheck($enable)
	{
		$this->enablePermissionCheck = is_bool($enable) ? $enable : (bool)$enable;
	}
	public function getScope()
	{
		return $this->scope;
	}
	public function setScope($scope)
	{
		if (DuplicateIndexType::checkScopeValue($scope))
			$this->scope = $scope;
	}

	public function setLimitByAssignedUser(bool $limitByAssignedUser): void
	{
		$this->limitByAssignedUser = $limitByAssignedUser;
	}

	public function limitByAssignedUser(): bool
	{
		return $this->limitByAssignedUser;
	}

	public function setIndexDate(Main\Type\DateTime $date): void
	{
		$this->indexDate = $date;
	}

	public function clearIndexDate(): void
	{
		$this->indexDate = null;
	}

	public function getIndexDate(): ?Main\Type\DateTime
	{
		return $this->indexDate;
	}

	public function setLimitByDirtyIndexItems(bool $limitByDirtyIndexItems): void
	{
		$this->limitByDirtyIndexItems = $limitByDirtyIndexItems;
	}

	public function limitByDirtyIndexItems(): bool
	{
		return $this->limitByDirtyIndexItems;
	}

	public function setCheckChangedOnly(bool $checkChangedOnly): void
	{
		$this->checkChangedOnly = $checkChangedOnly;
	}

	public function isCheckChangedOnly(): bool
	{
		return $this->checkChangedOnly;
	}

	public function getCategoryId(): ?int
	{
		if (in_array($this->entityTypeID, [CCrmOwnerType::Contact, CCrmOwnerType::Company], true))
		{
			if ($this->categoryId > 0)
			{
				return $this->categoryId;
			}

			$factory = Container::getInstance()->getFactory($this->entityTypeID);
			if ($factory && $factory->isCategoriesEnabled())
			{
				$itemInCustomCategory = $factory->getDataClass()::query()
					->setLimit(1)
					->where(Item::FIELD_NAME_CATEGORY_ID, '>', 0)
					->setSelect(['ID'])
					->setCacheTtl(60)
					->fetch()
				;
				if ($itemInCustomCategory)
				{
					return $this->categoryId;
				}
			}
		}

		return null;
	}
}
