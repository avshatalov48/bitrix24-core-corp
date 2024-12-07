<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Sign\Access\Permission;
use Bitrix\Sign\Access;
use Bitrix\Sign\Access\Permission\PermissionDictionary;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;

use Bitrix\Main;
use CCrmPerms;

class CheckDocumentAccess implements Contract\Operation
{
	private const ERROR_INVALID_AUTHENTICATION = 'invalid_authentication';

	public function __construct(
		private Item\Document $document,
		private int | string $permissionId,
		private ?Access\AccessController $accessController = null,
	)
	{
		$userId = Main\Engine\CurrentUser::get()->getId();
		if ($userId !== null)
		{
			$this->accessController ??= new Access\AccessController($userId);
		}
	}

	public function launch(): Main\Result
	{
		$result = new Main\Result();

		if ($this->accessController === null)
		{
			return $result->addError(new Main\Error('No current user'));
		}

		if (array_key_exists($this->permissionId, Permission\SignPermissionDictionary::getList()))
		{
			return $this->checkSignPermission($this->permissionId);
		}

		if (!Main\Loader::includeModule('crm'))
		{
			return $result->addError(new Main\Error('Module `crm` is not installed'));
		}

		if (
			in_array(
				$this->document->entityTypeId,
				[\CCrmOwnerType::SmartDocument, \CCrmOwnerType::SmartB2eDocument],
				true
			)
			&& array_key_exists($this->permissionId, Permission\PermissionDictionary::getCrmPermissionMap())
		)
		{
			return $this->checkCrmPermission($this->permissionId);
		}

		return $result;
	}

	private function checkSignPermission(int $permissionId): Main\Result
	{
		$result = new Main\Result();
		if (!Main\Loader::includeModule('crm'))
		{
			return $result->addError(new Main\Error('Module `crm` is not installed'));
		}

		$user = $this->accessController->getUser();
		$permission = (new Access\Service\RolePermissionService())->getValueForPermission(
			$user->getRoles(),
			$permissionId
		);

		if ($permission === CCrmPerms::PERM_ALL || $user->isAdmin())
		{
			return $result;
		}

		$allowedUserIds = match ($permission)
		{
			CCrmPerms::PERM_SUBDEPARTMENT => $user->getUserDepartmentMembers(true),
			CCrmPerms::PERM_DEPARTMENT => $user->getUserDepartmentMembers(),
			CCrmPerms::PERM_SELF => [$user->getUserId()],
			default => []
		};

		if (!in_array($this->document->createdById, $allowedUserIds))
		{
			$result->addError($this->createAccessError());
		}

		return $result;
	}

	private function checkCrmPermission(string $permissionId): Main\Result
	{
		$result = new Main\Result();

		[$permission, $entity] = PermissionDictionary::getCrmPermissionMap()[$permissionId];
		$userPermissions = \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions(Main\Engine\CurrentUser::get()->getId());

		if (method_exists($userPermissions, $permission) && $userPermissions->{$permission}($entity, $this->document->entityId))
		{
			return $result;
		}

		return $result->addError($this->createAccessError());
	}

	private function createAccessError(): Main\Error
	{
		return new Main\Error(
			'Access denied.',
			self::ERROR_INVALID_AUTHENTICATION
		);
	}
}
