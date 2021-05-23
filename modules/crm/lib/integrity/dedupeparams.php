<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

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
}