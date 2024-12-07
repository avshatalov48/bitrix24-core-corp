<?php

namespace Bitrix\Crm\Security;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Disk\Security\SecurityContext;

class DiskSecurityContext extends SecurityContext
{
	private UserPermissions $userPermissions;
	private ?int $entityTypeId = null;
	private ?int $entityId = null;
	private ?int $categoryId = null;

	public function __construct($user)
	{
		parent::__construct($user);

		$userId = $this->getUserId();
		if ($userId === self::GUEST_USER)
		{
			$userId = 0;
		}

		$this->userPermissions = Container::getInstance()->getUserPermissions($userId);
	}

	public function setOptions(array $options): self
	{
		if (isset($options['entityTypeId']))
		{
			$this->entityTypeId = (int)$options['entityTypeId'];
		}

		if (isset($options['entityId']))
		{
			$this->entityId = (int)$options['entityId'];
		}

		if (isset($options['categoryId']))
		{
			$this->categoryId = (int)$options['categoryId'];
		}

		return $this;
	}

	final public function canAdd($targetId): bool
	{
		if (!isset($this->entityTypeId))
		{
			return false;
		}

		return $this->userPermissions->checkAddPermissions($this->entityTypeId, $this->categoryId);
	}

	final public function canChangeRights($objectId): bool
	{
		return false;
	}

	final public function canChangeSettings($objectId): bool
	{
		return false;
	}

	final public function canCreateWorkflow($objectId): bool
	{
		return false;
	}

	final public function canDelete($objectId): bool
	{
		if (!isset($this->entityTypeId))
		{
			return false;
		}

		return $this->userPermissions->checkDeletePermissions($this->entityTypeId, 0, $this->categoryId);
	}

	final public function canMarkDeleted($objectId): bool
	{
		return false;
	}

	final public function canMove($objectId, $targetId): bool
	{
		return false;
	}

	final public function canRead($objectId): bool
	{
		if (!isset($this->entityTypeId, $this->entityId))
		{
			return false;
		}

		return $this->userPermissions->checkReadPermissions($this->entityTypeId, $this->entityId, $this->categoryId);
	}

	final public function canRename($objectId): bool
	{
		return false;
	}

	final public function canRestore($objectId): bool
	{
		return false;
	}

	final public function canShare($objectId): bool
	{
		return false;
	}

	final public function canUpdate($objectId): bool
	{
		return false;
	}

	final public function canStartBizProc($objectId): bool
	{
		return false;
	}

	final public function getSqlExpressionForList($columnObjectId, $columnCreatedBy)
	{
		return '1 = 0';
	}
}
