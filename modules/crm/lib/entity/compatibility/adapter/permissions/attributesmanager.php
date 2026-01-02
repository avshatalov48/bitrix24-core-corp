<?php
namespace Bitrix\Crm\Entity\Compatibility\Adapter\Permissions;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Service\UserPermissions\Helper\Stage;

class AttributesManager
{
	private array $attributesCache = [];
	private  ?int $assignedById = null;
	private  ?int $categoryId = null;
	private ?bool $isOpened = null;
	private array $concernedUserIds = [];
	private ?bool $isMyCompany = null;
	private ?string $stageId = null;

	public function __construct(
		private readonly int $entityTypeId,
		private readonly int $userId,
		private readonly string $permissionType,
	)
	{
	}

	public function hasAccess(): bool
	{
		$entityAttributes = $this->getAttributes($this->userId);
		$permissionEntity = (new PermissionEntityTypeHelper($this->entityTypeId))
			->getPermissionEntityTypeForCategory((int)$this->categoryId)
		;

		return PermissionsManager::getInstance($this->userId)
			->getPermissionLevel($permissionEntity, $this->permissionType)
			->hasPermissionByEntityAttributes($entityAttributes)
		;
	}

	public function setCategoryId(int $categoryId): self
	{
		$this->categoryId = $categoryId;

		return $this;
	}

	public function setAssignedById(int $assignedById): self
	{
		$this->assignedById = $assignedById;

		return $this;
	}

	public function setOpened($opened): self
	{
		if (!is_null($opened))
		{
			$this->isOpened = ($opened === 'Y');
		}

		return $this;
	}

	public function setStageId($stageId): self
	{
		if (!is_null($stageId))
		{
			$this->stageId = $stageId;
		}

		return $this;
	}

	public function setConcernedUserIds(?array $concernedUserIds): self
	{
		if (!is_null($concernedUserIds))
		{
			$this->concernedUserIds = $concernedUserIds;
		}

		return $this;
	}

	public function setIsMyCompany(?string $isMyCompany): self
	{
		if (!is_null($isMyCompany))
		{
			$this->isMyCompany = ($isMyCompany === 'Y');
		}

		return $this;
	}

	public function prepareFields(array &$fields): void
	{
		if ($this->userId === $this->assignedById)
		{
			return;
		}

		$currentUserAttributes = $this->getAttributes($this->userId);
		$permissionEntity = (new PermissionEntityTypeHelper($this->entityTypeId))
			->getPermissionEntityTypeForCategory((int)$this->categoryId)
		;

		$needChangeAssignedById = PermissionsManager::getInstance($this->userId)
			->getPermissionLevel($permissionEntity, $this->permissionType)
			->isPermissionLevelEqualsToByEntityAttributes(UserPermissions::PERMISSION_SELF, $currentUserAttributes)
		;

		if ($needChangeAssignedById)
		{
			$assignedByIdField = ($this->entityTypeId === \CCrmOwnerType::Invoice)
				? 'RESPONSIBLE_ID'
				: 'ASSIGNED_BY_ID'
			;
			$fields[$assignedByIdField] = $this->userId;
			$this->setAssignedById($this->userId);
		}
	}

	/**
	 * @see also \Bitrix\Crm\Service\UserPermissions\EntityItem::prepareItemPermissionAttributes
	 */
	private function getAttributes(int $userId): array
	{
		if (!isset($this->attributesCache[$userId]))
		{
			$attributes = [
				UserPermissions::ATTRIBUTES_USER_PREFIX . $userId, // U123 for example
			];

			if (!empty($this->isOpened))
			{
				$attributes[] = UserPermissions::ATTRIBUTES_OPENED;
			}

			if (!empty($this->stageId))
			{
				$attributes[] = Stage::getStageIdAttributeByEntityTypeId($this->entityTypeId, $this->stageId);
			}

			if ($this->isMyCompany)
			{
				$attributes[] = UserPermissions::ATTRIBUTES_READ_ALL;
			}

			foreach ($this->concernedUserIds as $concernedUserId)
			{
				$attributes[] = UserPermissions::ATTRIBUTES_CONCERNED_USER_PREFIX . $concernedUserId;  // CU123 for example
			}

			$userAttributes = \Bitrix\Crm\Service\Container::getInstance()
				->getUserPermissions($userId)
				->getAttributesProvider()
				->getEntityAttributes();

			$this->attributesCache[$userId] = array_merge($attributes, $userAttributes['INTRANET']);
		}

		return $this->attributesCache[$userId];
	}

	public function registerEntityAttributes(int $id, ?array $fields = null): void
	{
		$securityRegisterOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
			->setEntityAttributes($this->getAttributes($this->assignedById))
		;
		if ($fields)
		{
			$securityRegisterOptions->setEntityFields($fields);
		}

		$permissionEntity = (new PermissionEntityTypeHelper($this->entityTypeId))
			->getPermissionEntityTypeForCategory((int)$this->categoryId)
		;

		\Bitrix\Crm\Security\Manager::getEntityController($this->entityTypeId)
			->register($permissionEntity, $id, $securityRegisterOptions)
		;
	}

	public function unregisterEntityAttributes(int $id): void
	{
		$permissionEntity = (new PermissionEntityTypeHelper($this->entityTypeId))
			->getPermissionEntityTypeForCategory((int)$this->categoryId)
		;

		\Bitrix\Crm\Security\Manager::getEntityController($this->entityTypeId)
			->unregister($permissionEntity, $id)
		;
	}
}
